<?php

namespace App\Http\Controllers;

use App\Http\Requests\Applicant\ProfileSetupRequest;
use App\Models\Profile;
use App\Services\Cv\CvTextExtractor;
use App\Services\Uploads\Uploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApplicantProfileController extends Controller
{
    /**
     * The applicant details that fill every template.
     */
    public function edit(Request $request): Response
    {
        $profile = $request->user()->profile;

        return Inertia::render('Onboarding', [
            'profile' => $profile,
            'isFirstRun' => $profile === null,
            'status' => $request->session()->get('status'),
            'limits' => [
                'photoMaxKb' => config('emailcv.uploads.photo.max_kb'),
                'cvMaxKb' => config('emailcv.uploads.cv.max_kb'),
                'cvMimes' => config('emailcv.uploads.cv.mimes'),
            ],
        ]);
    }

    public function update(ProfileSetupRequest $request, Uploader $uploader, CvTextExtractor $extractor): RedirectResponse
    {
        $user = $request->user();
        $profile = $user->profile ?? new Profile(['user_id' => $user->id]);
        $profile->user_id = $user->id;

        $profile->fill($request->safe()->except(['photo', 'cv', 'cv_text']));

        if ($request->hasFile('photo')) {
            $uploader->delete($profile->photo_public_id, 'image');

            $photo = $uploader->uploadImage($request->file('photo'), 'photos/'.$user->id);
            $profile->photo_url = $photo->url;
            $profile->photo_public_id = $photo->publicId;
        }

        if ($request->hasFile('cv')) {
            $file = $request->file('cv');

            // Read the text before uploading — the temp file is gone afterwards.
            $extraction = $extractor->extract($file);

            $uploader->delete($profile->cv_public_id, $profile->cv_resource_type);

            $cv = $uploader->uploadDocument($file, 'cvs/'.$user->id);
            $profile->cv_url = $cv->url;
            $profile->cv_public_id = $cv->publicId;
            $profile->cv_filename = $cv->filename;
            $profile->cv_resource_type = $cv->resourceType;
            $profile->cv_bytes = $cv->bytes;
            $profile->cv_text = $extraction['text'];
            $profile->cv_parse_status = $extraction['status'];
            $profile->cv_parsed_at = now();
        } elseif ($request->filled('cv_text')) {
            // The user corrected the extracted text by hand.
            $profile->cv_text = $request->string('cv_text')->toString();
            $profile->cv_parse_status = CvTextExtractor::STATUS_PARSED;
            $profile->cv_parsed_at = now();
        }

        $profile->save();

        if (! $user->hasCompletedOnboarding()) {
            $user->forceFill(['onboarded_at' => now()])->save();
        }

        $message = match ($profile->cv_parse_status) {
            CvTextExtractor::STATUS_EMPTY => 'Profile saved. We could not read text from that CV — it may be a scanned image. Paste your CV text below so the AI can use it.',
            CvTextExtractor::STATUS_UNSUPPORTED => 'Profile saved. We can only read text from PDF and DOCX files — paste your CV text below so the AI can use it.',
            CvTextExtractor::STATUS_FAILED => 'Profile saved, but we could not read that CV file. Paste your CV text below so the AI can use it.',
            default => 'Profile saved.',
        };

        Inertia::flash('toast', [
            'type' => $profile->cv_parse_status === CvTextExtractor::STATUS_PARSED || $profile->cv_parse_status === null ? 'success' : 'warning',
            'message' => $message,
        ]);

        return $request->boolean('continue')
            ? to_route('templates.index')
            : to_route('applicant-profile.edit');
    }
}

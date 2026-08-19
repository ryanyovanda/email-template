<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiGeneration;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AiUsageController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();

        return Inertia::render('admin/AiUsage', [
            'generations' => AiGeneration::query()
                ->with(['user:id,name,email,banned_at', 'application:id,title,company'])
                ->when($status !== '', fn ($query) => $query->where('status', $status))
                ->when($request->filled('user'), fn ($query) => $query->where('user_id', $request->integer('user')))
                ->latest()
                ->paginate(30)
                ->withQueryString()
                ->through(fn (AiGeneration $generation): array => [
                    'id' => $generation->id,
                    'user' => $generation->user?->only(['id', 'name', 'email', 'banned_at']),
                    'application' => $generation->application?->only(['id', 'title', 'company']),
                    'model' => $generation->model,
                    'status' => $generation->status,
                    'error' => $generation->error,
                    'total_tokens' => $generation->total_tokens,
                    'duration_ms' => $generation->duration_ms,
                    'ip_address' => $generation->ip_address,
                    'created_at' => $generation->created_at?->toDateTimeString(),
                ]),
            'filters' => [
                'status' => $status,
                'user' => $request->string('user')->toString(),
            ],
        ]);
    }
}

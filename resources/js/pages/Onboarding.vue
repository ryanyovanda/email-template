<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    ArrowRight,
    CheckCircle2,
    FileText,
    Loader2,
    TriangleAlert,
    Upload,
    User as UserIcon,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    edit as applicantProfileEdit,
    update as applicantProfileUpdate,
} from '@/routes/applicant-profile';

type Profile = {
    full_name: string;
    headline: string | null;
    contact_email: string;
    phone: string | null;
    location: string | null;
    portfolio_url: string | null;
    linkedin_url: string | null;
    photo_url: string | null;
    cv_url: string | null;
    cv_filename: string | null;
    cv_text: string | null;
    cv_parse_status: string | null;
};

const props = defineProps<{
    profile: Profile | null;
    isFirstRun: boolean;
    status: string | null;
    limits: { photoMaxKb: number; cvMaxKb: number; cvMimes: string[] };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'My profile', href: applicantProfileEdit() }],
    },
});

const form = useForm({
    full_name: props.profile?.full_name ?? '',
    headline: props.profile?.headline ?? '',
    contact_email: props.profile?.contact_email ?? '',
    phone: props.profile?.phone ?? '',
    location: props.profile?.location ?? '',
    portfolio_url: props.profile?.portfolio_url ?? '',
    linkedin_url: props.profile?.linkedin_url ?? '',
    cv_text: props.profile?.cv_text ?? '',
    photo: null as File | null,
    cv: null as File | null,
    continue: false,
});

const photoPreview = ref<string | null>(props.profile?.photo_url ?? null);
const cvName = ref<string | null>(props.profile?.cv_filename ?? null);

const cvAccept = computed(() =>
    props.limits.cvMimes.map((mime) => `.${mime}`).join(','),
);

const cvNeedsAttention = computed(
    () =>
        props.profile !== null &&
        props.profile.cv_parse_status !== null &&
        props.profile.cv_parse_status !== 'parsed',
);

const cvWordCount = computed(
    () => form.cv_text.trim().split(/\s+/).filter(Boolean).length,
);

function onPhotoChange(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.photo = file;
    photoPreview.value = file
        ? URL.createObjectURL(file)
        : (props.profile?.photo_url ?? null);
}

function onCvChange(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.cv = file;
    cvName.value = file?.name ?? (props.profile?.cv_filename ?? null);
}

function submit(andContinue: boolean): void {
    form.continue = andContinue;
    form.post(applicantProfileUpdate().url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.photo = null;
            form.cv = null;
        },
    });
}
</script>

<template>
    <Head title="My profile" />

    <div class="mx-auto w-full max-w-3xl space-y-8 p-4">
        <Heading
            :title="isFirstRun ? 'Set up your profile' : 'My profile'"
            :description="
                isFirstRun
                    ? 'These details fill in automatically on every template you use. You only do this once.'
                    : 'Change these once and every future application picks up the new details.'
            "
        />

        <div
            v-if="status"
            class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-200"
        >
            {{ status }}
        </div>

        <form class="space-y-8" @submit.prevent="submit(false)">
            <!-- Identity -->
            <section class="rounded-xl border p-5">
                <h2 class="flex items-center gap-2 text-sm font-semibold">
                    <UserIcon class="size-4" /> Who you are
                </h2>

                <div class="mt-5 flex items-start gap-5">
                    <div class="shrink-0 text-center">
                        <div
                            class="bg-muted flex size-20 items-center justify-center overflow-hidden rounded-full border"
                        >
                            <img
                                v-if="photoPreview"
                                :src="photoPreview"
                                alt=""
                                class="size-full object-cover"
                            />
                            <UserIcon
                                v-else
                                class="text-muted-foreground size-7"
                            />
                        </div>
                        <label
                            class="text-primary mt-2 inline-block cursor-pointer text-xs underline"
                        >
                            {{ photoPreview ? 'Change' : 'Add photo' }}
                            <input
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="onPhotoChange"
                            />
                        </label>
                    </div>

                    <div class="grid flex-1 gap-4 sm:grid-cols-2">
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="full_name">Full name</Label>
                            <Input
                                id="full_name"
                                v-model="form.full_name"
                                required
                                placeholder="Jane Doe"
                            />
                            <InputError :message="form.errors.full_name" />
                        </div>

                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="headline">Headline</Label>
                            <Input
                                id="headline"
                                v-model="form.headline"
                                placeholder="Learning & Development Specialist"
                            />
                            <p class="text-muted-foreground text-xs">
                                One line describing what you do. Some templates
                                show it under your name.
                            </p>
                            <InputError :message="form.errors.headline" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="contact_email">Contact email</Label>
                            <Input
                                id="contact_email"
                                v-model="form.contact_email"
                                type="email"
                                required
                                placeholder="you@example.com"
                            />
                            <InputError :message="form.errors.contact_email" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="phone">Phone / WhatsApp</Label>
                            <Input
                                id="phone"
                                v-model="form.phone"
                                placeholder="0800-000-0000"
                            />
                            <InputError :message="form.errors.phone" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="location">Location</Label>
                            <Input
                                id="location"
                                v-model="form.location"
                                placeholder="Central Jakarta"
                            />
                            <InputError :message="form.errors.location" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="portfolio_url">Portfolio URL</Label>
                            <Input
                                id="portfolio_url"
                                v-model="form.portfolio_url"
                                type="url"
                                placeholder="https://yoursite.com"
                            />
                            <InputError :message="form.errors.portfolio_url" />
                        </div>

                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="linkedin_url">LinkedIn URL</Label>
                            <Input
                                id="linkedin_url"
                                v-model="form.linkedin_url"
                                type="url"
                                placeholder="https://linkedin.com/in/you"
                            />
                            <InputError :message="form.errors.linkedin_url" />
                        </div>
                    </div>
                </div>
                <InputError class="mt-3" :message="form.errors.photo" />
            </section>

            <!-- CV -->
            <section class="rounded-xl border p-5">
                <h2 class="flex items-center gap-2 text-sm font-semibold">
                    <FileText class="size-4" /> Your CV
                </h2>
                <p class="text-muted-foreground mt-1 text-sm">
                    We read the text out of your CV so the AI can write from
                    your real experience. The file itself is stored so you can
                    attach it in Gmail.
                </p>

                <label
                    class="hover:bg-accent/40 mt-4 flex cursor-pointer items-center gap-4 rounded-lg border border-dashed px-4 py-5 transition-colors"
                >
                    <Upload class="text-muted-foreground size-5 shrink-0" />
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-medium">
                            {{ cvName ?? 'Choose a file' }}
                        </div>
                        <div class="text-muted-foreground text-xs">
                            {{ limits.cvMimes.join(', ').toUpperCase() }} up to
                            {{ Math.round(limits.cvMaxKb / 1024) }} MB
                        </div>
                    </div>
                    <span class="text-primary text-xs underline">Browse</span>
                    <input
                        type="file"
                        :accept="cvAccept"
                        class="hidden"
                        @change="onCvChange"
                    />
                </label>
                <InputError class="mt-2" :message="form.errors.cv" />

                <div
                    v-if="cvNeedsAttention"
                    class="mt-4 flex gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-200"
                >
                    <TriangleAlert class="mt-0.5 size-4 shrink-0" />
                    <p>
                        We could not read text from your CV file — it is
                        probably a scanned image. Paste your CV text below so
                        the AI has something to work from.
                    </p>
                </div>

                <div class="mt-5 grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="cv_text">CV text</Label>
                        <span
                            class="text-xs"
                            :class="
                                cvWordCount >= 60
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-muted-foreground'
                            "
                        >
                            <CheckCircle2
                                v-if="cvWordCount >= 60"
                                class="mr-1 inline size-3"
                            />{{ cvWordCount }} words
                        </span>
                    </div>
                    <Textarea
                        id="cv_text"
                        v-model="form.cv_text"
                        rows="10"
                        class="font-mono text-xs"
                        placeholder="Upload a CV above to fill this automatically, or paste your CV text here."
                    />
                    <p class="text-muted-foreground text-xs">
                        Edit anything the parser got wrong. This text is what
                        the AI reads — nothing else from the file is used.
                    </p>
                    <InputError :message="form.errors.cv_text" />
                </div>
            </section>

            <div class="flex flex-wrap items-center gap-3">
                <Button type="submit" :disabled="form.processing">
                    <Loader2
                        v-if="form.processing"
                        class="size-4 animate-spin"
                    />
                    Save profile
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    :disabled="form.processing"
                    @click="submit(true)"
                >
                    Save and choose a template
                    <ArrowRight class="size-4" />
                </Button>
            </div>
        </form>
    </div>
</template>

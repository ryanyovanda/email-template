<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Check,
    Copy,
    Download,
    ExternalLink,
    FileWarning,
    Loader2,
    PencilLine,
    Save,
    Sparkles,
} from '@lucide/vue';
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import GmailPreview from '@/components/GmailPreview.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { edit as applicantProfileEdit } from '@/routes/applicant-profile';
import {
    aiDraft,
    copied as markCopied,
    download as downloadApplication,
    index as applicationsIndex,
    render as renderApplication,
    update as updateApplication,
} from '@/routes/applications';

type Field = {
    token: string;
    label: string;
    type: 'text' | 'textarea' | 'email' | 'url' | 'image' | 'list';
    source: string;
    ai: boolean;
    help: string | null;
    required: boolean;
};

type ApplicationData = {
    id: number;
    title: string;
    display_name: string;
    company: string | null;
    position: string | null;
    recipient_name: string | null;
    job_post: string | null;
    mode: 'manual' | 'ai';
    field_values: Record<string, unknown>;
};

const props = defineProps<{
    application: ApplicationData;
    template: {
        id: number;
        name: string;
        accent_color: string;
        fields: Field[];
    } | null;
    profile: Record<string, string | null> | null;
    hasCvText: boolean;
    initialHtml: string;
    credits: number;
    draftPrice: number;
    extensionUrl: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'My applications', href: applicationsIndex() },
            { title: 'Editor', href: applicationsIndex() },
        ],
    },
});

const page = usePage();

/** List fields round-trip as newline-separated text so they stay editable. */
function toEditable(value: unknown): string {
    if (Array.isArray(value)) {
        return value.join('\n');
    }

    return value === null || value === undefined ? '' : String(value);
}

const form = reactive({
    title: props.application.title,
    company: props.application.company ?? '',
    position: props.application.position ?? '',
    recipient_name: props.application.recipient_name ?? '',
    job_post: props.application.job_post ?? '',
    mode: props.application.mode,
});

const values = reactive<Record<string, string>>(
    Object.fromEntries(
        (props.template?.fields ?? []).map((field) => [
            field.token,
            toEditable(props.application.field_values[field.token]),
        ]),
    ),
);

const html = ref(props.initialHtml);
const subject = ref('');
const rendering = ref(false);
const generating = ref(false);
const saving = ref(false);
const copiedRecently = ref(false);
const credits = ref(props.credits);
const errors = ref<Record<string, string>>({});

const contentFields = computed(
    () => props.template?.fields.filter((f) => f.source === 'content') ?? [],
);

type RoleToken = 'company' | 'position' | 'recipient_name';

const ROLE_TOKENS: RoleToken[] = ['company', 'position', 'recipient_name'];

/**
 * Company, role and hiring contact are read out of the job posting by the AI
 * rather than typed up front. They stay editable because the AI can misread a
 * posting, and the wrong name in a greeting is the worst thing to get wrong.
 */
const roleFields = computed(() =>
    (props.template?.fields ?? []).filter(
        (field): field is Field & { token: RoleToken } =>
            ROLE_TOKENS.includes(field.token as RoleToken),
    ),
);

/**
 * The company is what identifies an application to a person, so it leads the
 * page and updates live as it is typed.
 */
const displayName = computed(
    () => form.company.trim() || form.title.trim() || 'Untitled application',
);

const canAfford = computed(() => credits.value >= props.draftPrice);

const canGenerate = computed(
    () =>
        props.hasCvText &&
        canAfford.value &&
        form.job_post.trim().length >= 80 &&
        !generating.value,
);

const senderName = computed(
    () => props.profile?.full_name ?? page.props.auth.user.name,
);

const senderEmail = computed(
    () => props.profile?.contact_email ?? page.props.auth.user.email,
);

function headers(): Record<string, string> {
    return {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': page.props.csrfToken as string,
        'X-Requested-With': 'XMLHttpRequest',
    };
}

let renderTimer: number | undefined;

async function refreshPreview(): Promise<void> {
    if (!props.template) {
        return;
    }

    rendering.value = true;

    try {
        const response = await fetch(
            renderApplication(props.application.id).url,
            {
                method: 'POST',
                headers: headers(),
                credentials: 'same-origin',
                body: JSON.stringify({
                    company: form.company,
                    position: form.position,
                    recipient_name: form.recipient_name,
                    field_values: { ...values },
                }),
            },
        );

        if (response.ok) {
            const data = await response.json();
            html.value = data.html;
            subject.value = data.subject;
        }
    } finally {
        rendering.value = false;
    }
}

function scheduleRender(): void {
    window.clearTimeout(renderTimer);
    renderTimer = window.setTimeout(refreshPreview, 400);
}

watch(
    [values, () => [form.company, form.position, form.recipient_name]],
    scheduleRender,
    {
        deep: true,
    },
);

onBeforeUnmount(() => window.clearTimeout(renderTimer));

// Prime the subject line, which the server derives from the role and company.
refreshPreview();

async function generate(): Promise<void> {
    if (!canGenerate.value) {
        return;
    }

    generating.value = true;
    errors.value = {};

    try {
        const response = await fetch(aiDraft(props.application.id).url, {
            method: 'POST',
            headers: headers(),
            credentials: 'same-origin',
            body: JSON.stringify({
                job_post: form.job_post,
                company: form.company,
                position: form.position,
                recipient_name: form.recipient_name,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            errors.value = data.errors ?? {};
            toast.error(data.message ?? 'The AI could not write a draft.');

            return;
        }

        Object.entries(data.field_values as Record<string, unknown>).forEach(
            ([token, value]) => {
                if (token in values) {
                    values[token] = toEditable(value);
                }
            },
        );

        form.company = data.application.company ?? form.company;
        form.position = data.application.position ?? form.position;
        form.recipient_name =
            data.application.recipient_name ?? form.recipient_name;
        form.mode = 'ai';

        html.value = data.html;
        credits.value = data.credits;

        toast.success(data.message);
        scheduleRender();
    } catch {
        toast.error('We could not reach the AI service. Please try again.');
    } finally {
        generating.value = false;
    }
}

function save(): void {
    saving.value = true;

    router.put(
        updateApplication(props.application.id).url,
        { ...form, field_values: { ...values } },
        {
            preserveScroll: true,
            preserveState: true,
            onError: (received) =>
                (errors.value = received as Record<string, string>),
            onFinish: () => (saving.value = false),
        },
    );
}

async function copyHtml(): Promise<void> {
    try {
        await navigator.clipboard.writeText(html.value);

        copiedRecently.value = true;
        window.setTimeout(() => (copiedRecently.value = false), 2500);

        toast.success('HTML copied. Paste it into the Gmail extension.');

        void fetch(markCopied(props.application.id).url, {
            method: 'POST',
            headers: headers(),
            credentials: 'same-origin',
        });
    } catch {
        toast.error(
            'Your browser blocked the clipboard. Use “Download .html” instead.',
        );
    }
}
</script>

<template>
    <Head :title="displayName" />

    <div
        v-if="!template"
        class="m-4 rounded-xl border border-dashed p-12 text-center"
    >
        <FileWarning class="mx-auto size-8 text-muted-foreground" />
        <p class="mt-3 text-sm">
            The template behind this application was removed by an
            administrator. Start a new application from the gallery.
        </p>
    </div>

    <div
        v-else
        class="grid gap-6 p-4 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]"
    >
        <!-- FORM COLUMN -->
        <div class="space-y-6">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <Heading
                    :title="displayName"
                    :description="
                        [form.position, `${template.name} template`]
                            .filter(Boolean)
                            .join(' · ')
                    "
                />
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="saving"
                    @click="save"
                >
                    <Loader2 v-if="saving" class="size-4 animate-spin" />
                    <Save v-else class="size-4" />
                    Save draft
                </Button>
            </div>

            <!-- Mode switch -->
            <div class="grid grid-cols-2 gap-1 rounded-lg bg-muted p-1">
                <button
                    type="button"
                    :class="
                        cn(
                            'flex items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                            form.mode === 'ai'
                                ? 'bg-background shadow-sm'
                                : 'text-muted-foreground hover:text-foreground',
                        )
                    "
                    @click="form.mode = 'ai'"
                >
                    <Sparkles class="size-4" /> Generate with AI
                </button>
                <button
                    type="button"
                    :class="
                        cn(
                            'flex items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                            form.mode === 'manual'
                                ? 'bg-background shadow-sm'
                                : 'text-muted-foreground hover:text-foreground',
                        )
                    "
                    @click="form.mode = 'manual'"
                >
                    <PencilLine class="size-4" /> Write it myself
                </button>
            </div>

            <!-- AI panel -->
            <section
                v-if="form.mode === 'ai'"
                class="space-y-4 rounded-xl border p-5"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2
                            class="flex items-center gap-2 text-sm font-semibold"
                        >
                            <Sparkles class="size-4" /> Generate from the job
                            posting
                        </h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Paste the posting. The AI writes each field using
                            only what is in your CV.
                        </p>
                    </div>
                    <span
                        class="shrink-0 rounded-full border px-2.5 py-1 text-xs"
                        :class="
                            canAfford
                                ? 'text-muted-foreground'
                                : 'border-destructive/40 text-destructive'
                        "
                    >
                        {{ credits.toLocaleString() }} credits
                    </span>
                </div>

                <div
                    v-if="!hasCvText"
                    class="flex flex-wrap items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-200"
                >
                    <span class="flex-1"
                        >We have no CV text for you yet, so there is nothing for
                        the AI to write from.</span
                    >
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="applicantProfileEdit()">Add my CV</Link>
                    </Button>
                </div>

                <div class="grid gap-2">
                    <Label for="job_post">Job posting</Label>
                    <Textarea
                        id="job_post"
                        v-model="form.job_post"
                        rows="9"
                        placeholder="Paste the full job posting here — responsibilities, requirements, everything."
                    />
                    <p class="text-xs text-muted-foreground">
                        {{ form.job_post.trim().length }} characters
                        <span v-if="form.job_post.trim().length < 80"
                            >— at least 80 needed</span
                        >
                    </p>
                </div>

                <Button
                    class="w-full"
                    :disabled="!canGenerate"
                    @click="generate"
                >
                    <Loader2 v-if="generating" class="size-4 animate-spin" />
                    <Sparkles v-else class="size-4" />
                    {{
                        generating
                            ? 'Writing your draft…'
                            : `Generate draft · ${draftPrice} credits`
                    }}
                </Button>

                <p
                    v-if="!canAfford"
                    class="text-center text-xs text-muted-foreground"
                >
                    This costs {{ draftPrice }} credits and you have
                    {{ credits }}. Your allowance tops up next month — you can
                    still fill the fields in below.
                </p>
                <p v-else class="text-center text-xs text-muted-foreground">
                    Always read the draft before sending — the AI can get
                    details wrong.
                </p>
            </section>

            <!-- Content fields -->
            <section class="space-y-5 rounded-xl border p-5">
                <div>
                    <h2 class="text-sm font-semibold">Email content</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Everything here is editable, whether you wrote it or the
                        AI did.
                    </p>
                </div>

                <!-- Read out of the job posting, kept correctable -->
                <div
                    v-if="roleFields.length"
                    class="grid gap-4 rounded-lg border bg-muted/40 p-4 sm:grid-cols-2"
                >
                    <div
                        v-for="field in roleFields"
                        :key="field.token"
                        class="grid gap-2"
                        :class="
                            field.token === 'recipient_name'
                                ? 'sm:col-span-2'
                                : ''
                        "
                    >
                        <Label :for="field.token">
                            {{ field.label }}
                            <Sparkles
                                class="ml-1 inline size-3 text-muted-foreground"
                            />
                        </Label>
                        <Input :id="field.token" v-model="form[field.token]" />
                    </div>
                    <p class="text-xs text-muted-foreground sm:col-span-2">
                        Filled in from the job posting when you generate. Check
                        the name before you send.
                    </p>
                </div>

                <div
                    v-for="field in contentFields"
                    :key="field.token"
                    class="grid gap-2"
                >
                    <Label :for="field.token">
                        {{ field.label }}
                        <Sparkles
                            v-if="field.ai"
                            class="ml-1 inline size-3 text-muted-foreground"
                        />
                    </Label>

                    <Textarea
                        v-if="field.type === 'textarea'"
                        :id="field.token"
                        v-model="values[field.token]"
                        rows="4"
                    />
                    <Textarea
                        v-else-if="field.type === 'list'"
                        :id="field.token"
                        v-model="values[field.token]"
                        rows="5"
                        placeholder="One per line"
                    />
                    <Input
                        v-else
                        :id="field.token"
                        v-model="values[field.token]"
                        :type="
                            field.type === 'email'
                                ? 'email'
                                : field.type === 'url'
                                  ? 'url'
                                  : 'text'
                        "
                    />

                    <p v-if="field.help" class="text-xs text-muted-foreground">
                        {{ field.help }}
                    </p>
                </div>

                <p
                    v-if="contentFields.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    This template fills itself entirely from your profile and
                    the job posting.
                </p>

                <div class="grid gap-2 border-t pt-5">
                    <Label for="title">Draft label</Label>
                    <Input id="title" v-model="form.title" />
                    <p class="text-xs text-muted-foreground">
                        Only used in your list when no company has been picked
                        up yet.
                    </p>
                </div>
            </section>
        </div>

        <!-- PREVIEW COLUMN -->
        <div class="xl:sticky xl:top-4 xl:h-[calc(100vh-6rem)]">
            <div class="flex h-full flex-col">
                <GmailPreview
                    class="min-h-0 flex-1"
                    :html="html"
                    :subject="subject"
                    :sender-name="senderName"
                    :sender-email="senderEmail"
                    :attachment="profile?.cv_filename ?? null"
                    :loading="rendering"
                />

                <div class="mt-4 space-y-3 rounded-xl border p-4">
                    <div class="flex flex-wrap gap-2">
                        <Button class="flex-1" @click="copyHtml">
                            <Check v-if="copiedRecently" class="size-4" />
                            <Copy v-else class="size-4" />
                            {{ copiedRecently ? 'Copied' : 'Copy HTML' }}
                        </Button>
                        <Button variant="outline" as-child>
                            <a :href="downloadApplication(application.id).url">
                                <Download class="size-4" /> Download .html
                            </a>
                        </Button>
                    </div>

                    <p class="text-xs leading-relaxed text-muted-foreground">
                        Open Gmail, click the extension's
                        <strong>Insert HTML</strong> button, paste this, attach
                        your CV, and send. The email goes from your own address,
                        so replies come straight back to you.
                    </p>

                    <a
                        :href="extensionUrl"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-1 text-xs text-primary underline"
                    >
                        Get the Chrome extension
                        <ExternalLink class="size-3" />
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

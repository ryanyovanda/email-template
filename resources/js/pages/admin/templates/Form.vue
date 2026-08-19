<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    CircleAlert,
    Info,
    Loader2,
    Save,
    Sparkles,
    TriangleAlert,
} from '@lucide/vue';
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';
import GmailPreview from '@/components/GmailPreview.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { dashboard as adminDashboard } from '@/routes/admin';
import {
    analyse as analyseTemplate,
    index as adminTemplatesIndex,
    store as storeTemplate,
    update as updateTemplate,
} from '@/routes/admin/templates';

type Field = {
    token: string;
    label: string;
    type: string;
    source: string;
    ai: boolean;
    help: string;
    default: string;
    required: boolean;
};

type TemplateData = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    accent_color: string;
    thumbnail_url: string | null;
    html: string;
    fields: Field[];
    is_active: boolean;
    sort_order: number;
};

const props = defineProps<{
    template: TemplateData | null;
    tokenReference: {
        profile: string[];
        application: string[];
        template: string[];
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: adminDashboard() },
            { title: 'Templates', href: adminTemplatesIndex() },
        ],
    },
});

const page = usePage();

const STARTER_HTML = `<table width="100%" cellpadding="0" cellspacing="0" border="0" role="presentation" style="background:#f5f5f5;padding:24px 0;font-family:Helvetica,Arial,sans-serif;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" border="0" role="presentation" style="width:100%;max-width:600px;background:#ffffff;border-radius:12px;">
      <tr><td style="padding:24px 28px;border-top:4px solid {{ accent }};">
        <h1 style="margin:0;font-size:20px;color:#111111;">{{ full_name }}</h1>
        <p style="margin:4px 0 0;font-size:13px;color:#666666;">{{ headline }}</p>
      </td></tr>
      <tr><td style="padding:0 28px 28px;">
        <p style="font-size:14px;line-height:1.7;color:#333333;">Dear {{ recipient_name }},</p>
        <p style="font-size:14px;line-height:1.7;color:#333333;">{{ intro_paragraph }}</p>
        <p style="font-size:14px;line-height:1.7;color:#333333;">{{ closing_paragraph }}</p>
        <p style="font-size:13px;color:#888888;">{{ full_name }} &middot; {{ phone }} &middot; {{ location }}</p>
      </td></tr>
    </table>
  </td></tr>
</table>`;

const form = reactive({
    name: props.template?.name ?? '',
    slug: props.template?.slug ?? '',
    description: props.template?.description ?? '',
    accent_color: props.template?.accent_color ?? '#E86A33',
    thumbnail_url: props.template?.thumbnail_url ?? '',
    html: props.template?.html ?? STARTER_HTML,
    is_active: props.template?.is_active ?? true,
    sort_order: props.template?.sort_order ?? 0,
});

const fields = ref<Field[]>(props.template?.fields ?? []);
const warnings = ref<{ level: string; message: string }[]>([]);
const previewHtml = ref('');
const analysing = ref(false);
const saving = ref(false);
const errors = ref<Record<string, string>>({});

const errorWarnings = computed(() =>
    warnings.value.filter((w) => w.level === 'error'),
);
const softWarnings = computed(() =>
    warnings.value.filter((w) => w.level !== 'error'),
);

const contentFields = computed(() =>
    fields.value.filter((field) => field.source === 'content'),
);
const systemFields = computed(() =>
    fields.value.filter((field) => field.source !== 'content'),
);

let timer: number | undefined;

async function analyse(): Promise<void> {
    analysing.value = true;

    try {
        const response = await fetch(analyseTemplate().url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': page.props.csrfToken as string,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                html: form.html,
                fields: fields.value,
                accent_color: form.accent_color,
            }),
        });

        if (response.ok) {
            const data = await response.json();
            // Helper text arrives as null for untouched fields; the input needs
            // a string to bind to.
            fields.value = (data.fields as Field[]).map((field) => ({
                ...field,
                help: field.help ?? '',
                default: field.default ?? '',
            }));
            warnings.value = data.warnings;
            previewHtml.value = data.html;
        }
    } finally {
        analysing.value = false;
    }
}

function schedule(): void {
    window.clearTimeout(timer);
    timer = window.setTimeout(analyse, 600);
}

watch(() => [form.html, form.accent_color], schedule);
onBeforeUnmount(() => window.clearTimeout(timer));

analyse();

function save(): void {
    saving.value = true;
    errors.value = {};

    const payload = { ...form, fields: fields.value };
    const options = {
        preserveScroll: true,
        onError: (received: Record<string, string>) => (errors.value = received),
        onFinish: () => (saving.value = false),
    };

    if (props.template) {
        router.put(updateTemplate(props.template.id).url, payload, options);

        return;
    }

    router.post(storeTemplate().url, payload, options);
}
</script>

<template>
    <Head :title="template ? `Edit ${template.name}` : 'New template'" />

    <div class="grid gap-6 p-4 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
        <!-- EDITOR -->
        <div class="space-y-6">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <Heading
                    :title="template ? template.name : 'New template'"
                    description="Tokens are detected as you type — the user's form is built from them."
                />
                <Button :disabled="saving" @click="save">
                    <Loader2 v-if="saving" class="size-4 animate-spin" />
                    <Save v-else class="size-4" />
                    {{ template ? 'Save changes' : 'Create template' }}
                </Button>
            </div>

            <section class="grid gap-4 rounded-xl border p-5 sm:grid-cols-2">
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="name">Name</Label>
                    <Input id="name" v-model="form.name" placeholder="Grayscale Accent" />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2 sm:col-span-2">
                    <Label for="description">Description</Label>
                    <Textarea
                        id="description"
                        v-model="form.description"
                        rows="2"
                        placeholder="Who this template suits and when to pick it."
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="grid gap-2">
                    <Label for="accent_color">Accent colour</Label>
                    <div class="flex gap-2">
                        <input
                            id="accent_color"
                            v-model="form.accent_color"
                            type="color"
                            class="h-9 w-12 cursor-pointer rounded-md border"
                        />
                        <Input v-model="form.accent_color" class="flex-1" />
                    </div>
                    <p class="text-muted-foreground text-xs">
                        Available in the HTML as the accent token.
                    </p>
                    <InputError :message="errors.accent_color" />
                </div>

                <div class="grid gap-2">
                    <Label for="sort_order">Sort order</Label>
                    <Input
                        id="sort_order"
                        v-model="form.sort_order"
                        type="number"
                        min="0"
                    />
                    <InputError :message="errors.sort_order" />
                </div>

                <div class="grid gap-2 sm:col-span-2">
                    <Label for="thumbnail_url">Thumbnail URL (optional)</Label>
                    <Input
                        id="thumbnail_url"
                        v-model="form.thumbnail_url"
                        placeholder="https://res.cloudinary.com/…"
                    />
                    <InputError :message="errors.thumbnail_url" />
                </div>

                <label class="flex items-center gap-2 text-sm sm:col-span-2">
                    <input
                        v-model="form.is_active"
                        type="checkbox"
                        class="size-4 rounded border"
                    />
                    Visible to users in the template gallery
                </label>
            </section>

            <!-- HTML -->
            <section class="space-y-3 rounded-xl border p-5">
                <div class="flex items-center justify-between">
                    <Label for="html">Email HTML</Label>
                    <span
                        v-if="analysing"
                        class="text-muted-foreground animate-pulse text-xs"
                        >checking…</span
                    >
                </div>
                <Textarea
                    id="html"
                    v-model="form.html"
                    rows="18"
                    class="font-mono text-xs"
                    spellcheck="false"
                />
                <InputError :message="errors.html" />

                <div class="text-muted-foreground space-y-1 text-xs">
                    <p class="flex items-start gap-1.5">
                        <Info class="mt-0.5 size-3.5 shrink-0" />
                        <span>
                            Write a single value as a token in double braces. For
                            a repeating list, wrap a block between a
                            <code>#token</code> opener and a
                            <code>/token</code> closer, and use a lone dot for
                            each item.
                        </span>
                    </p>
                </div>

                <!-- Compatibility warnings -->
                <div v-if="warnings.length" class="space-y-2 pt-1">
                    <div
                        v-for="(warning, index) in [
                            ...errorWarnings,
                            ...softWarnings,
                        ]"
                        :key="index"
                        :class="
                            cn(
                                'flex gap-2 rounded-lg border px-3 py-2 text-xs',
                                warning.level === 'error'
                                    ? 'border-destructive/40 bg-destructive/5 text-destructive'
                                    : 'border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-200',
                            )
                        "
                    >
                        <CircleAlert
                            v-if="warning.level === 'error'"
                            class="mt-0.5 size-3.5 shrink-0"
                        />
                        <TriangleAlert v-else class="mt-0.5 size-3.5 shrink-0" />
                        <span>{{ warning.message }}</span>
                    </div>
                </div>
            </section>

            <!-- Detected fields -->
            <section class="space-y-4 rounded-xl border p-5">
                <div>
                    <h2 class="text-sm font-semibold">
                        Detected fields ({{ contentFields.length }})
                    </h2>
                    <p class="text-muted-foreground mt-1 text-sm">
                        These become the user's form. Rename the labels and mark
                        which ones the AI should write.
                    </p>
                </div>

                <div
                    v-if="contentFields.length === 0"
                    class="text-muted-foreground rounded-lg border border-dashed p-6 text-center text-sm"
                >
                    No content tokens yet — add some to the HTML above.
                </div>

                <div
                    v-for="field in contentFields"
                    :key="field.token"
                    class="grid gap-3 rounded-lg border p-3 sm:grid-cols-[minmax(0,1fr)_130px]"
                >
                    <div class="grid gap-2">
                        <code
                            class="bg-muted w-fit rounded px-1.5 py-0.5 text-xs"
                            >{{ field.token }}</code
                        >
                        <Input v-model="field.label" placeholder="Label" />
                        <Input
                            v-model="field.help"
                            placeholder="Helper text (optional)"
                            class="text-xs"
                        />
                        <Input
                            v-model="field.default"
                            placeholder="Starting value (optional)"
                            class="text-xs"
                        />
                    </div>

                    <div class="space-y-2">
                        <select
                            v-model="field.type"
                            class="border-input dark:bg-input/30 h-9 w-full rounded-md border bg-transparent px-2 text-sm"
                        >
                            <option value="text">Short text</option>
                            <option value="textarea">Paragraph</option>
                            <option value="list">List</option>
                            <option value="url">URL</option>
                            <option value="email">Email</option>
                            <option value="image">Image URL</option>
                        </select>

                        <label
                            class="flex items-center gap-2 text-xs"
                            :title="'Let the AI write this field'"
                        >
                            <input
                                v-model="field.ai"
                                type="checkbox"
                                class="size-3.5 rounded border"
                            />
                            <Sparkles class="size-3" /> AI writes it
                        </label>
                        <label class="flex items-center gap-2 text-xs">
                            <input
                                v-model="field.required"
                                type="checkbox"
                                class="size-3.5 rounded border"
                            />
                            Required
                        </label>
                    </div>
                </div>

                <div v-if="systemFields.length" class="pt-2">
                    <h3 class="text-muted-foreground text-xs font-medium">
                        Filled automatically ({{ systemFields.length }})
                    </h3>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <code
                            v-for="field in systemFields"
                            :key="field.token"
                            class="bg-muted rounded px-1.5 py-0.5 text-xs"
                            >{{ field.token }}</code
                        >
                    </div>
                </div>
            </section>

            <!-- Token reference -->
            <section class="rounded-xl border p-5">
                <h2 class="text-sm font-semibold">Tokens you can use</h2>

                <div class="mt-3 space-y-3 text-xs">
                    <div>
                        <div class="text-muted-foreground mb-1.5">
                            From the user's profile
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <code
                                v-for="token in tokenReference.profile"
                                :key="token"
                                class="bg-muted rounded px-1.5 py-0.5"
                                >{{ token }}</code
                            >
                        </div>
                    </div>
                    <div>
                        <div class="text-muted-foreground mb-1.5">
                            From the application (the AI fills blanks)
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <code
                                v-for="token in tokenReference.application"
                                :key="token"
                                class="bg-muted rounded px-1.5 py-0.5"
                                >{{ token }}</code
                            >
                        </div>
                    </div>
                    <div>
                        <div class="text-muted-foreground mb-1.5">
                            Palette, derived from the accent colour above
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <code
                                v-for="token in tokenReference.template"
                                :key="token"
                                class="bg-muted rounded px-1.5 py-0.5"
                                >{{ token }}</code
                            >
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- PREVIEW -->
        <div class="xl:sticky xl:top-4 xl:h-[calc(100vh-6rem)]">
            <GmailPreview
                :html="previewHtml"
                subject="Application — Senior Associate | Fajira Zenitha Purnama"
                sender-name="Fajira Zenitha Purnama"
                sender-email="fajira@example.com"
                attachment="cv.pdf"
                :loading="analysing"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BookOpen,
    Braces,
    CircleAlert,
    CircleCheck,
    Eye,
    Loader2,
    Save,
    Scissors,
    TriangleAlert,
} from '@lucide/vue';
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';
import GmailPreview from '@/components/GmailPreview.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import TemplateModeTabs from '@/components/TemplateModeTabs.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import {
    analyse as analyseTemplate,
    index as templatesIndex,
} from '@/routes/templates';
import { store as storeBuiltTemplate } from '@/routes/templates/build';

type Example = {
    key: string;
    name: string;
    description: string;
    accent_color: string;
    html: string;
};

type Field = {
    token: string;
    label: string;
    type: string;
    source: string;
};

type Warning = { level: string; message: string };

type GuideSection = { title: string; body: string; items: string[] };

type TokenGroup = {
    group: string;
    note: string;
    tokens: { token: string; description: string }[];
};

const props = defineProps<{
    guide: GuideSection[];
    tokens: TokenGroup[];
    examples: Example[];
    starter: Example;
    maxBytes: number;
    terms: string[];
    designPrice: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Templates', href: templatesIndex() },
            { title: 'Make your own', href: templatesIndex() },
        ],
    },
});

const page = usePage();

const form = reactive({
    name: props.starter.name,
    description: props.starter.description,
    accent_color: props.starter.accent_color,
    html: props.starter.html,
    accept_terms: false,
});

const preview = ref('');
const fields = ref<Field[]>([]);
const problems = ref<string[]>([]);
const removed = ref<string[]>([]);
const warnings = ref<Warning[]>([]);
const size = ref(props.starter.html.length);
const analysing = ref(false);
const saving = ref(false);
const errors = ref<Record<string, string>>({});
const pane = ref<'preview' | 'guide' | 'tokens'>('preview');
const editor = ref<HTMLTextAreaElement | null>(null);
const loadedExample = ref<string | null>(props.starter.key);

const contentFields = computed(() =>
    fields.value.filter((field) => field.source === 'content'),
);
const autoFields = computed(() =>
    fields.value.filter((field) => field.source !== 'content'),
);
const softWarnings = computed(() =>
    warnings.value.filter((warning) => warning.level !== 'error'),
);

const overSize = computed(() => size.value > props.maxBytes);

const ready = computed(
    () =>
        !analysing.value &&
        problems.value.length === 0 &&
        form.name.trim().length >= 2 &&
        form.html.trim().length > 0,
);

const canSave = computed(
    () => ready.value && form.accept_terms && !saving.value,
);

/**
 * Guide copy marks tokens with backticks so the rules stay readable as plain
 * strings on the server. Split them out rather than reaching for a markdown
 * dependency for one piece of formatting.
 */
const CODE_SPAN = new RegExp('`([^`]+)`', 'g');

function segments(text: string): { text: string; code: boolean }[] {
    return text
        .split(CODE_SPAN)
        .map((part, index) => ({ text: part, code: index % 2 === 1 }))
        .filter((segment) => segment.text !== '');
}

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
                accent_color: form.accent_color,
            }),
        });

        if (!response.ok) {
            return;
        }

        const data = await response.json();

        preview.value = data.preview;
        fields.value = data.fields;
        problems.value = data.problems;
        removed.value = data.removed;
        warnings.value = data.warnings;
        size.value = data.size;
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

function loadExample(example: Example): void {
    if (
        loadedExample.value !== example.key &&
        !window.confirm(
            `Replace what is in the editor with "${example.name}"? Your current HTML will be lost.`,
        )
    ) {
        return;
    }

    form.name = example.name;
    form.description = example.description;
    form.accent_color = example.accent_color;
    form.html = example.html;
    loadedExample.value = example.key;
    pane.value = 'preview';
}

/**
 * Drop a token where the caret is, so the reference is usable without leaving
 * the keyboard or hand-typing the braces.
 */
function tokenLabel(token: string): string {
    return '{{ ' + token + ' }}';
}

function insertToken(token: string): void {
    const snippet = token.startsWith('#')
        ? `{{ ${token} }}\n\n{{ /${token.slice(1).trim()} }}`
        : `{{ ${token} }}`;

    const field = editor.value;

    if (!field) {
        form.html += snippet;

        return;
    }

    const start = field.selectionStart;
    const end = field.selectionEnd;

    form.html = form.html.slice(0, start) + snippet + form.html.slice(end);
    loadedExample.value = null;

    window.requestAnimationFrame(() => {
        field.focus();
        field.setSelectionRange(start + snippet.length, start + snippet.length);
    });
}

function save(): void {
    saving.value = true;
    errors.value = {};

    router.post(
        storeBuiltTemplate().url,
        { ...form },
        {
            preserveScroll: true,
            onError: (received: Record<string, string>) =>
                (errors.value = received),
            onFinish: () => (saving.value = false),
        },
    );
}
</script>

<template>
    <Head title="Make your own template" />

    <div class="space-y-6 p-4">
        <div class="space-y-5">
            <Button variant="ghost" size="sm" class="-ml-2" as-child>
                <Link :href="templatesIndex()">
                    <ArrowLeft class="size-4" /> Back to templates
                </Link>
            </Button>

            <Heading
                title="Design your own template"
                description="Choose how you want to build it. Both end up in the same place: a private template you can use for every application."
            />

            <TemplateModeTabs mode="html" :price="designPrice" />
        </div>

        <!-- EXAMPLES -->
        <section class="space-y-2">
            <p class="text-xs text-muted-foreground">
                Start from a working example — each one saves as-is, then change
                whatever you like.
            </p>
            <div class="grid gap-3 sm:grid-cols-3">
                <button
                    v-for="example in examples"
                    :key="example.key"
                    type="button"
                    :class="
                        cn(
                            'rounded-lg border p-3 text-left transition-colors',
                            loadedExample === example.key
                                ? 'border-primary bg-primary/5'
                                : 'hover:bg-accent/40',
                        )
                    "
                    @click="loadExample(example)"
                >
                    <span class="flex items-center gap-2">
                        <span
                            class="size-3 shrink-0 rounded-full"
                            :style="{ backgroundColor: example.accent_color }"
                        />
                        <span class="text-sm font-medium">{{
                            example.name
                        }}</span>
                    </span>
                    <span
                        class="mt-1.5 block text-xs leading-relaxed text-muted-foreground"
                    >
                        {{ example.description }}
                    </span>
                </button>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
            <!-- EDITOR -->
            <div class="space-y-5">
                <section
                    class="grid gap-4 rounded-xl border p-5 sm:grid-cols-2"
                >
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            maxlength="60"
                            placeholder="My letter"
                        />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="accent">Accent colour</Label>
                        <div class="flex gap-2">
                            <input
                                id="accent"
                                v-model="form.accent_color"
                                type="color"
                                class="h-9 w-12 shrink-0 cursor-pointer rounded-md border bg-transparent"
                            />
                            <Input v-model="form.accent_color" maxlength="7" />
                        </div>
                        <InputError :message="errors.accent_color" />
                    </div>
                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="description">Description</Label>
                        <Input
                            id="description"
                            v-model="form.description"
                            maxlength="400"
                            placeholder="Who this suits and when to pick it"
                        />
                        <InputError :message="errors.description" />
                    </div>
                </section>

                <div class="grid gap-2">
                    <div class="flex items-end justify-between gap-3">
                        <Label for="html">Your HTML</Label>
                        <span
                            class="text-xs"
                            :class="
                                overSize
                                    ? 'text-destructive'
                                    : 'text-muted-foreground'
                            "
                        >
                            {{ size.toLocaleString() }} /
                            {{ maxBytes.toLocaleString() }}
                        </span>
                    </div>
                    <textarea
                        id="html"
                        ref="editor"
                        v-model="form.html"
                        spellcheck="false"
                        class="min-h-[420px] w-full rounded-lg border bg-background p-3 font-mono text-xs leading-relaxed focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        placeholder="Paste or write your table-based email HTML here."
                    />
                    <InputError :message="errors.html" />
                </div>

                <!-- BLOCKING PROBLEMS -->
                <section
                    v-if="problems.length"
                    class="rounded-xl border border-destructive/40 bg-destructive/5 p-4"
                >
                    <h2
                        class="flex items-center gap-2 text-sm font-semibold text-destructive"
                    >
                        <CircleAlert class="size-4" />
                        Fix these before saving
                    </h2>
                    <ul class="mt-2.5 space-y-1.5">
                        <li
                            v-for="problem in problems"
                            :key="problem"
                            class="text-sm leading-relaxed text-destructive"
                        >
                            {{ problem }}
                        </li>
                    </ul>
                </section>

                <section
                    v-else-if="!analysing"
                    class="flex items-center gap-2 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-200"
                >
                    <CircleCheck class="size-4 shrink-0" />
                    This template is valid and ready to save.
                </section>

                <!-- WHAT THE FILTER TOOK OUT -->
                <section
                    v-if="removed.length"
                    class="rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-950/40"
                >
                    <h2
                        class="flex items-center gap-2 text-sm font-semibold text-amber-900 dark:text-amber-200"
                    >
                        <Scissors class="size-4" />
                        Removed from your HTML
                    </h2>
                    <p
                        class="mt-1 text-xs leading-relaxed text-amber-800 dark:text-amber-300/80"
                    >
                        The preview and the saved template are what is left
                        after this. Nothing here is recoverable once you save.
                    </p>
                    <ul class="mt-2.5 space-y-1.5">
                        <li
                            v-for="entry in removed"
                            :key="entry"
                            class="text-sm leading-relaxed text-amber-900 dark:text-amber-200"
                        >
                            {{ entry }}
                        </li>
                    </ul>
                </section>

                <!-- SOFT WARNINGS -->
                <section
                    v-if="softWarnings.length"
                    class="rounded-xl border p-4"
                >
                    <h2 class="flex items-center gap-2 text-sm font-semibold">
                        <TriangleAlert class="size-4 text-muted-foreground" />
                        Worth fixing
                    </h2>
                    <ul class="mt-2.5 space-y-1.5">
                        <li
                            v-for="warning in softWarnings"
                            :key="warning.message"
                            class="text-sm leading-relaxed text-muted-foreground"
                        >
                            {{ warning.message }}
                        </li>
                    </ul>
                </section>

                <!-- DETECTED FIELDS -->
                <section class="rounded-xl border p-5">
                    <h2 class="text-sm font-semibold">
                        The form this builds
                        <span class="font-normal text-muted-foreground"
                            >&middot; {{ contentFields.length }} to fill
                            in</span
                        >
                    </h2>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Detected from your placeholders. These become the boxes
                        you type into on each application.
                    </p>

                    <div
                        v-if="contentFields.length"
                        class="mt-3 flex flex-wrap gap-1.5"
                    >
                        <span
                            v-for="field in contentFields"
                            :key="field.token"
                            class="rounded-md border bg-muted/50 px-2 py-1 font-mono text-xs"
                        >
                            {{ field.token }}
                            <span class="text-muted-foreground">{{
                                field.type
                            }}</span>
                        </span>
                    </div>

                    <div v-if="autoFields.length" class="mt-3">
                        <p class="text-xs text-muted-foreground">
                            Filled in automatically
                        </p>
                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                            <span
                                v-for="field in autoFields"
                                :key="field.token"
                                class="rounded-md border px-2 py-1 font-mono text-xs text-muted-foreground"
                            >
                                {{ field.token }}
                            </span>
                        </div>
                    </div>
                </section>

                <!-- TERMS -->
                <section class="rounded-xl border p-5">
                    <h2 class="text-sm font-semibold">Before you save</h2>
                    <ul class="mt-3 space-y-2">
                        <li
                            v-for="(term, index) in terms"
                            :key="index"
                            class="flex gap-2.5 text-sm leading-relaxed text-muted-foreground"
                        >
                            <span
                                class="mt-1.5 size-1 shrink-0 rounded-full bg-current text-muted-foreground/50"
                            />
                            <span>{{ term }}</span>
                        </li>
                    </ul>

                    <label
                        class="mt-4 flex cursor-pointer items-start gap-3 text-sm"
                    >
                        <input
                            v-model="form.accept_terms"
                            type="checkbox"
                            class="mt-0.5 size-4 shrink-0 rounded border"
                        />
                        <span>
                            I have the right to use this HTML, and I agree that
                            an administrator may publish this design to the
                            shared template library.
                        </span>
                    </label>
                    <InputError class="mt-2" :message="errors.accept_terms" />
                </section>

                <div class="flex flex-wrap items-center gap-3">
                    <Button :disabled="!canSave" @click="save">
                        <Loader2 v-if="saving" class="size-4 animate-spin" />
                        <Save v-else class="size-4" />
                        Save my template
                    </Button>
                    <span class="text-xs text-muted-foreground">
                        Free — writing your own uses no credits.
                    </span>
                </div>
            </div>

            <!-- PREVIEW / GUIDE / TOKENS -->
            <div class="xl:sticky xl:top-4 xl:self-start">
                <div class="mb-3 flex gap-1 rounded-lg border p-1">
                    <button
                        v-for="tab in [
                            { key: 'preview', label: 'Preview', icon: Eye },
                            { key: 'guide', label: 'Guide', icon: BookOpen },
                            {
                                key: 'tokens',
                                label: 'Placeholders',
                                icon: Braces,
                            },
                        ]"
                        :key="tab.key"
                        type="button"
                        :class="
                            cn(
                                'flex flex-1 items-center justify-center gap-1.5 rounded-md px-3 py-1.5 text-sm transition-colors',
                                pane === tab.key
                                    ? 'bg-primary text-primary-foreground'
                                    : 'hover:bg-accent',
                            )
                        "
                        @click="pane = tab.key as typeof pane"
                    >
                        <component :is="tab.icon" class="size-4" />
                        {{ tab.label }}
                    </button>
                </div>

                <GmailPreview
                    v-show="pane === 'preview'"
                    :html="preview"
                    :subject="`Application &mdash; ${form.name}`"
                    :loading="analysing"
                />

                <!-- GUIDE -->
                <div
                    v-show="pane === 'guide'"
                    class="max-h-[75vh] space-y-5 overflow-y-auto rounded-xl border p-5"
                >
                    <section v-for="section in guide" :key="section.title">
                        <h2 class="text-sm font-semibold">
                            {{ section.title }}
                        </h2>
                        <p
                            class="mt-1.5 text-sm leading-relaxed text-muted-foreground"
                        >
                            <template
                                v-for="(part, i) in segments(section.body)"
                                :key="i"
                            >
                                <code
                                    v-if="part.code"
                                    class="rounded bg-muted px-1 py-0.5 font-mono text-xs"
                                    >{{ part.text }}</code
                                >
                                <template v-else>{{ part.text }}</template>
                            </template>
                        </p>
                        <ul class="mt-2.5 space-y-2">
                            <li
                                v-for="(item, index) in section.items"
                                :key="index"
                                class="flex gap-2.5 text-sm leading-relaxed text-muted-foreground"
                            >
                                <span
                                    class="mt-1.5 size-1 shrink-0 rounded-full bg-current text-muted-foreground/50"
                                />
                                <span>
                                    <template
                                        v-for="(part, i) in segments(item)"
                                        :key="i"
                                    >
                                        <code
                                            v-if="part.code"
                                            class="rounded bg-muted px-1 py-0.5 font-mono text-xs text-foreground"
                                            >{{ part.text }}</code
                                        >
                                        <template v-else>{{
                                            part.text
                                        }}</template>
                                    </template>
                                </span>
                            </li>
                        </ul>
                    </section>
                </div>

                <!-- TOKEN REFERENCE -->
                <div
                    v-show="pane === 'tokens'"
                    class="max-h-[75vh] space-y-5 overflow-y-auto rounded-xl border p-5"
                >
                    <p class="text-xs text-muted-foreground">
                        Click any placeholder to drop it into your HTML where
                        the cursor is.
                    </p>
                    <section v-for="group in tokens" :key="group.group">
                        <h2 class="text-sm font-semibold">{{ group.group }}</h2>
                        <p
                            class="mt-1 text-xs leading-relaxed text-muted-foreground"
                        >
                            {{ group.note }}
                        </p>
                        <ul class="mt-2.5 space-y-1">
                            <li
                                v-for="entry in group.tokens"
                                :key="entry.token"
                                class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5"
                            >
                                <button
                                    type="button"
                                    class="rounded-md border bg-muted/50 px-1.5 py-0.5 font-mono text-xs transition-colors hover:bg-accent"
                                    @click="insertToken(entry.token)"
                                >
                                    {{ tokenLabel(entry.token) }}
                                </button>
                                <span class="text-xs text-muted-foreground">{{
                                    entry.description
                                }}</span>
                            </li>
                        </ul>
                    </section>
                </div>
            </div>
        </div>
    </div>
</template>

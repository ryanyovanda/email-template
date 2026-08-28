<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Eye,
    Loader2,
    Sparkles,
    Trash2,
    TriangleAlert,
    Users,
    Wand2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import GmailPreview from '@/components/GmailPreview.vue';
import Heading from '@/components/Heading.vue';
import TemplateThumbnail from '@/components/TemplateThumbnail.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { edit as applicantProfileEdit } from '@/routes/applicant-profile';
import { store as storeApplication } from '@/routes/applications';
import {
    destroy as destroyTemplate,
    generate as generateTemplate,
    index as templatesIndex,
} from '@/routes/templates';

type Template = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    accent_color: string;
    thumbnail_url: string | null;
    field_count: number;
    ai_field_count: number;
    is_mine: boolean;
    is_global: boolean;
    is_community: boolean;
    preview_html: string;
};

defineProps<{
    templates: Template[];
    hasCvText: boolean;
    credits: number;
    designPrice: number;
    highlight: number | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Templates', href: templatesIndex() }],
    },
});

const startingId = ref<number | null>(null);
const previewing = ref<Template | null>(null);

const previewOpen = computed({
    get: () => previewing.value !== null,
    set: (open: boolean) => {
        if (!open) {
            previewing.value = null;
        }
    },
});

function remove(template: Template): void {
    if (
        !window.confirm(
            `Delete "${template.name}"? Drafts already using it keep their saved copy.`,
        )
    ) {
        return;
    }

    router.delete(destroyTemplate(template.id).url, { preserveScroll: true });
}

function start(template: Template): void {
    startingId.value = template.id;

    router.post(
        storeApplication().url,
        { email_template_id: template.id },
        { onFinish: () => (startingId.value = null) },
    );
}
</script>

<template>
    <Head title="Templates" />

    <div class="space-y-6 p-4">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                title="Choose a template"
                description="Pick a layout, then fill it in yourself or let the AI draft it from your CV and the job posting."
            />
            <Button variant="outline" as-child>
                <Link :href="generateTemplate()">
                    <Sparkles class="size-4" />
                    Design your own
                    <span class="text-xs text-muted-foreground">
                        {{ designPrice }} credits, or free by hand
                    </span>
                </Link>
            </Button>
        </div>

        <div
            v-if="!hasCvText"
            class="flex flex-wrap items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-200"
        >
            <TriangleAlert class="size-4 shrink-0" />
            <span class="flex-1"
                >AI drafting needs your CV text. Manual filling works without
                it.</span
            >
            <Button variant="outline" size="sm" as-child>
                <a :href="applicantProfileEdit().url">Add my CV</a>
            </Button>
        </div>

        <div
            v-if="templates.length === 0"
            class="rounded-xl border border-dashed p-12 text-center"
        >
            <p class="text-sm text-muted-foreground">
                No templates are available yet.
            </p>
            <Button class="mt-4" as-child>
                <Link :href="generateTemplate()">
                    <Sparkles class="size-4" /> Design one
                </Link>
            </Button>
        </div>

        <div v-else class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="template in templates"
                :key="template.id"
                class="group relative flex flex-col overflow-hidden rounded-xl border transition-colors hover:border-primary/40"
                :class="
                    highlight === template.id
                        ? 'ring-2 ring-primary/60 ring-offset-2'
                        : ''
                "
            >
                <button
                    type="button"
                    class="group/preview relative block w-full border-b text-left focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    :aria-label="`Preview ${template.name}`"
                    @click="previewing = template"
                >
                    <TemplateThumbnail :html="template.preview_html" />

                    <span
                        class="absolute inset-0 flex items-center justify-center bg-black/45 opacity-0 transition-opacity group-hover/preview:opacity-100 group-focus-visible/preview:opacity-100"
                    >
                        <span
                            class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-medium text-neutral-900 shadow-sm"
                        >
                            <Eye class="size-4" /> Preview
                        </span>
                    </span>
                </button>

                <div class="flex flex-1 flex-col p-5">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="font-semibold">{{ template.name }}</h3>
                        <span
                            v-if="template.is_mine && !template.is_global"
                            class="shrink-0 rounded-full border px-2 py-0.5 text-[10px] text-muted-foreground"
                            >Yours</span
                        >
                        <span
                            v-else-if="template.is_community"
                            class="inline-flex shrink-0 items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] text-muted-foreground"
                        >
                            <Users class="size-2.5" /> Community
                        </span>
                    </div>
                    <p
                        v-if="template.description"
                        class="mt-1.5 flex-1 text-sm leading-relaxed text-muted-foreground"
                    >
                        {{ template.description }}
                    </p>

                    <div
                        class="mt-4 flex items-center gap-3 text-xs text-muted-foreground"
                    >
                        <span>{{ template.field_count }} fields</span>
                        <span
                            v-if="template.ai_field_count"
                            class="inline-flex items-center gap-1"
                        >
                            <Sparkles class="size-3" />
                            {{ template.ai_field_count }} AI-writable
                        </span>
                    </div>

                    <div class="mt-4 flex gap-2">
                        <Button
                            class="flex-1"
                            :disabled="startingId !== null"
                            @click="start(template)"
                        >
                            <Loader2
                                v-if="startingId === template.id"
                                class="size-4 animate-spin"
                            />
                            <Wand2 v-else class="size-4" />
                            Use this template
                        </Button>
                        <Button
                            v-if="template.is_mine && !template.is_global"
                            variant="outline"
                            size="icon"
                            class="text-muted-foreground hover:text-destructive"
                            aria-label="Delete this template"
                            @click="remove(template)"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                </div>
            </article>
        </div>
    </div>

    <!-- Full-size preview, in the Gmail chrome the finished email is judged in -->
    <Dialog v-model:open="previewOpen">
        <DialogContent class="max-h-[92vh] max-w-4xl overflow-hidden p-0">
            <DialogHeader class="border-b px-6 pt-6 pb-4">
                <DialogTitle>{{ previewing?.name }}</DialogTitle>
                <DialogDescription>
                    {{
                        previewing?.description ??
                        'Sample content, your own profile details.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <div class="max-h-[64vh] overflow-y-auto px-6 py-4">
                <GmailPreview
                    v-if="previewing"
                    :html="previewing.preview_html"
                    subject="Application — Senior Associate | Acme Corp"
                    :sender-name="$page.props.auth.user.name"
                    :sender-email="$page.props.auth.user.email"
                    attachment="cv.pdf"
                />
            </div>

            <div class="flex flex-wrap gap-2 border-t px-6 py-4">
                <Button
                    class="flex-1"
                    :disabled="startingId !== null"
                    @click="previewing && start(previewing)"
                >
                    <Wand2 class="size-4" /> Use this template
                </Button>
                <Button variant="outline" @click="previewing = null">
                    Close
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>

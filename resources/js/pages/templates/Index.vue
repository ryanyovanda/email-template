<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Loader2, Sparkles, TriangleAlert, Wand2 } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { edit as applicantProfileEdit } from '@/routes/applicant-profile';
import { store as storeApplication } from '@/routes/applications';
import { index as templatesIndex } from '@/routes/templates';

type Template = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    accent_color: string;
    thumbnail_url: string | null;
    field_count: number;
    ai_field_count: number;
};

defineProps<{
    templates: Template[];
    hasCvText: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Templates', href: templatesIndex() }],
    },
});

const startingId = ref<number | null>(null);

function start(template: Template): void {
    startingId.value = template.id;

    router.post(
        storeApplication().url,
        { email_template_id: template.id, title: `Application — ${template.name}` },
        { onFinish: () => (startingId.value = null) },
    );
}
</script>

<template>
    <Head title="Templates" />

    <div class="space-y-6 p-4">
        <Heading
            title="Choose a template"
            description="Pick a layout, then fill it in yourself or let the AI draft it from your CV and the job posting."
        />

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
            class="text-muted-foreground rounded-xl border border-dashed p-12 text-center text-sm"
        >
            No templates are available yet. Check back shortly.
        </div>

        <div v-else class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="template in templates"
                :key="template.id"
                class="group hover:border-primary/40 flex flex-col overflow-hidden rounded-xl border transition-colors"
            >
                <div
                    class="relative flex h-40 items-center justify-center overflow-hidden"
                    :style="{
                        background: `linear-gradient(135deg, ${template.accent_color}22, ${template.accent_color}05)`,
                    }"
                >
                    <img
                        v-if="template.thumbnail_url"
                        :src="template.thumbnail_url"
                        :alt="template.name"
                        class="size-full object-cover object-top"
                    />
                    <!-- Stand-in preview: a miniature of the email's shape -->
                    <div
                        v-else
                        class="w-40 rounded-md bg-white p-2.5 shadow-md ring-1 ring-black/5"
                    >
                        <div
                            class="h-5 rounded-sm"
                            :style="{ backgroundColor: template.accent_color }"
                        />
                        <div class="mt-2 space-y-1.5">
                            <div class="h-1.5 w-3/4 rounded-full bg-neutral-200" />
                            <div class="h-1.5 w-full rounded-full bg-neutral-100" />
                            <div class="h-1.5 w-full rounded-full bg-neutral-100" />
                            <div class="h-1.5 w-2/3 rounded-full bg-neutral-100" />
                        </div>
                        <div class="mt-2 flex gap-1">
                            <div
                                v-for="n in 3"
                                :key="n"
                                class="h-2 w-7 rounded-full"
                                :style="{
                                    backgroundColor: `${template.accent_color}33`,
                                }"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex flex-1 flex-col p-5">
                    <h3 class="font-semibold">{{ template.name }}</h3>
                    <p
                        v-if="template.description"
                        class="text-muted-foreground mt-1.5 flex-1 text-sm leading-relaxed"
                    >
                        {{ template.description }}
                    </p>

                    <div
                        class="text-muted-foreground mt-4 flex items-center gap-3 text-xs"
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

                    <Button
                        class="mt-4 w-full"
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
                </div>
            </article>
        </div>
    </div>
</template>

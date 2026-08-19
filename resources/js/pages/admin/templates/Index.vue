<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { dashboard as adminDashboard } from '@/routes/admin';
import {
    create as createTemplate,
    destroy as destroyTemplate,
    edit as editTemplate,
    index as adminTemplatesIndex,
} from '@/routes/admin/templates';

type TemplateRow = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    accent_color: string;
    thumbnail_url: string | null;
    is_active: boolean;
    sort_order: number;
    field_count: number;
    applications_count: number;
    updated_at: string | null;
};

defineProps<{ templates: TemplateRow[] }>();

const blurb =
    'Paste email HTML with double-brace tokens. The fill-in form builds itself from whatever tokens you use.';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: adminDashboard() },
            { title: 'Templates', href: adminTemplatesIndex() },
        ],
    },
});

function remove(template: TemplateRow): void {
    if (
        !window.confirm(
            `Delete "${template.name}"? ${template.applications_count} draft(s) use it — they keep their saved HTML but can no longer be edited.`,
        )
    ) {
        return;
    }

    router.delete(destroyTemplate(template.id).url);
}
</script>

<template>
    <Head title="Templates" />

    <div class="space-y-6 p-4">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                title="Templates"
                :description="blurb"
            />
            <Button as-child>
                <Link :href="createTemplate()">
                    <Plus class="size-4" /> New template
                </Link>
            </Button>
        </div>

        <div class="divide-y rounded-xl border">
            <div
                v-for="template in templates"
                :key="template.id"
                class="flex flex-wrap items-center gap-4 p-4"
            >
                <div
                    class="size-10 shrink-0 rounded-lg"
                    :style="{ backgroundColor: template.accent_color }"
                />

                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="font-medium">{{ template.name }}</span>
                        <span
                            v-if="!template.is_active"
                            class="text-muted-foreground rounded-full border px-2 py-0.5 text-[10px]"
                            >hidden</span
                        >
                    </div>
                    <p
                        v-if="template.description"
                        class="text-muted-foreground mt-0.5 line-clamp-1 text-xs"
                    >
                        {{ template.description }}
                    </p>
                    <div class="text-muted-foreground mt-1 text-xs">
                        {{ template.field_count }} tokens ·
                        {{ template.applications_count }} drafts · updated
                        {{ template.updated_at }}
                    </div>
                </div>

                <Button variant="outline" size="sm" as-child>
                    <Link :href="editTemplate(template.id)">
                        <Pencil class="size-3.5" /> Edit
                    </Link>
                </Button>
                <Button
                    variant="ghost"
                    size="icon-sm"
                    class="text-muted-foreground hover:text-destructive"
                    @click="remove(template)"
                >
                    <Trash2 class="size-4" />
                </Button>
            </div>

            <div
                v-if="templates.length === 0"
                class="text-muted-foreground p-12 text-center text-sm"
            >
                No templates yet. Create the first one so users have something
                to choose.
            </div>
        </div>
    </div>
</template>

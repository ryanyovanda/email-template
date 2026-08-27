<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Code2,
    Globe,
    Pencil,
    Plus,
    Trash2,
    Undo2,
    UserRound,
} from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { dashboard as adminDashboard } from '@/routes/admin';
import {
    create as createTemplate,
    demote as demoteTemplate,
    destroy as destroyTemplate,
    edit as editTemplate,
    index as adminTemplatesIndex,
    promote as promoteTemplate,
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
    visibility: string;
    origin: string;
    is_user_made: boolean;
    is_hand_written: boolean;
    brief: string | null;
    author: { id: number; name: string; email: string } | null;
    terms_accepted: boolean;
    promoted_at: string | null;
};

defineProps<{
    templates: TemplateRow[];
    filters: { filter: string };
    counts: { all: number; user: number; pending: number; global: number };
}>();

const tabs = [
    { key: '', label: 'All', count: 'all' as const },
    { key: 'user', label: 'Made by users', count: 'user' as const },
    { key: 'pending', label: 'Awaiting review', count: 'pending' as const },
    { key: 'global', label: 'Shared library', count: 'global' as const },
];

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

function promote(template: TemplateRow): void {
    if (
        !window.confirm(
            `Publish "${template.name}" to the shared library? Every user will be able to choose it.`,
        )
    ) {
        return;
    }

    router.post(promoteTemplate(template.id).url, {}, { preserveScroll: true });
}

function demote(template: TemplateRow): void {
    router.delete(demoteTemplate(template.id).url, { preserveScroll: true });
}

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
            <Heading title="Templates" :description="blurb" />
            <Button as-child>
                <Link :href="createTemplate()">
                    <Plus class="size-4" /> New template
                </Link>
            </Button>
        </div>

        <div class="inline-flex flex-wrap gap-1 rounded-lg bg-muted p-1">
            <Button
                v-for="tab in tabs"
                :key="tab.key"
                variant="ghost"
                size="sm"
                :class="
                    cn(
                        'h-7 gap-1.5 px-3 text-xs',
                        filters.filter === tab.key && 'bg-background shadow-sm',
                    )
                "
                as-child
            >
                <Link
                    :href="adminTemplatesIndex({ query: { filter: tab.key } })"
                >
                    {{ tab.label }}
                    <span class="text-muted-foreground">{{
                        counts[tab.count]
                    }}</span>
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
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-medium">{{ template.name }}</span>
                        <span
                            v-if="!template.is_active"
                            class="rounded-full border px-2 py-0.5 text-[10px] text-muted-foreground"
                            >hidden</span
                        >
                        <span
                            v-if="template.visibility === 'global'"
                            class="inline-flex items-center gap-1 rounded-full border border-emerald-500/40 px-2 py-0.5 text-[10px] text-emerald-600 dark:text-emerald-400"
                        >
                            <Globe class="size-2.5" /> shared
                        </span>
                        <span
                            v-if="template.is_user_made"
                            class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] text-muted-foreground"
                        >
                            <UserRound class="size-2.5" />
                            {{ template.author?.email ?? 'deleted user' }}
                        </span>
                        <span
                            v-if="template.is_hand_written"
                            class="inline-flex items-center gap-1 rounded-full border border-amber-500/40 px-2 py-0.5 text-[10px] text-amber-600 dark:text-amber-400"
                            title="Written by hand — check the author had the right to use this markup before publishing it."
                        >
                            <Code2 class="size-2.5" /> hand-written
                        </span>
                    </div>
                    <p
                        v-if="template.description"
                        class="mt-0.5 line-clamp-1 text-xs text-muted-foreground"
                    >
                        {{ template.description }}
                    </p>
                    <p
                        v-if="template.brief"
                        class="mt-1 line-clamp-2 text-xs text-muted-foreground italic"
                    >
                        Asked for: “{{ template.brief }}”
                    </p>
                    <div class="mt-1 text-xs text-muted-foreground">
                        {{ template.field_count }} tokens ·
                        {{ template.applications_count }} drafts · updated
                        {{ template.updated_at }}
                        <span v-if="template.promoted_at">
                            · shared {{ template.promoted_at }}</span
                        >
                        <span
                            v-if="
                                template.is_user_made &&
                                !template.terms_accepted
                            "
                            class="text-destructive"
                        >
                            · no sharing consent on record</span
                        >
                    </div>
                </div>

                <Button
                    v-if="template.visibility !== 'global'"
                    size="sm"
                    :disabled="
                        template.is_user_made && !template.terms_accepted
                    "
                    @click="promote(template)"
                >
                    <Globe class="size-3.5" /> Publish
                </Button>
                <Button
                    v-else
                    variant="outline"
                    size="sm"
                    @click="demote(template)"
                >
                    <Undo2 class="size-3.5" /> Unpublish
                </Button>

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
                class="p-12 text-center text-sm text-muted-foreground"
            >
                No templates yet. Create the first one so users have something
                to choose.
            </div>
        </div>
    </div>
</template>

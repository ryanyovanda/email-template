<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CheckCircle2, Plus, Sparkles, Trash2 } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { paginationLabel } from '@/lib/pagination';
import {
    destroy as destroyApplication,
    edit as editApplication,
    index as applicationsIndex,
} from '@/routes/applications';
import { index as templatesIndex } from '@/routes/templates';

type ApplicationRow = {
    id: number;
    title: string;
    display_name: string;
    company: string | null;
    position: string | null;
    mode: string;
    template: { id: number; name: string; accent_color: string } | null;
    updated_at: string | null;
    last_copied_at: string | null;
};

type Paginated = {
    data: ApplicationRow[];
    links: { url: string | null; label: string; active: boolean }[];
};

defineProps<{
    applications: Paginated;
    credits: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'My applications', href: applicationsIndex() }],
    },
});

function remove(application: ApplicationRow): void {
    if (
        !window.confirm(
            `Delete "${application.display_name}"? This cannot be undone.`,
        )
    ) {
        return;
    }

    router.delete(destroyApplication(application.id).url, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="My applications" />

    <div class="space-y-6 p-4">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <Heading
                title="My applications"
                :description="`${credits.toLocaleString()} credits available.`"
            />
            <Button as-child>
                <Link :href="templatesIndex()">
                    <Plus class="size-4" /> New application
                </Link>
            </Button>
        </div>

        <div
            v-if="applications.data.length === 0"
            class="rounded-xl border border-dashed p-12 text-center"
        >
            <p class="text-sm text-muted-foreground">
                No applications yet. Pick a template to write your first one.
            </p>
            <Button class="mt-4" as-child>
                <Link :href="templatesIndex()">Browse templates</Link>
            </Button>
        </div>

        <div v-else class="divide-y rounded-xl border">
            <div
                v-for="application in applications.data"
                :key="application.id"
                class="flex items-center gap-4 p-4 transition-colors hover:bg-accent/40"
            >
                <div
                    class="h-10 w-1.5 shrink-0 rounded-full"
                    :style="{
                        backgroundColor:
                            application.template?.accent_color ?? '#d4d4d8',
                    }"
                />

                <div class="min-w-0 flex-1">
                    <Link
                        :href="editApplication(application.id)"
                        class="truncate font-medium hover:underline"
                        :class="
                            application.company ? '' : 'text-muted-foreground'
                        "
                    >
                        {{ application.display_name }}
                    </Link>
                    <div
                        class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground"
                    >
                        <span v-if="application.position">{{
                            application.position
                        }}</span>
                        <span v-if="application.template">{{
                            application.template.name
                        }}</span>
                        <span
                            v-if="application.mode === 'ai'"
                            class="inline-flex items-center gap-1"
                        >
                            <Sparkles class="size-3" /> AI draft
                        </span>
                        <span
                            v-if="application.last_copied_at"
                            class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400"
                        >
                            <CheckCircle2 class="size-3" /> copied
                            {{ application.last_copied_at }}
                        </span>
                        <span v-else>edited {{ application.updated_at }}</span>
                    </div>
                </div>

                <Button variant="outline" size="sm" as-child>
                    <Link :href="editApplication(application.id)">Open</Link>
                </Button>
                <Button
                    variant="ghost"
                    size="icon-sm"
                    class="text-muted-foreground hover:text-destructive"
                    @click="remove(application)"
                >
                    <Trash2 class="size-4" />
                </Button>
            </div>
        </div>

        <div
            v-if="applications.links.length > 3"
            class="flex flex-wrap gap-1.5"
        >
            <Button
                v-for="link in applications.links"
                :key="link.label"
                :variant="link.active ? 'default' : 'outline'"
                size="sm"
                :disabled="!link.url"
                as-child
            >
                <Link v-if="link.url" :href="link.url">{{
                    paginationLabel(link.label)
                }}</Link>
                <span v-else>{{ paginationLabel(link.label) }}</span>
            </Button>
        </div>
    </div>
</template>

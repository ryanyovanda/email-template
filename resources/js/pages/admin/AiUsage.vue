<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { paginationLabel } from '@/lib/pagination';
import { cn } from '@/lib/utils';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as aiUsageIndex } from '@/routes/admin/ai-usage';
import { index as adminUsersIndex } from '@/routes/admin/users';

type Row = {
    id: number;
    user: {
        id: number;
        name: string;
        email: string;
        banned_at: string | null;
    } | null;
    application: { id: number; title: string; company: string | null } | null;
    model: string;
    status: string;
    error: string | null;
    total_tokens: number;
    duration_ms: number;
    ip_address: string | null;
    created_at: string | null;
};

const props = defineProps<{
    generations: {
        data: Row[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { status: string; user: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: adminDashboard() },
            { title: 'AI usage', href: aiUsageIndex() },
        ],
    },
});

const tabs = [
    { key: '', label: 'All' },
    { key: 'success', label: 'Successful' },
    { key: 'failed', label: 'Failed' },
];
</script>

<template>
    <Head title="AI usage" />

    <div class="space-y-6 p-4">
        <Heading
            title="AI usage log"
            description="Every DeepSeek call this app has made, with tokens spent and the IP it came from."
        />

        <div class="inline-flex gap-1 rounded-lg bg-muted p-1">
            <Button
                v-for="tab in tabs"
                :key="tab.key"
                variant="ghost"
                size="sm"
                :class="
                    cn(
                        'h-7 px-3 text-xs',
                        props.filters.status === tab.key &&
                            'bg-background shadow-sm',
                    )
                "
                as-child
            >
                <Link
                    :href="
                        aiUsageIndex({
                            query: {
                                status: tab.key,
                                user: props.filters.user,
                            },
                        })
                    "
                    >{{ tab.label }}</Link
                >
            </Button>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-xs text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">When</th>
                        <th class="px-4 py-3 text-left font-medium">User</th>
                        <th class="px-4 py-3 text-left font-medium">
                            Application
                        </th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Tokens</th>
                        <th class="px-4 py-3 text-right font-medium">Took</th>
                        <th class="px-4 py-3 text-left font-medium">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="row in generations.data"
                        :key="row.id"
                        class="hover:bg-accent/30"
                    >
                        <td
                            class="px-4 py-3 text-xs whitespace-nowrap text-muted-foreground"
                        >
                            {{ row.created_at }}
                        </td>
                        <td class="px-4 py-3">
                            <Link
                                v-if="row.user"
                                :href="
                                    adminUsersIndex({
                                        query: { search: row.user.email },
                                    })
                                "
                                class="hover:underline"
                            >
                                <div class="font-medium">
                                    {{ row.user.name }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ row.user.email }}
                                </div>
                            </Link>
                            <span v-else class="text-xs text-muted-foreground"
                                >deleted</span
                            >
                        </td>
                        <td class="px-4 py-3 text-xs">
                            <div>{{ row.application?.title ?? '—' }}</div>
                            <div class="text-muted-foreground">
                                {{ row.application?.company }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span
                                :class="
                                    cn(
                                        'rounded-full border px-2 py-0.5 text-[10px]',
                                        row.status === 'success'
                                            ? 'border-emerald-500/40 text-emerald-600 dark:text-emerald-400'
                                            : 'border-destructive/40 text-destructive',
                                    )
                                "
                                >{{ row.status }}</span
                            >
                            <div
                                v-if="row.error"
                                class="mt-1 max-w-xs truncate text-xs text-muted-foreground"
                                :title="row.error"
                            >
                                {{ row.error }}
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            {{ row.total_tokens.toLocaleString() }}
                        </td>
                        <td
                            class="px-4 py-3 text-right text-xs text-muted-foreground"
                        >
                            {{ (row.duration_ms / 1000).toFixed(1) }}s
                        </td>
                        <td
                            class="px-4 py-3 font-mono text-xs text-muted-foreground"
                        >
                            {{ row.ip_address }}
                        </td>
                    </tr>
                    <tr v-if="generations.data.length === 0">
                        <td
                            colspan="7"
                            class="px-4 py-10 text-center text-sm text-muted-foreground"
                        >
                            No AI calls recorded yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="generations.links.length > 3" class="flex flex-wrap gap-1.5">
            <Button
                v-for="link in generations.links"
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

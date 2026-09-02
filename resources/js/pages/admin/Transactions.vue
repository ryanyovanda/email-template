<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Clock, Coins, Receipt, TrendingUp } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { paginationLabel } from '@/lib/pagination';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as adminTransactionsIndex } from '@/routes/admin/transactions';

type Row = {
    id: number;
    number: string;
    user: { name: string; email: string } | null;
    credits: number;
    amount: number;
    currency: string;
    status: string;
    reference: string;
    at: string | null;
    paidAt: string | null;
};

defineProps<{
    transactions: {
        data: Row[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { status: string };
    currency: string;
    stats: {
        revenue: number;
        paidCount: number;
        creditsSold: number;
        pendingCount: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: adminDashboard() },
            { title: 'Transactions', href: adminTransactionsIndex() },
        ],
    },
});

const money = (n: number): string => new Intl.NumberFormat('id-ID').format(n);

const tabs = [
    { key: '', label: 'All' },
    { key: 'paid', label: 'Paid' },
    { key: 'pending', label: 'Pending' },
    { key: 'expired', label: 'Expired' },
    { key: 'failed', label: 'Failed' },
];

const setFilter = (status: string) => {
    router.get(
        adminTransactionsIndex().url,
        { status },
        { preserveState: true, replace: true, preserveScroll: true },
    );
};

const statusLabel = (s: string): string =>
    (
        ({
            pending: 'Pending',
            paid: 'Paid',
            failed: 'Failed',
            expired: 'Expired',
        }) as Record<string, string>
    )[s] ?? s;

const statusClass = (s: string): string =>
    (
        ({
            paid: 'text-emerald-600',
            pending: 'text-amber-600',
            failed: 'text-red-500',
            expired: 'text-muted-foreground',
        }) as Record<string, string>
    )[s] ?? 'text-muted-foreground';
</script>

<template>
    <Head title="Transactions" />

    <div class="space-y-6 p-4 md:p-6">
        <Heading
            title="Transactions"
            description="Every credit purchase across all users. Revenue counts paid purchases only."
        />

        <!-- Stats -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border bg-card p-4">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <TrendingUp class="size-4" />
                    <span class="text-sm">Revenue</span>
                </div>
                <p class="mt-2 text-2xl font-bold">
                    {{ currency }} {{ money(stats.revenue) }}
                </p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <Receipt class="size-4" />
                    <span class="text-sm">Paid orders</span>
                </div>
                <p class="mt-2 text-2xl font-bold">{{ stats.paidCount }}</p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <Coins class="size-4" />
                    <span class="text-sm">Credits sold</span>
                </div>
                <p class="mt-2 text-2xl font-bold">{{ stats.creditsSold }}</p>
            </div>
            <div class="rounded-xl border bg-card p-4">
                <div class="flex items-center gap-2 text-muted-foreground">
                    <Clock class="size-4" />
                    <span class="text-sm">Pending</span>
                </div>
                <p class="mt-2 text-2xl font-bold">{{ stats.pendingCount }}</p>
            </div>
        </div>

        <!-- Filter tabs -->
        <div class="flex flex-wrap gap-1">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                class="rounded-lg px-3 py-1.5 text-sm font-medium"
                :class="
                    filters.status === tab.key
                        ? 'bg-primary text-primary-foreground'
                        : 'text-muted-foreground hover:bg-muted'
                "
                @click="setFilter(tab.key)"
            >
                {{ tab.label }}
            </button>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto rounded-xl border bg-card">
            <table class="w-full min-w-[800px] text-sm">
                <thead>
                    <tr class="border-b text-left text-muted-foreground">
                        <th class="px-4 py-2 font-medium">Invoice</th>
                        <th class="px-4 py-2 font-medium">User</th>
                        <th class="px-4 py-2 font-medium">Credits</th>
                        <th class="px-4 py-2 font-medium">Amount</th>
                        <th class="px-4 py-2 font-medium">Status</th>
                        <th class="px-4 py-2 font-medium">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="transactions.data.length === 0">
                        <td
                            colspan="6"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No transactions found.
                        </td>
                    </tr>
                    <tr
                        v-for="row in transactions.data"
                        :key="row.id"
                        class="border-b last:border-0"
                    >
                        <td class="px-4 py-2 font-mono text-xs">
                            {{ row.number }}
                        </td>
                        <td class="px-4 py-2">
                            <div v-if="row.user" class="font-medium">
                                {{ row.user.name }}
                            </div>
                            <div
                                v-if="row.user"
                                class="text-xs text-muted-foreground"
                            >
                                {{ row.user.email }}
                            </div>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td class="px-4 py-2">{{ row.credits }}</td>
                        <td class="px-4 py-2">
                            {{ row.currency }} {{ money(row.amount) }}
                        </td>
                        <td
                            class="px-4 py-2 font-medium"
                            :class="statusClass(row.status)"
                        >
                            {{ statusLabel(row.status) }}
                        </td>
                        <td class="px-4 py-2 text-muted-foreground">
                            {{ row.at }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="transactions.links.length > 3" class="flex flex-wrap gap-1">
            <button
                v-for="link in transactions.links"
                :key="link.label"
                :disabled="!link.url"
                class="rounded-md border px-3 py-1 text-sm disabled:opacity-40"
                :class="link.active ? 'bg-primary text-primary-foreground' : ''"
                @click="link.url && router.get(link.url)"
            >
                {{ paginationLabel(link.label) }}
            </button>
        </div>
    </div>
</template>

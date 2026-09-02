<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDownRight, ArrowUpRight, Coins, FileText } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { paginationLabel } from '@/lib/pagination';
import {
    index as creditsIndex,
    invoice as creditsInvoice,
} from '@/routes/credits';

type LedgerRow = {
    id: number;
    amount: number;
    reason: string;
    label: string;
    description: string | null;
    at: string | null;
};

type Purchase = {
    id: number;
    credits: number;
    amount: number;
    currency: string;
    status: string;
    invoiceUrl: string | null;
    at: string | null;
    paidAt: string | null;
};

defineProps<{
    balance: number;
    currency: string;
    ledger: {
        data: LedgerRow[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    purchases: Purchase[];
}>();

const money = (n: number): string => new Intl.NumberFormat('id-ID').format(n);

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
    <Head title="Transaction history" />

    <div class="mx-auto max-w-5xl space-y-8 p-4 md:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <Heading
                title="Transaction history"
                description="Every credit movement on your account, and your purchase invoices."
            />
            <Button as-child variant="outline">
                <Link :href="creditsIndex().url">Buy credits</Link>
            </Button>
        </div>

        <!-- Balance -->
        <div
            class="flex items-center gap-3 rounded-xl border bg-card px-5 py-4"
        >
            <div
                class="flex size-10 items-center justify-center rounded-full bg-primary/10"
            >
                <Coins class="size-5 text-primary" />
            </div>
            <div>
                <p class="text-sm text-muted-foreground">Current balance</p>
                <p class="text-2xl font-bold">
                    {{ balance }}
                    <span class="text-base font-normal text-muted-foreground">
                        credits
                    </span>
                </p>
            </div>
        </div>

        <!-- Purchases -->
        <section v-if="purchases.length > 0">
            <h2 class="mb-3 text-lg font-semibold">Purchases</h2>
            <div class="overflow-x-auto rounded-xl border bg-card">
                <table class="w-full min-w-[640px] text-sm">
                    <thead>
                        <tr class="border-b text-left text-muted-foreground">
                            <th class="px-4 py-2 font-medium">Date</th>
                            <th class="px-4 py-2 font-medium">Credits</th>
                            <th class="px-4 py-2 font-medium">Amount</th>
                            <th class="px-4 py-2 font-medium">Status</th>
                            <th class="px-4 py-2 text-right font-medium">
                                Invoice
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="p in purchases"
                            :key="p.id"
                            class="border-b last:border-0"
                        >
                            <td class="px-4 py-2 text-muted-foreground">
                                {{ p.at }}
                            </td>
                            <td class="px-4 py-2">+{{ p.credits }}</td>
                            <td class="px-4 py-2">
                                {{ p.currency }} {{ money(p.amount) }}
                            </td>
                            <td
                                class="px-4 py-2 font-medium"
                                :class="statusClass(p.status)"
                            >
                                {{ statusLabel(p.status) }}
                            </td>
                            <td class="px-4 py-2 text-right">
                                <Button as-child size="sm" variant="outline">
                                    <Link
                                        :href="
                                            creditsInvoice({ purchase: p.id })
                                                .url
                                        "
                                    >
                                        <FileText class="mr-1 size-3.5" />
                                        View
                                    </Link>
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Ledger -->
        <section>
            <h2 class="mb-3 text-lg font-semibold">Credit ledger</h2>
            <div
                v-if="ledger.data.length === 0"
                class="rounded-xl border bg-card px-4 py-8 text-center text-sm text-muted-foreground"
            >
                No credit activity yet.
            </div>
            <div v-else class="overflow-x-auto rounded-xl border bg-card">
                <table class="w-full min-w-[560px] text-sm">
                    <thead>
                        <tr class="border-b text-left text-muted-foreground">
                            <th class="px-4 py-2 font-medium">Date</th>
                            <th class="px-4 py-2 font-medium">Activity</th>
                            <th class="px-4 py-2 text-right font-medium">
                                Credits
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in ledger.data"
                            :key="row.id"
                            class="border-b last:border-0"
                        >
                            <td class="px-4 py-2 text-muted-foreground">
                                {{ row.at }}
                            </td>
                            <td class="px-4 py-2">
                                <div class="font-medium">{{ row.label }}</div>
                                <div
                                    v-if="row.description"
                                    class="text-xs text-muted-foreground"
                                >
                                    {{ row.description }}
                                </div>
                            </td>
                            <td
                                class="px-4 py-2 text-right font-medium"
                                :class="
                                    row.amount >= 0
                                        ? 'text-emerald-600'
                                        : 'text-red-500'
                                "
                            >
                                <span
                                    class="inline-flex items-center justify-end gap-1"
                                >
                                    <ArrowUpRight
                                        v-if="row.amount >= 0"
                                        class="size-3.5"
                                    />
                                    <ArrowDownRight v-else class="size-3.5" />
                                    {{ row.amount >= 0 ? '+' : ''
                                    }}{{ row.amount }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="ledger.links.length > 3"
                class="mt-4 flex flex-wrap gap-1"
            >
                <button
                    v-for="link in ledger.links"
                    :key="link.label"
                    :disabled="!link.url"
                    class="rounded-md border px-3 py-1 text-sm disabled:opacity-40"
                    :class="
                        link.active ? 'bg-primary text-primary-foreground' : ''
                    "
                    @click="link.url && router.get(link.url)"
                >
                    {{ paginationLabel(link.label) }}
                </button>
            </div>
        </section>
    </div>
</template>

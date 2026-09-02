<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Printer } from '@lucide/vue';
import { history as creditsHistory } from '@/routes/credits';

type Invoice = {
    id: number;
    number: string;
    credits: number;
    amount: number;
    currency: string;
    status: string;
    reference: string;
    createdAt: string | null;
    paidAt: string | null;
};

defineProps<{
    invoice: Invoice;
    buyer: { name: string; email: string };
}>();

// Standalone page — no app sidebar, so it prints cleanly.
defineOptions({ layout: null });

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

const print = () => window.print();
</script>

<template>
    <Head :title="`Invoice ${invoice.number}`" />

    <div class="min-h-screen bg-neutral-100 py-8 text-neutral-900">
        <!-- Toolbar (hidden when printing) -->
        <div
            class="mx-auto mb-6 flex max-w-2xl items-center justify-between px-4 print:hidden"
        >
            <Link
                :href="creditsHistory().url"
                class="inline-flex items-center gap-1.5 text-sm text-neutral-600 hover:text-neutral-900"
            >
                <ArrowLeft class="size-4" />
                Back to history
            </Link>
            <button
                type="button"
                class="inline-flex items-center gap-1.5 rounded-lg bg-neutral-900 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800"
                @click="print"
            >
                <Printer class="size-4" />
                Print / Save PDF
            </button>
        </div>

        <!-- Invoice sheet -->
        <div
            class="mx-auto max-w-2xl bg-white p-8 shadow-sm md:p-12 print:max-w-none print:shadow-none"
        >
            <!-- Header -->
            <div
                class="flex items-start justify-between border-b border-neutral-200 pb-6"
            >
                <div>
                    <div
                        class="flex size-9 items-center justify-center rounded-lg bg-neutral-900 font-bold text-white"
                    >
                        A
                    </div>
                    <p class="mt-3 text-lg font-bold">ApplyMail</p>
                    <p class="text-sm text-neutral-500">caaampaign.my.id</p>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold">INVOICE</p>
                    <p class="mt-1 text-sm text-neutral-500">
                        {{ invoice.number }}
                    </p>
                    <span
                        class="mt-2 inline-block rounded-full px-3 py-1 text-xs font-semibold"
                        :class="
                            invoice.status === 'paid'
                                ? 'bg-emerald-100 text-emerald-700'
                                : 'bg-amber-100 text-amber-700'
                        "
                    >
                        {{ statusLabel(invoice.status) }}
                    </span>
                </div>
            </div>

            <!-- Meta -->
            <div class="grid grid-cols-2 gap-6 py-6 text-sm">
                <div>
                    <p
                        class="mb-1 text-xs font-semibold tracking-wide text-neutral-400 uppercase"
                    >
                        Billed to
                    </p>
                    <p class="font-medium">{{ buyer.name }}</p>
                    <p class="text-neutral-500">{{ buyer.email }}</p>
                </div>
                <div class="text-right">
                    <p
                        class="mb-1 text-xs font-semibold tracking-wide text-neutral-400 uppercase"
                    >
                        Details
                    </p>
                    <p>
                        <span class="text-neutral-500">Issued:</span>
                        {{ invoice.createdAt }}
                    </p>
                    <p v-if="invoice.paidAt">
                        <span class="text-neutral-500">Paid:</span>
                        {{ invoice.paidAt }}
                    </p>
                    <p class="mt-1 text-xs break-all text-neutral-400">
                        Ref: {{ invoice.reference }}
                    </p>
                </div>
            </div>

            <!-- Line items -->
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="border-y border-neutral-200 text-left text-neutral-500"
                    >
                        <th class="py-2 font-medium">Description</th>
                        <th class="py-2 text-right font-medium">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b border-neutral-100">
                        <td class="py-3">
                            {{ invoice.credits }} ApplyMail credits
                        </td>
                        <td class="py-3 text-right">
                            {{ invoice.currency }} {{ money(invoice.amount) }}
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td class="py-3 text-right font-semibold">Total</td>
                        <td class="py-3 text-right text-lg font-bold">
                            {{ invoice.currency }} {{ money(invoice.amount) }}
                        </td>
                    </tr>
                </tfoot>
            </table>

            <!-- Footer -->
            <div
                class="mt-8 border-t border-neutral-200 pt-6 text-center text-xs text-neutral-400"
            >
                <p>Thank you for using ApplyMail.</p>
                <p class="mt-1">
                    Payment processed securely by Xendit. This invoice was
                    generated automatically.
                </p>
            </div>
        </div>
    </div>
</template>

<style>
@media print {
    body {
        background: #fff;
    }
}
</style>

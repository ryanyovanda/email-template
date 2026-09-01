<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Check, Coins, CreditCard, Loader2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { checkout } from '@/routes/credits';

type Pkg = {
    id: number;
    name: string;
    credits: number;
    price: number;
};

const props = defineProps<{
    balance: number;
    packages: Pkg[];
    custom: {
        pricePerCredit: number;
        minAmount: number;
        maxAmount: number;
    };
    currency: string;
    configured: boolean;
    recent: {
        id: number;
        credits: number;
        amount: number;
        status: string;
        at: string | null;
    }[];
}>();

const money = (n: number): string => new Intl.NumberFormat('id-ID').format(n);

const selectedPackage = ref<number | null>(
    props.packages.length > 0 ? props.packages[0].id : null,
);

const customCredits = ref<number | null>(null);
const useCustom = ref(false);
const processing = ref(false);

const customPrice = computed(() =>
    customCredits.value && customCredits.value > 0
        ? customCredits.value * props.custom.pricePerCredit
        : 0,
);

const customValid = computed(
    () =>
        customPrice.value >= props.custom.minAmount &&
        customPrice.value <= props.custom.maxAmount,
);

const statusLabel = (s: string): string =>
    ({
        pending: 'Pending',
        paid: 'Paid',
        failed: 'Failed',
        expired: 'Expired',
    })[s] ?? s;

const statusClass = (s: string): string =>
    ({
        paid: 'text-emerald-600',
        pending: 'text-amber-600',
        failed: 'text-red-500',
        expired: 'text-muted-foreground',
    })[s] ?? 'text-muted-foreground';

const buyPackage = (id: number) => {
    if (!props.configured || processing.value) {
        return;
    }

    processing.value = true;
    selectedPackage.value = id;

    router.post(
        checkout().url,
        { package_id: id },
        {
            onFinish: () => {
                processing.value = false;
            },
        },
    );
};

const buyCustom = () => {
    if (!props.configured || processing.value || !customValid.value) {
        return;
    }

    processing.value = true;

    router.post(
        checkout().url,
        { credits: customCredits.value },
        {
            onFinish: () => {
                processing.value = false;
            },
        },
    );
};
</script>

<template>
    <Head title="Buy credits" />

    <div class="mx-auto max-w-5xl space-y-8 p-4 md:p-6">
        <Heading
            title="Buy credits"
            description="Top up your balance to draft and design more application emails with AI."
        />

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

        <div
            v-if="!configured"
            class="rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-700 dark:text-amber-300"
        >
            Checkout is temporarily unavailable. Please try again later.
        </div>

        <!-- Packages -->
        <section v-if="packages.length > 0">
            <h2 class="mb-4 text-lg font-semibold">Choose a package</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="pkg in packages"
                    :key="pkg.id"
                    class="flex flex-col rounded-xl border bg-card p-5 transition hover:border-primary/50 hover:shadow-sm"
                >
                    <p class="text-sm font-medium text-muted-foreground">
                        {{ pkg.name }}
                    </p>
                    <p class="mt-2 text-3xl font-bold">
                        {{ pkg.credits }}
                        <span
                            class="text-base font-normal text-muted-foreground"
                        >
                            credits
                        </span>
                    </p>
                    <p class="mt-1 text-lg font-semibold">
                        {{ currency }} {{ money(pkg.price) }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ currency }}
                        {{ money(Math.round(pkg.price / pkg.credits)) }} /
                        credit
                    </p>
                    <Button
                        class="mt-4 w-full"
                        :disabled="!configured || processing"
                        @click="buyPackage(pkg.id)"
                    >
                        <Loader2
                            v-if="processing && selectedPackage === pkg.id"
                            class="mr-1.5 size-4 animate-spin"
                        />
                        <CreditCard v-else class="mr-1.5 size-4" />
                        Buy now
                    </Button>
                </div>
            </div>
        </section>

        <!-- Custom amount -->
        <section class="rounded-xl border bg-card p-5 md:p-6">
            <button
                type="button"
                class="flex w-full items-center justify-between text-left"
                @click="useCustom = !useCustom"
            >
                <div>
                    <h2 class="text-lg font-semibold">Custom amount</h2>
                    <p class="text-sm text-muted-foreground">
                        Prefer a specific number of credits? Enter it here.
                    </p>
                </div>
                <span class="rounded-full border px-3 py-1 text-xs font-medium">
                    {{ useCustom ? 'Hide' : 'Choose' }}
                </span>
            </button>

            <div v-if="useCustom" class="mt-5 space-y-4">
                <div class="max-w-xs space-y-1.5">
                    <Label for="custom-credits">Number of credits</Label>
                    <Input
                        id="custom-credits"
                        v-model.number="customCredits"
                        type="number"
                        min="1"
                        placeholder="e.g. 250"
                    />
                    <p class="text-xs text-muted-foreground">
                        {{ currency }} {{ money(custom.pricePerCredit) }} per
                        credit.
                    </p>
                </div>

                <div
                    v-if="customCredits && customCredits > 0"
                    class="flex items-center gap-2 text-sm"
                >
                    <Check v-if="customValid" class="size-4 text-emerald-600" />
                    <span :class="customValid ? '' : 'text-red-500'">
                        Total: {{ currency }} {{ money(customPrice) }}
                        <template v-if="!customValid">
                            — must be between {{ currency }}
                            {{ money(custom.minAmount) }} and {{ currency }}
                            {{ money(custom.maxAmount) }}
                        </template>
                    </span>
                </div>

                <Button
                    :disabled="!configured || processing || !customValid"
                    @click="buyCustom"
                >
                    <Loader2
                        v-if="processing"
                        class="mr-1.5 size-4 animate-spin"
                    />
                    <CreditCard v-else class="mr-1.5 size-4" />
                    Continue to payment
                </Button>
            </div>
        </section>

        <!-- Recent purchases -->
        <section v-if="recent.length > 0">
            <h2 class="mb-3 text-lg font-semibold">Recent purchases</h2>
            <div class="overflow-x-auto rounded-xl border bg-card">
                <table class="w-full min-w-[480px] text-sm">
                    <thead>
                        <tr class="border-b text-left text-muted-foreground">
                            <th class="px-4 py-2 font-medium">When</th>
                            <th class="px-4 py-2 font-medium">Credits</th>
                            <th class="px-4 py-2 font-medium">Amount</th>
                            <th class="px-4 py-2 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in recent"
                            :key="row.id"
                            class="border-b last:border-0"
                        >
                            <td class="px-4 py-2 text-muted-foreground">
                                {{ row.at }}
                            </td>
                            <td class="px-4 py-2">{{ row.credits }}</td>
                            <td class="px-4 py-2">
                                {{ currency }} {{ money(row.amount) }}
                            </td>
                            <td
                                class="px-4 py-2 font-medium"
                                :class="statusClass(row.status)"
                            >
                                {{ statusLabel(row.status) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <p class="text-center text-xs text-muted-foreground">
            Payments are processed securely by Xendit (QRIS, virtual account,
            e-wallet, or card). Credits are added automatically once your
            payment is confirmed.
        </p>
    </div>
</template>

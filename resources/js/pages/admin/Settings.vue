<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Coins, Package, Plus, Save, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard as adminDashboard } from '@/routes/admin';
import {
    destroyPackage,
    index as adminSettingsIndex,
    storePackage,
    updatePackage,
    updatePricing,
} from '@/routes/admin/settings';

type Pkg = {
    id: number;
    name: string;
    credits: number;
    price: number;
    is_active: boolean;
    sort_order: number;
};

const props = defineProps<{
    pricing: {
        applicationDraft: number;
        templateDesign: number;
        monthlyGrant: number;
        promotionReward: number;
    };
    pricePerCredit: number;
    currency: string;
    paymentsConfigured: boolean;
    packages: Pkg[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: adminDashboard() },
            { title: 'Settings', href: adminSettingsIndex() },
        ],
    },
});

const money = (n: number): string => new Intl.NumberFormat('id-ID').format(n);

// --- Pricing form ---
const pricingForm = useForm({
    applicationDraft: props.pricing.applicationDraft,
    templateDesign: props.pricing.templateDesign,
    monthlyGrant: props.pricing.monthlyGrant,
    promotionReward: props.pricing.promotionReward,
});

const savePricing = () => {
    pricingForm.put(updatePricing().url, { preserveScroll: true });
};

// --- New package form ---
const newPackage = useForm({
    name: '',
    credits: 100,
    price: 15000,
    is_active: true,
    sort_order: 0,
});

const addPackage = () => {
    newPackage.post(storePackage().url, {
        preserveScroll: true,
        onSuccess: () => newPackage.reset(),
    });
};

// --- Inline edit of an existing package ---
const editing = ref<Record<number, Pkg>>({});

const startEdit = (pkg: Pkg) => {
    editing.value[pkg.id] = { ...pkg };
};

const cancelEdit = (id: number) => {
    delete editing.value[id];
};

const saveEdit = (id: number) => {
    const row = editing.value[id];

    router.put(
        updatePackage({ package: id }).url,
        { ...row },
        {
            preserveScroll: true,
            onSuccess: () => cancelEdit(id),
        },
    );
};

const removePackage = (id: number) => {
    if (
        !confirm('Delete this package? Existing purchases keep their credits.')
    ) {
        return;
    }

    router.delete(destroyPackage({ package: id }).url, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Settings" />

    <div class="space-y-8 p-4 md:p-6">
        <Heading
            title="Settings"
            description="Credit pricing and the packages users can buy. Changes take effect immediately — no redeploy."
        />

        <div
            v-if="!paymentsConfigured"
            class="rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-700 dark:text-amber-300"
        >
            Payments are not configured yet. Set
            <code class="font-mono">XENDIT_SECRET_KEY</code> and
            <code class="font-mono">XENDIT_CALLBACK_TOKEN</code> in the server
            environment to enable checkout. Packages can still be edited here.
        </div>

        <!-- Pricing -->
        <section class="rounded-xl border bg-card p-5 md:p-6">
            <div class="mb-4 flex items-center gap-2">
                <Coins class="size-5 text-muted-foreground" />
                <h2 class="text-lg font-semibold">Credit pricing</h2>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="space-y-1.5">
                    <Label for="draft">AI draft cost</Label>
                    <Input
                        id="draft"
                        v-model.number="pricingForm.applicationDraft"
                        type="number"
                        min="0"
                    />
                    <p class="text-xs text-muted-foreground">
                        Credits per AI application draft.
                    </p>
                </div>
                <div class="space-y-1.5">
                    <Label for="design">AI template design cost</Label>
                    <Input
                        id="design"
                        v-model.number="pricingForm.templateDesign"
                        type="number"
                        min="0"
                    />
                    <p class="text-xs text-muted-foreground">
                        Credits per AI-designed template.
                    </p>
                </div>
                <div class="space-y-1.5">
                    <Label for="grant">Monthly free grant</Label>
                    <Input
                        id="grant"
                        v-model.number="pricingForm.monthlyGrant"
                        type="number"
                        min="0"
                    />
                    <p class="text-xs text-muted-foreground">
                        Free credits topped up each month.
                    </p>
                </div>
                <div class="space-y-1.5">
                    <Label for="reward">Publish reward</Label>
                    <Input
                        id="reward"
                        v-model.number="pricingForm.promotionReward"
                        type="number"
                        min="0"
                    />
                    <p class="text-xs text-muted-foreground">
                        Credits when a template is published.
                    </p>
                </div>
            </div>

            <div class="mt-5 flex justify-end">
                <Button :disabled="pricingForm.processing" @click="savePricing">
                    <Save class="mr-1.5 size-4" />
                    Save pricing
                </Button>
            </div>
        </section>

        <!-- Packages -->
        <section class="rounded-xl border bg-card p-5 md:p-6">
            <div class="mb-4 flex items-center gap-2">
                <Package class="size-5 text-muted-foreground" />
                <h2 class="text-lg font-semibold">Credit packages</h2>
                <span class="text-sm text-muted-foreground">
                    ({{ currency }}. Custom top-ups use
                    {{ money(pricePerCredit) }}/credit.)
                </span>
            </div>

            <!-- Existing packages -->
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <thead>
                        <tr class="border-b text-left text-muted-foreground">
                            <th class="py-2 pr-3 font-medium">Name</th>
                            <th class="py-2 pr-3 font-medium">Credits</th>
                            <th class="py-2 pr-3 font-medium">
                                Price ({{ currency }})
                            </th>
                            <th class="py-2 pr-3 font-medium">Order</th>
                            <th class="py-2 pr-3 font-medium">Active</th>
                            <th class="py-2 pr-3 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="packages.length === 0">
                            <td
                                colspan="6"
                                class="py-6 text-center text-muted-foreground"
                            >
                                No packages yet. Add one below.
                            </td>
                        </tr>
                        <tr
                            v-for="pkg in packages"
                            :key="pkg.id"
                            class="border-b last:border-0"
                        >
                            <template v-if="editing[pkg.id]">
                                <td class="py-2 pr-3">
                                    <Input
                                        v-model="editing[pkg.id].name"
                                        class="h-8"
                                    />
                                </td>
                                <td class="py-2 pr-3">
                                    <Input
                                        v-model.number="editing[pkg.id].credits"
                                        type="number"
                                        class="h-8 w-24"
                                    />
                                </td>
                                <td class="py-2 pr-3">
                                    <Input
                                        v-model.number="editing[pkg.id].price"
                                        type="number"
                                        class="h-8 w-28"
                                    />
                                </td>
                                <td class="py-2 pr-3">
                                    <Input
                                        v-model.number="
                                            editing[pkg.id].sort_order
                                        "
                                        type="number"
                                        class="h-8 w-20"
                                    />
                                </td>
                                <td class="py-2 pr-3">
                                    <input
                                        v-model="editing[pkg.id].is_active"
                                        type="checkbox"
                                        class="size-4"
                                    />
                                </td>
                                <td class="py-2 pr-3">
                                    <div class="flex justify-end gap-2">
                                        <Button
                                            size="sm"
                                            @click="saveEdit(pkg.id)"
                                        >
                                            Save
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            @click="cancelEdit(pkg.id)"
                                        >
                                            Cancel
                                        </Button>
                                    </div>
                                </td>
                            </template>
                            <template v-else>
                                <td class="py-2 pr-3 font-medium">
                                    {{ pkg.name }}
                                </td>
                                <td class="py-2 pr-3">{{ pkg.credits }}</td>
                                <td class="py-2 pr-3">
                                    {{ money(pkg.price) }}
                                </td>
                                <td class="py-2 pr-3">{{ pkg.sort_order }}</td>
                                <td class="py-2 pr-3">
                                    <span
                                        :class="
                                            pkg.is_active
                                                ? 'text-emerald-600'
                                                : 'text-muted-foreground'
                                        "
                                    >
                                        {{ pkg.is_active ? 'Yes' : 'No' }}
                                    </span>
                                </td>
                                <td class="py-2 pr-3">
                                    <div class="flex justify-end gap-2">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            @click="startEdit(pkg)"
                                        >
                                            Edit
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            @click="removePackage(pkg.id)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </td>
                            </template>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Add package -->
            <div class="mt-6 rounded-lg border border-dashed p-4">
                <h3 class="mb-3 text-sm font-semibold">Add a package</h3>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="space-y-1.5">
                        <Label for="np-name">Name</Label>
                        <Input
                            id="np-name"
                            v-model="newPackage.name"
                            placeholder="Starter"
                        />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="np-credits">Credits</Label>
                        <Input
                            id="np-credits"
                            v-model.number="newPackage.credits"
                            type="number"
                            min="1"
                        />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="np-price">Price ({{ currency }})</Label>
                        <Input
                            id="np-price"
                            v-model.number="newPackage.price"
                            type="number"
                            min="1"
                        />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="np-order">Sort order</Label>
                        <Input
                            id="np-order"
                            v-model.number="newPackage.sort_order"
                            type="number"
                            min="0"
                        />
                    </div>
                    <div class="flex items-end">
                        <Button
                            class="w-full"
                            :disabled="newPackage.processing"
                            @click="addPackage"
                        >
                            <Plus class="mr-1.5 size-4" />
                            Add
                        </Button>
                    </div>
                </div>
                <p
                    v-if="newPackage.errors.name || newPackage.errors.credits"
                    class="mt-2 text-xs text-red-500"
                >
                    {{ newPackage.errors.name || newPackage.errors.credits }}
                </p>
            </div>
        </section>
    </div>
</template>

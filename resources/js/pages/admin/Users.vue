<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Ban, Coins, Search, ShieldCheck } from '@lucide/vue';
import { ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { paginationLabel } from '@/lib/pagination';
import { cn } from '@/lib/utils';
import { dashboard as adminDashboard } from '@/routes/admin';
import {
    ban as banUser,
    credits as adjustCredits,
    index as adminUsersIndex,
    unban as unbanUser,
} from '@/routes/admin/users';

type UserRow = {
    id: number;
    name: string;
    email: string;
    role: string;
    banned_at: string | null;
    ban_reason: string | null;
    credits: number;
    applications_count: number;
    ai_today: number;
    full_name: string | null;
    has_cv: boolean;
    created_at: string | null;
};

const props = defineProps<{
    users: {
        data: UserRow[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { search: string; filter: string };
    defaults: {
        monthlyGrant: number;
        draftPrice: number;
        templatePrice: number;
        abuseThreshold: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: adminDashboard() },
            { title: 'Users', href: adminUsersIndex() },
        ],
    },
});

const search = ref(props.filters.search);
const banTarget = ref<UserRow | null>(null);
const banReason = ref('');
const creditTarget = ref<UserRow | null>(null);
const creditAmount = ref<string>('');
const creditReason = ref('');

const tabs = [
    { key: '', label: 'All' },
    { key: 'heavy', label: 'Heavy AI use' },
    { key: 'banned', label: 'Suspended' },
    { key: 'admins', label: 'Admins' },
];

let searchTimer: number | undefined;

watch(search, (value) => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => {
        router.get(
            adminUsersIndex().url,
            { search: value, filter: props.filters.filter },
            { preserveState: true, replace: true, preserveScroll: true },
        );
    }, 350);
});

function openBan(user: UserRow): void {
    banTarget.value = user;
    banReason.value = '';
}

function confirmBan(): void {
    if (!banTarget.value) {
        return;
    }

    router.post(
        banUser(banTarget.value.id).url,
        { reason: banReason.value },
        {
            preserveScroll: true,
            onSuccess: () => (banTarget.value = null),
        },
    );
}

function unban(user: UserRow): void {
    router.delete(unbanUser(user.id).url, { preserveScroll: true });
}

function openCredits(user: UserRow): void {
    creditTarget.value = user;
    creditAmount.value = '';
    creditReason.value = '';
}

function saveCredits(): void {
    if (!creditTarget.value || creditAmount.value === '') {
        return;
    }

    router.post(
        adjustCredits(creditTarget.value.id).url,
        { amount: Number(creditAmount.value), reason: creditReason.value },
        {
            preserveScroll: true,
            onSuccess: () => (creditTarget.value = null),
        },
    );
}
</script>

<template>
    <Head title="Users" />

    <div class="space-y-6 p-4">
        <Heading
            title="Users"
            :description="`Everyone gets ${defaults.monthlyGrant} credits a month. A draft costs ${defaults.draftPrice}, a template design ${defaults.templatePrice}.`"
        />

        <div class="flex flex-wrap items-center gap-3">
            <div class="relative flex-1 sm:max-w-xs">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    class="pl-9"
                    placeholder="Search name or email"
                />
            </div>

            <div class="flex flex-wrap gap-1 rounded-lg bg-muted p-1">
                <Button
                    v-for="tab in tabs"
                    :key="tab.key"
                    variant="ghost"
                    size="sm"
                    :class="
                        cn(
                            'h-7 px-3 text-xs',
                            filters.filter === tab.key &&
                                'bg-background shadow-sm',
                        )
                    "
                    as-child
                >
                    <Link
                        :href="
                            adminUsersIndex({
                                query: {
                                    filter: tab.key,
                                    search: filters.search,
                                },
                            })
                        "
                        >{{ tab.label }}</Link
                    >
                </Button>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-xs text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">User</th>
                        <th class="px-4 py-3 text-left font-medium">Joined</th>
                        <th class="px-4 py-3 text-right font-medium">Apps</th>
                        <th class="px-4 py-3 text-right font-medium">
                            AI today
                        </th>
                        <th class="px-4 py-3 text-right font-medium">
                            Credits
                        </th>
                        <th class="px-4 py-3 text-right font-medium">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="user in users.data"
                        :key="user.id"
                        class="hover:bg-accent/30"
                    >
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2 font-medium">
                                {{ user.full_name ?? user.name }}
                                <span
                                    v-if="user.role === 'admin'"
                                    class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-normal"
                                >
                                    <ShieldCheck class="size-3" /> admin
                                </span>
                                <span
                                    v-if="user.banned_at"
                                    class="rounded-full border border-destructive/40 px-2 py-0.5 text-[10px] font-normal text-destructive"
                                >
                                    suspended
                                </span>
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{ user.email }}
                                <span v-if="!user.has_cv"> · no CV</span>
                            </div>
                            <div
                                v-if="user.ban_reason"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ user.ban_reason }}
                            </div>
                        </td>
                        <td
                            class="px-4 py-3 text-xs whitespace-nowrap text-muted-foreground"
                        >
                            {{ user.created_at }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            {{ user.applications_count }}
                        </td>
                        <td
                            class="px-4 py-3 text-right"
                            :class="
                                user.ai_today >= defaults.abuseThreshold
                                    ? 'font-semibold text-destructive'
                                    : ''
                            "
                        >
                            {{ user.ai_today }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span
                                :class="
                                    user.credits < defaults.draftPrice
                                        ? 'font-semibold text-destructive'
                                        : ''
                                "
                                >{{ user.credits.toLocaleString() }}</span
                            >
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1.5">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="openCredits(user)"
                                >
                                    <Coins class="size-3.5" />
                                    Credits
                                </Button>
                                <Button
                                    v-if="user.banned_at"
                                    variant="outline"
                                    size="sm"
                                    @click="unban(user)"
                                >
                                    Reinstate
                                </Button>
                                <Button
                                    v-else-if="user.role !== 'admin'"
                                    variant="outline"
                                    size="sm"
                                    class="text-destructive"
                                    @click="openBan(user)"
                                >
                                    <Ban class="size-3.5" /> Suspend
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="users.data.length === 0">
                        <td
                            colspan="6"
                            class="px-4 py-10 text-center text-sm text-muted-foreground"
                        >
                            No users match that filter.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="users.links.length > 3" class="flex flex-wrap gap-1.5">
            <Button
                v-for="link in users.links"
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

    <!-- Suspend dialog -->
    <div
        v-if="banTarget"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="banTarget = null"
    >
        <div
            class="w-full max-w-md rounded-xl border bg-background p-5 shadow-lg"
        >
            <h2 class="text-base font-semibold">
                Suspend {{ banTarget.name }}?
            </h2>
            <p class="mt-1 text-sm text-muted-foreground">
                They are signed out immediately and cannot log back in. Their
                drafts are kept.
            </p>

            <div class="mt-4 grid gap-2">
                <Label for="reason">Reason (shown to them at login)</Label>
                <Textarea
                    id="reason"
                    v-model="banReason"
                    rows="3"
                    placeholder="Automated abuse of the AI endpoint."
                />
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <Button variant="outline" @click="banTarget = null">
                    Cancel
                </Button>
                <Button
                    variant="destructive"
                    :disabled="banReason.trim().length === 0"
                    @click="confirmBan"
                >
                    Suspend account
                </Button>
            </div>
        </div>
    </div>

    <!-- AI limit dialog -->
    <!-- Credit adjustment dialog -->
    <div
        v-if="creditTarget"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="creditTarget = null"
    >
        <div
            class="w-full max-w-md rounded-xl border bg-background p-5 shadow-lg"
        >
            <h2 class="text-base font-semibold">
                Adjust credits for {{ creditTarget.name }}
            </h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Balance is {{ creditTarget.credits.toLocaleString() }}. Enter a
                positive number to add credits or a negative one to take them
                away. Recorded against your account.
            </p>

            <div class="mt-4 grid gap-2">
                <Label for="amount">Amount</Label>
                <Input
                    id="amount"
                    v-model="creditAmount"
                    type="number"
                    placeholder="e.g. 300 or -50"
                />
            </div>

            <div class="mt-3 grid gap-2">
                <Label for="credit-reason">Note (optional)</Label>
                <Input
                    id="credit-reason"
                    v-model="creditReason"
                    placeholder="Goodwill after a failed generation"
                />
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <Button variant="outline" @click="creditTarget = null">
                    Cancel
                </Button>
                <Button
                    :disabled="
                        creditAmount === '' || Number(creditAmount) === 0
                    "
                    @click="saveCredits"
                >
                    Apply
                </Button>
            </div>
        </div>
    </div>
</template>

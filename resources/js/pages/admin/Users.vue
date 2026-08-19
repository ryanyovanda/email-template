<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Ban, Search, ShieldCheck, SlidersHorizontal } from '@lucide/vue';
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
    index as adminUsersIndex,
    limit as updateLimit,
    unban as unbanUser,
} from '@/routes/admin/users';

type UserRow = {
    id: number;
    name: string;
    email: string;
    role: string;
    banned_at: string | null;
    ban_reason: string | null;
    ai_monthly_limit: number | null;
    effective_monthly_limit: number;
    applications_count: number;
    ai_today: number;
    ai_month: number;
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
        monthlyLimit: number;
        dailyLimit: number;
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
const limitTarget = ref<UserRow | null>(null);
const limitValue = ref<string>('');

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

function openLimit(user: UserRow): void {
    limitTarget.value = user;
    limitValue.value = user.ai_monthly_limit?.toString() ?? '';
}

function saveLimit(): void {
    if (!limitTarget.value) {
        return;
    }

    router.patch(
        updateLimit(limitTarget.value.id).url,
        {
            ai_monthly_limit:
                limitValue.value === '' ? null : Number(limitValue.value),
        },
        {
            preserveScroll: true,
            onSuccess: () => (limitTarget.value = null),
        },
    );
}
</script>

<template>
    <Head title="Users" />

    <div class="space-y-6 p-4">
        <Heading
            title="Users"
            :description="`Default allowance is ${defaults.dailyLimit} AI generations a day and ${defaults.monthlyLimit} a month.`"
        />

        <div class="flex flex-wrap items-center gap-3">
            <div class="relative flex-1 sm:max-w-xs">
                <Search
                    class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2"
                />
                <Input
                    v-model="search"
                    class="pl-9"
                    placeholder="Search name or email"
                />
            </div>

            <div class="bg-muted flex flex-wrap gap-1 rounded-lg p-1">
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
                                query: { filter: tab.key, search: filters.search },
                            })
                        "
                        >{{ tab.label }}</Link
                    >
                </Button>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-muted-foreground text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">User</th>
                        <th class="px-4 py-3 text-left font-medium">Joined</th>
                        <th class="px-4 py-3 text-right font-medium">Apps</th>
                        <th class="px-4 py-3 text-right font-medium">
                            AI today
                        </th>
                        <th class="px-4 py-3 text-right font-medium">
                            AI this month
                        </th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
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
                                    class="border-destructive/40 text-destructive rounded-full border px-2 py-0.5 text-[10px] font-normal"
                                >
                                    suspended
                                </span>
                            </div>
                            <div class="text-muted-foreground text-xs">
                                {{ user.email }}
                                <span v-if="!user.has_cv"> · no CV</span>
                            </div>
                            <div
                                v-if="user.ban_reason"
                                class="text-destructive mt-1 text-xs"
                            >
                                {{ user.ban_reason }}
                            </div>
                        </td>
                        <td
                            class="text-muted-foreground px-4 py-3 text-xs whitespace-nowrap"
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
                                    ? 'text-destructive font-semibold'
                                    : ''
                            "
                        >
                            {{ user.ai_today }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            {{ user.ai_month }}
                            <span class="text-muted-foreground text-xs">
                                / {{ user.effective_monthly_limit }}
                                <span v-if="user.ai_monthly_limit !== null"
                                    >*</span
                                >
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1.5">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="openLimit(user)"
                                >
                                    <SlidersHorizontal class="size-3.5" />
                                    Limit
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
                            class="text-muted-foreground px-4 py-10 text-center text-sm"
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
        <div class="bg-background w-full max-w-md rounded-xl border p-5 shadow-lg">
            <h2 class="text-base font-semibold">
                Suspend {{ banTarget.name }}?
            </h2>
            <p class="text-muted-foreground mt-1 text-sm">
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
    <div
        v-if="limitTarget"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @click.self="limitTarget = null"
    >
        <div class="bg-background w-full max-w-md rounded-xl border p-5 shadow-lg">
            <h2 class="text-base font-semibold">
                AI limit for {{ limitTarget.name }}
            </h2>
            <p class="text-muted-foreground mt-1 text-sm">
                Monthly generation cap. Leave blank to use the app default of
                {{ defaults.monthlyLimit }}. Set 0 to block AI entirely while
                keeping the account usable.
            </p>

            <div class="mt-4 grid gap-2">
                <Label for="limit">Monthly limit</Label>
                <Input
                    id="limit"
                    v-model="limitValue"
                    type="number"
                    min="0"
                    :placeholder="`${defaults.monthlyLimit} (default)`"
                />
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <Button variant="outline" @click="limitTarget = null">
                    Cancel
                </Button>
                <Button @click="saveLimit">Save limit</Button>
            </div>
        </div>
    </div>
</template>

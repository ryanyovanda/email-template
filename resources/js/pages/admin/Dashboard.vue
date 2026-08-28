<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Activity, AlertTriangle, LayoutTemplate, Users } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as aiUsageIndex } from '@/routes/admin/ai-usage';
import { index as adminTemplatesIndex } from '@/routes/admin/templates';
import { index as adminUsersIndex } from '@/routes/admin/users';

defineProps<{
    stats: {
        users: number;
        bannedUsers: number;
        newUsersThisWeek: number;
        applications: number;
        templates: number;
        activeTemplates: number;
        generationsToday: number;
        generationsThisMonth: number;
        tokensThisMonth: number;
        failuresToday: number;
    };
    abuseThreshold: number;
    heavyUsers: {
        user: {
            id: number;
            name: string;
            email: string;
            banned_at: string | null;
        } | null;
        generations: number;
        tokens: number;
    }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Admin', href: adminDashboard() }],
    },
});
</script>

<template>
    <Head title="Admin" />

    <div class="space-y-6 p-4">
        <Heading
            title="Admin"
            description="Who is signing up, what they are generating, and which templates they can choose from."
        />

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border p-4">
                <div class="text-xs text-muted-foreground">
                    Registered users
                </div>
                <div class="mt-1 text-2xl font-semibold">{{ stats.users }}</div>
                <div class="mt-1 text-xs text-muted-foreground">
                    +{{ stats.newUsersThisWeek }} this week ·
                    {{ stats.bannedUsers }} suspended
                </div>
            </div>
            <div class="rounded-xl border p-4">
                <div class="text-xs text-muted-foreground">Applications</div>
                <div class="mt-1 text-2xl font-semibold">
                    {{ stats.applications }}
                </div>
            </div>
            <div class="rounded-xl border p-4">
                <div class="text-xs text-muted-foreground">AI runs today</div>
                <div class="mt-1 text-2xl font-semibold">
                    {{ stats.generationsToday }}
                </div>
                <div
                    class="mt-1 text-xs"
                    :class="
                        stats.failuresToday > 0
                            ? 'text-destructive'
                            : 'text-muted-foreground'
                    "
                >
                    {{ stats.failuresToday }} failed
                </div>
            </div>
            <div class="rounded-xl border p-4">
                <div class="text-xs text-muted-foreground">
                    Tokens this month
                </div>
                <div class="mt-1 text-2xl font-semibold">
                    {{ stats.tokensThisMonth.toLocaleString() }}
                </div>
                <div class="mt-1 text-xs text-muted-foreground">
                    over {{ stats.generationsThisMonth }} runs
                </div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <Button variant="outline" class="h-auto justify-start p-4" as-child>
                <Link :href="adminUsersIndex()">
                    <Users class="size-4" />
                    <span class="text-left">
                        <span class="block text-sm font-medium">Users</span>
                        <span class="block text-xs text-muted-foreground"
                            >Review, limit and suspend accounts</span
                        >
                    </span>
                </Link>
            </Button>
            <Button variant="outline" class="h-auto justify-start p-4" as-child>
                <Link :href="adminTemplatesIndex()">
                    <LayoutTemplate class="size-4" />
                    <span class="text-left">
                        <span class="block text-sm font-medium">Templates</span>
                        <span class="block text-xs text-muted-foreground">
                            {{ stats.activeTemplates }} of
                            {{ stats.templates }} active
                        </span>
                    </span>
                </Link>
            </Button>
            <Button variant="outline" class="h-auto justify-start p-4" as-child>
                <Link :href="aiUsageIndex()">
                    <Activity class="size-4" />
                    <span class="text-left">
                        <span class="block text-sm font-medium"
                            >AI usage log</span
                        >
                        <span class="block text-xs text-muted-foreground"
                            >Every call, with tokens and errors</span
                        >
                    </span>
                </Link>
            </Button>
        </div>

        <!-- Abuse watch -->
        <section class="rounded-xl border p-5">
            <h2 class="flex items-center gap-2 text-sm font-semibold">
                <AlertTriangle class="size-4" /> Heavy usage today
            </h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Accounts past {{ abuseThreshold }} generations in a single day.
                Worth a look before they burn through the API budget.
            </p>

            <p
                v-if="heavyUsers.length === 0"
                class="mt-4 text-sm text-muted-foreground"
            >
                Nobody is over the threshold right now.
            </p>

            <ul v-else class="mt-4 divide-y">
                <li
                    v-for="row in heavyUsers"
                    :key="row.user?.id ?? 0"
                    class="flex items-center gap-4 py-3"
                >
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-medium">
                            {{ row.user?.name ?? 'Deleted user' }}
                            <span
                                v-if="row.user?.banned_at"
                                class="ml-1 text-xs text-destructive"
                                >suspended</span
                            >
                        </div>
                        <div class="truncate text-xs text-muted-foreground">
                            {{ row.user?.email }}
                        </div>
                    </div>
                    <div class="text-right text-xs">
                        <div class="font-medium">
                            {{ row.generations }} runs
                        </div>
                        <div class="text-muted-foreground">
                            {{ row.tokens.toLocaleString() }} tokens
                        </div>
                    </div>
                    <Button variant="outline" size="sm" as-child>
                        <Link
                            :href="
                                adminUsersIndex({
                                    query: { search: row.user?.email },
                                })
                            "
                            >Review</Link
                        >
                    </Button>
                </li>
            </ul>
        </section>
    </div>
</template>

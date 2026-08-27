<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Check,
    Puzzle,
    FileText,
    Mail,
    Sparkles,
} from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { edit as applicantProfileEdit } from '@/routes/applicant-profile';
import {
    edit as editApplication,
    index as applicationsIndex,
} from '@/routes/applications';
import { index as templatesIndex } from '@/routes/templates';

const props = defineProps<{
    profile: Record<string, string | null> | null;
    hasCvText: boolean;
    checklist: {
        profile: boolean;
        cv: boolean;
        cvText: boolean;
        firstApplication: boolean;
        extension: boolean;
    };
    stats: {
        applications: number;
        sent: number;
        credits: number;
        monthlyGrant: number;
        draftPrice: number;
        templatePrice: number;
        draftsAffordable: number;
    };
    creditHistory: {
        id: number;
        amount: number;
        label: string;
        description: string | null;
        at: string | null;
    }[];
    recent: {
        id: number;
        title: string;
        company: string | null;
        position: string | null;
        template: { name: string; accent_color: string } | null;
        updated_at: string | null;
    }[];
    templateCount: number;
    extensionUrl: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const steps = computed(() => [
    {
        key: 'extension',
        title: 'Install the Gmail extension',
        description:
            'Insert and Send HTML with Gmail — this is what pastes your finished email into a Gmail compose window.',
        done: props.checklist.extension,
        href: props.extensionUrl,
        external: true,
        cta: 'Open Puzzle Web Store',
    },
    {
        key: 'profile',
        title: 'Fill in your details and CV',
        description:
            'Name, contact, photo and CV. We read the text out of the CV so the AI can write from it.',
        done: props.checklist.profile && props.checklist.cvText,
        href: applicantProfileEdit().url,
        external: false,
        cta: props.checklist.profile ? 'Review profile' : 'Set up profile',
    },
    {
        key: 'template',
        title: 'Pick a template and write',
        description: `${props.templateCount} layouts to choose from. Fill it in yourself, or paste a job posting and let the AI draft it.`,
        done: props.checklist.firstApplication,
        href: templatesIndex().url,
        external: false,
        cta: 'Browse templates',
    },
]);

const nextStep = computed(() => steps.value.find((step) => !step.done));
</script>

<template>
    <Head title="Dashboard" />

    <div class="space-y-6 p-4">
        <!-- Stats -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border p-4">
                <div class="text-xs text-muted-foreground">Applications</div>
                <div class="mt-1 text-2xl font-semibold">
                    {{ stats.applications }}
                </div>
            </div>
            <div class="rounded-xl border p-4">
                <div class="text-xs text-muted-foreground">Sent out</div>
                <div class="mt-1 text-2xl font-semibold">{{ stats.sent }}</div>
            </div>
            <div class="rounded-xl border p-4">
                <div class="text-xs text-muted-foreground">Credits</div>
                <div class="mt-1 text-2xl font-semibold">
                    {{ stats.credits.toLocaleString() }}
                    <span class="text-sm font-normal text-muted-foreground"
                        >/ {{ stats.monthlyGrant }} a month</span
                    >
                </div>
            </div>
            <div class="rounded-xl border p-4">
                <div class="text-xs text-muted-foreground">That buys you</div>
                <div class="mt-1 text-2xl font-semibold">
                    {{ stats.draftsAffordable }}
                    <span class="text-sm font-normal text-muted-foreground"
                        >AI drafts</span
                    >
                </div>
                <div class="mt-1 text-xs text-muted-foreground">
                    {{ stats.draftPrice }} each &middot; a template design costs
                    {{ stats.templatePrice }}
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
            <!-- Getting started -->
            <section class="rounded-xl border p-5">
                <h2 class="text-sm font-semibold">Getting started</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{
                        nextStep
                            ? 'Three steps and you can send your first application.'
                            : 'You are all set up. Write your next application whenever you like.'
                    }}
                </p>

                <ol class="mt-5 space-y-4">
                    <li
                        v-for="(step, index) in steps"
                        :key="step.key"
                        class="flex gap-4"
                    >
                        <div
                            :class="
                                cn(
                                    'flex size-7 shrink-0 items-center justify-center rounded-full border text-xs font-semibold',
                                    step.done
                                        ? 'border-emerald-500 bg-emerald-500 text-white'
                                        : 'text-muted-foreground',
                                )
                            "
                        >
                            <Check v-if="step.done" class="size-4" />
                            <span v-else>{{ index + 1 }}</span>
                        </div>

                        <div class="min-w-0 flex-1">
                            <h3 class="text-sm font-medium">
                                {{ step.title }}
                            </h3>
                            <p
                                class="mt-0.5 text-sm leading-relaxed text-muted-foreground"
                            >
                                {{ step.description }}
                            </p>
                            <Button
                                class="mt-2"
                                :variant="
                                    nextStep?.key === step.key
                                        ? 'default'
                                        : 'outline'
                                "
                                size="sm"
                                as-child
                            >
                                <a
                                    v-if="step.external"
                                    :href="step.href"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    <Puzzle class="size-4" /> {{ step.cta }}
                                </a>
                                <Link v-else :href="step.href">
                                    {{ step.cta }}
                                    <ArrowRight class="size-4" />
                                </Link>
                            </Button>
                        </div>
                    </li>
                </ol>
            </section>

            <!-- Recent -->
            <section class="rounded-xl border p-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold">Recent drafts</h2>
                    <Link
                        :href="applicationsIndex()"
                        class="text-xs text-muted-foreground underline"
                        >View all</Link
                    >
                </div>

                <div
                    v-if="recent.length === 0"
                    class="mt-6 flex flex-col items-center gap-3 py-6 text-center text-sm text-muted-foreground"
                >
                    <Mail class="size-6" />
                    <p>Nothing here yet.</p>
                    <Button size="sm" as-child>
                        <Link :href="templatesIndex()">
                            <Sparkles class="size-4" /> Write your first one
                        </Link>
                    </Button>
                </div>

                <ul v-else class="mt-4 space-y-1">
                    <li v-for="item in recent" :key="item.id">
                        <Link
                            :href="editApplication(item.id)"
                            class="flex items-center gap-3 rounded-lg px-2 py-2.5 transition-colors hover:bg-accent/50"
                        >
                            <div
                                class="h-8 w-1 shrink-0 rounded-full"
                                :style="{
                                    backgroundColor:
                                        item.template?.accent_color ??
                                        '#d4d4d8',
                                }"
                            />
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium">
                                    {{ item.title }}
                                </div>
                                <div
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{
                                        [item.position, item.company]
                                            .filter(Boolean)
                                            .join(' · ') || 'No role set'
                                    }}
                                    · {{ item.updated_at }}
                                </div>
                            </div>
                            <FileText
                                class="size-4 shrink-0 text-muted-foreground"
                            />
                        </Link>
                    </li>
                </ul>
            </section>
        </div>

        <!-- Credit ledger: a balance nobody can account for reads as a bug -->
        <section v-if="creditHistory.length" class="rounded-xl border p-5">
            <h2 class="text-sm font-semibold">Recent credit activity</h2>
            <ul class="mt-3 divide-y">
                <li
                    v-for="entry in creditHistory"
                    :key="entry.id"
                    class="flex items-center gap-4 py-2.5"
                >
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm">{{ entry.label }}</div>
                        <div
                            v-if="entry.description"
                            class="truncate text-xs text-muted-foreground"
                        >
                            {{ entry.description }}
                        </div>
                    </div>
                    <div class="shrink-0 text-xs text-muted-foreground">
                        {{ entry.at }}
                    </div>
                    <div
                        class="w-16 shrink-0 text-right text-sm font-medium tabular-nums"
                        :class="
                            entry.amount >= 0
                                ? 'text-emerald-600 dark:text-emerald-400'
                                : 'text-muted-foreground'
                        "
                    >
                        {{ entry.amount > 0 ? '+' : '' }}{{ entry.amount }}
                    </div>
                </li>
            </ul>
        </section>
    </div>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Puzzle,
    FileText,
    LayoutTemplate,
    Mail,
    PencilLine,
    Send,
    Sparkles,
} from '@lucide/vue';
import { dashboard, login, register } from '@/routes';

const steps = [
    {
        icon: Puzzle,
        title: 'Install the Gmail extension',
        body: 'A free Puzzle extension that pastes HTML straight into a Gmail compose window.',
    },
    {
        icon: FileText,
        title: 'Add your details once',
        body: 'Name, contact, photo and CV. We read the text out of your CV so it never has to be retyped.',
    },
    {
        icon: LayoutTemplate,
        title: 'Pick a template',
        body: 'Designed layouts that survive Gmail — table-based, inline styles, no broken boxes.',
    },
    {
        icon: Sparkles,
        title: 'Write it, or let AI draft it',
        body: 'Fill the fields yourself, or paste the job posting and get a draft grounded in your real CV.',
    },
    {
        icon: Mail,
        title: 'Check the Gmail preview',
        body: 'See the exact message a recruiter opens, on desktop and mobile, before anything is sent.',
    },
    {
        icon: Send,
        title: 'Copy and send',
        body: 'Paste into Gmail and send from your own address, so replies come back to you.',
    },
];
</script>

<template>
    <Head title="Job application emails that look like you meant it" />

    <div
        class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
    >
        <header
            class="mx-auto flex max-w-5xl items-center justify-between px-6 py-6"
        >
            <div class="flex items-center gap-2 font-semibold">
                <Mail class="size-5" />
                <span>ApplyMail</span>
            </div>

            <nav class="flex items-center gap-2 text-sm">
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="rounded-md border border-[#19140035] px-4 py-1.5 hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="login()"
                        class="rounded-md px-4 py-1.5 hover:underline"
                        >Log in</Link
                    >
                    <Link
                        :href="register()"
                        class="rounded-md bg-[#1b1b18] px-4 py-1.5 text-white dark:bg-[#EDEDEC] dark:text-[#1b1b18]"
                        >Get started</Link
                    >
                </template>
            </nav>
        </header>

        <main class="mx-auto max-w-5xl px-6 pb-24">
            <!-- Hero -->
            <section class="py-16 lg:py-24">
                <h1
                    class="max-w-3xl text-4xl leading-tight font-semibold tracking-tight lg:text-5xl"
                >
                    Job application emails that look like you meant it.
                </h1>
                <p
                    class="mt-5 max-w-2xl text-lg leading-relaxed text-[#706f6c] dark:text-[#A1A09A]"
                >
                    Designed HTML templates for job applications, filled from
                    your CV, previewed exactly as Gmail will render them, and
                    sent from your own address.
                </p>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <Link
                        :href="$page.props.auth.user ? dashboard() : register()"
                        class="inline-flex items-center gap-2 rounded-md bg-[#1b1b18] px-6 py-3 text-sm font-medium text-white dark:bg-[#EDEDEC] dark:text-[#1b1b18]"
                    >
                        {{
                            $page.props.auth.user
                                ? 'Go to dashboard'
                                : 'Create a free account'
                        }}
                        <ArrowRight class="size-4" />
                    </Link>
                    <span class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        No card needed. AI drafting included.
                    </span>
                </div>
            </section>

            <!-- Two ways -->
            <section class="grid gap-4 sm:grid-cols-2">
                <div
                    class="rounded-xl border border-[#e3e3e0] p-6 dark:border-[#3E3E3A]"
                >
                    <PencilLine class="size-5" />
                    <h2 class="mt-3 font-semibold">Write it yourself</h2>
                    <p
                        class="mt-2 text-sm leading-relaxed text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Every template breaks down into plain fields. Type your
                        own words and watch the email build itself next to you.
                    </p>
                </div>
                <div
                    class="rounded-xl border border-[#e3e3e0] p-6 dark:border-[#3E3E3A]"
                >
                    <Sparkles class="size-5" />
                    <h2 class="mt-3 font-semibold">
                        Or generate from the posting
                    </h2>
                    <p
                        class="mt-2 text-sm leading-relaxed text-[#706f6c] dark:text-[#A1A09A]"
                    >
                        Paste the job posting. The AI writes each field using
                        only what your CV actually says — then you edit every
                        line before it goes out.
                    </p>
                </div>
            </section>

            <!-- How it works -->
            <section class="mt-20">
                <h2 class="text-2xl font-semibold tracking-tight">
                    How it works
                </h2>

                <div
                    class="mt-8 grid gap-x-8 gap-y-8 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <div
                        v-for="(step, index) in steps"
                        :key="step.title"
                        class="flex gap-4"
                    >
                        <div
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg border border-[#e3e3e0] dark:border-[#3E3E3A]"
                        >
                            <component :is="step.icon" class="size-4" />
                        </div>
                        <div>
                            <h3 class="text-sm font-medium">
                                {{ index + 1 }}. {{ step.title }}
                            </h3>
                            <p
                                class="mt-1 text-sm leading-relaxed text-[#706f6c] dark:text-[#A1A09A]"
                            >
                                {{ step.body }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Why not just send from the app -->
            <section
                class="mt-20 rounded-xl border border-[#e3e3e0] p-8 dark:border-[#3E3E3A]"
            >
                <h2 class="text-lg font-semibold">
                    Why you send it from Gmail, not from us
                </h2>
                <p
                    class="mt-3 max-w-3xl text-sm leading-relaxed text-[#706f6c] dark:text-[#A1A09A]"
                >
                    An application sent from a third-party server arrives with
                    someone else's domain in the headers and often lands in
                    spam. Copying the HTML into your own Gmail means the message
                    comes from your real address, the recruiter's reply reaches
                    you directly, and the thread lives in your sent folder like
                    any other email you wrote.
                </p>
            </section>
        </main>
    </div>
</template>

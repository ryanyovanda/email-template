<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Check,
    ChevronDown,
    FileText,
    LayoutTemplate,
    Mail,
    Menu,
    Monitor,
    Puzzle,
    Send,
    Sparkles,
    UserPlus,
    X,
} from '@lucide/vue';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { login, register } from '@/routes';

const mobileMenuOpen = ref(false);
const openFaq = ref(0);

const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};

const setFaq = (i: number) => {
    openFaq.value = openFaq.value === i ? -1 : i;
};

type Step = {
    num: string;
    icon: typeof Mail;
    title: string;
    time: string;
    body: string;
    points: string[];
    note?: string;
};

const steps: Step[] = [
    {
        num: '01',
        icon: UserPlus,
        title: 'Create your account',
        time: '1 min',
        body: 'Sign up with an email and password. Passkeys and two-factor are available if you want them. Every new account starts with a monthly credit allowance, so you can try AI drafting right away — no card required.',
        points: [
            'Email + password, or add a passkey later',
            '300 free credits topped up at the start of each month',
        ],
    },
    {
        num: '02',
        icon: FileText,
        title: 'Build your profile & add your CV',
        time: '4 min',
        body: 'Fill in your name, headline, contact details and photo, then upload your CV as a PDF or Word file. ApplyMail extracts the text automatically and shows it in an editable box — that text is the only thing the AI ever reads.',
        points: [
            'PDF, DOC and DOCX supported (up to 8 MB)',
            'Scanned CV with no text layer? Paste the text in manually',
            'Your details fill every template automatically — type them once',
        ],
        note: 'The richer and more accurate your CV text, the sharper the AI drafts. Keep it current.',
    },
    {
        num: '03',
        icon: LayoutTemplate,
        title: 'Pick a template',
        time: '1 min',
        body: 'Browse the gallery. Every template is table-based with inline styles, so it renders correctly across Gmail, Outlook and Apple Mail on both desktop and mobile. You can also design your own — by hand for free, or with AI.',
        points: [
            'Curated templates, plus community designs',
            'Each one is Gmail-safe by construction',
            'Roll your own: hand-written is free, AI design costs credits',
        ],
    },
    {
        num: '04',
        icon: Sparkles,
        title: 'Fill it in — by hand or with AI',
        time: '3–10 min',
        body: 'Two modes live on the same screen. Type into the fields yourself and watch the email build beside you, or paste the full job posting and let the AI draft every field from your real CV. It also pulls out the company, role and hiring contact.',
        points: [
            'Manual mode: full control, edit as you go, always free',
            'AI mode: grounded in your CV only — it will not invent experience',
            'Each AI draft costs 10 credits, and only if it succeeds',
        ],
        note: 'The AI writes a starting point. Read and edit every line before you send — it can misread a posting.',
    },
    {
        num: '05',
        icon: Monitor,
        title: 'Preview exactly as Gmail renders it',
        time: '1 min',
        body: 'See the message a recruiter will actually open: a Gmail reading-pane mock with the subject, sender row and attachment chip, rendered in a sandboxed frame so nothing leaks in. Toggle between desktop and mobile to catch layout issues early.',
        points: [
            'Real Gmail-accurate rendering, not a rough approximation',
            'Desktop and mobile breakpoints in one click',
        ],
    },
    {
        num: '06',
        icon: Puzzle,
        title: 'Install the Gmail HTML extension',
        time: '2 min',
        body: 'ApplyMail sends nothing on your behalf — you send from your own Gmail. Install the free “Insert and Send HTML with Gmail” Chrome extension once. It adds a button to the Gmail compose window that lets you paste rich HTML.',
        points: [
            'Works on Chrome, Brave and Edge',
            'One-time install — you only do this on your first send',
        ],
        note: 'Sending from your own address keeps you out of spam and routes replies straight back to your inbox.',
    },
    {
        num: '07',
        icon: Send,
        title: 'Copy, paste and send',
        time: '1 min',
        body: 'Click “Copy HTML” (or download the .html file), open a Gmail compose window, use the extension button to insert it, attach your CV, and hit Send. The email leaves from your real address, and the recruiter’s reply comes to you.',
        points: [
            'Copy HTML or download the file — your choice',
            'Attach your CV before sending',
            'The thread lives in your Sent folder like any other email',
        ],
    },
];

const faqs = [
    {
        q: 'Does ApplyMail send the email for me?',
        a: 'No — and that is the whole point. You send from your own Gmail, so the message carries your real address, lands in the inbox instead of spam, and any reply comes straight back to you. ApplyMail only builds the HTML and shows you exactly how it will look.',
    },
    {
        q: 'Will the AI make up experience I do not have?',
        a: 'No. The AI reads only the text extracted from the CV you uploaded. If something is not in your CV, it will not appear in the draft. Everything it writes stays fully editable before you send.',
    },
    {
        q: 'How do credits work?',
        a: 'Every account is topped up to 300 credits at the start of each calendar month. An AI application draft costs 10 credits and an AI-designed template costs 30. Writing an email by hand, and any draft that fails, costs nothing.',
    },
    {
        q: 'What if my CV is a scanned image?',
        a: 'A scanned PDF has no text layer, so nothing can be extracted from it. When that happens ApplyMail asks you to paste your CV text in manually — that pasted text then works exactly like an extracted one.',
    },
    {
        q: 'Which email clients do the templates support?',
        a: 'Every template is built with tables and inline styles, the combination that survives real email clients. They are tested to render correctly in Gmail, Outlook and Apple Mail across desktop and mobile.',
    },
    {
        q: 'Is my data private?',
        a: 'Your CV and profile are used only to fill templates and generate your drafts. Community templates, if yours is ever published, are shown without your name or any personal detail. We do not sell your data.',
    },
];
</script>

<template>
    <Head>
        <title>How ApplyMail works — step-by-step tutorial</title>
        <meta
            name="description"
            content="Learn how ApplyMail turns your CV and a job posting into a designed application email, previewed as Gmail renders it and sent from your own inbox. Seven simple steps."
        />
        <meta property="og:title" content="How ApplyMail works — tutorial" />
        <meta
            property="og:description"
            content="From account to sent email in about fifteen minutes. Build a profile, pick a template, draft with AI, preview in Gmail, and send from your own address."
        />
        <meta property="og:type" content="article" />
    </Head>

    <div
        class="min-h-screen font-sans text-white antialiased"
        style="background: #0a0a0a"
    >
        <!-- ============ NAV ============ -->
        <header class="fixed top-0 right-0 left-0 z-50">
            <div
                class="border-b border-white/10"
                style="
                    background: rgba(10, 10, 10, 0.6);
                    backdrop-filter: blur(12px);
                    -webkit-backdrop-filter: blur(12px);
                "
            >
                <div
                    class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3.5 sm:px-6"
                >
                    <Link href="/" class="flex items-center gap-2.5">
                        <span
                            class="flex size-8 items-center justify-center rounded-md bg-white"
                        >
                            <Mail class="size-4 text-black" />
                        </span>
                        <span
                            class="text-base font-semibold tracking-tight text-white"
                            >ApplyMail</span
                        >
                    </Link>

                    <nav
                        class="hidden items-center gap-7 text-sm text-white/70 lg:flex"
                    >
                        <Link href="/#features" class="hover:text-white"
                            >Features</Link
                        >
                        <Link href="/tutorial" class="text-white"
                            >Tutorial</Link
                        >
                        <Link href="/#faq" class="hover:text-white">FAQ</Link>
                        <Link
                            v-if="$page.props.auth.user"
                            href="/dashboard"
                            class="rounded-md bg-white px-4 py-2 font-medium text-black transition-opacity hover:opacity-90"
                            >Dashboard</Link
                        >
                        <template v-else>
                            <Link :href="login()" class="hover:text-white"
                                >Log in</Link
                            >
                            <Link
                                :href="register()"
                                class="rounded-md bg-white px-4 py-2 font-medium text-black transition-opacity hover:opacity-90"
                                >Get started</Link
                            >
                        </template>
                    </nav>

                    <button
                        class="flex size-9 items-center justify-center rounded-md border border-white/15 lg:hidden"
                        aria-label="Toggle menu"
                        @click="toggleMobileMenu"
                    >
                        <X v-if="mobileMenuOpen" class="size-4" />
                        <Menu v-else class="size-4" />
                    </button>
                </div>

                <div
                    v-if="mobileMenuOpen"
                    class="border-t border-white/10 px-4 pb-4 lg:hidden"
                >
                    <div class="flex flex-col gap-1 pt-2 text-sm">
                        <Link
                            href="/#features"
                            class="rounded-md px-3 py-3 text-white/70"
                            @click="toggleMobileMenu"
                            >Features</Link
                        >
                        <Link
                            :href="register()"
                            class="mt-1 rounded-md bg-white px-3 py-3 text-center font-medium text-black"
                            @click="toggleMobileMenu"
                            >Get started</Link
                        >
                    </div>
                </div>
            </div>
        </header>

        <!-- ============ HERO ============ -->
        <section class="mx-auto max-w-6xl px-4 pt-36 pb-16 sm:px-6 sm:pt-44">
            <p
                class="mb-6 font-mono text-xs tracking-[0.25em] text-white/40 uppercase"
            >
                // The complete walkthrough
            </p>
            <h1
                class="max-w-4xl text-4xl leading-[1.05] font-bold tracking-tight sm:text-6xl md:text-7xl"
            >
                From sign-up to
                <span class="text-white/40">sent</span> in seven steps.
            </h1>
            <p class="mt-6 max-w-2xl text-lg leading-relaxed text-white/55">
                ApplyMail turns your CV and a job posting into a designed
                application email — previewed exactly as Gmail renders it, then
                sent from your own inbox. Here is the whole flow, start to
                finish. First run takes about fifteen minutes.
            </p>
            <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                <Link
                    :href="register()"
                    class="inline-flex items-center justify-center gap-2 rounded-md bg-white px-6 py-3 text-sm font-semibold text-black transition-opacity hover:opacity-90"
                >
                    Start free <ArrowRight class="size-4" />
                </Link>
                <a
                    href="#steps"
                    class="inline-flex items-center justify-center gap-2 rounded-md border border-white/20 px-6 py-3 text-sm font-medium text-white/80 transition-colors hover:border-white/40 hover:text-white"
                >
                    Read the steps
                </a>
            </div>

            <!-- quick index -->
            <div
                class="mt-14 grid grid-cols-2 gap-px overflow-hidden rounded-lg border border-white/10 bg-white/10 sm:grid-cols-4"
            >
                <div
                    v-for="s in steps"
                    :key="s.num"
                    class="flex items-center gap-3 px-4 py-4"
                    style="background: #0a0a0a"
                >
                    <span class="font-mono text-xs text-white/30">{{
                        s.num
                    }}</span>
                    <span class="text-xs font-medium text-white/70">{{
                        s.title
                    }}</span>
                </div>
            </div>
        </section>

        <!-- ============ STEPS ============ -->
        <section id="steps" class="mx-auto max-w-4xl px-4 pb-24 sm:px-6">
            <div class="relative">
                <!-- vertical spine -->
                <div
                    class="absolute top-0 bottom-0 left-[22px] hidden w-px bg-white/10 sm:block"
                ></div>

                <article
                    v-for="step in steps"
                    :key="step.num"
                    class="relative mb-6 sm:pl-20"
                >
                    <!-- node -->
                    <div
                        class="absolute top-0 left-0 hidden size-11 items-center justify-center rounded-full border border-white/15 font-mono text-sm font-semibold sm:flex"
                        style="background: #0a0a0a"
                    >
                        {{ step.num }}
                    </div>

                    <div
                        class="rounded-xl border border-white/10 p-6 sm:p-8"
                        style="background: rgba(255, 255, 255, 0.03)"
                    >
                        <div class="mb-4 flex items-center gap-3">
                            <component
                                :is="step.icon"
                                class="size-5 text-white"
                            />
                            <span
                                class="font-mono text-xs tracking-wider text-white/40 uppercase"
                                >{{ step.time }}</span
                            >
                        </div>

                        <h2 class="mb-3 text-2xl font-bold sm:text-3xl">
                            <span class="text-white/30 sm:hidden"
                                >{{ step.num }}.
                            </span>
                            {{ step.title }}
                        </h2>
                        <p class="mb-6 leading-relaxed text-white/60">
                            {{ step.body }}
                        </p>

                        <ul class="space-y-2.5">
                            <li
                                v-for="point in step.points"
                                :key="point"
                                class="flex items-start gap-3 text-sm text-white/75"
                            >
                                <Check
                                    class="mt-0.5 size-4 shrink-0 text-white/50"
                                />
                                <span>{{ point }}</span>
                            </li>
                        </ul>

                        <div
                            v-if="step.note"
                            class="mt-6 border-l-2 border-white/25 pl-4 text-sm leading-relaxed text-white/45"
                        >
                            {{ step.note }}
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <!-- ============ WHY THIS WAY ============ -->
        <section class="border-t border-white/10 px-4 py-20 sm:px-6">
            <div class="mx-auto grid max-w-6xl gap-10 lg:grid-cols-2">
                <div>
                    <p
                        class="mb-4 font-mono text-xs tracking-[0.25em] text-white/40 uppercase"
                    >
                        // Why send it yourself
                    </p>
                    <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">
                        The extension exists so the email stays yours.
                    </h2>
                </div>
                <div class="space-y-5 text-white/60">
                    <p class="leading-relaxed">
                        An application sent from a third-party server arrives
                        with someone else’s domain in the headers. It often
                        lands in spam, and any reply goes somewhere the
                        applicant never reads.
                    </p>
                    <p class="leading-relaxed">
                        Copying the HTML into your own Gmail fixes all of that:
                        the message comes from your real address, the thread
                        sits in your Sent folder, and the recruiter’s reply
                        arrives in your inbox. ApplyMail’s job is to produce the
                        HTML and show you exactly how it will look — you stay in
                        control of the send.
                    </p>
                </div>
            </div>
        </section>

        <!-- ============ FAQ ============ -->
        <section class="border-t border-white/10 px-4 py-20 sm:px-6">
            <div class="mx-auto max-w-3xl">
                <p
                    class="mb-4 font-mono text-xs tracking-[0.25em] text-white/40 uppercase"
                >
                    // Questions
                </p>
                <h2 class="mb-12 text-3xl font-bold tracking-tight sm:text-4xl">
                    Good to know before you start.
                </h2>

                <div class="divide-y divide-white/10 border-y border-white/10">
                    <div v-for="(faq, i) in faqs" :key="faq.q" class="py-2">
                        <button
                            class="flex w-full items-center justify-between gap-4 py-4 text-left"
                            @click="setFaq(i)"
                        >
                            <span class="text-base font-medium">{{
                                faq.q
                            }}</span>
                            <ChevronDown
                                class="size-4 shrink-0 text-white/40 transition-transform duration-200"
                                :class="openFaq === i ? 'rotate-180' : ''"
                            />
                        </button>
                        <p
                            v-show="openFaq === i"
                            class="pr-8 pb-4 text-sm leading-relaxed text-white/55"
                        >
                            {{ faq.a }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============ CTA ============ -->
        <section class="border-t border-white/10 px-4 py-24 sm:px-6">
            <div
                class="mx-auto max-w-3xl rounded-2xl border border-white/15 px-6 py-14 text-center"
                style="background: rgba(255, 255, 255, 0.03)"
            >
                <h2
                    class="mx-auto max-w-xl text-3xl font-bold tracking-tight sm:text-4xl"
                >
                    Ready to send one that actually gets read?
                </h2>
                <p class="mx-auto mt-4 max-w-md text-white/55">
                    Free to start, 300 credits a month, no card required. Your
                    first email is about fifteen minutes away.
                </p>
                <Link
                    :href="register()"
                    class="mt-8 inline-flex items-center gap-2 rounded-md bg-white px-7 py-3.5 text-sm font-semibold text-black transition-opacity hover:opacity-90"
                >
                    Create your account <ArrowRight class="size-4" />
                </Link>
            </div>
        </section>

        <!-- ============ FOOTER ============ -->
        <footer class="border-t border-white/10 px-4 py-10 sm:px-6">
            <div
                class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 sm:flex-row"
            >
                <div class="flex items-center gap-2">
                    <span
                        class="flex size-6 items-center justify-center rounded bg-white"
                    >
                        <Mail class="size-3.5 text-black" />
                    </span>
                    <span class="text-sm font-semibold">ApplyMail</span>
                </div>
                <div class="flex gap-6 text-sm text-white/40">
                    <Link href="/" class="hover:text-white">Home</Link>
                    <Link href="/tutorial" class="hover:text-white"
                        >Tutorial</Link
                    >
                    <Link :href="login()" class="hover:text-white">Log in</Link>
                </div>
                <p class="text-xs text-white/25">
                    © 2026 ApplyMail. Sent from your Gmail.
                </p>
            </div>
        </footer>
    </div>
</template>

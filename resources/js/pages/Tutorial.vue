<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Puzzle,
    FileText,
    LayoutTemplate,
    Mail,
    Send,
    Sparkles,
    ChevronRight,
} from '@lucide/vue';
import { login, register } from '@/routes';

const steps = [
    {
        num: '01',
        icon: Puzzle,
        title: 'Install the Chrome extension',
        time: '2 min',
        body: 'Download the free ApplyMail extension from the Chrome Web Store. It adds a single button to your Gmail compose window — nothing else changes.',
        tip: 'Works on Chrome, Brave, and Edge. No Firefox support yet.',
        screenshot: true,
    },
    {
        num: '02',
        icon: FileText,
        title: 'Setup your profile',
        time: '5 min',
        body: 'Upload your CV (PDF) and fill in your name, contact details, and a short bio. We extract the text automatically so you never retype the same information.',
        tip: 'The better your CV, the better the AI drafts. Keep it up to date.',
        screenshot: true,
    },
    {
        num: '03',
        icon: LayoutTemplate,
        title: 'Pick a template',
        time: '1 min',
        body: 'Browse the template gallery. Each design is table-based with inline styles — proven to render correctly in Gmail, Outlook, and Apple Mail on desktop and mobile.',
        tip: "Pick the template that matches the company's vibe. Startup? Go modern. Corp? Go clean.",
        screenshot: true,
    },
    {
        num: '04',
        icon: Sparkles,
        title: 'Fill it or let AI draft',
        time: '3-10 min',
        body: 'Option A: Fill each field manually and watch the email build in real time beside you. Option B: Paste the job posting URL or text, and the AI generates a draft using only what your CV actually says.',
        tip: "Always read and edit every AI-generated line before sending. It's a draft, not a final.",
        screenshot: true,
    },
    {
        num: '05',
        icon: Mail,
        title: 'Preview in Gmail',
        time: '1 min',
        body: "Hit preview to see the exact message a recruiter would open — rendered in Gmail's engine, on desktop and mobile breakpoints. Spot layout issues before they reach anyone.",
        tip: 'Check both dark mode and light mode if you can.',
        screenshot: true,
    },
    {
        num: '06',
        icon: Send,
        title: 'Copy HTML and send',
        time: '1 min',
        body: 'Click "Copy HTML". Open Gmail, click the ApplyMail button in compose, paste. Your email is now filled with pixel-perfect HTML. Review once more and hit Send.',
        tip: 'Sent from your Gmail address. Recruiter replies go straight to your inbox.',
        screenshot: true,
    },
];

const faqs = [
    {
        q: 'Apakah aplikasi ini gratis?',
        a: 'Ya, gratis untuk digunakan. AI drafting mungkin memerlukan kredit untuk penggunaan berlebih di masa depan.',
    },
    {
        q: 'Apakah data CV saya aman?',
        a: 'CV-mu disimpan secara terenkripsi dan hanya digunakan untuk mengisi template dan generate draft AI. Kami tidak menjualnya.',
    },
    {
        q: 'Apakah bekerja selain Gmail?',
        a: 'Saat ini ekstensi hanya untuk Gmail. Tapi HTML yang dihasilkan bisa dipaste ke email client lain secara manual.',
    },
    {
        q: 'Apakah AI bisa berbohong soal pengalaman saya?',
        a: 'Tidak. AI kami hanya menggunakan teks dari CV yang kamu upload. Jika pengalaman tidak ada di CV, tidak akan muncul di draft.',
    },
];
</script>

<template>
    <Head title="Tutorial — ApplyMail" />

    <div class="min-h-screen bg-[#0f0f0f] font-sans text-white">
        <!-- NAV -->
        <header
            class="fixed top-0 right-0 left-0 z-50 border-b border-white/5 bg-[#0f0f0f]/90 backdrop-blur-sm"
        >
            <div
                class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4"
            >
                <Link
                    href="/"
                    class="flex items-center gap-2 transition-opacity duration-200 hover:opacity-80"
                >
                    <Mail class="size-5 text-[#FF5C28]" />
                    <span class="text-base font-bold tracking-tight"
                        >ApplyMail</span
                    >
                </Link>

                <nav class="flex items-center gap-1 text-sm">
                    <span class="px-4 py-2 font-semibold text-[#FF5C28]"
                        >Tutorial</span
                    >
                    <Link
                        v-if="$page.props.auth.user"
                        href="/dashboard"
                        class="ml-2 rounded-md bg-[#FF5C28] px-5 py-2 font-bold text-white transition-colors duration-200 hover:bg-[#e04d1f]"
                        >Dashboard</Link
                    >
                    <template v-else>
                        <Link
                            :href="login()"
                            class="px-4 py-2 text-white/60 transition-colors duration-200 hover:text-white"
                            >Log in</Link
                        >
                        <Link
                            :href="register()"
                            class="ml-2 rounded-md bg-[#FF5C28] px-5 py-2 font-bold text-white transition-colors duration-200 hover:bg-[#e04d1f]"
                            >Get started</Link
                        >
                    </template>
                </nav>
            </div>
        </header>

        <!-- HERO -->
        <section class="bg-[#0f0f0f] px-6 pt-40 pb-24">
            <div class="mx-auto max-w-6xl">
                <div
                    class="mb-6 flex items-center gap-2 text-sm font-bold tracking-[0.2em] text-[#FF5C28] uppercase"
                >
                    <Link href="/" class="hover:underline">Home</Link>
                    <ChevronRight class="size-3" />
                    <span>Tutorial</span>
                </div>
                <h1
                    class="mb-6 max-w-4xl text-6xl leading-none font-black tracking-tight md:text-8xl"
                >
                    From zero to sent.<br />
                    <span class="text-[#FF5C28]">6 steps.</span>
                </h1>
                <p class="max-w-xl text-lg leading-relaxed text-white/50">
                    Install the extension, build your profile, pick a template,
                    let AI draft, preview, copy & send. Takes under 15 minutes
                    the first time.
                </p>
            </div>
        </section>

        <!-- STEPS -->
        <section class="bg-white px-6 py-24 text-[#0f0f0f]">
            <div class="mx-auto max-w-6xl">
                <div
                    v-for="(step, idx) in steps"
                    :key="step.num"
                    class="group"
                    :class="
                        idx < steps.length - 1
                            ? 'mb-24 border-b border-[#0f0f0f]/8 pb-24'
                            : ''
                    "
                >
                    <div class="grid items-start gap-12 lg:grid-cols-2">
                        <!-- Left: content -->
                        <div>
                            <div
                                class="mb-4 text-8xl leading-none font-black text-[#0f0f0f]/6 transition-colors duration-300 select-none group-hover:text-[#FF5C28]/15"
                            >
                                {{ step.num }}
                            </div>
                            <div class="mb-4 flex items-center gap-2">
                                <component
                                    :is="step.icon"
                                    class="size-5 text-[#FF5C28]"
                                />
                                <span
                                    class="text-xs font-bold tracking-wider text-[#0f0f0f]/30 uppercase"
                                    >{{ step.time }}</span
                                >
                            </div>
                            <h2 class="mb-4 text-3xl font-black">
                                {{ step.title }}
                            </h2>
                            <p
                                class="mb-6 text-base leading-relaxed text-[#0f0f0f]/60"
                            >
                                {{ step.body }}
                            </p>
                            <div
                                class="flex items-start gap-3 rounded-md border border-[#FF5C28]/20 bg-[#FF5C28]/5 p-4"
                            >
                                <span
                                    class="mt-0.5 shrink-0 text-xs font-black tracking-wider text-[#FF5C28] uppercase"
                                    >Tip</span
                                >
                                <p
                                    class="text-sm leading-relaxed text-[#0f0f0f]/60"
                                >
                                    {{ step.tip }}
                                </p>
                            </div>
                        </div>

                        <!-- Right: screenshot placeholder -->
                        <div
                            class="flex aspect-video flex-col items-center justify-center gap-3 rounded-xl border border-[#0f0f0f]/8 bg-[#0f0f0f]/4"
                        >
                            <component
                                :is="step.icon"
                                class="size-8 text-[#0f0f0f]/20"
                            />
                            <span
                                class="text-xs font-medium tracking-wider text-[#0f0f0f]/25 uppercase"
                                >Screenshot — Step {{ step.num }}</span
                            >
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section class="bg-[#0f0f0f] px-6 py-24">
            <div class="mx-auto max-w-6xl">
                <p
                    class="mb-4 text-sm font-bold tracking-[0.2em] text-[#FF5C28] uppercase"
                >
                    FAQ
                </p>
                <h2 class="mb-16 text-5xl font-black tracking-tight">
                    Ada pertanyaan?
                </h2>

                <div class="grid gap-8 sm:grid-cols-2">
                    <div
                        v-for="faq in faqs"
                        :key="faq.q"
                        class="border-t border-white/10 pt-8"
                    >
                        <h3 class="mb-3 text-lg font-black">{{ faq.q }}</h3>
                        <p class="text-sm leading-relaxed text-white/40">
                            {{ faq.a }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="bg-[#FF5C28] px-6 py-20">
            <div
                class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-8 md:flex-row"
            >
                <h2
                    class="max-w-xl text-4xl leading-tight font-black text-white md:text-5xl"
                >
                    Siap coba? Gratis, dan makan waktu 15 menit.
                </h2>
                <Link
                    :href="register()"
                    class="inline-flex shrink-0 items-center gap-2 rounded-md bg-white px-8 py-4 text-base font-black whitespace-nowrap text-[#FF5C28] transition-colors duration-200 hover:bg-white/90"
                >
                    Mulai gratis sekarang
                    <ArrowRight class="size-4" />
                </Link>
            </div>
        </section>

        <!-- FOOTER -->
        <footer class="border-t border-white/5 bg-[#0f0f0f] px-6 py-10">
            <div
                class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 sm:flex-row"
            >
                <div class="flex items-center gap-2">
                    <Mail class="size-4 text-[#FF5C28]" />
                    <span class="text-sm font-bold">ApplyMail</span>
                </div>
                <div class="flex gap-6 text-sm text-white/30">
                    <Link
                        href="/"
                        class="transition-colors duration-200 hover:text-white"
                        >Home</Link
                    >
                    <Link
                        href="/tutorial"
                        class="transition-colors duration-200 hover:text-white"
                        >Tutorial</Link
                    >
                    <Link
                        :href="login()"
                        class="transition-colors duration-200 hover:text-white"
                        >Log in</Link
                    >
                </div>
                <p class="text-xs text-white/20">
                    © 2025 ApplyMail. Sent from your Gmail.
                </p>
            </div>
        </footer>
    </div>
</template>

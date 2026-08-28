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
        tip: 'Pick the template that matches the company\'s vibe. Startup? Go modern. Corp? Go clean.',
        screenshot: true,
    },
    {
        num: '04',
        icon: Sparkles,
        title: 'Fill it or let AI draft',
        time: '3-10 min',
        body: 'Option A: Fill each field manually and watch the email build in real time beside you. Option B: Paste the job posting URL or text, and the AI generates a draft using only what your CV actually says.',
        tip: 'Always read and edit every AI-generated line before sending. It\'s a draft, not a final.',
        screenshot: true,
    },
    {
        num: '05',
        icon: Mail,
        title: 'Preview in Gmail',
        time: '1 min',
        body: 'Hit preview to see the exact message a recruiter would open — rendered in Gmail\'s engine, on desktop and mobile breakpoints. Spot layout issues before they reach anyone.',
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

    <div class="min-h-screen bg-[#0f0f0f] text-white font-sans">

        <!-- NAV -->
        <header class="fixed top-0 left-0 right-0 z-50 bg-[#0f0f0f]/90 backdrop-blur-sm border-b border-white/5">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <Link href="/" class="flex items-center gap-2 hover:opacity-80 transition-opacity duration-200">
                    <Mail class="size-5 text-[#FF5C28]" />
                    <span class="text-base font-bold tracking-tight">ApplyMail</span>
                </Link>

                <nav class="flex items-center gap-1 text-sm">
                    <span class="px-4 py-2 text-[#FF5C28] font-semibold">Tutorial</span>
                    <Link
                        v-if="$page.props.auth.user"
                        href="/dashboard"
                        class="ml-2 px-5 py-2 rounded-md bg-[#FF5C28] text-white font-bold hover:bg-[#e04d1f] transition-colors duration-200"
                    >Dashboard</Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="px-4 py-2 text-white/60 hover:text-white transition-colors duration-200"
                        >Log in</Link>
                        <Link
                            :href="register()"
                            class="ml-2 px-5 py-2 rounded-md bg-[#FF5C28] text-white font-bold hover:bg-[#e04d1f] transition-colors duration-200"
                        >Get started</Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- HERO -->
        <section class="pt-40 pb-24 px-6 bg-[#0f0f0f]">
            <div class="mx-auto max-w-6xl">
                <div class="flex items-center gap-2 text-[#FF5C28] text-sm font-bold uppercase tracking-[0.2em] mb-6">
                    <Link href="/" class="hover:underline">Home</Link>
                    <ChevronRight class="size-3" />
                    <span>Tutorial</span>
                </div>
                <h1 class="text-6xl md:text-8xl font-black leading-none tracking-tight mb-6 max-w-4xl">
                    From zero to sent.<br />
                    <span class="text-[#FF5C28]">6 steps.</span>
                </h1>
                <p class="text-lg text-white/50 max-w-xl leading-relaxed">
                    Install the extension, build your profile, pick a template, let AI draft, preview, copy & send. Takes under 15 minutes the first time.
                </p>
            </div>
        </section>

        <!-- STEPS -->
        <section class="bg-white text-[#0f0f0f] py-24 px-6">
            <div class="mx-auto max-w-6xl">

                <div
                    v-for="(step, idx) in steps"
                    :key="step.num"
                    class="group"
                    :class="idx < steps.length - 1 ? 'mb-24 pb-24 border-b border-[#0f0f0f]/8' : ''"
                >
                    <div class="grid gap-12 lg:grid-cols-2 items-start">
                        <!-- Left: content -->
                        <div>
                            <div class="text-8xl font-black text-[#0f0f0f]/6 leading-none mb-4 select-none group-hover:text-[#FF5C28]/15 transition-colors duration-300">
                                {{ step.num }}
                            </div>
                            <div class="flex items-center gap-2 mb-4">
                                <component :is="step.icon" class="size-5 text-[#FF5C28]" />
                                <span class="text-xs font-bold uppercase tracking-wider text-[#0f0f0f]/30">{{ step.time }}</span>
                            </div>
                            <h2 class="text-3xl font-black mb-4">{{ step.title }}</h2>
                            <p class="text-[#0f0f0f]/60 leading-relaxed mb-6 text-base">{{ step.body }}</p>
                            <div class="flex items-start gap-3 bg-[#FF5C28]/5 border border-[#FF5C28]/20 rounded-md p-4">
                                <span class="text-[#FF5C28] font-black text-xs uppercase tracking-wider shrink-0 mt-0.5">Tip</span>
                                <p class="text-sm text-[#0f0f0f]/60 leading-relaxed">{{ step.tip }}</p>
                            </div>
                        </div>

                        <!-- Right: screenshot placeholder -->
                        <div class="aspect-video bg-[#0f0f0f]/4 rounded-xl flex flex-col items-center justify-center gap-3 border border-[#0f0f0f]/8">
                            <component :is="step.icon" class="size-8 text-[#0f0f0f]/20" />
                            <span class="text-xs text-[#0f0f0f]/25 font-medium uppercase tracking-wider">Screenshot — Step {{ step.num }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- FAQ -->
        <section class="bg-[#0f0f0f] py-24 px-6">
            <div class="mx-auto max-w-6xl">
                <p class="text-[#FF5C28] text-sm font-bold uppercase tracking-[0.2em] mb-4">FAQ</p>
                <h2 class="text-5xl font-black tracking-tight mb-16">Ada pertanyaan?</h2>

                <div class="grid gap-8 sm:grid-cols-2">
                    <div
                        v-for="faq in faqs"
                        :key="faq.q"
                        class="border-t border-white/10 pt-8"
                    >
                        <h3 class="font-black text-lg mb-3">{{ faq.q }}</h3>
                        <p class="text-white/40 leading-relaxed text-sm">{{ faq.a }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="bg-[#FF5C28] py-20 px-6">
            <div class="mx-auto max-w-6xl flex flex-col md:flex-row items-center justify-between gap-8">
                <h2 class="text-4xl md:text-5xl font-black text-white leading-tight max-w-xl">
                    Siap coba? Gratis, dan makan waktu 15 menit.
                </h2>
                <Link
                    :href="register()"
                    class="shrink-0 inline-flex items-center gap-2 px-8 py-4 bg-white text-[#FF5C28] font-black text-base rounded-md hover:bg-white/90 transition-colors duration-200 whitespace-nowrap"
                >
                    Mulai gratis sekarang
                    <ArrowRight class="size-4" />
                </Link>
            </div>
        </section>

        <!-- FOOTER -->
        <footer class="bg-[#0f0f0f] border-t border-white/5 py-10 px-6">
            <div class="mx-auto max-w-6xl flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <Mail class="size-4 text-[#FF5C28]" />
                    <span class="font-bold text-sm">ApplyMail</span>
                </div>
                <div class="flex gap-6 text-white/30 text-sm">
                    <Link href="/" class="hover:text-white transition-colors duration-200">Home</Link>
                    <Link href="/tutorial" class="hover:text-white transition-colors duration-200">Tutorial</Link>
                    <Link :href="login()" class="hover:text-white transition-colors duration-200">Log in</Link>
                </div>
                <p class="text-white/20 text-xs">© 2025 ApplyMail. Sent from your Gmail.</p>
            </div>
        </footer>

    </div>
</template>

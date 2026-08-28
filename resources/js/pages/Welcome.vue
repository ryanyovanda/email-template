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
        num: '01',
        icon: Puzzle,
        title: 'Install extension',
        body: 'Free Chrome extension that pastes HTML straight into Gmail compose. One click, zero config.',
    },
    {
        num: '02',
        icon: FileText,
        title: 'Setup your profile',
        body: 'Name, contact, photo, CV. We extract the text so you never retype the same info twice.',
    },
    {
        num: '03',
        icon: LayoutTemplate,
        title: 'Pick a template',
        body: 'Table-based, inline-styled layouts that survive Gmail rendering on every device.',
    },
    {
        num: '04',
        icon: Sparkles,
        title: 'Fill or let AI draft',
        body: 'Paste the job posting and get a draft grounded in your real CV — every line editable before send.',
    },
    {
        num: '05',
        icon: Mail,
        title: 'Preview in Gmail',
        body: 'See the exact message the recruiter opens — desktop and mobile — before anything leaves.',
    },
    {
        num: '06',
        icon: Send,
        title: 'Copy and send',
        body: 'Paste into your Gmail. Sent from your real address. Replies come back to you.',
    },
];

const features = [
    {
        icon: Sparkles,
        title: 'AI grounded in your CV',
        body: 'No hallucinations. The AI only uses what your uploaded CV actually says.',
    },
    {
        icon: LayoutTemplate,
        title: 'Pixel-perfect templates',
        body: 'HTML email that looks identical in Gmail, Outlook, and Apple Mail.',
    },
    {
        icon: Mail,
        title: 'Live Gmail preview',
        body: 'A real-time renderer so you know exactly what the recruiter sees.',
    },
    {
        icon: FileText,
        title: 'Your address, always',
        body: 'Sent from your Gmail. Zero spam risk. Replies land in your real inbox.',
    },
];
</script>

<template>
    <Head title="Email lamaran yang bikin HR berhenti scroll — ApplyMail" />

    <div class="min-h-screen bg-[#0f0f0f] text-white font-sans">

        <!-- NAV -->
        <header class="fixed top-0 left-0 right-0 z-50 bg-[#0f0f0f]/90 backdrop-blur-sm border-b border-white/5">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <div class="flex items-center gap-2">
                    <Mail class="size-5 text-[#FF5C28]" />
                    <span class="text-base font-bold tracking-tight">ApplyMail</span>
                </div>

                <nav class="flex items-center gap-1 text-sm">
                    <Link
                        href="/tutorial"
                        class="px-4 py-2 text-white/60 hover:text-white transition-colors duration-200"
                    >
                        Tutorial
                    </Link>
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="px-4 py-2 rounded-md bg-white text-[#0f0f0f] font-semibold hover:bg-white/90 transition-colors duration-200"
                    >
                        Dashboard
                    </Link>
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
        <section class="min-h-screen flex flex-col justify-center px-6 pt-24 pb-16 bg-[#0f0f0f]">
            <div class="mx-auto max-w-6xl w-full">
                <p class="text-[#FF5C28] text-sm font-bold uppercase tracking-[0.2em] mb-8">
                    Job Application Email Builder
                </p>
                <h1 class="text-6xl md:text-8xl lg:text-9xl font-black leading-none tracking-tight mb-8 max-w-5xl">
                    Email lamaran yang bikin HR
                    <span class="text-[#FF5C28]"> berhenti</span>
                    scroll.
                </h1>
                <p class="text-lg md:text-xl text-white/50 max-w-xl mb-12 leading-relaxed">
                    Designed HTML templates, filled from your CV, previewed as Gmail renders them, sent from your own address.
                </p>
                <div class="flex flex-wrap gap-4 items-center">
                    <Link
                        :href="$page.props.auth.user ? dashboard() : register()"
                        class="inline-flex items-center gap-2 px-8 py-4 bg-[#FF5C28] text-white font-bold text-base rounded-md hover:bg-[#e04d1f] transition-colors duration-200"
                    >
                        {{ $page.props.auth.user ? 'Go to dashboard' : 'Coba gratis sekarang' }}
                        <ArrowRight class="size-4" />
                    </Link>
                    <Link
                        href="/tutorial"
                        class="inline-flex items-center gap-2 px-8 py-4 text-white/60 font-semibold text-base hover:text-white transition-colors duration-200"
                    >
                        Lihat cara kerja →
                    </Link>
                </div>
                <p class="mt-6 text-white/30 text-sm">No credit card. AI drafting included.</p>
            </div>
        </section>

        <!-- HOW IT WORKS -->
        <section class="bg-white text-[#0f0f0f] py-24 px-6">
            <div class="mx-auto max-w-6xl">
                <p class="text-[#FF5C28] text-sm font-bold uppercase tracking-[0.2em] mb-4">Process</p>
                <h2 class="text-5xl md:text-6xl font-black tracking-tight mb-20">How it works.</h2>

                <div class="grid gap-12 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="step in steps"
                        :key="step.num"
                        class="group"
                    >
                        <div class="text-7xl font-black text-[#0f0f0f]/8 mb-4 leading-none select-none group-hover:text-[#FF5C28]/20 transition-colors duration-300">
                            {{ step.num }}
                        </div>
                        <div class="flex items-center gap-2 mb-3">
                            <component :is="step.icon" class="size-4 text-[#FF5C28]" />
                            <h3 class="font-bold text-base">{{ step.title }}</h3>
                        </div>
                        <p class="text-[#0f0f0f]/50 text-sm leading-relaxed">{{ step.body }}</p>
                    </div>
                </div>

                <div class="mt-16 pt-12 border-t border-[#0f0f0f]/10">
                    <Link
                        href="/tutorial"
                        class="inline-flex items-center gap-2 text-sm font-bold text-[#0f0f0f] hover:text-[#FF5C28] transition-colors duration-200 uppercase tracking-wider"
                    >
                        Full tutorial guide <ArrowRight class="size-4" />
                    </Link>
                </div>
            </div>
        </section>

        <!-- FEATURES -->
        <section class="bg-[#f5f5f3] text-[#0f0f0f] py-24 px-6">
            <div class="mx-auto max-w-6xl">
                <p class="text-[#FF5C28] text-sm font-bold uppercase tracking-[0.2em] mb-4">Features</p>
                <h2 class="text-5xl md:text-6xl font-black tracking-tight mb-20">Built different.</h2>

                <div class="grid gap-16 sm:grid-cols-2">
                    <div
                        v-for="feat in features"
                        :key="feat.title"
                        class="group"
                    >
                        <component :is="feat.icon" class="size-6 text-[#FF5C28] mb-6" />
                        <h3 class="text-2xl font-black mb-3 group-hover:text-[#FF5C28] transition-colors duration-200">{{ feat.title }}</h3>
                        <p class="text-[#0f0f0f]/50 leading-relaxed">{{ feat.body }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- WHY GMAIL SECTION -->
        <section class="bg-[#0f0f0f] text-white py-24 px-6">
            <div class="mx-auto max-w-6xl">
                <p class="text-[#FF5C28] text-sm font-bold uppercase tracking-[0.2em] mb-4">Why Gmail, not us</p>
                <div class="border-l-4 border-[#FF5C28] pl-8 max-w-4xl">
                    <p class="text-3xl md:text-4xl font-black leading-tight mb-8">
                        "Lamaran dari server orang lain sering masuk spam. Dari Gmailmu sendiri? Recruiter langsung baca."
                    </p>
                    <p class="text-white/40 leading-relaxed max-w-2xl">
                        Email yang dikirim dari server pihak ketiga membawa domain mereka di header — dan sering dianggap spam.
                        Dengan menempelkan HTML ke Gmail-mu sendiri, pesan datang dari alamat aslimu, balasan masuk langsung ke inbox,
                        dan thread tersimpan di folder Sent seperti email biasa.
                    </p>
                </div>
            </div>
        </section>

        <!-- TWO MODES -->
        <section class="bg-white text-[#0f0f0f] py-24 px-6">
            <div class="mx-auto max-w-6xl">
                <p class="text-[#FF5C28] text-sm font-bold uppercase tracking-[0.2em] mb-4">Two modes</p>
                <h2 class="text-5xl md:text-6xl font-black tracking-tight mb-16">Your call.</h2>

                <div class="grid gap-0 sm:grid-cols-2 border border-[#0f0f0f]/10">
                    <div class="p-12 border-b sm:border-b-0 sm:border-r border-[#0f0f0f]/10 group hover:bg-[#0f0f0f] hover:text-white transition-colors duration-300">
                        <PencilLine class="size-6 mb-6 text-[#FF5C28]" />
                        <h3 class="text-2xl font-black mb-4">Tulis sendiri</h3>
                        <p class="text-[#0f0f0f]/50 leading-relaxed group-hover:text-white/50 transition-colors duration-300">
                            Setiap template terbagi jadi field-field sederhana. Ketik kata-katamu dan email terbentuk langsung di sebelahmu.
                        </p>
                    </div>
                    <div class="p-12 group hover:bg-[#0f0f0f] hover:text-white transition-colors duration-300">
                        <Sparkles class="size-6 mb-6 text-[#FF5C28]" />
                        <h3 class="text-2xl font-black mb-4">Biarkan AI draft</h3>
                        <p class="text-[#0f0f0f]/50 leading-relaxed group-hover:text-white/50 transition-colors duration-300">
                            Paste job posting, AI menulis tiap field berdasarkan CV aslimu — lalu kamu edit setiap baris sebelum dikirim.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA BANNER -->
        <section class="bg-[#FF5C28] py-24 px-6">
            <div class="mx-auto max-w-6xl flex flex-col md:flex-row items-center justify-between gap-8">
                <h2 class="text-4xl md:text-5xl font-black text-white leading-tight max-w-xl">
                    Siap bikin lamaran yang diingat recruiter?
                </h2>
                <Link
                    :href="$page.props.auth.user ? dashboard() : register()"
                    class="shrink-0 inline-flex items-center gap-2 px-8 py-4 bg-white text-[#FF5C28] font-black text-base rounded-md hover:bg-white/90 transition-colors duration-200 whitespace-nowrap"
                >
                    {{ $page.props.auth.user ? 'Go to dashboard' : 'Mulai gratis' }}
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
                    <Link href="/tutorial" class="hover:text-white transition-colors duration-200">Tutorial</Link>
                    <Link :href="login()" class="hover:text-white transition-colors duration-200">Log in</Link>
                    <Link :href="register()" class="hover:text-white transition-colors duration-200">Register</Link>
                </div>
                <p class="text-white/20 text-xs">© 2025 ApplyMail. Sent from your Gmail.</p>
            </div>
        </footer>

    </div>
</template>

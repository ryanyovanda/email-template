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

    <div class="min-h-screen bg-[#0f0f0f] font-sans text-white">
        <!-- NAV -->
        <header
            class="fixed top-0 right-0 left-0 z-50 border-b border-white/5 bg-[#0f0f0f]/90 backdrop-blur-sm"
        >
            <div
                class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4"
            >
                <div class="flex items-center gap-2">
                    <Mail class="size-5 text-[#FF5C28]" />
                    <span class="text-base font-bold tracking-tight"
                        >ApplyMail</span
                    >
                </div>

                <nav class="flex items-center gap-1 text-sm">
                    <Link
                        href="/tutorial"
                        class="px-4 py-2 text-white/60 transition-colors duration-200 hover:text-white"
                    >
                        Tutorial
                    </Link>
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="rounded-md bg-white px-4 py-2 font-semibold text-[#0f0f0f] transition-colors duration-200 hover:bg-white/90"
                    >
                        Dashboard
                    </Link>
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
        <section
            class="flex min-h-screen flex-col justify-center bg-[#0f0f0f] px-6 pt-24 pb-16"
        >
            <div class="mx-auto w-full max-w-6xl">
                <p
                    class="mb-8 text-sm font-bold tracking-[0.2em] text-[#FF5C28] uppercase"
                >
                    Job Application Email Builder
                </p>
                <h1
                    class="mb-8 max-w-5xl text-6xl leading-none font-black tracking-tight md:text-8xl lg:text-9xl"
                >
                    Email lamaran yang bikin HR
                    <span class="text-[#FF5C28]"> berhenti</span>
                    scroll.
                </h1>
                <p
                    class="mb-12 max-w-xl text-lg leading-relaxed text-white/50 md:text-xl"
                >
                    Designed HTML templates, filled from your CV, previewed as
                    Gmail renders them, sent from your own address.
                </p>
                <div class="flex flex-wrap items-center gap-4">
                    <Link
                        :href="$page.props.auth.user ? dashboard() : register()"
                        class="inline-flex items-center gap-2 rounded-md bg-[#FF5C28] px-8 py-4 text-base font-bold text-white transition-colors duration-200 hover:bg-[#e04d1f]"
                    >
                        {{
                            $page.props.auth.user
                                ? 'Go to dashboard'
                                : 'Coba gratis sekarang'
                        }}
                        <ArrowRight class="size-4" />
                    </Link>
                    <Link
                        href="/tutorial"
                        class="inline-flex items-center gap-2 px-8 py-4 text-base font-semibold text-white/60 transition-colors duration-200 hover:text-white"
                    >
                        Lihat cara kerja →
                    </Link>
                </div>
                <p class="mt-6 text-sm text-white/30">
                    No credit card. AI drafting included.
                </p>
            </div>
        </section>

        <!-- HOW IT WORKS -->
        <section class="bg-white px-6 py-24 text-[#0f0f0f]">
            <div class="mx-auto max-w-6xl">
                <p
                    class="mb-4 text-sm font-bold tracking-[0.2em] text-[#FF5C28] uppercase"
                >
                    Process
                </p>
                <h2
                    class="mb-20 text-5xl font-black tracking-tight md:text-6xl"
                >
                    How it works.
                </h2>

                <div class="grid gap-12 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="step in steps" :key="step.num" class="group">
                        <div
                            class="mb-4 text-7xl leading-none font-black text-[#0f0f0f]/8 transition-colors duration-300 select-none group-hover:text-[#FF5C28]/20"
                        >
                            {{ step.num }}
                        </div>
                        <div class="mb-3 flex items-center gap-2">
                            <component
                                :is="step.icon"
                                class="size-4 text-[#FF5C28]"
                            />
                            <h3 class="text-base font-bold">
                                {{ step.title }}
                            </h3>
                        </div>
                        <p class="text-sm leading-relaxed text-[#0f0f0f]/50">
                            {{ step.body }}
                        </p>
                    </div>
                </div>

                <div class="mt-16 border-t border-[#0f0f0f]/10 pt-12">
                    <Link
                        href="/tutorial"
                        class="inline-flex items-center gap-2 text-sm font-bold tracking-wider text-[#0f0f0f] uppercase transition-colors duration-200 hover:text-[#FF5C28]"
                    >
                        Full tutorial guide <ArrowRight class="size-4" />
                    </Link>
                </div>
            </div>
        </section>

        <!-- FEATURES -->
        <section class="bg-[#f5f5f3] px-6 py-24 text-[#0f0f0f]">
            <div class="mx-auto max-w-6xl">
                <p
                    class="mb-4 text-sm font-bold tracking-[0.2em] text-[#FF5C28] uppercase"
                >
                    Features
                </p>
                <h2
                    class="mb-20 text-5xl font-black tracking-tight md:text-6xl"
                >
                    Built different.
                </h2>

                <div class="grid gap-16 sm:grid-cols-2">
                    <div
                        v-for="feat in features"
                        :key="feat.title"
                        class="group"
                    >
                        <component
                            :is="feat.icon"
                            class="mb-6 size-6 text-[#FF5C28]"
                        />
                        <h3
                            class="mb-3 text-2xl font-black transition-colors duration-200 group-hover:text-[#FF5C28]"
                        >
                            {{ feat.title }}
                        </h3>
                        <p class="leading-relaxed text-[#0f0f0f]/50">
                            {{ feat.body }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- WHY GMAIL SECTION -->
        <section class="bg-[#0f0f0f] px-6 py-24 text-white">
            <div class="mx-auto max-w-6xl">
                <p
                    class="mb-4 text-sm font-bold tracking-[0.2em] text-[#FF5C28] uppercase"
                >
                    Why Gmail, not us
                </p>
                <div class="max-w-4xl border-l-4 border-[#FF5C28] pl-8">
                    <p
                        class="mb-8 text-3xl leading-tight font-black md:text-4xl"
                    >
                        "Lamaran dari server orang lain sering masuk spam. Dari
                        Gmailmu sendiri? Recruiter langsung baca."
                    </p>
                    <p class="max-w-2xl leading-relaxed text-white/40">
                        Email yang dikirim dari server pihak ketiga membawa
                        domain mereka di header — dan sering dianggap spam.
                        Dengan menempelkan HTML ke Gmail-mu sendiri, pesan
                        datang dari alamat aslimu, balasan masuk langsung ke
                        inbox, dan thread tersimpan di folder Sent seperti email
                        biasa.
                    </p>
                </div>
            </div>
        </section>

        <!-- TWO MODES -->
        <section class="bg-white px-6 py-24 text-[#0f0f0f]">
            <div class="mx-auto max-w-6xl">
                <p
                    class="mb-4 text-sm font-bold tracking-[0.2em] text-[#FF5C28] uppercase"
                >
                    Two modes
                </p>
                <h2
                    class="mb-16 text-5xl font-black tracking-tight md:text-6xl"
                >
                    Your call.
                </h2>

                <div
                    class="grid gap-0 border border-[#0f0f0f]/10 sm:grid-cols-2"
                >
                    <div
                        class="group border-b border-[#0f0f0f]/10 p-12 transition-colors duration-300 hover:bg-[#0f0f0f] hover:text-white sm:border-r sm:border-b-0"
                    >
                        <PencilLine class="mb-6 size-6 text-[#FF5C28]" />
                        <h3 class="mb-4 text-2xl font-black">Tulis sendiri</h3>
                        <p
                            class="leading-relaxed text-[#0f0f0f]/50 transition-colors duration-300 group-hover:text-white/50"
                        >
                            Setiap template terbagi jadi field-field sederhana.
                            Ketik kata-katamu dan email terbentuk langsung di
                            sebelahmu.
                        </p>
                    </div>
                    <div
                        class="group p-12 transition-colors duration-300 hover:bg-[#0f0f0f] hover:text-white"
                    >
                        <Sparkles class="mb-6 size-6 text-[#FF5C28]" />
                        <h3 class="mb-4 text-2xl font-black">
                            Biarkan AI draft
                        </h3>
                        <p
                            class="leading-relaxed text-[#0f0f0f]/50 transition-colors duration-300 group-hover:text-white/50"
                        >
                            Paste job posting, AI menulis tiap field berdasarkan
                            CV aslimu — lalu kamu edit setiap baris sebelum
                            dikirim.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA BANNER -->
        <section class="bg-[#FF5C28] px-6 py-24">
            <div
                class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-8 md:flex-row"
            >
                <h2
                    class="max-w-xl text-4xl leading-tight font-black text-white md:text-5xl"
                >
                    Siap bikin lamaran yang diingat recruiter?
                </h2>
                <Link
                    :href="$page.props.auth.user ? dashboard() : register()"
                    class="inline-flex shrink-0 items-center gap-2 rounded-md bg-white px-8 py-4 text-base font-black whitespace-nowrap text-[#FF5C28] transition-colors duration-200 hover:bg-white/90"
                >
                    {{
                        $page.props.auth.user
                            ? 'Go to dashboard'
                            : 'Mulai gratis'
                    }}
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
                        href="/tutorial"
                        class="transition-colors duration-200 hover:text-white"
                        >Tutorial</Link
                    >
                    <Link
                        :href="login()"
                        class="transition-colors duration-200 hover:text-white"
                        >Log in</Link
                    >
                    <Link
                        :href="register()"
                        class="transition-colors duration-200 hover:text-white"
                        >Register</Link
                    >
                </div>
                <p class="text-xs text-white/20">
                    © 2025 ApplyMail. Sent from your Gmail.
                </p>
            </div>
        </footer>
    </div>
</template>

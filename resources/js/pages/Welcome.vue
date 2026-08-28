<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Bot,
    Check,
    CreditCard,
    Eye,
    LayoutTemplate,
    Lock,
    Mail,
    PencilLine,
    Smartphone,
    Sparkles,
    User,
    Zap,
} from '@lucide/vue';
import { dashboard, login, register } from '@/routes';

const steps = [
    {
        num: '01',
        icon: User,
        title: 'Setup profil & CV',
        body: 'Upload CV-mu, isi nama dan kontak. Kami ekstrak teksnya biar kamu tidak perlu ketik ulang hal yang sama berkali-kali.',
    },
    {
        num: '02',
        icon: LayoutTemplate,
        title: 'Pilih template',
        body: 'Template HTML berbasis tabel, inline-styled, yang tampil sama persis di Gmail, Outlook, dan Apple Mail.',
    },
    {
        num: '03',
        icon: Sparkles,
        title: 'Isi atau AI draft',
        body: 'Paste job posting, AI menulis setiap field berdasarkan CV aslimu — lalu kamu edit sebelum dikirim.',
    },
    {
        num: '04',
        icon: Mail,
        title: 'Copy & kirim',
        body: 'Paste ke Gmail-mu sendiri. Terkirim dari alamat aslimu. Balasan masuk langsung ke inbox kamu.',
    },
];
</script>

<template>
    <Head title="Email lamaran yang bikin HR berhenti scroll — ApplyMail" />

    <div
        class="min-h-screen font-sans"
        style="background: #f5f5f0; color: #1a1a1a"
    >
        <!-- NAV -->
        <header
            class="fixed top-0 right-0 left-0 z-50 border-b"
            style="
                background: rgba(245, 245, 240, 0.85);
                backdrop-filter: blur(12px);
                border-color: rgba(0, 0, 0, 0.06);
            "
        >
            <div
                class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4"
            >
                <div class="flex items-center gap-2">
                    <div
                        class="flex size-7 items-center justify-center rounded-lg"
                        style="background: #8b5cf6"
                    >
                        <Mail class="size-4 text-white" />
                    </div>
                    <span class="text-base font-bold tracking-tight"
                        >ApplyMail</span
                    >
                </div>

                <nav class="flex items-center gap-1 text-sm">
                    <Link
                        href="/tutorial"
                        class="px-4 py-2 font-medium transition-colors duration-200"
                        style="color: #666"
                    >
                        Tutorial
                    </Link>
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="rounded-full px-5 py-2 text-sm font-semibold text-white transition-opacity duration-200 hover:opacity-90"
                        style="background: #8b5cf6"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="px-4 py-2 font-medium transition-colors duration-200"
                            style="color: #666"
                            >Log in</Link
                        >
                        <Link
                            :href="register()"
                            class="ml-1 rounded-full px-5 py-2 text-sm font-semibold text-white transition-opacity duration-200 hover:opacity-90"
                            style="background: #8b5cf6"
                            >Get started</Link
                        >
                    </template>
                </nav>
            </div>
        </header>

        <!-- HERO -->
        <section
            class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden px-6 pt-20"
        >
            <!-- Subtle radial glow -->
            <div
                class="pointer-events-none absolute inset-0"
                style="
                    background: radial-gradient(
                        ellipse 80% 60% at 50% 0%,
                        rgba(139, 92, 246, 0.08) 0%,
                        transparent 70%
                    );
                "
            ></div>

            <div class="relative z-10 mx-auto max-w-5xl text-center">
                <!-- Pill badge -->
                <div class="mb-8 inline-flex items-center gap-2">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-sm font-medium text-white"
                        style="background: #8b5cf6"
                    >
                        <Sparkles class="size-3.5" />
                        AI-powered email lamaran
                    </span>
                </div>

                <!-- Headline -->
                <h1
                    class="mb-6 text-6xl leading-none font-black tracking-tight text-gray-900 lg:text-8xl"
                >
                    Email lamaran yang<br />
                    bikin HR berhenti<br />
                    <span style="color: #8b5cf6">scroll.</span>
                </h1>

                <!-- Subtext -->
                <p
                    class="mx-auto mb-10 max-w-lg text-xl leading-relaxed"
                    style="color: #888"
                >
                    Buat email lamaran HTML yang elegan dari CV kamu, preview
                    persis kayak di Gmail, kirim dari akun Gmail kamu sendiri.
                </p>

                <!-- CTA buttons -->
                <div
                    class="mb-8 flex flex-wrap items-center justify-center gap-4"
                >
                    <Link
                        :href="$page.props.auth.user ? dashboard() : register()"
                        class="inline-flex items-center gap-2 rounded-xl px-7 py-3.5 text-base font-bold text-white transition-opacity duration-200 hover:opacity-90"
                        style="background: #8b5cf6"
                    >
                        Mulai gratis
                        <ArrowRight class="size-4" />
                    </Link>
                    <Link
                        href="/tutorial"
                        class="inline-flex items-center gap-2 rounded-xl border px-7 py-3.5 text-base font-semibold transition-colors duration-200"
                        style="
                            border-color: rgba(0, 0, 0, 0.15);
                            color: #555;
                            background: rgba(255, 255, 255, 0.6);
                        "
                    >
                        Lihat tutorial
                    </Link>
                </div>

                <!-- Social proof -->
                <div
                    class="flex flex-wrap items-center justify-center gap-6 text-sm font-medium"
                    style="color: #999"
                >
                    <span class="flex items-center gap-1.5">
                        <Check class="size-4" style="color: #8b5cf6" />
                        Gratis selamanya
                    </span>
                    <span class="flex items-center gap-1.5">
                        <Check class="size-4" style="color: #8b5cf6" />
                        Tidak perlu kartu kredit
                    </span>
                    <span class="flex items-center gap-1.5">
                        <Check class="size-4" style="color: #8b5cf6" />
                        AI drafting included
                    </span>
                </div>
            </div>

            <!-- Hero mockup card — CSS-only email preview -->
            <div class="relative mx-auto mt-20 w-full max-w-3xl px-4">
                <div
                    class="overflow-hidden rounded-2xl"
                    style="
                        background: rgba(255, 255, 255, 0.7);
                        backdrop-filter: blur(20px);
                        border: 1px solid rgba(255, 255, 255, 0.6);
                        box-shadow:
                            0 32px 80px rgba(0, 0, 0, 0.1),
                            0 0 0 1px rgba(0, 0, 0, 0.04);
                    "
                >
                    <!-- Mock email app chrome -->
                    <div
                        class="flex items-center gap-2 border-b px-5 py-3.5"
                        style="
                            background: rgba(255, 255, 255, 0.8);
                            border-color: rgba(0, 0, 0, 0.07);
                        "
                    >
                        <div class="size-3 rounded-full bg-red-400"></div>
                        <div class="size-3 rounded-full bg-yellow-400"></div>
                        <div class="size-3 rounded-full bg-green-400"></div>
                        <div
                            class="mx-auto rounded-md px-24 py-1 text-xs"
                            style="background: rgba(0, 0, 0, 0.05); color: #888"
                        >
                            mail.google.com
                        </div>
                    </div>

                    <!-- Email content preview -->
                    <div class="flex">
                        <!-- Sidebar -->
                        <div
                            class="hidden w-48 shrink-0 border-r p-4 md:block"
                            style="
                                background: rgba(255, 255, 255, 0.5);
                                border-color: rgba(0, 0, 0, 0.06);
                            "
                        >
                            <div
                                class="mb-3 h-2.5 w-16 rounded-full"
                                style="background: rgba(0, 0, 0, 0.08)"
                            ></div>
                            <div class="space-y-2">
                                <div
                                    class="flex items-center gap-2 rounded-lg px-2 py-1.5"
                                    style="background: rgba(139, 92, 246, 0.1)"
                                >
                                    <div
                                        class="size-2 rounded-full"
                                        style="background: #8b5cf6"
                                    ></div>
                                    <div
                                        class="h-2 w-12 rounded-full"
                                        style="
                                            background: rgba(139, 92, 246, 0.4);
                                        "
                                    ></div>
                                </div>
                                <div
                                    v-for="i in 4"
                                    :key="i"
                                    class="flex items-center gap-2 px-2 py-1.5"
                                >
                                    <div
                                        class="size-2 rounded-full"
                                        style="background: rgba(0, 0, 0, 0.1)"
                                    ></div>
                                    <div
                                        class="h-2 rounded-full"
                                        :style="`width: ${[48, 40, 56, 36][i - 1]}px; background: rgba(0,0,0,0.07)`"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- Email body -->
                        <div class="flex-1 p-6">
                            <!-- Email header -->
                            <div
                                class="mb-5 border-b pb-4"
                                style="border-color: rgba(0, 0, 0, 0.06)"
                            >
                                <div
                                    class="mb-1.5 h-4 w-64 rounded-full"
                                    style="background: rgba(0, 0, 0, 0.12)"
                                ></div>
                                <div class="flex items-center gap-2">
                                    <div
                                        class="size-6 rounded-full"
                                        style="
                                            background: linear-gradient(
                                                135deg,
                                                #8b5cf6,
                                                #3b82f6
                                            );
                                        "
                                    ></div>
                                    <div
                                        class="h-2.5 w-32 rounded-full"
                                        style="background: rgba(0, 0, 0, 0.08)"
                                    ></div>
                                    <div
                                        class="ml-auto h-2 w-16 rounded-full"
                                        style="background: rgba(0, 0, 0, 0.05)"
                                    ></div>
                                </div>
                            </div>

                            <!-- Email HTML template visualization -->
                            <div class="space-y-3">
                                <!-- Header block -->
                                <div
                                    class="rounded-xl p-4"
                                    style="
                                        background: linear-gradient(
                                            135deg,
                                            #8b5cf6 0%,
                                            #3b82f6 100%
                                        );
                                    "
                                >
                                    <div class="mb-2 flex items-center gap-2">
                                        <div
                                            class="size-8 rounded-full bg-white/30"
                                        ></div>
                                        <div class="space-y-1">
                                            <div
                                                class="h-2.5 w-24 rounded-full bg-white/80"
                                            ></div>
                                            <div
                                                class="h-1.5 w-16 rounded-full bg-white/50"
                                            ></div>
                                        </div>
                                    </div>
                                    <div
                                        class="h-1.5 w-full rounded-full bg-white/20"
                                    ></div>
                                </div>

                                <!-- Content blocks -->
                                <div
                                    class="space-y-2 rounded-xl bg-white p-4"
                                    style="
                                        border: 1px solid rgba(0, 0, 0, 0.06);
                                    "
                                >
                                    <div
                                        class="h-2.5 w-3/4 rounded-full"
                                        style="background: rgba(0, 0, 0, 0.1)"
                                    ></div>
                                    <div
                                        class="h-2 w-full rounded-full"
                                        style="background: rgba(0, 0, 0, 0.06)"
                                    ></div>
                                    <div
                                        class="h-2 w-5/6 rounded-full"
                                        style="background: rgba(0, 0, 0, 0.06)"
                                    ></div>
                                    <div
                                        class="h-2 w-full rounded-full"
                                        style="background: rgba(0, 0, 0, 0.06)"
                                    ></div>
                                    <div
                                        class="h-2 w-2/3 rounded-full"
                                        style="background: rgba(0, 0, 0, 0.06)"
                                    ></div>
                                </div>

                                <!-- Skills chips row -->
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="(chip, i) in [
                                            'Vue.js',
                                            'Laravel',
                                            'TypeScript',
                                            'Tailwind',
                                        ]"
                                        :key="chip"
                                        class="rounded-full px-3 py-1 text-xs font-medium text-white"
                                        :style="`background: ${['#8B5CF6', '#3B82F6', '#10B981', '#F59E0B'][i]}`"
                                        >{{ chip }}</span
                                    >
                                </div>

                                <!-- CTA button block -->
                                <div class="flex justify-center pt-1">
                                    <div
                                        class="rounded-lg px-8 py-2.5"
                                        style="background: #8b5cf6"
                                    >
                                        <div
                                            class="h-2.5 w-20 rounded-full bg-white/80"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom fade -->
                <div
                    class="pointer-events-none absolute inset-x-0 bottom-0 h-24 rounded-b-2xl"
                    style="
                        background: linear-gradient(
                            to top,
                            #f5f5f0 0%,
                            transparent 100%
                        );
                    "
                ></div>
            </div>
        </section>

        <!-- LOGO STRIP -->
        <section class="py-16">
            <div class="mx-auto max-w-6xl px-6">
                <div class="flex flex-wrap items-center justify-center gap-8">
                    <p class="text-sm font-medium" style="color: #bbb">
                        Dipakai oleh job seekers di
                    </p>
                    <div class="flex flex-wrap items-center gap-6">
                        <span
                            v-for="brand in [
                                'Tokopedia',
                                'Gojek',
                                'Traveloka',
                                'Shopee',
                                'Grab',
                            ]"
                            :key="brand"
                            class="text-sm font-semibold"
                            style="color: #ccc"
                            >{{ brand }}</span
                        >
                    </div>
                </div>
            </div>
        </section>

        <!-- HOW IT WORKS — yellow accent -->
        <section class="px-6 py-28">
            <div class="mx-auto max-w-6xl">
                <!-- Section label -->
                <div class="mb-6 flex items-center">
                    <span
                        class="inline-flex items-center rounded-full px-4 py-1.5 text-sm font-semibold"
                        style="
                            background: rgba(245, 158, 11, 0.12);
                            color: #f59e0b;
                        "
                    >
                        <Zap class="mr-1.5 size-3.5" />
                        Cara kerja
                    </span>
                </div>

                <h2
                    class="mb-16 text-5xl leading-tight font-black tracking-tight text-gray-900 md:text-6xl"
                >
                    Dari CV ke email cantik,<br />
                    dalam 3 menit.
                </h2>

                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="step in steps"
                        :key="step.num"
                        class="group relative overflow-hidden rounded-2xl p-7 transition-transform duration-300 hover:-translate-y-1"
                        style="
                            background: rgba(255, 255, 255, 0.65);
                            backdrop-filter: blur(16px);
                            border: 1px solid rgba(255, 255, 255, 0.5);
                            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.05);
                        "
                    >
                        <!-- Step number -->
                        <div
                            class="mb-4 text-6xl leading-none font-black select-none"
                            style="color: rgba(245, 158, 11, 0.2)"
                        >
                            {{ step.num }}
                        </div>

                        <!-- Icon -->
                        <div
                            class="mb-4 inline-flex size-11 items-center justify-center rounded-xl"
                            style="background: rgba(245, 158, 11, 0.12)"
                        >
                            <component
                                :is="step.icon"
                                class="size-5"
                                style="color: #f59e0b"
                            />
                        </div>

                        <h3 class="mb-2 text-base font-bold text-gray-900">
                            {{ step.title }}
                        </h3>
                        <p class="text-sm leading-relaxed" style="color: #888">
                            {{ step.body }}
                        </p>
                    </div>
                </div>

                <div class="mt-12">
                    <Link
                        href="/tutorial"
                        class="inline-flex items-center gap-2 text-sm font-semibold transition-opacity duration-200 hover:opacity-70"
                        style="color: #f59e0b"
                    >
                        Panduan lengkap <ArrowRight class="size-4" />
                    </Link>
                </div>
            </div>
        </section>

        <!-- FEATURES GRID — blue accent, bento-style -->
        <section
            class="px-6 py-28"
            style="background: rgba(255, 255, 255, 0.4)"
        >
            <div class="mx-auto max-w-6xl">
                <!-- Section label -->
                <div class="mb-6 flex items-center">
                    <span
                        class="inline-flex items-center rounded-full px-4 py-1.5 text-sm font-semibold"
                        style="
                            background: rgba(59, 130, 246, 0.1);
                            color: #3b82f6;
                        "
                    >
                        <Sparkles class="mr-1.5 size-3.5" />
                        Fitur
                    </span>
                </div>

                <h2
                    class="mb-14 text-5xl font-black tracking-tight text-gray-900 md:text-6xl"
                >
                    Semua yang kamu butuhkan
                </h2>

                <!-- Bento grid -->
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <!-- AI Draft — spans 2 cols -->
                    <div
                        class="col-span-2 overflow-hidden rounded-2xl p-8 transition-transform duration-300 hover:-translate-y-1"
                        style="
                            background: rgba(255, 255, 255, 0.65);
                            backdrop-filter: blur(16px);
                            border: 1px solid rgba(255, 255, 255, 0.5);
                            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.05);
                        "
                    >
                        <div
                            class="mb-5 inline-flex size-12 items-center justify-center rounded-xl"
                            style="background: rgba(59, 130, 246, 0.12)"
                        >
                            <Bot class="size-6" style="color: #3b82f6" />
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-gray-900">
                            AI Draft dari job posting
                        </h3>
                        <p class="text-sm leading-relaxed" style="color: #888">
                            Paste lowongan, dapat draft grounded dari CV aslimu.
                            Zero hallucination — AI hanya pakai apa yang ada di
                            CV-mu.
                        </p>
                    </div>

                    <!-- Gmail preview — spans 2 rows -->
                    <div
                        class="col-span-2 row-span-2 overflow-hidden rounded-2xl p-8 transition-transform duration-300 hover:-translate-y-1 lg:col-span-1"
                        style="
                            background: rgba(255, 255, 255, 0.65);
                            backdrop-filter: blur(16px);
                            border: 1px solid rgba(255, 255, 255, 0.5);
                            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.05);
                        "
                    >
                        <div
                            class="mb-5 inline-flex size-12 items-center justify-center rounded-xl"
                            style="background: rgba(16, 185, 129, 0.12)"
                        >
                            <Eye class="size-6" style="color: #10b981" />
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-gray-900">
                            Preview persis kayak Gmail
                        </h3>
                        <p
                            class="mb-6 text-sm leading-relaxed"
                            style="color: #888"
                        >
                            Renderer real-time yang tunjukkan email-mu persis
                            seperti recruiter lihat — desktop dan mobile.
                        </p>
                        <!-- Mini preview mockup -->
                        <div
                            class="overflow-hidden rounded-xl p-4"
                            style="
                                background: rgba(16, 185, 129, 0.06);
                                border: 1px solid rgba(16, 185, 129, 0.15);
                            "
                        >
                            <div class="mb-2 flex items-center gap-1.5">
                                <div
                                    class="h-2 w-24 rounded-full"
                                    style="background: rgba(16, 185, 129, 0.3)"
                                ></div>
                            </div>
                            <div class="space-y-1.5">
                                <div
                                    class="h-1.5 w-full rounded-full"
                                    style="background: rgba(0, 0, 0, 0.07)"
                                ></div>
                                <div
                                    class="h-1.5 w-4/5 rounded-full"
                                    style="background: rgba(0, 0, 0, 0.05)"
                                ></div>
                                <div
                                    class="h-1.5 w-full rounded-full"
                                    style="background: rgba(0, 0, 0, 0.05)"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile toggle -->
                    <div
                        class="col-span-2 overflow-hidden rounded-2xl p-7 transition-transform duration-300 hover:-translate-y-1 lg:col-span-1"
                        style="
                            background: rgba(255, 255, 255, 0.65);
                            backdrop-filter: blur(16px);
                            border: 1px solid rgba(255, 255, 255, 0.5);
                            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.05);
                        "
                    >
                        <div
                            class="mb-4 inline-flex size-11 items-center justify-center rounded-xl"
                            style="background: rgba(139, 92, 246, 0.12)"
                        >
                            <Smartphone class="size-5" style="color: #8b5cf6" />
                        </div>
                        <h3 class="mb-1.5 text-base font-bold text-gray-900">
                            Desktop & mobile toggle
                        </h3>
                        <p class="text-sm leading-relaxed" style="color: #888">
                            Cek tampilan di kedua mode sebelum kirim.
                        </p>
                    </div>

                    <!-- Template -->
                    <div
                        class="col-span-2 overflow-hidden rounded-2xl p-7 transition-transform duration-300 hover:-translate-y-1 lg:col-span-1"
                        style="
                            background: rgba(255, 255, 255, 0.65);
                            backdrop-filter: blur(16px);
                            border: 1px solid rgba(255, 255, 255, 0.5);
                            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.05);
                        "
                    >
                        <div
                            class="mb-4 inline-flex size-11 items-center justify-center rounded-xl"
                            style="background: rgba(245, 158, 11, 0.12)"
                        >
                            <LayoutTemplate
                                class="size-5"
                                style="color: #f59e0b"
                            />
                        </div>
                        <h3 class="mb-1.5 text-base font-bold text-gray-900">
                            Template elegan
                        </h3>
                        <p class="text-sm leading-relaxed" style="color: #888">
                            HTML email yang survive Gmail rendering di setiap
                            device.
                        </p>
                    </div>

                    <!-- Send from Gmail — spans 2 cols -->
                    <div
                        class="col-span-2 overflow-hidden rounded-2xl p-8 transition-transform duration-300 hover:-translate-y-1"
                        style="
                            background: rgba(255, 255, 255, 0.65);
                            backdrop-filter: blur(16px);
                            border: 1px solid rgba(255, 255, 255, 0.5);
                            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.05);
                        "
                    >
                        <div
                            class="mb-5 inline-flex size-12 items-center justify-center rounded-xl"
                            style="background: rgba(236, 72, 153, 0.12)"
                        >
                            <Lock class="size-6" style="color: #ec4899" />
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-gray-900">
                            Kirim dari Gmail kamu sendiri
                        </h3>
                        <p class="text-sm leading-relaxed" style="color: #888">
                            Lamaran terkirim dari alamat Gmail aslimu — bukan
                            server kami. Zero spam risk, rekruter balas langsung
                            ke inbox-mu.
                        </p>
                    </div>

                    <!-- Credits -->
                    <div
                        class="col-span-2 overflow-hidden rounded-2xl p-7 transition-transform duration-300 hover:-translate-y-1"
                        style="
                            background: rgba(255, 255, 255, 0.65);
                            backdrop-filter: blur(16px);
                            border: 1px solid rgba(255, 255, 255, 0.5);
                            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.05);
                        "
                    >
                        <div
                            class="mb-4 inline-flex size-11 items-center justify-center rounded-xl"
                            style="background: rgba(59, 130, 246, 0.12)"
                        >
                            <CreditCard class="size-5" style="color: #3b82f6" />
                        </div>
                        <h3 class="mb-1.5 text-base font-bold text-gray-900">
                            Sistem kredit transparan
                        </h3>
                        <p class="text-sm leading-relaxed" style="color: #888">
                            Bayar sesuai pemakaian. Tidak ada biaya tersembunyi.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- WHY GMAIL — green accent -->
        <section class="px-6 py-28" style="background: #ecece6">
            <div class="mx-auto max-w-6xl">
                <div class="mb-6 flex items-center">
                    <span
                        class="inline-flex items-center rounded-full px-4 py-1.5 text-sm font-semibold"
                        style="
                            background: rgba(16, 185, 129, 0.12);
                            color: #10b981;
                        "
                    >
                        <Mail class="mr-1.5 size-3.5" />
                        Kenapa Gmail
                    </span>
                </div>

                <div
                    class="max-w-3xl rounded-2xl p-10"
                    style="
                        border-left: 4px solid #10b981;
                        background: rgba(255, 255, 255, 0.5);
                        backdrop-filter: blur(12px);
                    "
                >
                    <p
                        class="mb-6 text-2xl leading-snug font-bold text-gray-900 md:text-3xl"
                    >
                        "Lamaran yang dikirim dari server orang lain sering
                        masuk spam. Kalau dikirim dari Gmail-mu sendiri,
                        recruiter balas langsung ke kamu — bukan ke kita."
                    </p>
                    <p class="text-base leading-relaxed" style="color: #777">
                        Email dari server pihak ketiga membawa domain mereka di
                        header — dan sering dianggap spam filter. Dengan paste
                        HTML ke Gmail-mu sendiri, pesan datang dari alamatmu
                        yang asli, balasan masuk ke inbox-mu, dan thread
                        tersimpan di Sent seperti email biasa.
                    </p>
                </div>
            </div>
        </section>

        <!-- TWO MODES — pink accent -->
        <section class="px-6 py-28">
            <div class="mx-auto max-w-6xl">
                <div class="mb-6 flex items-center">
                    <span
                        class="inline-flex items-center rounded-full px-4 py-1.5 text-sm font-semibold"
                        style="
                            background: rgba(236, 72, 153, 0.1);
                            color: #ec4899;
                        "
                    >
                        <Sparkles class="mr-1.5 size-3.5" />
                        Dua mode
                    </span>
                </div>

                <h2
                    class="mb-14 text-5xl font-black tracking-tight text-gray-900 md:text-6xl"
                >
                    Terserah kamu.
                </h2>

                <div class="grid gap-6 sm:grid-cols-2">
                    <!-- Manual mode -->
                    <div
                        class="group overflow-hidden rounded-2xl p-10 transition-transform duration-300 hover:-translate-y-1"
                        style="
                            background: rgba(255, 255, 255, 0.65);
                            backdrop-filter: blur(16px);
                            border: 1px solid rgba(255, 255, 255, 0.5);
                            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.05);
                        "
                    >
                        <div
                            class="mb-5 inline-flex size-12 items-center justify-center rounded-xl"
                            style="background: rgba(107, 114, 128, 0.1)"
                        >
                            <PencilLine class="size-6" style="color: #6b7280" />
                        </div>
                        <h3 class="mb-3 text-2xl font-black text-gray-900">
                            Manual mode
                        </h3>
                        <p class="leading-relaxed" style="color: #888">
                            Setiap template terbagi jadi field-field sederhana.
                            Ketik kata-katamu dan email terbentuk langsung di
                            sebelahmu — full control di tanganmu.
                        </p>
                    </div>

                    <!-- AI mode -->
                    <div
                        class="group overflow-hidden rounded-2xl p-10 transition-transform duration-300 hover:-translate-y-1"
                        style="
                            background: rgba(255, 255, 255, 0.65);
                            backdrop-filter: blur(16px);
                            border: 1px solid rgba(255, 255, 255, 0.5);
                            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.05);
                        "
                    >
                        <div class="mb-5 flex items-center justify-between">
                            <div
                                class="inline-flex size-12 items-center justify-center rounded-xl"
                                style="background: rgba(236, 72, 153, 0.12)"
                            >
                                <Sparkles
                                    class="size-6"
                                    style="color: #ec4899"
                                />
                            </div>
                            <span
                                class="rounded-full px-3 py-1 text-xs font-bold text-white"
                                style="background: #ec4899"
                            >
                                Populer ✨
                            </span>
                        </div>
                        <h3 class="mb-3 text-2xl font-black text-gray-900">
                            AI draft mode
                        </h3>
                        <p class="leading-relaxed" style="color: #888">
                            Paste job posting, AI menulis tiap field berdasarkan
                            CV aslimu — lalu kamu edit setiap baris sebelum
                            dikirim. Cepat, relevan, masuk akal.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA BANNER -->
        <section class="px-6 py-32" style="background: #0f0f0f">
            <div class="mx-auto max-w-3xl text-center">
                <h2
                    class="mb-6 text-5xl leading-tight font-black text-white md:text-6xl"
                >
                    Siap bikin email lamaran yang beda?
                </h2>
                <p class="mb-10 text-lg" style="color: #666">
                    Gratis selamanya. Tidak perlu kartu kredit. Langsung dari
                    browser-mu.
                </p>
                <Link
                    :href="$page.props.auth.user ? dashboard() : register()"
                    class="inline-flex items-center gap-2 rounded-xl px-8 py-4 text-base font-bold text-white transition-opacity duration-200 hover:opacity-90"
                    style="background: #8b5cf6"
                >
                    {{
                        $page.props.auth.user
                            ? 'Ke dashboard'
                            : 'Mulai gratis sekarang'
                    }}
                    <ArrowRight class="size-4" />
                </Link>
            </div>
        </section>

        <!-- FOOTER -->
        <footer
            class="border-t px-6 py-12"
            style="background: #0f0f0f; border-color: rgba(255, 255, 255, 0.06)"
        >
            <div class="mx-auto max-w-6xl">
                <div
                    class="mb-8 flex flex-col items-center justify-between gap-6 sm:flex-row"
                >
                    <!-- Logo -->
                    <div class="flex items-center gap-2">
                        <div
                            class="flex size-7 items-center justify-center rounded-lg"
                            style="background: #8b5cf6"
                        >
                            <Mail class="size-4 text-white" />
                        </div>
                        <span class="text-sm font-bold text-white"
                            >ApplyMail</span
                        >
                    </div>

                    <!-- Links -->
                    <div class="flex gap-6 text-sm" style="color: #555">
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
                </div>

                <div
                    class="border-t pt-8 text-center text-xs"
                    style="border-color: rgba(255, 255, 255, 0.06); color: #444"
                >
                    © 2025 ApplyMail. Sent from your Gmail.
                </div>
            </div>
        </footer>
    </div>
</template>

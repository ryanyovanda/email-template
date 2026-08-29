<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Check,
    Clipboard,
    Eye,
    LayoutTemplate,
    Lock,
    Mail,
    Menu,
    Send,
    Sparkles,
    User,
    Wand2,
    X,
    Zap,
} from '@lucide/vue';
import { onMounted, ref } from 'vue';
import ThreeBackground from '@/components/ThreeBackground.vue';
import { dashboard, login, register } from '@/routes';

const mobileMenuOpen = ref(false);

const glass = {
    background: 'rgba(255,255,255,0.08)',
    backdropFilter: 'blur(20px) saturate(180%)',
    WebkitBackdropFilter: 'blur(20px) saturate(180%)',
    border: '1px solid rgba(255,255,255,0.18)',
    boxShadow:
        '0 8px 32px rgba(0,0,0,0.2), inset 0 1px 0 rgba(255,255,255,0.25)',
};

const steps = [
    {
        num: '01',
        icon: User,
        title: 'Setup profil & CV',
        body: 'Upload CV-mu, isi nama dan kontak. Kami ekstrak teksnya biar kamu nggak perlu ketik ulang hal yang sama berkali-kali.',
    },
    {
        num: '02',
        icon: LayoutTemplate,
        title: 'Pilih template',
        body: 'Template HTML berbasis tabel, inline-styled, yang tampil sama persis di Gmail, Outlook, dan Apple Mail.',
    },
    {
        num: '03',
        icon: Wand2,
        title: 'Isi atau AI draft',
        body: 'Paste job posting, AI nulis tiap field berdasarkan CV aslimu — lalu kamu edit sebelum dikirim.',
    },
    {
        num: '04',
        icon: Send,
        title: 'Copy & kirim',
        body: 'Paste ke Gmail-mu sendiri. Terkirim dari alamat aslimu. Balasan masuk langsung ke inbox kamu.',
    },
];

const features = [
    {
        icon: Sparkles,
        title: 'AI drafting cerdas',
        body: 'Tempel lowongan, AI merangkai email yang nyambung sama CV kamu. Bukan template generik.',
    },
    {
        icon: Eye,
        title: 'Preview persis Gmail',
        body: 'Lihat hasil akhir sebelum kirim. Yang kamu lihat = yang HR lihat, sampai ke pixel.',
    },
    {
        icon: LayoutTemplate,
        title: 'Template email-safe',
        body: 'HTML berbasis tabel yang nggak rusak di client email manapun.',
    },
    {
        icon: Lock,
        title: 'Data kamu, milik kamu',
        body: 'CV dan data tersimpan aman. Dikirim lewat Gmail-mu sendiri, bukan server pihak ketiga.',
    },
    {
        icon: Zap,
        title: 'Cepat banget',
        body: 'Dari CV ke email siap kirim dalam hitungan menit, bukan jam.',
    },
    {
        icon: Mail,
        title: 'Kirim dari Gmail-mu',
        body: 'Alamat asli kamu, reputasi kamu. Balasan langsung ke inbox pribadi.',
    },
];

// Scroll-reveal — runs after mount, observes every [data-animate] element.
onMounted(() => {
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in-view');
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.15 },
    );

    document
        .querySelectorAll('[data-animate]')
        .forEach((el) => observer.observe(el));
});
</script>

<template>
    <Head title="Email lamaran yang bikin HR berhenti scroll — ApplyMail" />

    <div
        class="relative min-h-screen overflow-x-hidden font-sans text-white"
        style="
            background: linear-gradient(
                160deg,
                #1e1b4b 0%,
                #312e81 30%,
                #6d28d9 60%,
                #9d174d 100%
            );
        "
    >
        <ThreeBackground />

        <!-- NAV -->
        <header class="fixed top-0 right-0 left-0 z-50">
            <div
                class="mx-auto mt-3 flex max-w-6xl items-center justify-between rounded-2xl px-4 py-3 sm:mt-4 sm:px-5"
                :style="{ ...glass, marginLeft: '1rem', marginRight: '1rem' }"
            >
                <!-- Logo -->
                <div class="flex items-center gap-2">
                    <div
                        class="flex size-8 items-center justify-center rounded-xl"
                        style="
                            background: linear-gradient(
                                135deg,
                                #a855f7,
                                #ec4899
                            );
                            box-shadow: 0 4px 16px rgba(168, 85, 247, 0.5);
                        "
                    >
                        <Mail class="size-4 text-white" />
                    </div>
                    <span class="text-base font-bold tracking-tight text-white"
                        >ApplyMail</span
                    >
                </div>

                <!-- Desktop nav -->
                <nav class="hidden items-center gap-1 text-sm lg:flex">
                    <Link
                        href="/tutorial"
                        class="rounded-full px-4 py-2 font-medium text-white/80 transition-colors duration-200 hover:text-white"
                    >
                        Tutorial
                    </Link>
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="ml-1 rounded-full px-5 py-2 text-sm font-semibold text-white transition-transform duration-200 hover:scale-105"
                        style="
                            background: linear-gradient(
                                135deg,
                                #a855f7,
                                #ec4899
                            );
                            box-shadow: 0 4px 20px rgba(168, 85, 247, 0.5);
                        "
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-full px-4 py-2 font-medium text-white/80 transition-colors duration-200 hover:text-white"
                            >Log in</Link
                        >
                        <Link
                            :href="register()"
                            class="ml-1 rounded-full px-5 py-2 text-sm font-semibold text-white transition-transform duration-200 hover:scale-105"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    #a855f7,
                                    #ec4899
                                );
                                box-shadow: 0 4px 20px rgba(168, 85, 247, 0.5);
                            "
                            >Get started</Link
                        >
                    </template>
                </nav>

                <!-- Mobile: CTA + hamburger -->
                <div class="flex items-center gap-2 lg:hidden">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="rounded-full px-4 py-1.5 text-sm font-semibold text-white"
                        style="
                            background: linear-gradient(
                                135deg,
                                #a855f7,
                                #ec4899
                            );
                        "
                        >Dashboard</Link
                    >
                    <Link
                        v-else
                        :href="register()"
                        class="rounded-full px-4 py-1.5 text-sm font-semibold text-white"
                        style="
                            background: linear-gradient(
                                135deg,
                                #a855f7,
                                #ec4899
                            );
                        "
                        >Mulai gratis</Link
                    >
                    <button
                        class="flex size-9 items-center justify-center rounded-xl text-white"
                        style="background: rgba(255, 255, 255, 0.12)"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                    >
                        <X v-if="mobileMenuOpen" class="size-4" />
                        <Menu v-else class="size-4" />
                    </button>
                </div>
            </div>

            <!-- Mobile menu dropdown -->
            <div
                v-if="mobileMenuOpen"
                class="mx-4 mt-2 rounded-2xl px-3 py-3 lg:hidden"
                :style="glass"
            >
                <div class="flex flex-col gap-1 text-sm">
                    <Link
                        href="/tutorial"
                        class="rounded-xl px-4 py-3 font-medium text-white/90 hover:bg-white/10"
                        @click="mobileMenuOpen = false"
                        >Tutorial</Link
                    >
                    <Link
                        v-if="!$page.props.auth.user"
                        :href="login()"
                        class="rounded-xl px-4 py-3 font-medium text-white/90 hover:bg-white/10"
                        @click="mobileMenuOpen = false"
                        >Log in</Link
                    >
                </div>
            </div>
        </header>

        <!-- HERO -->
        <section
            class="relative flex min-h-screen flex-col items-center justify-center px-4 pt-28 pb-16 sm:px-6 sm:pt-32"
        >
            <div
                class="relative z-10 mx-auto max-w-5xl text-center"
                data-animate
            >
                <!-- Badge -->
                <div class="mb-6 inline-flex sm:mb-8">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-xs font-medium text-white sm:text-sm"
                        :style="glass"
                    >
                        <Sparkles class="size-3.5" style="color: #fbbf24" />
                        AI-powered email lamaran
                    </span>
                </div>

                <!-- Headline -->
                <h1
                    class="mb-5 text-4xl leading-[1.05] font-black tracking-tight text-white sm:mb-6 sm:text-5xl md:text-6xl lg:text-8xl"
                >
                    Email lamaran yang<br />
                    bikin HR berhenti<br />
                    <span
                        class="bg-clip-text text-transparent"
                        style="
                            background-image: linear-gradient(
                                100deg,
                                #fbbf24,
                                #f472b6 45%,
                                #a855f7 100%
                            );
                            -webkit-background-clip: text;
                            background-clip: text;
                        "
                        >scroll.</span
                    >
                </h1>

                <!-- Subtext -->
                <p
                    class="mx-auto mb-8 max-w-sm text-base leading-relaxed text-white/70 sm:mb-10 sm:max-w-xl sm:text-xl"
                >
                    Ubah CV + lowongan jadi email HTML yang elegan, preview
                    persis kayak di Gmail, lalu kirim dari akun Gmail kamu
                    sendiri.
                </p>

                <!-- CTA -->
                <div
                    class="mb-8 flex flex-col items-center justify-center gap-3 sm:flex-row sm:gap-4"
                >
                    <Link
                        :href="$page.props.auth.user ? dashboard() : register()"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-2xl px-8 py-4 text-base font-bold text-white transition-transform duration-200 hover:scale-105 sm:w-auto"
                        style="
                            background: linear-gradient(
                                135deg,
                                #a855f7,
                                #ec4899
                            );
                            box-shadow: 0 8px 30px rgba(236, 72, 153, 0.5);
                        "
                    >
                        Mulai gratis
                        <ArrowRight class="size-4" />
                    </Link>
                    <Link
                        href="/tutorial"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-2xl px-8 py-4 text-base font-semibold text-white transition-transform duration-200 hover:scale-105 sm:w-auto"
                        :style="glass"
                    >
                        Lihat tutorial
                    </Link>
                </div>

                <!-- Social proof -->
                <div
                    class="flex flex-wrap items-center justify-center gap-3 text-xs font-medium text-white/60 sm:gap-6 sm:text-sm"
                >
                    <span class="flex items-center gap-1.5"
                        ><Check class="size-4" style="color: #fbbf24" />Gratis
                        selamanya</span
                    >
                    <span class="flex items-center gap-1.5"
                        ><Check class="size-4" style="color: #fbbf24" />Tanpa
                        kartu kredit</span
                    >
                    <span class="flex items-center gap-1.5"
                        ><Check class="size-4" style="color: #fbbf24" />AI
                        drafting included</span
                    >
                </div>
            </div>

            <!-- Hero mockup — hidden below md -->
            <div
                class="relative mx-auto mt-14 hidden w-full max-w-3xl px-2 md:mt-20 md:block"
                data-animate
            >
                <div
                    class="float-card overflow-hidden rounded-3xl"
                    :style="glass"
                >
                    <!-- Browser chrome -->
                    <div
                        class="flex items-center gap-2 border-b px-5 py-3.5"
                        style="
                            border-color: rgba(255, 255, 255, 0.12);
                            background: rgba(255, 255, 255, 0.05);
                        "
                    >
                        <div class="size-3 rounded-full bg-red-400"></div>
                        <div class="size-3 rounded-full bg-yellow-400"></div>
                        <div class="size-3 rounded-full bg-green-400"></div>
                        <div
                            class="mx-auto rounded-md px-24 py-1 text-xs text-white/60"
                            style="background: rgba(255, 255, 255, 0.08)"
                        >
                            mail.google.com
                        </div>
                    </div>
                    <!-- Email body -->
                    <div class="flex">
                        <div
                            class="hidden w-48 shrink-0 border-r p-4 sm:block"
                            style="border-color: rgba(255, 255, 255, 0.1)"
                        >
                            <div
                                class="mb-3 h-2.5 w-16 rounded-full"
                                style="background: rgba(255, 255, 255, 0.18)"
                            ></div>
                            <div class="space-y-2">
                                <div
                                    class="flex items-center gap-2 rounded-lg px-2 py-1.5"
                                    style="background: rgba(168, 85, 247, 0.25)"
                                >
                                    <div
                                        class="size-2 rounded-full"
                                        style="background: #c084fc"
                                    ></div>
                                    <div
                                        class="h-2 w-12 rounded-full"
                                        style="
                                            background: rgba(
                                                192,
                                                132,
                                                252,
                                                0.7
                                            );
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
                                        style="
                                            background: rgba(
                                                255,
                                                255,
                                                255,
                                                0.2
                                            );
                                        "
                                    ></div>
                                    <div
                                        class="h-2 rounded-full"
                                        :style="`width: ${[48, 40, 56, 36][i - 1]}px; background: rgba(255,255,255,0.14)`"
                                    ></div>
                                </div>
                            </div>
                        </div>
                        <div class="flex-1 p-6">
                            <div
                                class="mb-5 border-b pb-4"
                                style="border-color: rgba(255, 255, 255, 0.12)"
                            >
                                <div
                                    class="mb-1.5 h-4 w-64 rounded-full"
                                    style="
                                        background: rgba(255, 255, 255, 0.22);
                                    "
                                ></div>
                                <div class="flex items-center gap-2">
                                    <div
                                        class="size-6 rounded-full"
                                        style="
                                            background: linear-gradient(
                                                135deg,
                                                #a855f7,
                                                #ec4899
                                            );
                                        "
                                    ></div>
                                    <div
                                        class="h-2.5 w-32 rounded-full"
                                        style="
                                            background: rgba(
                                                255,
                                                255,
                                                255,
                                                0.16
                                            );
                                        "
                                    ></div>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div
                                    class="rounded-xl p-4"
                                    style="
                                        background: linear-gradient(
                                            135deg,
                                            #a855f7 0%,
                                            #ec4899 100%
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
                                </div>
                                <div
                                    class="space-y-2 rounded-xl p-4"
                                    style="background: rgba(255, 255, 255, 0.9)"
                                >
                                    <div
                                        class="h-2.5 w-3/4 rounded-full"
                                        style="background: rgba(0, 0, 0, 0.12)"
                                    ></div>
                                    <div
                                        class="h-2 w-full rounded-full"
                                        style="background: rgba(0, 0, 0, 0.07)"
                                    ></div>
                                    <div
                                        class="h-2 w-5/6 rounded-full"
                                        style="background: rgba(0, 0, 0, 0.07)"
                                    ></div>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="(chip, i) in [
                                            'Vue.js',
                                            'Laravel',
                                            'TypeScript',
                                            'Tailwind',
                                        ]"
                                        :key="chip"
                                        class="rounded-full px-3 py-1 text-xs font-semibold text-white"
                                        :style="`background: ${['#a855f7', '#3b82f6', '#10b981', '#f59e0b'][i]}`"
                                        >{{ chip }}</span
                                    >
                                </div>
                                <div class="flex justify-center pt-1">
                                    <div
                                        class="rounded-lg px-8 py-2.5"
                                        style="
                                            background: linear-gradient(
                                                135deg,
                                                #a855f7,
                                                #ec4899
                                            );
                                        "
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
            </div>
        </section>

        <!-- LOGO STRIP -->
        <section class="px-4 py-12 sm:px-6 sm:py-16" data-animate>
            <div class="mx-auto max-w-6xl">
                <div
                    class="flex flex-wrap items-center justify-center gap-4 rounded-2xl px-6 py-6 sm:gap-8"
                    :style="glass"
                >
                    <p
                        class="w-full text-center text-xs font-medium text-white/50 sm:w-auto sm:text-sm"
                    >
                        Dipakai oleh job seekers di
                    </p>
                    <div
                        class="flex flex-wrap items-center justify-center gap-4 sm:gap-6"
                    >
                        <span
                            v-for="brand in [
                                'Tokopedia',
                                'Gojek',
                                'Traveloka',
                                'Shopee',
                                'Grab',
                            ]"
                            :key="brand"
                            class="text-xs font-bold text-white/70 sm:text-sm"
                            >{{ brand }}</span
                        >
                    </div>
                </div>
            </div>
        </section>

        <!-- HOW IT WORKS -->
        <section class="px-4 py-16 sm:px-6 sm:py-24 lg:py-28">
            <div class="mx-auto max-w-6xl">
                <div class="mb-10 text-center sm:mb-14" data-animate>
                    <span
                        class="mb-4 inline-flex items-center rounded-full px-4 py-1.5 text-xs font-semibold text-white sm:text-sm"
                        :style="glass"
                    >
                        Cara kerja
                    </span>
                    <h2
                        class="text-3xl font-black tracking-tight text-white sm:text-4xl lg:text-5xl"
                    >
                        Empat langkah, email siap kirim
                    </h2>
                </div>
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="(step, i) in steps"
                        :key="step.num"
                        data-animate
                        class="rounded-3xl p-6"
                        :style="{ ...glass, transitionDelay: `${i * 80}ms` }"
                    >
                        <div
                            class="mb-4 flex size-12 items-center justify-center rounded-2xl"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    #a855f7,
                                    #ec4899
                                );
                                box-shadow: 0 6px 20px rgba(168, 85, 247, 0.4);
                            "
                        >
                            <component
                                :is="step.icon"
                                class="size-5 text-white"
                            />
                        </div>
                        <span class="text-xs font-bold text-white/40">{{
                            step.num
                        }}</span>
                        <h3 class="mt-1 text-lg font-bold text-white">
                            {{ step.title }}
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-white/65">
                            {{ step.body }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- FEATURES BENTO -->
        <section class="px-4 py-16 sm:px-6 sm:py-24 lg:py-28">
            <div class="mx-auto max-w-6xl">
                <div class="mb-10 text-center sm:mb-14" data-animate>
                    <span
                        class="mb-4 inline-flex items-center rounded-full px-4 py-1.5 text-xs font-semibold text-white sm:text-sm"
                        :style="glass"
                    >
                        Fitur
                    </span>
                    <h2
                        class="text-3xl font-black tracking-tight text-white sm:text-4xl lg:text-5xl"
                    >
                        Semua yang kamu butuh buat<br class="hidden sm:block" />
                        email lamaran yang stand out
                    </h2>
                </div>
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="(f, i) in features"
                        :key="f.title"
                        data-animate
                        class="rounded-3xl p-7"
                        :style="{ ...glass, transitionDelay: `${i * 80}ms` }"
                    >
                        <div
                            class="mb-4 flex size-12 items-center justify-center rounded-2xl"
                            style="background: rgba(255, 255, 255, 0.12)"
                        >
                            <component
                                :is="f.icon"
                                class="size-5"
                                style="color: #fbbf24"
                            />
                        </div>
                        <h3 class="text-lg font-bold text-white">
                            {{ f.title }}
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-white/65">
                            {{ f.body }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- WHY GMAIL — quote -->
        <section class="px-4 py-16 sm:px-6 sm:py-24 lg:py-28">
            <div class="mx-auto max-w-4xl">
                <div
                    class="rounded-[2rem] px-8 py-12 text-center sm:px-14 sm:py-16"
                    :style="glass"
                    data-animate
                >
                    <div
                        class="mx-auto mb-6 flex size-14 items-center justify-center rounded-2xl"
                        style="
                            background: linear-gradient(
                                135deg,
                                #a855f7,
                                #ec4899
                            );
                            box-shadow: 0 8px 24px rgba(236, 72, 153, 0.45);
                        "
                    >
                        <Mail class="size-6 text-white" />
                    </div>
                    <p
                        class="text-2xl leading-snug font-bold text-white sm:text-3xl lg:text-4xl"
                    >
                        “Kenapa kirim dari Gmail sendiri? Karena HR percaya
                        alamat asli. Reputasi kamu tetap milik kamu, balasan
                        masuk langsung ke inbox pribadi.”
                    </p>
                    <p class="mt-6 text-sm font-medium text-white/60">
                        Filosofi ApplyMail — kamu yang pegang kendali
                    </p>
                </div>
            </div>
        </section>

        <!-- TWO MODES -->
        <section class="px-4 py-16 sm:px-6 sm:py-24 lg:py-28">
            <div class="mx-auto max-w-6xl">
                <div class="mb-10 text-center sm:mb-14" data-animate>
                    <span
                        class="mb-4 inline-flex items-center rounded-full px-4 py-1.5 text-xs font-semibold text-white sm:text-sm"
                        :style="glass"
                    >
                        Dua mode
                    </span>
                    <h2
                        class="text-3xl font-black tracking-tight text-white sm:text-4xl lg:text-5xl"
                    >
                        Mau ketik sendiri atau dibantu AI?
                    </h2>
                </div>
                <div class="grid gap-5 md:grid-cols-2">
                    <div data-animate class="rounded-3xl p-8" :style="glass">
                        <div
                            class="mb-5 flex size-12 items-center justify-center rounded-2xl"
                            style="background: rgba(255, 255, 255, 0.12)"
                        >
                            <Clipboard class="size-5" style="color: #fbbf24" />
                        </div>
                        <h3 class="text-xl font-bold text-white">Manual</h3>
                        <p class="mt-2 text-sm leading-relaxed text-white/65">
                            Kendali penuh. Isi tiap field sendiri, susun pesanmu
                            kata demi kata. Cocok buat yang suka nulis dari nol.
                        </p>
                        <ul class="mt-5 space-y-2.5">
                            <li
                                v-for="item in [
                                    'Template email-safe siap pakai',
                                    'Preview realtime kayak Gmail',
                                    'Kontrol penuh tiap kata',
                                ]"
                                :key="item"
                                class="flex items-center gap-2 text-sm text-white/75"
                            >
                                <Check
                                    class="size-4 shrink-0"
                                    style="color: #34d399"
                                />
                                {{ item }}
                            </li>
                        </ul>
                    </div>
                    <div
                        data-animate
                        class="rounded-3xl p-8"
                        :style="{
                            ...glass,
                            transitionDelay: '80ms',
                            border: '1px solid rgba(251,191,36,0.35)',
                        }"
                    >
                        <div
                            class="mb-5 flex size-12 items-center justify-center rounded-2xl"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    #a855f7,
                                    #ec4899
                                );
                                box-shadow: 0 6px 20px rgba(168, 85, 247, 0.4);
                            "
                        >
                            <Wand2 class="size-5 text-white" />
                        </div>
                        <h3
                            class="flex items-center gap-2 text-xl font-bold text-white"
                        >
                            AI Draft
                            <span
                                class="rounded-full px-2.5 py-0.5 text-xs font-bold text-white"
                                style="
                                    background: linear-gradient(
                                        135deg,
                                        #fbbf24,
                                        #f472b6
                                    );
                                "
                                >populer</span
                            >
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-white/65">
                            Tempel lowongan, biarkan AI merangkai email yang
                            nyambung sama CV kamu. Edit sepuasnya sebelum kirim.
                        </p>
                        <ul class="mt-5 space-y-2.5">
                            <li
                                v-for="item in [
                                    'Draft otomatis dari job posting',
                                    'Berbasis CV asli kamu',
                                    'Tetap bisa diedit manual',
                                ]"
                                :key="item"
                                class="flex items-center gap-2 text-sm text-white/75"
                            >
                                <Check
                                    class="size-4 shrink-0"
                                    style="color: #34d399"
                                />
                                {{ item }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA BANNER -->
        <section class="px-4 py-16 sm:px-6 sm:py-24 lg:py-28">
            <div class="mx-auto max-w-4xl">
                <div
                    class="relative overflow-hidden rounded-[2rem] px-8 py-14 text-center sm:px-14 sm:py-20"
                    :style="{
                        ...glass,
                        background:
                            'linear-gradient(135deg, rgba(168,85,247,0.35), rgba(236,72,153,0.3))',
                    }"
                    data-animate
                >
                    <div
                        class="pointer-events-none absolute -top-16 -right-16 size-56 rounded-full"
                        style="
                            background: radial-gradient(
                                circle,
                                rgba(251, 191, 36, 0.35),
                                transparent 70%
                            );
                        "
                    ></div>
                    <h2
                        class="text-3xl font-black tracking-tight text-white sm:text-5xl"
                    >
                        Siap bikin HR berhenti scroll?
                    </h2>
                    <p
                        class="mx-auto mt-4 max-w-lg text-base text-white/75 sm:text-lg"
                    >
                        Gratis selamanya, tanpa kartu kredit. Kirim email
                        lamaran pertamamu dalam beberapa menit.
                    </p>
                    <div
                        class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row sm:gap-4"
                    >
                        <Link
                            :href="
                                $page.props.auth.user ? dashboard() : register()
                            "
                            class="inline-flex w-full items-center justify-center gap-2 rounded-2xl px-8 py-4 text-base font-bold text-white transition-transform duration-200 hover:scale-105 sm:w-auto"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    #fbbf24,
                                    #ec4899
                                );
                                box-shadow: 0 8px 30px rgba(236, 72, 153, 0.5);
                            "
                        >
                            Mulai gratis sekarang
                            <ArrowRight class="size-4" />
                        </Link>
                        <Link
                            href="/tutorial"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-2xl px-8 py-4 text-base font-semibold text-white transition-transform duration-200 hover:scale-105 sm:w-auto"
                            :style="glass"
                        >
                            Lihat tutorial dulu
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- FOOTER -->
        <footer class="px-4 pb-10 sm:px-6">
            <div class="mx-auto max-w-6xl">
                <div
                    class="flex flex-col items-center justify-between gap-6 rounded-3xl px-6 py-8 sm:flex-row sm:px-8"
                    :style="glass"
                >
                    <div class="flex items-center gap-2">
                        <div
                            class="flex size-8 items-center justify-center rounded-xl"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    #a855f7,
                                    #ec4899
                                );
                            "
                        >
                            <Mail class="size-4 text-white" />
                        </div>
                        <span class="text-base font-bold text-white"
                            >ApplyMail</span
                        >
                    </div>
                    <nav
                        class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm text-white/70"
                    >
                        <Link href="/tutorial" class="hover:text-white"
                            >Tutorial</Link
                        >
                        <Link
                            v-if="$page.props.auth.user"
                            :href="dashboard()"
                            class="hover:text-white"
                            >Dashboard</Link
                        >
                        <template v-else>
                            <Link :href="login()" class="hover:text-white"
                                >Log in</Link
                            >
                            <Link :href="register()" class="hover:text-white"
                                >Get started</Link
                            >
                        </template>
                    </nav>
                    <p class="text-xs text-white/45">
                        © 2026 ApplyMail. Dibuat untuk job seekers Indonesia.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
[data-animate] {
    opacity: 0;
    transform: translateY(30px);
    transition:
        opacity 0.7s ease,
        transform 0.7s ease;
}

[data-animate].in-view {
    opacity: 1;
    transform: translateY(0);
}

@keyframes float {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-14px);
    }
}

.float-card {
    animation: float 6s ease-in-out infinite;
}
</style>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Check,
    ChevronDown,
    CreditCard,
    Eye,
    LayoutTemplate,
    Lock,
    Mail,
    Menu,
    Send,
    ShieldCheck,
    Sparkles,
    User,
    Wand2,
    X,
} from '@lucide/vue';
import gsap from 'gsap';
import ScrollTrigger from 'gsap/ScrollTrigger';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import ThreeGlobe from '@/components/ThreeGlobe.vue';
import { dashboard, login, register } from '@/routes';

const rootEl = ref(null);
const mobileMenuOpen = ref(false);
const openFaq = ref(0);

let gsapCtx: gsap.Context | null = null;

const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};

const closeMobileMenu = () => {
    mobileMenuOpen.value = false;
};

const toggleFaq = (index: number) => {
    if (openFaq.value === index) {
        openFaq.value = -1;

        return;
    }

    openFaq.value = index;
};

const steps = [
    {
        num: '01',
        icon: User,
        title: 'Add your CV & profile',
        body: 'Upload your CV and confirm your name and contact details. We extract the text once so you never retype the same information again.',
    },
    {
        num: '02',
        icon: LayoutTemplate,
        title: 'Pick a template',
        body: 'Choose a table-based, inline-styled HTML layout that renders identically in Gmail, Outlook and Apple Mail — no broken formatting.',
    },
    {
        num: '03',
        icon: Wand2,
        title: 'Draft with AI or write it',
        body: 'Paste the job posting and let AI compose each field from your real CV, or write every line yourself. You always edit before sending.',
    },
    {
        num: '04',
        icon: Send,
        title: 'Copy into Gmail & send',
        body: 'Copy the finished email into your own Gmail and hit send. It goes out from your real address, so replies come straight back to you.',
    },
];

const features = [
    {
        icon: Sparkles,
        title: 'AI grounded in your CV',
        body: 'Paste a job posting and get a tailored first draft built from your actual experience. The model works from your CV, so it references real roles and skills instead of inventing them.',
        span: 'lg:col-span-2 lg:row-span-2',
    },
    {
        icon: Eye,
        title: 'Pixel-accurate Gmail preview',
        body: 'See the exact email the recruiter will open — desktop and mobile — before you copy a single line.',
        span: 'lg:col-span-1',
    },
    {
        icon: LayoutTemplate,
        title: 'A template library that survives',
        body: 'Table-based, inline-styled HTML that holds its shape across every major email client.',
        span: 'lg:col-span-1',
    },
    {
        icon: ShieldCheck,
        title: 'Sent from your own address',
        body: 'Delivery runs through your Gmail, not a shared marketing server — so you inherit your own sending reputation and stay out of spam.',
        span: 'lg:col-span-2',
    },
    {
        icon: CreditCard,
        title: 'Simple credit system',
        body: 'Start free and spend a credit only when you generate an email. No subscription required to try it.',
        span: 'lg:col-span-1',
    },
    {
        icon: Lock,
        title: 'Your data stays yours',
        body: 'Your CV and profile are stored securely and used only to build your emails — never resold, never shared.',
        span: 'lg:col-span-1',
    },
];

const stats = [
    { value: '10k+', label: 'Emails composed' },
    { value: '92%', label: 'Primary-inbox rate' },
    { value: '3 min', label: 'Average setup time' },
    { value: '100%', label: 'Sent from your Gmail' },
];

const faqs = [
    {
        q: 'Does ApplyMail send emails for me?',
        a: 'No — and that is the point. ApplyMail builds the email and shows you an exact Gmail preview, then you copy it into your own inbox and hit send. The message goes out from your real address, so every reply lands directly with you.',
    },
    {
        q: 'Will my emails end up in spam?',
        a: 'Because you send from your own Gmail account, you inherit your established personal sending reputation instead of a shared marketing server. That single factor is the biggest reason application emails reach a recruiter’s primary inbox rather than the spam folder.',
    },
    {
        q: 'Does the AI make things up about me?',
        a: 'No. The AI drafts strictly from the CV and details you provide, so it references your genuine experience rather than inventing skills or job titles. Every field stays fully editable, so you can refine any line before it ever leaves your screen.',
    },
    {
        q: 'What will the email actually look like to the recruiter?',
        a: 'Exactly like the preview. We render table-based, inline-styled HTML that survives Gmail, Outlook and Apple Mail, and we show you both desktop and mobile views before you copy it. What you see is what they open.',
    },
    {
        q: 'How much does ApplyMail cost?',
        a: 'You can start for free. Drafting and generating emails run on a simple credit system, so you only spend when you actually create a message — there is no subscription required just to try it out.',
    },
    {
        q: 'Is my CV and personal data safe?',
        a: 'Your CV and profile are stored securely and used only to build your emails. Because delivery happens through your own Gmail, the content of your message is never routed through a third-party sending server.',
    },
];

const productLinks = [
    { label: 'Features', href: '#features' },
    { label: 'How it works', href: '#how-it-works' },
    { label: 'Two modes', href: '#modes' },
    { label: 'FAQ', href: '#faq' },
];

const resourceLinks = [
    { label: 'Tutorial', href: '/tutorial' },
    { label: 'Templates', href: '#features' },
    { label: 'Deliverability', href: '#why-gmail' },
];

const legalLinks = [
    { label: 'Privacy', href: '#' },
    { label: 'Terms', href: '#' },
    { label: 'Contact', href: '#' },
];

onMounted(() => {
    const faqSchema = {
        '@context': 'https://schema.org',
        '@type': 'FAQPage',
        mainEntity: faqs.map((item) => ({
            '@type': 'Question',
            name: item.q,
            acceptedAnswer: {
                '@type': 'Answer',
                text: item.a,
            },
        })),
    };

    const script = document.createElement('script');
    script.type = 'application/ld+json';
    script.textContent = JSON.stringify(faqSchema);
    document.head.appendChild(script);

    gsap.registerPlugin(ScrollTrigger);

    gsapCtx = gsap.context(() => {
        // Hero — on-load stagger (not scroll-driven)
        const heroTl = gsap.timeline({
            defaults: { ease: 'power3.out' },
        });

        heroTl.from('.js-hero-item', {
            opacity: 0,
            y: 44,
            duration: 0.9,
            stagger: 0.12,
        });

        // Trust strip — fade up + logos drift in from the right
        gsap.from('.js-trust', {
            scrollTrigger: {
                trigger: '.js-trust',
                start: 'top 85%',
                toggleActions: 'play none none reverse',
            },
            opacity: 0,
            y: 20,
            duration: 0.7,
            ease: 'power2.out',
        });

        gsap.from('.js-trust-logo', {
            scrollTrigger: {
                trigger: '.js-trust',
                start: 'top 80%',
                toggleActions: 'play none none reverse',
            },
            opacity: 0,
            x: 32,
            duration: 0.6,
            stagger: 0.08,
            ease: 'power2.out',
        });

        // Section headings — each reveals as it scrolls into view
        gsap.utils.toArray<Element>('.js-reveal').forEach((el) => {
            gsap.from(el, {
                scrollTrigger: {
                    trigger: el,
                    start: 'top 80%',
                    toggleActions: 'play none none reverse',
                },
                opacity: 0,
                y: 28,
                duration: 0.7,
                ease: 'power2.out',
            });
        });

        // How it works — draw the horizontal connector as it enters (scrub)
        gsap.from('.js-timeline-line', {
            scrollTrigger: {
                trigger: '.js-timeline-line',
                start: 'top 85%',
                end: 'top 45%',
                scrub: true,
            },
            scaleX: 0,
            transformOrigin: 'left center',
            ease: 'none',
        });

        // How it works — draw the vertical connector (mobile, scrub)
        gsap.from('.js-timeline-line-v', {
            scrollTrigger: {
                trigger: '.js-timeline-line-v',
                start: 'top 85%',
                end: 'bottom 55%',
                scrub: true,
            },
            scaleY: 0,
            transformOrigin: 'top center',
            ease: 'none',
        });

        // How it works — numbered nodes pop in with a stagger
        gsap.from('.js-timeline-node', {
            scrollTrigger: {
                trigger: '#how-it-works',
                start: 'top 68%',
                toggleActions: 'play none none reverse',
            },
            opacity: 0,
            scale: 0.6,
            duration: 0.6,
            stagger: 0.15,
            ease: 'back.out(1.7)',
        });

        // Features bento — grid cells stagger in with scale + rise
        gsap.from('.js-feature-card', {
            scrollTrigger: {
                trigger: '#features',
                start: 'top 70%',
                toggleActions: 'play none none reverse',
            },
            opacity: 0,
            y: 40,
            scale: 0.95,
            duration: 0.7,
            stagger: { each: 0.1, from: 'start' },
            ease: 'power3.out',
        });

        // Why Gmail — split slides in from opposite sides
        gsap.from('.js-why-left', {
            scrollTrigger: {
                trigger: '#why-gmail',
                start: 'top 72%',
                toggleActions: 'play none none reverse',
            },
            opacity: 0,
            x: -48,
            duration: 0.9,
            ease: 'power3.out',
        });

        gsap.from('.js-why-right', {
            scrollTrigger: {
                trigger: '#why-gmail',
                start: 'top 72%',
                toggleActions: 'play none none reverse',
            },
            opacity: 0,
            x: 48,
            duration: 0.9,
            ease: 'power3.out',
        });

        // Stats band — scrubbed count-up feel, staggered
        gsap.from('.js-stat', {
            scrollTrigger: {
                trigger: '.js-stats',
                start: 'top 88%',
                end: 'top 48%',
                scrub: true,
            },
            opacity: 0,
            y: 56,
            stagger: 0.15,
            ease: 'none',
        });

        // Two modes — offset panels rise with different timing, AI scales in
        gsap.from('.js-mode-manual', {
            scrollTrigger: {
                trigger: '#modes',
                start: 'top 72%',
                toggleActions: 'play none none reverse',
            },
            opacity: 0,
            y: 64,
            duration: 0.85,
            ease: 'power3.out',
        });

        gsap.from('.js-mode-ai', {
            scrollTrigger: {
                trigger: '#modes',
                start: 'top 72%',
                toggleActions: 'play none none reverse',
            },
            opacity: 0,
            y: 64,
            scale: 0.9,
            duration: 0.95,
            delay: 0.18,
            ease: 'power3.out',
        });

        // FAQ — rows stagger-fade as they enter
        gsap.from('.js-faq-row', {
            scrollTrigger: {
                trigger: '#faq',
                start: 'top 80%',
                toggleActions: 'play none none reverse',
            },
            opacity: 0,
            y: 22,
            duration: 0.6,
            stagger: 0.08,
            ease: 'power2.out',
        });

        // CTA — scale + opacity pop
        gsap.from('.js-cta', {
            scrollTrigger: {
                trigger: '.js-cta',
                start: 'top 85%',
                toggleActions: 'play none none reverse',
            },
            opacity: 0,
            scale: 0.9,
            duration: 0.8,
            ease: 'back.out(1.4)',
        });
    }, rootEl);
});

onBeforeUnmount(() => {
    if (gsapCtx) {
        gsapCtx.revert();
    }
});
</script>

<template>
    <Head>
        <title>ApplyMail — Application emails that get read</title>
        <meta
            name="description"
            content="Turn your CV and any job posting into a polished HTML application email, previewed exactly as Gmail renders it, and sent from your own Gmail inbox."
        />
        <meta
            property="og:title"
            content="ApplyMail — Application emails that get read"
        />
        <meta
            property="og:description"
            content="Designed HTML application emails from your CV, previewed exactly as Gmail renders them, sent from your own address so you never land in spam."
        />
        <meta property="og:type" content="website" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta
            name="twitter:title"
            content="ApplyMail — Application emails that get read"
        />
        <meta
            name="twitter:description"
            content="Designed HTML application emails from your CV, previewed exactly as Gmail renders them, sent from your own Gmail."
        />
    </Head>

    <div
        ref="rootEl"
        class="relative min-h-screen overflow-x-hidden font-sans text-white antialiased"
        style="background: #0a0a0a"
    >
        <div>
            <!-- ============ NAV ============ -->
            <header class="fixed top-0 right-0 left-0 z-50">
                <div
                    class="border-b border-white/10"
                    style="
                        background: rgba(10, 10, 10, 0.55);
                        backdrop-filter: blur(12px);
                        -webkit-backdrop-filter: blur(12px);
                    "
                >
                    <div
                        class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3.5 sm:px-6"
                    >
                        <!-- Logo -->
                        <a href="#top" class="flex items-center gap-2.5">
                            <span
                                class="flex size-8 items-center justify-center rounded-md bg-white"
                            >
                                <Mail class="size-4 text-black" />
                            </span>
                            <span
                                class="text-base font-semibold tracking-tight text-white"
                                >ApplyMail</span
                            >
                        </a>

                        <!-- Desktop nav -->
                        <nav class="hidden items-center gap-7 text-sm lg:flex">
                            <a
                                href="#features"
                                class="text-white/60 transition-colors hover:text-white"
                                >Features</a
                            >
                            <a
                                href="#how-it-works"
                                class="text-white/60 transition-colors hover:text-white"
                                >How it works</a
                            >
                            <a
                                href="#faq"
                                class="text-white/60 transition-colors hover:text-white"
                                >FAQ</a
                            >
                            <Link
                                v-if="$page.props.auth.user"
                                :href="dashboard()"
                                class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-black transition-opacity hover:opacity-90"
                            >
                                Dashboard
                            </Link>
                            <template v-else>
                                <Link
                                    :href="login()"
                                    class="text-white/60 transition-colors hover:text-white"
                                    >Login</Link
                                >
                                <Link
                                    :href="register()"
                                    class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-black transition-opacity hover:opacity-90"
                                    >Get started</Link
                                >
                            </template>
                        </nav>

                        <!-- Mobile hamburger -->
                        <button
                            class="flex size-9 items-center justify-center rounded-md border border-white/10 text-white lg:hidden"
                            aria-label="Toggle menu"
                            @click="toggleMobileMenu"
                        >
                            <X v-if="mobileMenuOpen" class="size-4" />
                            <Menu v-else class="size-4" />
                        </button>
                    </div>
                </div>

                <!-- Mobile dropdown -->
                <div
                    v-if="mobileMenuOpen"
                    class="border-b border-white/10 lg:hidden"
                    style="
                        background: rgba(10, 10, 10, 0.92);
                        backdrop-filter: blur(12px);
                        -webkit-backdrop-filter: blur(12px);
                    "
                >
                    <div
                        class="mx-auto flex max-w-6xl flex-col gap-1 px-4 py-4"
                    >
                        <a
                            href="#features"
                            class="rounded-md px-3 py-3 text-sm text-white/80 hover:bg-white/5"
                            @click="closeMobileMenu"
                            >Features</a
                        >
                        <a
                            href="#how-it-works"
                            class="rounded-md px-3 py-3 text-sm text-white/80 hover:bg-white/5"
                            @click="closeMobileMenu"
                            >How it works</a
                        >
                        <a
                            href="#faq"
                            class="rounded-md px-3 py-3 text-sm text-white/80 hover:bg-white/5"
                            @click="closeMobileMenu"
                            >FAQ</a
                        >
                        <Link
                            v-if="$page.props.auth.user"
                            :href="dashboard()"
                            class="mt-1 rounded-md bg-white px-3 py-3 text-center text-sm font-semibold text-black"
                            @click="closeMobileMenu"
                            >Dashboard</Link
                        >
                        <template v-else>
                            <Link
                                :href="login()"
                                class="rounded-md px-3 py-3 text-sm text-white/80 hover:bg-white/5"
                                @click="closeMobileMenu"
                                >Login</Link
                            >
                            <Link
                                :href="register()"
                                class="mt-1 rounded-md bg-white px-3 py-3 text-center text-sm font-semibold text-black"
                                @click="closeMobileMenu"
                                >Get started</Link
                            >
                        </template>
                    </div>
                </div>
            </header>

            <main id="top">
                <!-- ============ HERO ============ -->
                <section
                    class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden px-4 pt-32 pb-20 text-center sm:px-6"
                >
                    <!-- Wireframe globe, confined to the hero -->
                    <div
                        class="pointer-events-none absolute inset-0"
                        aria-hidden="true"
                    >
                        <ThreeGlobe />
                    </div>

                    <!-- Vignette: fades the globe into black behind the copy -->
                    <div
                        class="pointer-events-none absolute inset-0"
                        style="
                            background: radial-gradient(
                                ellipse at center,
                                transparent 0%,
                                transparent 40%,
                                #0a0a0a 85%
                            );
                        "
                    ></div>

                    <div class="relative z-10 mx-auto max-w-4xl">
                        <p
                            class="js-hero-item mb-6 font-mono text-xs tracking-[0.25em] text-white/40 uppercase sm:text-sm"
                        >
                            // AI-powered application emails
                        </p>

                        <h1
                            class="js-hero-item mb-6 text-4xl leading-[1.02] font-bold tracking-tight text-white sm:text-6xl md:text-7xl lg:text-8xl"
                        >
                            Application emails
                            <br class="hidden sm:block" />
                            that actually
                            <span class="text-white/40">get read.</span>
                        </h1>

                        <p
                            class="js-hero-item mx-auto mb-9 max-w-2xl text-base leading-relaxed text-[#a1a1aa] sm:text-xl"
                        >
                            Turn your CV and any job posting into a designed
                            HTML email, previewed exactly as Gmail renders it —
                            then sent from your own inbox, so replies come back
                            to you and you never land in spam.
                        </p>

                        <div
                            class="js-hero-item mb-10 flex flex-col items-center justify-center gap-3 sm:flex-row sm:gap-4"
                        >
                            <Link
                                :href="
                                    $page.props.auth.user
                                        ? dashboard()
                                        : register()
                                "
                                class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-white px-7 py-3.5 text-base font-semibold text-black transition-opacity hover:opacity-90 sm:w-auto"
                            >
                                Get started free
                                <ArrowRight class="size-4" />
                            </Link>
                            <a
                                href="/tutorial"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-md border border-white/15 px-7 py-3.5 text-base font-semibold text-white transition-colors hover:bg-white/5 sm:w-auto"
                            >
                                Watch the tutorial
                            </a>
                        </div>

                        <p
                            class="js-hero-item font-mono text-xs tracking-wide text-white/45 sm:text-sm"
                        >
                            3 min setup · Gmail-native · No credit card required
                        </p>
                    </div>
                </section>

                <!-- ============ TRUST STRIP ============ -->
                <section
                    class="border-y border-white/10"
                    style="background: rgba(255, 255, 255, 0.02)"
                >
                    <div class="js-trust mx-auto max-w-6xl px-4 py-8 sm:px-6">
                        <p
                            class="mb-6 text-center font-mono text-xs tracking-[0.2em] text-white/35 uppercase"
                        >
                            Renders pixel-perfect across
                        </p>
                        <div
                            class="flex flex-wrap items-center justify-center gap-x-8 gap-y-4 text-lg font-semibold tracking-tight text-white/50 sm:gap-x-14 sm:text-2xl"
                        >
                            <span class="js-trust-logo">Gmail</span>
                            <span class="js-trust-logo">Outlook</span>
                            <span class="js-trust-logo">Apple&nbsp;Mail</span>
                            <span class="js-trust-logo">Superhuman</span>
                            <span class="js-trust-logo">Proton&nbsp;Mail</span>
                            <span class="js-trust-logo">Chrome</span>
                        </div>
                    </div>
                </section>

                <!-- ============ HOW IT WORKS (TIMELINE) ============ -->
                <section
                    id="how-it-works"
                    class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-28"
                >
                    <div class="js-reveal mb-14 max-w-2xl">
                        <p
                            class="mb-3 font-mono text-xs tracking-[0.2em] text-white/40 uppercase"
                        >
                            // How it works
                        </p>
                        <h2
                            class="mb-4 text-3xl font-bold tracking-tight text-white sm:text-5xl"
                        >
                            From CV to a sent email in four steps.
                        </h2>
                        <p class="text-base text-[#a1a1aa] sm:text-lg">
                            No new inbox to manage and no sending server in the
                            middle. ApplyMail prepares the message; you send it
                            from the account recruiters already trust.
                        </p>
                    </div>

                    <!-- Desktop horizontal timeline -->
                    <div class="relative hidden lg:block">
                        <div
                            class="js-timeline-line absolute top-1/2 right-0 left-0 h-px bg-white/10"
                        ></div>
                        <div class="grid grid-cols-4 gap-6">
                            <div
                                v-for="(step, i) in steps"
                                :key="step.num"
                                class="grid"
                                style="
                                    grid-template-rows: 1fr auto 1fr;
                                    min-height: 22rem;
                                "
                            >
                                <div
                                    class="flex justify-center px-2"
                                    :class="
                                        i % 2 === 0
                                            ? 'row-start-1 items-end pb-10'
                                            : 'row-start-3 items-start pt-10'
                                    "
                                >
                                    <div
                                        class="rounded-lg border border-white/10 p-6"
                                        style="
                                            background: rgba(
                                                255,
                                                255,
                                                255,
                                                0.03
                                            );
                                            backdrop-filter: blur(12px);
                                            -webkit-backdrop-filter: blur(12px);
                                        "
                                    >
                                        <component
                                            :is="step.icon"
                                            class="mb-4 size-6 text-white"
                                        />
                                        <h3
                                            class="mb-2 text-lg font-semibold text-white"
                                        >
                                            {{ step.title }}
                                        </h3>
                                        <p
                                            class="text-sm leading-relaxed text-[#a1a1aa]"
                                        >
                                            {{ step.body }}
                                        </p>
                                    </div>
                                </div>
                                <div
                                    class="row-start-2 flex items-center justify-center"
                                >
                                    <div
                                        class="js-timeline-node flex size-14 items-center justify-center rounded-full border border-white/20 bg-[#0a0a0a] font-mono text-sm font-semibold text-white"
                                    >
                                        {{ step.num }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile vertical timeline -->
                    <div class="relative lg:hidden">
                        <div
                            class="js-timeline-line-v absolute top-2 bottom-2 left-[27px] w-px bg-white/10"
                        ></div>
                        <div class="flex flex-col gap-8">
                            <div
                                v-for="step in steps"
                                :key="step.num"
                                class="relative flex gap-5"
                            >
                                <div
                                    class="js-timeline-node z-10 flex size-14 shrink-0 items-center justify-center rounded-full border border-white/20 bg-[#0a0a0a] font-mono text-sm font-semibold text-white"
                                >
                                    {{ step.num }}
                                </div>
                                <div class="pt-1.5">
                                    <h3
                                        class="mb-1.5 text-lg font-semibold text-white"
                                    >
                                        {{ step.title }}
                                    </h3>
                                    <p
                                        class="text-sm leading-relaxed text-[#a1a1aa]"
                                    >
                                        {{ step.body }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ============ FEATURES (BENTO) ============ -->
                <section
                    id="features"
                    class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-28"
                >
                    <div class="js-reveal mb-14 max-w-2xl">
                        <p
                            class="mb-3 font-mono text-xs tracking-[0.2em] text-white/40 uppercase"
                        >
                            // Features
                        </p>
                        <h2
                            class="mb-4 text-3xl font-bold tracking-tight text-white sm:text-5xl"
                        >
                            Everything you need to write, preview and send.
                        </h2>
                        <p class="text-base text-[#a1a1aa] sm:text-lg">
                            Built around one idea: the email a recruiter opens
                            should look exactly the way you designed it, and it
                            should arrive from you.
                        </p>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <div
                            v-for="feature in features"
                            :key="feature.title"
                            class="js-feature-card flex flex-col rounded-xl border border-white/10 p-7"
                            :class="feature.span"
                            style="
                                background: rgba(255, 255, 255, 0.03);
                                backdrop-filter: blur(12px);
                                -webkit-backdrop-filter: blur(12px);
                            "
                        >
                            <div
                                class="mb-5 flex size-11 items-center justify-center rounded-lg border border-white/10 bg-white/5"
                            >
                                <component
                                    :is="feature.icon"
                                    class="size-5 text-white"
                                />
                            </div>
                            <h3
                                class="mb-2 text-xl font-semibold tracking-tight text-white"
                            >
                                {{ feature.title }}
                            </h3>
                            <p
                                class="text-sm leading-relaxed text-[#a1a1aa] sm:text-base"
                            >
                                {{ feature.body }}
                            </p>
                        </div>
                    </div>
                </section>

                <!-- ============ WHY GMAIL (SPLIT) ============ -->
                <section
                    id="why-gmail"
                    class="border-y border-white/10"
                    style="background: rgba(255, 255, 255, 0.02)"
                >
                    <div
                        class="mx-auto grid max-w-6xl grid-cols-1 gap-12 px-4 py-16 sm:px-6 sm:py-28 lg:grid-cols-2 lg:items-center lg:gap-16"
                    >
                        <div class="js-why-left">
                            <p
                                class="mb-3 font-mono text-xs tracking-[0.2em] text-white/40 uppercase"
                            >
                                // Why your own Gmail
                            </p>
                            <h2
                                class="mb-5 text-3xl font-bold tracking-tight text-white sm:text-5xl"
                            >
                                Sent from you, not from a server.
                            </h2>
                            <p
                                class="mb-4 text-base leading-relaxed text-[#a1a1aa] sm:text-lg"
                            >
                                Most tools blast your application from a shared
                                marketing server, which is exactly why those
                                messages get filtered into spam. ApplyMail does
                                the opposite: you copy the finished email into
                                your own Gmail and send it yourself.
                            </p>
                            <p
                                class="text-base leading-relaxed text-[#a1a1aa] sm:text-lg"
                            >
                                That means you inherit your personal sending
                                reputation, your real name and address appear in
                                the header, and every reply lands directly in
                                your inbox — no forwarding, no missed responses.
                            </p>
                        </div>

                        <!-- Monochrome flow diagram -->
                        <div class="js-why-right flex flex-col gap-5">
                            <div
                                class="rounded-xl border border-white/15 p-6"
                                style="background: rgba(255, 255, 255, 0.04)"
                            >
                                <p
                                    class="mb-4 flex items-center gap-2 font-mono text-xs tracking-wider text-white/70 uppercase"
                                >
                                    <Check class="size-4" /> The ApplyMail way
                                </p>
                                <div
                                    class="flex items-center justify-between gap-2 text-sm font-semibold text-white sm:text-base"
                                >
                                    <span
                                        class="rounded-md border border-white/15 px-3 py-2"
                                        >Your Gmail</span
                                    >
                                    <ArrowRight
                                        class="size-5 shrink-0 text-white/50"
                                    />
                                    <span
                                        class="rounded-md border border-white/15 px-3 py-2"
                                        >Recruiter’s inbox</span
                                    >
                                </div>
                            </div>

                            <div
                                class="rounded-xl border border-white/10 p-6"
                                style="background: rgba(255, 255, 255, 0.02)"
                            >
                                <p
                                    class="mb-4 flex items-center gap-2 font-mono text-xs tracking-wider text-white/40 uppercase"
                                >
                                    <X class="size-4" /> The old way
                                </p>
                                <div
                                    class="flex items-center justify-between gap-2 text-sm font-semibold text-white/35 line-through sm:text-base"
                                >
                                    <span
                                        class="rounded-md border border-white/10 px-3 py-2"
                                        >Marketing server</span
                                    >
                                    <ArrowRight
                                        class="size-5 shrink-0 text-white/20"
                                    />
                                    <span
                                        class="rounded-md border border-white/10 px-3 py-2"
                                        >Spam folder</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ============ STATS BAND ============ -->
                <section style="background: rgba(255, 255, 255, 0.03)">
                    <div
                        class="js-stats mx-auto grid max-w-6xl grid-cols-2 gap-y-10 px-4 py-16 sm:px-6 sm:py-20 lg:grid-cols-4"
                    >
                        <div
                            v-for="stat in stats"
                            :key="stat.label"
                            class="js-stat text-center"
                        >
                            <p
                                class="text-4xl font-bold tracking-tight text-white sm:text-6xl"
                            >
                                {{ stat.value }}
                            </p>
                            <p
                                class="mt-2 text-xs tracking-wide text-[#71717a] sm:text-sm"
                            >
                                {{ stat.label }}
                            </p>
                        </div>
                    </div>
                </section>

                <!-- ============ TWO MODES (STAGGERED) ============ -->
                <section
                    id="modes"
                    class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-28"
                >
                    <div class="js-reveal mb-14 max-w-2xl">
                        <p
                            class="mb-3 font-mono text-xs tracking-[0.2em] text-white/40 uppercase"
                        >
                            // Two ways to write
                        </p>
                        <h2
                            class="mb-4 text-3xl font-bold tracking-tight text-white sm:text-5xl"
                        >
                            Full control, or a head start.
                        </h2>
                        <p class="text-base text-[#a1a1aa] sm:text-lg">
                            Write every word yourself when you know exactly what
                            to say, or let AI assemble a grounded first draft
                            you can shape in seconds. Same polished result
                            either way.
                        </p>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:gap-10"
                    >
                        <div
                            class="js-mode-manual rounded-2xl border border-white/10 p-8 sm:p-10 lg:mt-16"
                            style="background: rgba(255, 255, 255, 0.02)"
                        >
                            <p
                                class="mb-4 font-mono text-xs tracking-[0.2em] text-white/40 uppercase"
                            >
                                Manual
                            </p>
                            <h3
                                class="mb-3 text-2xl font-bold tracking-tight text-white sm:text-3xl"
                            >
                                Write it yourself
                            </h3>
                            <p
                                class="mb-6 text-base leading-relaxed text-[#a1a1aa]"
                            >
                                Start from a clean template and fill in each
                                field on your own terms. You control every
                                sentence, every emphasis and every detail —
                                ideal when you already know precisely what you
                                want to say to a specific team.
                            </p>
                            <ul class="space-y-3 text-sm text-white/80">
                                <li class="flex items-center gap-2.5">
                                    <Check class="size-4 shrink-0 text-white" />
                                    Complete editorial control
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <Check class="size-4 shrink-0 text-white" />
                                    Reuse and tweak past emails
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <Check class="size-4 shrink-0 text-white" />
                                    No credits spent on drafting
                                </li>
                            </ul>
                        </div>

                        <div
                            class="js-mode-ai relative rounded-2xl border border-white/25 p-8 sm:p-10"
                            style="background: rgba(255, 255, 255, 0.05)"
                        >
                            <span
                                class="absolute -top-3 left-8 rounded-full border border-white/20 bg-white px-3 py-1 font-mono text-[10px] font-semibold tracking-[0.15em] text-black uppercase"
                            >
                                Popular
                            </span>
                            <p
                                class="mb-4 font-mono text-xs tracking-[0.2em] text-white/50 uppercase"
                            >
                                AI-assisted
                            </p>
                            <h3
                                class="mb-3 text-2xl font-bold tracking-tight text-white sm:text-3xl"
                            >
                                Draft with AI
                            </h3>
                            <p
                                class="mb-6 text-base leading-relaxed text-[#a1a1aa]"
                            >
                                Paste the job posting and ApplyMail assembles a
                                tailored first draft from your CV in seconds —
                                grounded in your real experience, never
                                invented. Refine any line, then copy it into
                                Gmail and send.
                            </p>
                            <ul class="space-y-3 text-sm text-white/80">
                                <li class="flex items-center gap-2.5">
                                    <Check class="size-4 shrink-0 text-white" />
                                    Tailored to each job posting
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <Check class="size-4 shrink-0 text-white" />
                                    Grounded in your actual CV
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <Check class="size-4 shrink-0 text-white" />
                                    Fully editable before sending
                                </li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- ============ FAQ ============ -->
                <section
                    id="faq"
                    class="border-t border-white/10"
                    style="background: rgba(255, 255, 255, 0.02)"
                >
                    <div
                        class="mx-auto grid max-w-6xl grid-cols-1 gap-12 px-4 py-16 sm:px-6 sm:py-28 lg:grid-cols-[0.8fr_1.2fr] lg:gap-16"
                    >
                        <div class="js-reveal">
                            <p
                                class="mb-3 font-mono text-xs tracking-[0.2em] text-white/40 uppercase"
                            >
                                // FAQ
                            </p>
                            <h2
                                class="mb-4 text-3xl font-bold tracking-tight text-white sm:text-5xl"
                            >
                                Questions, answered.
                            </h2>
                            <p class="text-base text-[#a1a1aa] sm:text-lg">
                                Everything about how ApplyMail builds your
                                email, keeps it out of spam and protects your
                                data.
                            </p>
                        </div>

                        <div class="flex flex-col">
                            <div
                                v-for="(item, i) in faqs"
                                :key="item.q"
                                class="js-faq-row border-b border-white/10"
                            >
                                <button
                                    class="flex w-full items-center justify-between gap-4 py-5 text-left"
                                    @click="toggleFaq(i)"
                                >
                                    <h3
                                        class="text-base font-semibold text-white sm:text-lg"
                                    >
                                        {{ item.q }}
                                    </h3>
                                    <ChevronDown
                                        class="size-5 shrink-0 text-white/50 transition-transform duration-300"
                                        :class="
                                            openFaq === i ? 'rotate-180' : ''
                                        "
                                    />
                                </button>
                                <div
                                    v-show="openFaq === i"
                                    class="pr-8 pb-5 text-sm leading-relaxed text-[#a1a1aa] sm:text-base"
                                >
                                    {{ item.a }}
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ============ CTA BANNER ============ -->
                <section
                    class="px-4 py-20 sm:px-6 sm:py-32"
                    style="background: #0a0a0a"
                >
                    <div
                        class="js-cta mx-auto max-w-4xl rounded-2xl border border-white/15 px-6 py-14 text-center sm:px-12 sm:py-20"
                        style="background: rgba(255, 255, 255, 0.03)"
                    >
                        <h2
                            class="mx-auto mb-5 max-w-2xl text-3xl font-bold tracking-tight text-white sm:text-5xl"
                        >
                            Send an application they’ll actually open.
                        </h2>
                        <p
                            class="mx-auto mb-9 max-w-xl text-base text-[#a1a1aa] sm:text-lg"
                        >
                            Build your first designed email in minutes, preview
                            it exactly as Gmail renders it, and send it from
                            your own address. Free to start — no credit card
                            required.
                        </p>
                        <div
                            class="flex flex-col items-center justify-center gap-3 sm:flex-row sm:gap-4"
                        >
                            <Link
                                :href="
                                    $page.props.auth.user
                                        ? dashboard()
                                        : register()
                                "
                                class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-white px-7 py-3.5 text-base font-semibold text-black transition-opacity hover:opacity-90 sm:w-auto"
                            >
                                Get started free
                                <ArrowRight class="size-4" />
                            </Link>
                            <a
                                href="/tutorial"
                                class="inline-flex w-full items-center justify-center rounded-md border border-white/15 px-7 py-3.5 text-base font-semibold text-white transition-colors hover:bg-white/5 sm:w-auto"
                            >
                                Watch the tutorial
                            </a>
                        </div>
                    </div>
                </section>
            </main>

            <!-- ============ FOOTER ============ -->
            <footer
                class="border-t border-white/10"
                style="background: #0a0a0a"
            >
                <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
                    <div
                        class="grid grid-cols-2 gap-10 sm:grid-cols-4 lg:grid-cols-5"
                    >
                        <div class="col-span-2 lg:col-span-2">
                            <a href="#top" class="flex items-center gap-2.5">
                                <span
                                    class="flex size-8 items-center justify-center rounded-md bg-white"
                                >
                                    <Mail class="size-4 text-black" />
                                </span>
                                <span
                                    class="text-base font-semibold tracking-tight text-white"
                                    >ApplyMail</span
                                >
                            </a>
                            <p
                                class="mt-4 max-w-xs text-sm leading-relaxed text-[#71717a]"
                            >
                                Designed application emails from your CV,
                                previewed exactly as Gmail renders them, sent
                                from your own inbox.
                            </p>
                        </div>

                        <div>
                            <p
                                class="mb-4 font-mono text-xs tracking-[0.15em] text-white/40 uppercase"
                            >
                                Product
                            </p>
                            <ul class="space-y-3 text-sm">
                                <li
                                    v-for="link in productLinks"
                                    :key="link.label"
                                >
                                    <a
                                        :href="link.href"
                                        class="text-[#a1a1aa] transition-colors hover:text-white"
                                        >{{ link.label }}</a
                                    >
                                </li>
                            </ul>
                        </div>

                        <div>
                            <p
                                class="mb-4 font-mono text-xs tracking-[0.15em] text-white/40 uppercase"
                            >
                                Resources
                            </p>
                            <ul class="space-y-3 text-sm">
                                <li
                                    v-for="link in resourceLinks"
                                    :key="link.label"
                                >
                                    <a
                                        :href="link.href"
                                        class="text-[#a1a1aa] transition-colors hover:text-white"
                                        >{{ link.label }}</a
                                    >
                                </li>
                            </ul>
                        </div>

                        <div>
                            <p
                                class="mb-4 font-mono text-xs tracking-[0.15em] text-white/40 uppercase"
                            >
                                Legal
                            </p>
                            <ul class="space-y-3 text-sm">
                                <li
                                    v-for="link in legalLinks"
                                    :key="link.label"
                                >
                                    <a
                                        :href="link.href"
                                        class="text-[#a1a1aa] transition-colors hover:text-white"
                                        >{{ link.label }}</a
                                    >
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div
                        class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-white/10 pt-8 sm:flex-row"
                    >
                        <p class="text-xs text-[#71717a]">
                            © 2026 ApplyMail. All rights reserved.
                        </p>
                        <p class="font-mono text-xs text-[#71717a]">
                            Built for the primary inbox.
                        </p>
                    </div>
                </div>
            </footer>
        </div>
    </div>
</template>

<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

/* ═══════════════════════════════════════════════════════ */
/* FEATURES SECTION — DATA                                 */
/* ═══════════════════════════════════════════════════════ */
interface Feature {
    id: string;
    title: string;
    description: string;
}

const features: Feature[] = [
    {
        id: 'camera-mic',
        title: 'Mic and Camera',
        description:
            "Turn on your camera and mic in one tap and you're in the room. MeetWise streams clear video and crisp audio to everyone on the call, so the conversation feels like you're sitting across the table, not staring at a lag.",
    },
    {
        id: 'screen-share',
        title: 'Screen Sharing',
        description:
            'Share a tab, a window, or your whole screen the moment you need to show something instead of describe it. Walk through a deck, review a design, or debug together in real time — everyone sees exactly what you see.',
    },
    {
        id: 'chat',
        title: 'Real-Time Chat',
        description:
            'Drop a link, ask a quick question, or reply without interrupting the speaker. The chat runs alongside your meeting the entire time, so nothing said in the room gets lost once the call ends.',
    },
    {
        id: 'recording',
        title: 'Meeting Recording',
        description:
            "Missed a detail or stepped away for a minute? Record the meeting and watch it back later. Every recording stays attached to the meeting it came from, so you always know where to look.",
    },
    {
        id: 'ai-notes',
        title: 'AI Note Taker',
        description:
            'Let MeetWise listen and write while you talk. The AI note taker captures decisions and action items as the meeting happens, so your team can stay in the discussion instead of typing through it.',
    },
];

const activeIndex = ref(0);
const blockRefs = ref<HTMLElement[]>([]);
const triggers: ScrollTrigger[] = [];

function setBlockRef(el: Element | null, index: number) {
    if (el) blockRefs.value[index] = el as HTMLElement;
}

function setActive(index: number) {
    if (activeIndex.value === index) return;
    activeIndex.value = index;
}

onMounted(() => {
    blockRefs.value.forEach((el, index) => {
        // Dim every block to start, the active one is brought to full opacity below.
        gsap.set(el, { opacity: index === 0 ? 1 : 0.1 });

        const trigger = ScrollTrigger.create({
            trigger: el,
            start: 'top center',
            end: 'bottom center',
            onEnter: () => setActive(index),
            onEnterBack: () => setActive(index),
        });
        triggers.push(trigger);
    });
});

onUnmounted(() => {
    triggers.forEach((trigger) => trigger.kill());
    triggers.length = 0;
});

// Fade each text block in/out as the active feature changes, driven by GSAP.
watch(activeIndex, (index) => {
    blockRefs.value.forEach((el, i) => {
        gsap.to(el, { opacity: i === index ? 1 : 0.1, duration: 0.5, ease: 'power2.out' });
    });
});

/* ═══════════════════════════════════════════════════════ */
/* HOW IT WORKS SECTION — DATA                             */
/* ═══════════════════════════════════════════════════════ */
interface HowItWorksStep {
    id: string;
    title: string;
    description: string;
    icon: 'user' | 'link' | 'room'; // picks the back-layer shape + front icon in the template
}

const steps: HowItWorksStep[] = [
    {
        id: 'account',
        title: 'Sign Up or Log In',
        description:
            'Create your free account or log in in seconds. Your meetings, recordings, and notes are saved to your account.',
        icon: 'user',
    },
    {
        id: 'meeting',
        title: 'Create or Join a Meeting',
        description: "Start a new meeting with one click, or paste a shared link to join someone else's.",
        icon: 'link',
    },
    {
        id: 'room',
        title: 'Land in the Meeting Room',
        description:
            "You're in. Camera, mic, screen share, chat, and AI notes are all ready the moment you arrive.",
        icon: 'room',
    },
];

/* ═══════════════════════════════════════════════════════ */
/* HOW IT WORKS SECTION — ANIMATION                        */
/* ═══════════════════════════════════════════════════════ */

// Timeline layout (in timeline units — scrub maps these linearly onto scroll distance)
const HIW_STEP_PERIOD = 4; // a step starts this long after the previous one started
const HIW_LINE_DELAY = 0.4; // badge dot + line start this long after the step starts
const HIW_LINE_LEN = HIW_STEP_PERIOD - HIW_LINE_DELAY; // line arrives exactly as the next step starts
const HIW_LAST_LINE_LEN = 2.7; // the last line is shorter (it only runs a little past its column)
const HIW_HOLD = 1; // empty tail so the finished state stays on screen while the pin releases
const HIW_SCROLL_VIEWPORTS = 5; // pinned scroll distance, in viewport heights (higher = slower animation)
const HIW_SCRUB = 1.5; // seconds of smoothing behind the scrollbar (higher = softer, lazier follow)

// Icon "washed out" → "crisp" filter pair (same function list, so GSAP can interpolate it)
const HIW_ICON_DIM = 'brightness(0.55) contrast(0.7)';
const HIW_ICON_SHARP = 'brightness(1) contrast(1)';

const hiwSection = ref<HTMLElement | null>(null);
let hiwContext: gsap.Context | null = null;
let hiwMedia: gsap.MatchMedia | null = null;
let hiwDisposed = false;

interface HiwStepEls {
    root: HTMLElement;
    box: HTMLElement;
    icon: HTMLElement;
    badge: HTMLElement;
    badgeNum: HTMLElement;
    line: HTMLElement;
    title: HTMLElement;
    desc: HTMLElement;
}

// Collect each step's animated elements via data-hiw attributes
function getHiwSteps(section: HTMLElement): HiwStepEls[] {
    return Array.from(section.querySelectorAll<HTMLElement>('[data-hiw="step"]')).map((root) => {
        const pick = (key: string) => root.querySelector<HTMLElement>(`[data-hiw="${key}"]`)!;
        return {
            root,
            box: pick('box'),
            icon: pick('icon'),
            badge: pick('badge'),
            badgeNum: pick('badge-num'),
            line: pick('line'),
            title: pick('title'),
            desc: pick('desc'),
        };
    });
}

// Everything starts hidden — set before the first scroll so nothing flashes
function setHiwInitialState(els: HiwStepEls[]) {
    els.forEach((s) => {
        gsap.set(s.box, { opacity: 0 });
        gsap.set(s.icon, { opacity: 0.15, filter: HIW_ICON_DIM });
        gsap.set(s.badge, { opacity: 0, scale: 0.2, transformOrigin: '50% 50%' });
        gsap.set(s.badgeNum, { opacity: 0 });
        gsap.set(s.line, { scaleX: 0, transformOrigin: 'left center' });
        gsap.set(s.title, { opacity: 0, y: 40 });
        gsap.set(s.desc, { opacity: 0, y: 50 }); // trails the title a little further down
    });
}

// Text slides up while its opacity passes through a visibly dimmed stage (~35%) before full colour
function addTextReveal(tl: gsap.core.Timeline, el: HTMLElement, at: number) {
    tl.to(el, { y: 0, duration: 1.2, ease: 'power3.out' }, at);
    tl.to(el, { opacity: 0.35, duration: 0.5, ease: 'power2.out' }, at);
    tl.fromTo(
        el,
        { opacity: 0.35 },
        { opacity: 1, duration: 0.7, ease: 'power1.inOut', immediateRender: false },
        at + 0.5,
    );
}

// Desktop: ONE scrubbed + pinned timeline that builds the three steps one after another
function buildHiwPinnedTimeline(section: HTMLElement, els: HiwStepEls[]) {
    setHiwInitialState(els);

    // The navbar is sticky and takes real space, so the pin starts just below it
    const navHeight = () => document.querySelector('header')?.getBoundingClientRect().height ?? 72;

    const tl = gsap.timeline({
        defaults: { ease: 'none' }, // progress maps linearly to scroll; softness lives inside each tween
        scrollTrigger: {
            trigger: section,
            start: () => `top ${navHeight()}px`,
            end: () => `+=${Math.round(window.innerHeight * HIW_SCROLL_VIEWPORTS)}`,
            pin: true,
            scrub: HIW_SCRUB,
            anticipatePin: 1,
            invalidateOnRefresh: true,
        },
    });

    els.forEach((s, i) => {
        const at = i * HIW_STEP_PERIOD;
        const lineLen = i < els.length - 1 ? HIW_LINE_LEN : HIW_LAST_LINE_LEN;
        const lineAt = at + HIW_LINE_DELAY;

        // Phase 1 — visual box fades in; the icon sharpens from washed-out to full contrast
        tl.to(s.box, { opacity: 1, duration: 1.2, ease: 'power2.out' }, at);
        tl.to(s.icon, { opacity: 1, filter: HIW_ICON_SHARP, duration: 1.2, ease: 'power2.out' }, at);

        // Phase 2 — title, then description a beat behind it
        addTextReveal(tl, s.title, at);
        addTextReveal(tl, s.desc, at + 0.2);

        // Phase 3 — badge pops in as a dot and the line starts drawing from the left
        tl.to(s.badge, { opacity: 1, duration: 0.15 }, lineAt);
        tl.to(s.line, { scaleX: 1, duration: lineLen }, lineAt);

        // Phase 4 — badge grows to full size by the time the line is ~65% drawn
        tl.to(s.badge, { scale: 1, duration: lineLen * 0.65, ease: 'power2.out' }, lineAt);
        tl.to(s.badgeNum, { opacity: 1, duration: lineLen * 0.65, ease: 'power2.out' }, lineAt);
        // Phase 5 — the line reaches the next column's badge exactly when the next step starts (at + PERIOD)
    });

    // Short empty hold at the end (total ≈ 12 units)
    tl.to({}, { duration: HIW_HOLD }, tl.duration());
}

// Mobile / tablet: no pin — each step animates on its own when it scrolls into view
function buildHiwMobileTriggers(els: HiwStepEls[]) {
    setHiwInitialState(els);

    els.forEach((s) => {
        const tl = gsap.timeline({
            defaults: { ease: 'power2.out' },
            scrollTrigger: {
                trigger: s.root,
                start: 'top 80%',
                toggleActions: 'play none none reverse',
            },
        });

        tl.to(s.box, { opacity: 1, duration: 0.6 }, 0)
            .to(s.icon, { opacity: 1, filter: HIW_ICON_SHARP, duration: 0.6 }, 0)
            .to(s.badge, { opacity: 1, scale: 1, duration: 0.5 }, 0.2)
            .to(s.badgeNum, { opacity: 1, duration: 0.4 }, 0.3)
            .to(s.line, { scaleX: 1, duration: 0.8, ease: 'power2.inOut' }, 0.2)
            .to(s.title, { opacity: 1, y: 0, duration: 0.6 }, 0.2)
            .to(s.desc, { opacity: 1, y: 0, duration: 0.6 }, 0.35);
    });
}

const refreshHiw = () => {
    if (!hiwDisposed) ScrollTrigger.refresh();
};

onMounted(() => {
    const section = hiwSection.value;
    if (!section) return;
    hiwDisposed = false;

    // Everything for this section lives in one context so it can be reverted in one go
    hiwContext = gsap.context(() => {
        hiwMedia = gsap.matchMedia();
        hiwMedia.add(
            {
                isDesktop: '(min-width: 1024px)',
                isMobile: '(max-width: 1023px)',
                reduceMotion: '(prefers-reduced-motion: reduce)',
            },
            (context) => {
                const { isDesktop, isMobile, reduceMotion } = (context.conditions ?? {}) as Record<string, boolean>;

                // Reduced motion: no pin, no scrub — leave everything in its final, visible state
                if (reduceMotion) return;

                const els = getHiwSteps(section);
                if (isDesktop) buildHiwPinnedTimeline(section, els);
                else if (isMobile) buildHiwMobileTriggers(els);
            },
        );
    }, section);

    // Fonts (Inter / Lora) change text heights → recompute pin distances once they have loaded
    document.fonts?.ready.then(refreshHiw);
    if (document.readyState !== 'complete') window.addEventListener('load', refreshHiw, { once: true });
});

onUnmounted(() => {
    hiwDisposed = true;
    window.removeEventListener('load', refreshHiw);
    // Reverts only this section's tweens/triggers and removes its pin spacer
    hiwMedia?.revert();
    hiwContext?.revert();
    hiwMedia = null;
    hiwContext = null;
});

/* ═══════════════════════════════════════════════════════ */
/* CTA SECTION — ANIMATION                                 */
/* ═══════════════════════════════════════════════════════ */

// Pacing — tweak these to retime the loop (all in seconds, measured from the start of each loop)
const CTA_PERIOD = 4; // total length of one loop (the tail of it is pure rest)
const CTA_SCALE = 1.11; // how far the whole button grows
const CTA_SWEEP = 0.9; // the glow crosses the button between t = 0 and t = CTA_SWEEP
const CTA_HOLD_END = 2.5; // the button stays grown until this moment, then shrinks back
const CTA_FADE = 0.15; // glow fade-in / fade-out length at either end of the sweep
const CTA_GROW_START = 0.45; // growth begins when the glow is about halfway across
const CTA_GROW_END = 0.95;
const CTA_SHRINK = 0.35; // shrink length (CTA_HOLD_END → CTA_HOLD_END + CTA_SHRINK)

const ctaSection = ref<HTMLElement | null>(null);
let ctaContext: gsap.Context | null = null;
let ctaMedia: gsap.MatchMedia | null = null;
let ctaTimeline: gsap.core.Timeline | null = null;
let ctaInView = false;
let ctaDisposed = false;

// Build the ONE looping timeline. Every tween is a fromTo, so nothing depends on where the
// animation currently is — it is safe to invalidate() it at any moment (e.g. on resize).
function buildCtaLoop(section: HTMLElement): gsap.core.Timeline | null {
    // Query the DOM nodes directly — a template ref on <Link> would return the component instance
    const button = section.querySelector<HTMLElement>('[data-cta="button"]');
    const glow = section.querySelector<HTMLElement>('[data-cta="glow"]');
    if (!button || !glow) return null;

    // Rest state: normal-looking static button, glow invisible and vertically centred
    gsap.set(button, { scale: 1 });
    gsap.set(glow, { x: 0, yPercent: -50, opacity: 0 });

    const tl = gsap.timeline({ repeat: -1, paused: true });

    // Phase 1 — GLOW SWEEP: left edge → right edge. Function-based, so it is right at any width.
    tl.fromTo(
        glow,
        { x: 0 },
        { x: () => button.offsetWidth - glow.offsetWidth, duration: CTA_SWEEP, ease: 'power1.inOut' },
        0,
    );
    // …fading in over the first 0.15s and out over the last 0.15s of the sweep
    tl.fromTo(glow, { opacity: 0 }, { opacity: 1, duration: CTA_FADE, ease: 'none' }, 0);
    tl.fromTo(
        glow,
        { opacity: 1 },
        { opacity: 0, duration: CTA_FADE, ease: 'none', immediateRender: false },
        CTA_SWEEP - CTA_FADE,
    );

    // Phase 2 — GROW: the whole button (label included) scales up as the glow passes the middle
    tl.fromTo(
        button,
        { scale: 1 },
        { scale: CTA_SCALE, duration: CTA_GROW_END - CTA_GROW_START, ease: 'power2.out', immediateRender: false },
        CTA_GROW_START,
    );

    // Phase 3 — HOLD: nothing is scheduled between CTA_GROW_END and CTA_HOLD_END, the scale simply stays

    // Phase 4 — SHRINK: back to normal size
    tl.fromTo(
        button,
        { scale: CTA_SCALE },
        { scale: 1, duration: CTA_SHRINK, ease: 'power2.inOut', immediateRender: false },
        CTA_HOLD_END,
    );

    // Phase 5 — REST: pad the timeline with an empty tween so one loop lasts exactly CTA_PERIOD
    const remaining = CTA_PERIOD - tl.duration();
    if (remaining > 0) tl.to({}, { duration: remaining }, tl.duration());

    return tl;
}

// Play only while the section is on screen AND the tab is visible (one timeline, so nothing stacks)
function syncCtaPlayback() {
    if (!ctaTimeline) return;
    if (ctaInView && !document.hidden) ctaTimeline.play();
    else ctaTimeline.pause();
}

// Re-measure the sweep distance whenever ScrollTrigger refreshes (it also does this on window resize)
function onCtaRefresh() {
    ctaTimeline?.invalidate();
}

onMounted(() => {
    const section = ctaSection.value;
    if (!section) return;
    ctaDisposed = false;

    ctaContext = gsap.context(() => {
        ctaMedia = gsap.matchMedia();

        // Reduced motion → nothing is built, so the static button stays exactly as it is
        ctaMedia.add('(prefers-reduced-motion: no-preference)', () => {
            ctaTimeline = buildCtaLoop(section);
            if (!ctaTimeline) return;

            // Pause the loop while the section is off-screen, resume when it comes back
            const visibility = ScrollTrigger.create({
                trigger: section,
                start: 'top bottom',
                end: 'bottom top',
                onToggle: (self) => {
                    ctaInView = self.isActive;
                    syncCtaPlayback();
                },
            });
            ctaInView = visibility.isActive;
            syncCtaPlayback();

            return () => {
                ctaTimeline = null;
                ctaInView = false;
            };
        });
    }, section);

    document.addEventListener('visibilitychange', syncCtaPlayback);
    ScrollTrigger.addEventListener('refresh', onCtaRefresh);

    // Fonts change text widths/heights → refresh once so every trigger position (and the sweep) is exact
    document.fonts?.ready.then(() => {
        if (!ctaDisposed) ScrollTrigger.refresh();
    });
});

onUnmounted(() => {
    ctaDisposed = true;
    document.removeEventListener('visibilitychange', syncCtaPlayback);
    ScrollTrigger.removeEventListener('refresh', onCtaRefresh);
    // Reverts only this section's timeline + trigger
    ctaMedia?.revert();
    ctaContext?.revert();
    ctaMedia = null;
    ctaContext = null;
    ctaTimeline = null;
});
</script>

<template>
    <Head title="MeetWise — Video Meetings That Keep Your Team Moving Forward">
        <!-- Inter (existing) -->
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
        <!-- Lora serif — used only for the italic headline words -->
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="" />
        <link href="https://fonts.googleapis.com/css2?family=Lora:ital@1&display=swap" rel="stylesheet" />
        <meta
            name="description"
            content="MeetWise is a modern video conferencing platform for instant meetings, screen sharing, and real-time team collaboration.
             Stay connected, anywhere."
        />
    </Head>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- PAGE SHELL                                              -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <div
        class="min-h-screen"
        style="background-color: #080e0b; color: #edf7f5; font-family: 'InterVariable', 'Inter', sans-serif;"
    >

        <!-- ═══════════════════════════════════════════════════ -->
        <!-- STICKY NAVBAR                                       -->
        <!-- ═══════════════════════════════════════════════════ -->
        <header
            class="sticky top-0 z-50"
            style="background-color: rgba(8, 14, 11, 0.6); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); border-bottom: 1px solid rgba(255, 255, 255, 0.06);"
        >
            <div class="px-32 mx-auto py-4 flex items-center justify-between gap-8">

                <!-- Left — Logo + Wordmark -->
                <div class="flex items-center gap-3 flex-shrink-0">
                    <div
                        class="w-10 h-10 rounded-lg flex items-center justify-center"
                        style="background: linear-gradient(to right, #0C2C21, #185146);"
                    >
                        <div class="w-3.5 h-3.5 bg-white rotate-45 rounded-[2px]"></div>
                    </div>
                    <span class="text-xl font-semibold tracking-tight" style="color: #edf7f5;">
                        MeetWise
                    </span>
                </div>

                <!-- Center — Nav links (desktop only) -->
                <nav class="hidden md:flex items-center gap-10 text-base" style="color: rgba(237, 247, 245, 0.55);">
                    <a href="#" class="flex items-center gap-1 transition-colors hover:text-[#edf7f5]">
                        About
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </a>
                    <a href="#" class="flex items-center gap-1 transition-colors hover:text-[#edf7f5]">
                        Blog
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </a>
                    <a href="#" class="flex items-center gap-1 transition-colors hover:text-[#edf7f5]">
                        Pricing
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </a>
                    <a href="#" class="flex items-center gap-1 transition-colors hover:text-[#edf7f5]">
                        Pages
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </a>
                </nav>

                <!-- Right — Auth buttons -->
                <div class="flex items-center gap-4 flex-shrink-0">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="px-5 py-2 rounded-full text-sm font-medium text-white transition-opacity hover:opacity-90"
                        style="background: linear-gradient(to right, #0C2C21, #185146);"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="hidden md:inline text-base transition-colors hover:text-[#edf7f5]"
                            style="color: rgba(237, 247, 245, 0.55);"
                        >
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="px-6 py-3 rounded-full text-sm font-medium text-white transition-opacity hover:opacity-90"
                            style="background: linear-gradient(to right, #0C2C21, #185146);"
                        >
                            Get Started
                        </Link>
                    </template>
                </div>

            </div>
        </header>

        <!-- ═══════════════════════════════════════════════════ -->
        <!-- HERO SECTION                                        -->
        <!-- ═══════════════════════════════════════════════════ -->
        <section class="relative overflow-hidden flex items-center" style="min-height: calc(100vh - 4.5rem);">

            <!-- ── Gradient video background ── -->
            <!--
                The video is referenced from the CDN below.
                To serve it locally, download the file and place it at:
                  public/videos/hero-bg.mp4
                Then change the src to: /videos/hero-bg.mp4
            -->
            <div
                class="absolute z-0 pointer-events-none flex justify-center overflow-hidden"
                style="top: 0; left: 0; right: 0; bottom: 0;"
            >
                <video
                    src="/videos/hero-bg.mp4"
                    autoplay
                    loop
                    muted
                    playsinline
                    style="
                        width: 160%;
                        height: 140%;
                        object-fit: cover;
                        opacity: 0.5;
                        mix-blend-mode: screen;
                        filter: blur(60px) hue-rotate(-110deg) saturate(1.4);
                    "
                ></video>

                <!-- Brand-colored overlay — reinforces the green/teal tint -->
                <div
                    style="
                        position: absolute;
                        inset: 0;
                        background: linear-gradient(to right, rgba(12, 44, 33, 0.45), rgba(24, 81, 70, 0.35));
                        pointer-events: none;
                    "
                ></div>
            </div>

            <!-- ── Hero content ── -->
            <div class="relative z-10 text-center px-6 py-20 max-w-4xl mx-auto">

                <!-- Flash message (existing functionality, styled for dark theme) -->
                <div
                    v-if="$page.props.flash?.joinMessage"
                    class="inline-block mb-8 px-4 py-2 rounded-full text-sm"
                    style="background-color: rgba(56, 112, 100, 0.2); border: 1px solid rgba(56, 112, 100, 0.4); color: #84aaa2;"
                >
                    {{ $page.props.flash.joinMessage }}
                </div>

                <!-- Headline -->
                <h1 class="font-medium tracking-tight mb-6"
                    style="
                        font-size: clamp(2.5rem, 6vw, 4.5rem);
                        line-height: 1.1;
                        letter-spacing: -0.03em;
                        color: #edf7f5;">
                    Video Meetings That Keep<br />
                    Your Whole Team
                    <span class="hero-serif-italic">Moving Forward</span>
                </h1>

                <!-- Sub-copy -->
                <p
                    class="text-base md:text-lg leading-relaxed max-w-xl mx-auto mb-10"
                    style="color: rgba(237, 247, 245, 0.5);"
                >
                    MeetWise makes it easy to connect instantly, share your screen,
                    and collaborate in real time — whether you're across the hall
                    or across the globe.
                </p>

                <!-- CTA button -->
                <Link
                    :href="$page.props.auth.user ? dashboard() : register()"
                    class="inline-block px-9 py-3.5 rounded-full text-sm font-semibold text-white transition-opacity hover:opacity-90"
                    style="
                        background: linear-gradient(to right, #0C2C21, #185146);
                        box-shadow: 0 0 48px rgba(24, 81, 70, 0.5), 0 0 12px rgba(24, 81, 70, 0.3);
                    "
                >
                    Get Started
                </Link>

            </div>
        </section>

        <!-- ═══════════════════════════════════════════════════ -->
        <!-- FEATURES SECTION                                    -->
        <!-- ═══════════════════════════════════════════════════ -->
        <section class="relative px-6 md:px-16 lg:px-32 py-32">

            <div class="max-w-2xl mb-20">
                <h2
                    class="font-medium tracking-tight mb-5"
                    style="font-size: clamp(2rem, 4vw, 3rem); line-height: 1.15; letter-spacing: -0.02em; color: #edf7f5;"
                >
                    Everything a meeting
                    <span class="hero-serif-italic">actually needs</span>
                </h2>
                <p class="text-base md:text-lg leading-relaxed" style="color: rgba(237, 247, 245, 0.5);">
                    No plugins, no bolted-on tools — the whole meeting, from the first hello to the follow-up notes, lives in one place.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 lg:gap-24">

                <!-- ── Left — scrolling text blocks ── -->
                <div class="flex flex-col">
                    <div
                        v-for="(feature, index) in features"
                        :key="feature.id"
                        :ref="(el) => setBlockRef(el as Element | null, index)"
                        class="feature-block flex flex-col justify-center"
                        style="min-height: 60vh;"
                    >
                        <span
                            class="text-sm font-medium mb-4"
                            style="color: #84aaa2;"
                        >
                            {{ String(index + 1).padStart(2, '0') }}
                        </span>
                        <h3
                            class="text-2xl md:text-3xl font-medium tracking-tight mb-4"
                            style="color: #edf7f5; letter-spacing: -0.01em;"
                        >
                            {{ feature.title }}
                        </h3>
                        <p
                            class="text-base leading-relaxed max-w-md"
                            style="color: rgba(237, 247, 245, 0.55);"
                        >
                            {{ feature.description }}
                        </p>
                    </div>
                </div>

                <!-- ── Right — sticky visual panel ── -->
                <div class="hidden lg:block">
                    <div class="sticky top-32 h-[28rem] rounded-2xl overflow-hidden" style="border: 1px solid rgba(255, 255, 255, 0.08);">

                        <!-- Base panel background -->
                        <div class="absolute inset-0" style="background-color: #0b1512;"></div>

                        <!-- One layer per feature, crossfaded on activeIndex change -->
                        <Transition name="panel-fade" mode="out-in">
                            <div :key="activeIndex" class="absolute inset-0 flex items-center justify-center">
                                <div
                                    class="absolute inset-0"
                                    style="background: linear-gradient(160deg, rgba(12, 44, 33, 0.9), rgba(8, 14, 11, 0.95));"
                                ></div>

                                <!-- Mic & Camera -->
                                <svg v-if="activeIndex === 0" class="relative w-28 h-28" viewBox="0 0 96 96" fill="none">
                                    <rect x="10" y="26" width="46" height="34" rx="6" stroke="#84aaa2" stroke-width="2.5" />
                                    <path d="M56 38 L80 28 V58 L56 48 Z" stroke="#84aaa2" stroke-width="2.5" stroke-linejoin="round" />
                                    <circle cx="33" cy="43" r="8" stroke="#edf7f5" stroke-width="2.5" />
                                </svg>

                                <!-- Screen Sharing -->
                                <svg v-else-if="activeIndex === 1" class="relative w-28 h-28" viewBox="0 0 96 96" fill="none">
                                    <rect x="12" y="18" width="72" height="46" rx="4" stroke="#84aaa2" stroke-width="2.5" />
                                    <path d="M38 78 H58 M48 64 V78" stroke="#84aaa2" stroke-width="2.5" stroke-linecap="round" />
                                    <path d="M48 30 L48 50 M48 30 L38 40 M48 30 L58 40" stroke="#edf7f5" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>

                                <!-- Real-Time Chat -->
                                <svg v-else-if="activeIndex === 2" class="relative w-28 h-28" viewBox="0 0 96 96" fill="none">
                                    <path d="M14 24 H70 A6 6 0 0 1 76 30 V54 A6 6 0 0 1 70 60 H36 L22 72 V60 H14 A6 6 0 0 1 8 54 V30 A6 6 0 0 1 14 24 Z" stroke="#84aaa2" stroke-width="2.5" stroke-linejoin="round" />
                                    <circle cx="28" cy="42" r="2.5" fill="#edf7f5" />
                                    <circle cx="42" cy="42" r="2.5" fill="#edf7f5" />
                                    <circle cx="56" cy="42" r="2.5" fill="#edf7f5" />
                                </svg>

                                <!-- Meeting Recording -->
                                <svg v-else-if="activeIndex === 3" class="relative w-28 h-28" viewBox="0 0 96 96" fill="none">
                                    <rect x="12" y="24" width="72" height="48" rx="8" stroke="#84aaa2" stroke-width="2.5" />
                                    <circle cx="48" cy="48" r="12" fill="#edf7f5" fill-opacity="0.9" />
                                    <circle cx="48" cy="48" r="20" stroke="#84aaa2" stroke-width="2" stroke-dasharray="3 4" />
                                </svg>

                                <!-- AI Note Taker -->
                                <svg v-else class="relative w-28 h-28" viewBox="0 0 96 96" fill="none">
                                    <rect x="22" y="14" width="44" height="60" rx="4" stroke="#84aaa2" stroke-width="2.5" />
                                    <path d="M32 30 H56 M32 42 H56 M32 54 H46" stroke="#edf7f5" stroke-width="2.5" stroke-linecap="round" />
                                    <path
                                        d="M74 20 L77 27 L84 30 L77 33 L74 40 L71 33 L64 30 L71 27 Z"
                                        fill="#edf7f5"
                                    />
                                </svg>
                            </div>
                        </Transition>
                    </div>
                </div>

            </div>
        </section>

        <!-- ═══════════════════════════════════════════════════ -->
        <!-- HOW IT WORKS SECTION                                -->
        <!-- ═══════════════════════════════════════════════════ -->
        <section
            ref="hiwSection"
            class="hiw-section relative px-6 md:px-16 lg:px-32 py-24 lg:py-0 flex flex-col justify-center"
        >

            <!-- Section header (same style as the Features header) -->
            <div class="max-w-2xl mb-14 lg:mb-12">
                <h2
                    class="font-medium tracking-tight mb-5"
                    style="font-size: clamp(2rem, 4vw, 3rem); line-height: 1.15; letter-spacing: -0.02em; color: #edf7f5;"
                >
                    Up and running in
                    <span class="hero-serif-italic">three steps</span>
                </h2>
                <p class="text-base md:text-lg leading-relaxed" style="color: rgba(237, 247, 245, 0.5);">
                    No downloads, no setup — just three steps between you and your first meeting.
                </p>
            </div>

            <!--
                Steps grid. On lg+ each step is a subgrid spanning 4 rows
                (visual / timeline / title / description) so every row lines up across columns.
            -->
            <div class="hiw-grid grid grid-cols-1 gap-y-16 lg:grid-cols-3 lg:gap-y-0 lg:grid-rows-[repeat(4,auto)]">
                <div
                    v-for="(step, index) in steps"
                    :key="step.id"
                    data-hiw="step"
                    class="flex flex-col lg:grid lg:grid-rows-subgrid lg:row-span-4"
                >

                    <!-- 1 · VISUAL BOX — flat edges, 3:2, two layers -->
                    <div
                        data-hiw="box"
                        class="hiw-will relative overflow-hidden w-full"
                        style="
                            aspect-ratio: 3 / 2;
                            background: linear-gradient(160deg, rgba(12, 44, 33, 0.9), rgba(8, 14, 11, 0.95));
                            border: 1px solid rgba(255, 255, 255, 0.08);
                        "
                    >
                        <!-- Back layer: oversized faint shape, different per step, cropped by the box -->
                        <svg
                            class="absolute inset-0 w-full h-full"
                            viewBox="0 0 600 400"
                            preserveAspectRatio="xMidYMid slice"
                            fill="none"
                            aria-hidden="true"
                        >
                            <!-- Step 1 — concentric rings -->
                            <g v-if="step.icon === 'user'" stroke="#84aaa2" stroke-opacity="0.18" stroke-width="16">
                                <circle cx="500" cy="330" r="70" />
                                <circle cx="500" cy="330" r="130" />
                                <circle cx="500" cy="330" r="190" />
                                <circle cx="500" cy="330" r="250" />
                            </g>
                            <!-- Step 2 — sweeping curves -->
                            <g
                                v-else-if="step.icon === 'link'"
                                stroke="#84aaa2"
                                stroke-opacity="0.16"
                                stroke-width="34"
                                stroke-linecap="round"
                            >
                                <path d="M-60 330 C 120 80, 300 80, 380 230 S 600 420, 700 120" />
                                <path d="M-60 410 C 120 160, 300 160, 380 310 S 600 500, 700 200" />
                            </g>
                            <!-- Step 3 — big solid shapes -->
                            <g v-else fill="#84aaa2" fill-opacity="0.16">
                                <circle cx="70" cy="360" r="170" />
                                <rect x="360" y="-90" width="280" height="280" rx="36" transform="rotate(24 500 50)" />
                            </g>
                        </svg>

                        <!-- Front layer: one crisp centered icon -->
                        <div class="absolute inset-0 flex items-center justify-center">
                            <!-- Step 1 — user / login -->
                            <svg
                                v-if="step.icon === 'user'"
                                data-hiw="icon"
                                class="hiw-will w-20 h-20 md:w-24 md:h-24"
                                viewBox="0 0 96 96"
                                fill="none"
                            >
                                <circle cx="48" cy="34" r="13" stroke="#edf7f5" stroke-width="2.5" />
                                <path
                                    d="M22 78 C22 62 33 53 48 53 C63 53 74 62 74 78"
                                    stroke="#edf7f5"
                                    stroke-width="2.5"
                                    stroke-linecap="round"
                                />
                            </svg>

                            <!-- Step 2 — plus-video (create / join) -->
                            <svg
                                v-else-if="step.icon === 'link'"
                                data-hiw="icon"
                                class="hiw-will w-20 h-20 md:w-24 md:h-24"
                                viewBox="0 0 96 96"
                                fill="none"
                            >
                                <rect x="10" y="28" width="52" height="40" rx="6" stroke="#edf7f5" stroke-width="2.5" />
                                <path d="M62 42 L84 31 V65 L62 54 Z" stroke="#edf7f5" stroke-width="2.5" stroke-linejoin="round" />
                                <path d="M36 40 V56 M28 48 H44" stroke="#edf7f5" stroke-width="2.5" stroke-linecap="round" />
                            </svg>

                            <!-- Step 3 — video-call room -->
                            <svg
                                v-else
                                data-hiw="icon"
                                class="hiw-will w-20 h-20 md:w-24 md:h-24"
                                viewBox="0 0 96 96"
                                fill="none"
                            >
                                <rect x="10" y="16" width="76" height="50" rx="4" stroke="#edf7f5" stroke-width="2.5" />
                                <rect x="17" y="23" width="30" height="36" rx="2" stroke="#edf7f5" stroke-width="2" />
                                <circle cx="32" cy="36" r="5" stroke="#edf7f5" stroke-width="2" />
                                <path
                                    d="M23 54 C23 47 27 44 32 44 C37 44 41 47 41 54"
                                    stroke="#edf7f5"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                />
                                <rect x="52" y="23" width="27" height="16" rx="2" stroke="#edf7f5" stroke-width="2" />
                                <rect x="52" y="43" width="27" height="16" rx="2" stroke="#edf7f5" stroke-width="2" />
                                <path d="M36 80 H60 M48 66 V80" stroke="#edf7f5" stroke-width="2.5" stroke-linecap="round" />
                            </svg>
                        </div>
                    </div>

                    <!-- 2 · TIMELINE ROW — badge + connector line -->
                    <div class="relative mt-6 h-8">
                        <!--
                            Starts at the badge's centre (left: 1rem) and ends exactly at the next
                            column's badge (column width + gap). The last line only runs a short tail.
                        -->
                        <div
                            data-hiw="line"
                            class="hiw-will absolute"
                            :style="{
                                left: '1rem',
                                top: 'calc(50% - 1px)',
                                height: '2px',
                                width:
                                    index < steps.length - 1
                                        ? 'calc(100% - 1rem + var(--hiw-gap))'
                                        : 'calc(100% - 1rem + var(--hiw-tail))',
                                background: '#84aaa2',
                                transformOrigin: 'left center',
                            }"
                        ></div>
                        <div
                            data-hiw="badge"
                            class="hiw-will absolute left-0 top-0 z-10 w-8 h-8 flex items-center justify-center"
                            style="background: linear-gradient(to right, #0C2C21, #185146); border: 1px solid rgba(132, 170, 162, 0.45);"
                        >
                            <span data-hiw="badge-num" class="text-sm font-bold text-white">{{ index + 1 }}</span>
                        </div>
                    </div>

                    <!-- 3 · TITLE -->
                    <h3
                        data-hiw="title"
                        class="hiw-will mt-6 font-medium"
                        style="font-size: clamp(1.25rem, 1.6vw, 1.5rem); line-height: 1.25; letter-spacing: -0.01em; color: #edf7f5;"
                    >
                        {{ step.title }}
                    </h3>

                    <!-- 4 · DESCRIPTION -->
                    <p
                        data-hiw="desc"
                        class="hiw-will mt-3 text-base leading-relaxed"
                        style="max-width: 22rem; color: rgba(237, 247, 245, 0.55);"
                    >
                        {{ step.description }}
                    </p>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════════════════════════ -->
        <!-- CTA SECTION                                         -->
        <!-- ═══════════════════════════════════════════════════ -->
        <section ref="ctaSection" class="cta-section relative px-6 md:px-16 lg:px-32 py-28 lg:py-36 text-center">

            <!-- Soft static backdrop (not animated) -->
            <div
                class="absolute inset-0 pointer-events-none"
                style="background: radial-gradient(ellipse at center, rgba(24, 81, 70, 0.25) 0%, rgba(24, 81, 70, 0) 65%);"
                aria-hidden="true"
            ></div>

            <div class="relative max-w-3xl mx-auto">
                <h2
                    class="font-medium tracking-tight mb-5"
                    style="font-size: clamp(2rem, 4vw, 3rem); line-height: 1.15; letter-spacing: -0.02em; color: #edf7f5;"
                >
                    Start your next meeting in
                    <span class="hero-serif-italic">one click</span>
                </h2>

                <p
                    class="text-base md:text-lg leading-relaxed max-w-xl mx-auto mb-10"
                    style="color: rgba(237, 247, 245, 0.5);"
                >
                    Create a free account, share a link, and your team is in the room in seconds. No downloads, no setup.
                </p>

                <!-- Animated CTA button — same Inertia link + logic as the Hero button -->
                <Link
                    :href="$page.props.auth.user ? dashboard() : register()"
                    data-cta="button"
                    class="cta-btn relative inline-flex items-center justify-center overflow-hidden px-12 py-5 text-base font-semibold text-white"
                    style="
                        background: linear-gradient(to right, #0C2C21, #185146);
                        border-radius: 1.1rem;
                        box-shadow: 0 0 48px rgba(24, 81, 70, 0.5), 0 0 12px rgba(24, 81, 70, 0.3);
                    "
                >
                    <span data-cta="glow" class="cta-glow" aria-hidden="true"></span>
                    <span class="relative z-10">{{ $page.props.auth.user ? 'Go to Dashboard' : 'Get Started Free' }}</span>
                </Link>

            </div>
        </section>

    </div>
</template>

<style scoped>
/* Serif italic — applied only to the last two words of headlines */
.hero-serif-italic {
    font-family: 'Lora', Georgia, 'Times New Roman', serif;
    font-style: italic;
    font-weight: 400;
}

/* Crossfade between the sticky panel's feature visuals */
.panel-fade-enter-active,
.panel-fade-leave-active {
    transition: opacity 0.45s ease;
}
.panel-fade-enter-from,
.panel-fade-leave-to {
    opacity: 0;
}

/* ───────── How It Works ───────── */

/* Desktop: the section fills the viewport below the navbar so the pinned content is vertically centered */
@media (min-width: 1024px) {
    .hiw-section {
        min-height: calc(100vh - 4.5rem);
    }
}

/*
   --hiw-gap  = column gap (the connector line crosses it to reach the next badge)
   --hiw-tail = how far the last line runs past its column
   Both are 0 on mobile, where each line simply runs to the right edge of its column.
*/
.hiw-grid {
    --hiw-gap: 0px;
    --hiw-tail: 0px;
}
@media (min-width: 1024px) {
    .hiw-grid {
        --hiw-gap: 3rem;
        --hiw-tail: 3rem;
        column-gap: var(--hiw-gap);
    }
}
@media (min-width: 1280px) {
    .hiw-grid {
        --hiw-gap: 5rem;
    }
}

/* Hint the browser about the properties GSAP animates */
.hiw-will {
    will-change: transform, opacity;
}
@media (prefers-reduced-motion: reduce) {
    .hiw-will {
        will-change: auto;
    }
}

/* ───────── CTA ───────── */

/*
   The button is a rounded rectangle about 5.6× wider than it is tall. min-width gives it that
   width on desktop and shrinks to the container on small screens (so it never overflows at 320px).
*/
.cta-btn {
    will-change: transform;
    transform-origin: 50% 50%;
    min-width: min(100%, 22.4rem);
}
.cta-btn:focus-visible {
    outline: 2px solid #84aaa2;
    outline-offset: 4px;
}

/* The light blob that sweeps across the button (positioned and animated by GSAP; hidden at rest) */
.cta-glow {
    position: absolute;
    top: 50%;
    left: 0;
    height: 90%;
    aspect-ratio: 1;
    border-radius: 9999px;
    pointer-events: none;
    background: radial-gradient(
        circle,
        rgba(237, 247, 245, 0.55) 0%,
        rgba(237, 247, 245, 0.22) 40%,
        rgba(237, 247, 245, 0) 70%
    );
    filter: blur(4px);
    opacity: 0;
    will-change: transform, opacity;
}

@media (prefers-reduced-motion: reduce) {
    .cta-btn,
    .cta-glow {
        will-change: auto;
    }
}
</style>

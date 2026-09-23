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
                <h1
                    class="font-medium tracking-tight mb-6"
                    style="
                        font-size: clamp(2.5rem, 6vw, 4.5rem);
                        line-height: 1.1;
                        letter-spacing: -0.03em;
                        color: #edf7f5;
                    "
                >
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
</style>

<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
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
        class="min-h-screen overflow-x-hidden"
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
        <section class="relative overflow-hidden">

            <!-- ── Gradient video background ── -->
            <!--
                The video is referenced from the CDN below.
                To serve it locally, download the file and place it at:
                  public/videos/hero-bg.mp4
                Then change the src to: /videos/hero-bg.mp4
            -->
            <div
                class="absolute z-0 pointer-events-none flex justify-center overflow-hidden"
                style="top: -5rem; left: 0; right: 0; bottom: 0;"
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
            <div class="relative z-10 text-center px-6 pt-28 pb-36 max-w-4xl mx-auto">

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

    </div>
</template>

<style scoped>
/* Serif italic — applied only to the last two words of the headline */
.hero-serif-italic {
    font-family: 'Lora', Georgia, 'Times New Roman', serif;
    font-style: italic;
    font-weight: 400;
}
</style>

<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Form, Head } from '@inertiajs/vue3';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>
    <Head title="Log in">
        <link rel="preconnect" href="https://api.fontshare.com" />
        <link href="https://api.fontshare.com/v2/css?f[]=satoshi@400,500,700&display=swap" rel="stylesheet" />
    </Head>

    <div class="min-h-screen flex flex-col satoshi-font" style="background-color: #191a19;">

        <!-- ─────────────────────────────────────── -->
        <!-- 1. MOBILE NAVBAR (sm:hidden)            -->
        <!--    Gradient background, white text       -->
        <!-- ─────────────────────────────────────── -->
        <nav
            class="sm:hidden flex items-center justify-between px-5 py-4"
            style="background: linear-gradient(to right, #0C2C21, #185146);"
        >
            <!-- Logo + Name -->
            <span class="text-base font-bold tracking-wide" style="color: #ffffff;">
                🎥 MeetWise
            </span>

            <!-- Home link + Hamburger -->
            <div class="flex items-center gap-4">
                <a href="/" class="flex items-center gap-1" style="color: #ffffff;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span class="text-xs font-medium">Home</span>
                </a>
                <button type="button" style="color: #ffffff;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </nav>

        <!-- ─────────────────────────────────────── -->
        <!-- 2. MOBILE HEADING (sm:hidden)           -->
        <!--    Separate from navbar, dark bg         -->
        <!-- ─────────────────────────────────────── -->
        <div class="sm:hidden text-center px-6 pt-7 pb-2">
            <h1 class="text-xl font-bold mb-2" style="color: #edf7f5;">
                Welcome Back
            </h1>
            <p class="text-xs leading-relaxed" style="color: #84aaa2;">
                Log in to access your meetings and connect with your team.
            </p>
        </div>

        <!-- ─────────────────────────────────────── -->
        <!-- 3. MOBILE STEPS (sm:hidden)             -->
        <!--    Separate section, circles use #387064 -->
        <!-- ─────────────────────────────────────── -->
        <div class="sm:hidden flex justify-center items-start gap-2 px-5 mt-5 mb-2">

            <!-- Step 1 (active) -->
            <div class="flex flex-col items-center gap-1.5">
                <div
                    class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                    style="background-color: #387064; color: #ffffff;"
                >1</div>
                <span class="text-[9px] font-semibold text-center" style="color: #edf7f5;">Log in</span>
            </div>

            <!-- Connector -->
            <div class="h-px w-8 flex-shrink-0" style="background-color: #387064; opacity: 0.5; margin-top: 16px;"></div>

            <!-- Step 2 -->
            <div class="flex flex-col items-center gap-1.5">
                <div
                    class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                    style="background-color: rgba(56, 112, 100, 0.2); color: #84aaa2; border: 1px solid #387064;"
                >2</div>
                <span class="text-[9px] font-semibold text-center" style="color: #84aaa2;">Create</span>
            </div>

            <!-- Connector -->
            <div class="h-px w-8 flex-shrink-0" style="background-color: #387064; opacity: 0.5; margin-top: 16px;"></div>

            <!-- Step 3 -->
            <div class="flex flex-col items-center gap-1.5">
                <div
                    class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                    style="background-color: rgba(56, 112, 100, 0.2); color: #84aaa2; border: 1px solid #387064;"
                >3</div>
                <span class="text-[9px] font-semibold text-center" style="color: #84aaa2;">Start/Join</span>
            </div>

        </div>

        <!-- ─────────────────────────────────────── -->
        <!-- MAIN CONTENT ROW                        -->
        <!-- Tablet+: left panel + form              -->
        <!-- Mobile: only form (left panel hidden)   -->
        <!-- ─────────────────────────────────────── -->
        <div class="flex flex-col lg:flex-row flex-1">

            <!-- LEFT PANEL — hidden on mobile, shown on tablet + desktop -->
            <div
                class="hidden sm:flex flex-col relative overflow-hidden lg:w-[55%]"
                style="background: linear-gradient(to bottom right, #0C2C21, #185146);"
            >
                <!-- Decorative circles (desktop only) -->
                <div class="absolute top-[-100px] right-[-100px] w-[380px] h-[380px] rounded-full hidden lg:block"
                     style="background-color: #387064; opacity: 0.18;"></div>
                <div class="absolute bottom-[80px] left-[-80px] w-[260px] h-[260px] rounded-full hidden lg:block"
                     style="background-color: #84aaa2; opacity: 0.08;"></div>
                <div class="absolute top-[40%] right-[10%] w-[120px] h-[120px] rounded-full hidden lg:block"
                     style="background-color: #1e5f51; opacity: 0.25;"></div>

                <div class="relative z-10 p-8 lg:p-16 flex flex-col lg:h-full lg:justify-between">

                    <!-- Logo -->
                    <div class="text-center lg:text-left mb-5 lg:mb-0">
                        <span class="text-xl font-bold tracking-wide" style="color: #edf7f5;">
                            🎥 MeetWise
                        </span>
                    </div>

                    <!-- Heading -->
                    <div class="text-center lg:text-left my-4 lg:my-0">
                        <h1 class="text-3xl lg:text-5xl font-bold leading-tight mb-3" style="color: #edf7f5;">
                            Welcome<br />Back
                        </h1>
                        <p class="text-sm lg:text-base leading-relaxed" style="color: #84aaa2;">
                            Log in to access your meetings and connect with your team.
                        </p>
                    </div>

                    <!-- Tablet circles (sm to lg) -->
                    <div class="flex lg:hidden justify-center items-start gap-2 sm:gap-5 mt-6 pb-2">

                        <div class="flex flex-col items-center gap-2">
                            <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0"
                                 style="background-color: #edf7f5; color: #10372b;">1</div>
                            <span class="text-xs font-semibold text-center" style="color: #edf7f5;">Log in</span>
                        </div>

                        <div class="h-px w-10 sm:w-14 flex-shrink-0" style="background-color: #387064; margin-top: 20px;"></div>

                        <div class="flex flex-col items-center gap-2">
                            <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0"
                                 style="background-color: #174f44; color: #84aaa2;">2</div>
                            <span class="text-xs font-semibold text-center" style="color: #84aaa2;">Create</span>
                        </div>

                        <div class="h-px w-10 sm:w-14 flex-shrink-0" style="background-color: #387064; margin-top: 20px;"></div>

                        <div class="flex flex-col items-center gap-2">
                            <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0"
                                 style="background-color: #174f44; color: #84aaa2;">3</div>
                            <span class="text-xs font-semibold text-center" style="color: #84aaa2;">Start/Join</span>
                        </div>

                    </div>

                    <!-- Desktop cards -->
                    <div class="hidden lg:flex gap-3">

                        <div class="flex-1 rounded-2xl p-5" style="background-color: #edf7f5;">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold mb-3"
                                 style="background-color: #10372b; color: #edf7f5;">1</div>
                            <p class="text-xs font-semibold leading-snug" style="color: #10372b;">Log in to your<br />account</p>
                        </div>

                        <div class="flex-1 rounded-2xl p-5" style="background-color: #174f44;">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold mb-3"
                                 style="background-color: #1e5f51; color: #84aaa2;">2</div>
                            <p class="text-xs font-semibold leading-snug" style="color: #84aaa2;">Create<br />Meeting</p>
                        </div>

                        <div class="flex-1 rounded-2xl p-5" style="background-color: #174f44;">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold mb-3"
                                 style="background-color: #1e5f51; color: #84aaa2;">3</div>
                            <p class="text-xs font-semibold leading-snug" style="color: #84aaa2;">Start / Join<br />Meeting</p>
                        </div>

                    </div>
                </div>
            </div>

            <!-- FORM AREA — always visible -->
            <div
                class="flex-1 flex items-center justify-center p-6 sm:p-8 lg:p-16"
                style="background-color: #191a19;"
            >
                <div class="w-full max-w-sm">

                    <h2 class="text-2xl font-bold mb-1" style="color: #edf7f5;">Log In to Account</h2>
                    <p class="text-sm mb-8" style="color: #5b5d5c;">
                        Enter your email and password below to log in
                    </p>

                    <!-- Status message -->
                    <div v-if="status" class="mb-5 p-3 rounded-lg text-sm font-medium"
                         style="background-color: #1e5f51; color: #edf7f5;">
                        {{ status }}
                    </div>

                    <!-- Form -->
                    <Form
                        v-bind="store.form()"
                        :reset-on-success="['password']"
                        v-slot="{ errors, processing }"
                        class="flex flex-col gap-4"
                    >
                        <div class="flex flex-col gap-1.5">
                            <Label for="email" class="text-sm font-medium" style="color: #edf7f5;">Email address</Label>
                            <Input id="email" type="email" name="email" required autofocus
                                   :tabindex="1" autocomplete="email" placeholder="email@example.com"
                                   class="auth-input h-11 rounded-lg text-sm" />
                            <InputError :message="errors.email" />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <div class="flex items-center justify-between">
                                <Label for="password" class="text-sm font-medium" style="color: #edf7f5;">Password</Label>
                                <TextLink v-if="canResetPassword" :href="request()"
                                          class="text-xs underline underline-offset-4" :tabindex="5"
                                          style="color: #84aaa2;">
                                    Forgot password?
                                </TextLink>
                            </div>
                            <Input id="password" type="password" name="password" required
                                   :tabindex="2" autocomplete="current-password" placeholder="Password"
                                   class="auth-input h-11 rounded-lg text-sm" />
                            <InputError :message="errors.password" />
                        </div>

                        <div class="flex items-center gap-2">
                            <Checkbox id="remember" name="remember" :tabindex="3" />
                            <Label for="remember" class="text-sm cursor-pointer" style="color: #84aaa2;">
                                Remember me
                            </Label>
                        </div>

                        <Button type="submit"
                                class="w-full h-11 mt-1 font-semibold rounded-lg text-sm transition-opacity hover:opacity-90"
                                :tabindex="4" :disabled="processing"
                                data-test="login-button"
                                style="background-color: #edf7f5; color: #10372b;">
                            <Spinner v-if="processing" />
                            Log in
                        </Button>

                        <p v-if="canRegister" class="text-center text-sm" style="color: #5b5d5c;">
                            Don't have an account?
                            <TextLink :href="register()" :tabindex="5"
                                      class="underline underline-offset-4 ml-1"
                                      style="color: #84aaa2;">
                                Sign up
                            </TextLink>
                        </p>

                    </Form>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
.satoshi-font,
.satoshi-font * {
    font-family: 'Satoshi', sans-serif;
}

.auth-input {
    background-color: #0b291f !important;
    border-color: #387064 !important;
    color: #edf7f5 !important;
}

.auth-input::placeholder {
    color: #5b5d5c !important;
}

.auth-input:focus {
    border-color: #84aaa2 !important;
    outline: none !important;
    box-shadow: 0 0 0 2px rgba(56, 112, 100, 0.25) !important;
}
</style>

<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
// import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';
import { Form, Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Register">
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
                Get Started with Us
            </h1>
            <p class="text-xs leading-relaxed" style="color: #84aaa2;">
                Complete these easy steps to register your account.
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
                <span class="text-[9px] font-semibold text-center" style="color: #edf7f5;">Sign up</span>
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
        <!-- Tablet+: left panel + form side by side  -->
        <!-- Mobile: only form (left panel hidden)    -->
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
                            Get Started<br />with Us
                        </h1>
                        <p class="text-sm lg:text-base leading-relaxed" style="color: #84aaa2;">
                            Complete these easy steps to register your account.
                        </p>
                    </div>

                    <!-- Tablet circles (sm to lg) -->
                    <div class="flex lg:hidden justify-center items-start gap-2 sm:gap-5 mt-6 pb-2">

                        <div class="flex flex-col items-center gap-2">
                            <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0"
                                 style="background-color: #edf7f5; color: #10372b;">1</div>
                            <span class="text-xs font-semibold text-center" style="color: #edf7f5;">Sign up</span>
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
                            <p class="text-xs font-semibold leading-snug" style="color: #10372b;">Sign up your<br />account</p>
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

                    <h2 class="text-2xl font-bold mb-1" style="color: #edf7f5;">Sign Up Account</h2>
                    <p class="text-sm mb-6" style="color: #5b5d5c;">
                        Enter your details below to create your account
                    </p>

                    <!-- Flash message -->
                    <div v-if="$page.props.flash?.joinMessage"
                         class="mb-5 p-3 rounded-lg text-sm"
                         style="background-color: #1e5f51; border: 1px solid #387064; color: #edf7f5;">
                        {{ $page.props.flash.joinMessage }}
                    </div>

                    <!-- Google + GitHub (lg+ only, static) -->
                    <div class="hidden lg:flex gap-3 mb-5">
                        <button type="button"
                                class="flex-1 flex items-center justify-center gap-2 h-11 rounded-lg border text-sm font-medium cursor-default"
                                style="border-color: #387064; color: #edf7f5; background: transparent;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4 flex-shrink-0">
                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                            </svg>
                            Google
                        </button>
                        <button type="button"
                                class="flex-1 flex items-center justify-center gap-2 h-11 rounded-lg border text-sm font-medium cursor-default"
                                style="border-color: #387064; color: #edf7f5; background: transparent;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4 flex-shrink-0" fill="currentColor">
                                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                            </svg>
                            Github
                        </button>
                    </div>

                    <!-- Or divider (lg+ only) -->
                    <div class="hidden lg:flex items-center gap-4 mb-5">
                        <div class="flex-1 h-px" style="background-color: #387064; opacity: 0.35;"></div>
                        <span class="text-xs" style="color: #5b5d5c;">Or</span>
                        <div class="flex-1 h-px" style="background-color: #387064; opacity: 0.35;"></div>
                    </div>

                    <!-- Form -->
                    <Form
                        v-bind="store.form()"
                        :reset-on-success="['password', 'password_confirmation']"
                        v-slot="{ errors, processing }"
                        class="flex flex-col gap-4"
                    >
                        <div class="flex flex-col gap-1.5">
                            <Label for="name" class="text-sm font-medium" style="color: #edf7f5;">Name</Label>
                            <Input id="name" type="text" required autofocus :tabindex="1"
                                   autocomplete="name" name="name" placeholder="Full name"
                                   class="auth-input h-11 rounded-lg text-sm" />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <Label for="email" class="text-sm font-medium" style="color: #edf7f5;">Email address</Label>
                            <Input id="email" type="email" required :tabindex="2"
                                   autocomplete="email" name="email" placeholder="email@example.com"
                                   class="auth-input h-11 rounded-lg text-sm" />
                            <InputError :message="errors.email" />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <Label for="password" class="text-sm font-medium" style="color: #edf7f5;">Password</Label>
                            <Input id="password" type="password" required :tabindex="3"
                                   autocomplete="new-password" name="password" placeholder="Password"
                                   class="auth-input h-11 rounded-lg text-sm" />
                            <InputError :message="errors.password" />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <Label for="password_confirmation" class="text-sm font-medium" style="color: #edf7f5;">Confirm password</Label>
                            <Input id="password_confirmation" type="password" required :tabindex="4"
                                   autocomplete="new-password" name="password_confirmation" placeholder="Confirm password"
                                   class="auth-input h-11 rounded-lg text-sm" />
                            <InputError :message="errors.password_confirmation" />
                        </div>

                        <Button type="submit"
                                class="w-full h-11 mt-1 font-semibold rounded-lg text-sm transition-opacity hover:opacity-90"
                                tabindex="5" :disabled="processing"
                                data-test="register-user-button"
                                style="background-color: #edf7f5; color: #10372b;">
                            <Spinner v-if="processing" />
                            Create account
                        </Button>

                        <p class="text-center text-sm" style="color: #5b5d5c;">
                            Already have an account?
                            <TextLink :href="login()" class="underline underline-offset-4 ml-1" :tabindex="6" style="color: #84aaa2;">
                                Log in
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

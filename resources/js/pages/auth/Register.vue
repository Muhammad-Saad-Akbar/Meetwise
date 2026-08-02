<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';
import { Form, Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Register" />

    <div class="min-h-screen flex flex-col lg:flex-row">

        <!-- LEFT PANEL                          -->
        <!-- ═══════════════════════════════════ -->
        <div
            class="relative lg:w-[55%] flex flex-col justify-between p-10 lg:p-16 overflow-hidden"
            style="background-color: #10372b; min-height: 300px;"
        >
            <!-- Decorative background circles -->
            <div
                class="absolute top-[-100px] right-[-100px] w-[380px] h-[380px] rounded-full"
                style="background-color: #387064; opacity: 0.18;"
            ></div>
            <div
                class="absolute bottom-[80px] left-[-80px] w-[260px] h-[260px] rounded-full"
                style="background-color: #84aaa2; opacity: 0.08;"
            ></div>
            <div
                class="absolute top-[40%] right-[10%] w-[120px] h-[120px] rounded-full"
                style="background-color: #1e5f51; opacity: 0.25;"
            ></div>

            <!-- Logo -->
            <div class="relative z-10">
                <span class="text-xl font-bold tracking-wide" style="color: #edf7f5;">
                    🎥 MeetWise
                </span>
            </div>

            <!-- Main heading -->
            <div class="relative z-10 my-10 lg:my-0">
                <h1 class="text-4xl lg:text-5xl font-bold leading-tight mb-4" style="color: #edf7f5;">
                    Get Started<br />with Us
                </h1>
                <p class="text-sm lg:text-base leading-relaxed" style="color: #84aaa2;">
                    Complete these easy steps to<br class="hidden lg:block" />
                    register your account.
                </p>
            </div>

            <!-- Steps row -->
            <div class="relative z-10 flex gap-3">

                <!-- Step 1 — Active (white card) -->
                <div class="flex-1 rounded-2xl p-4 lg:p-5" style="background-color: #edf7f5;">
                    <div
                        class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold mb-3"
                        style="background-color: #10372b; color: #edf7f5;"
                    >1</div>
                    <p class="text-xs font-semibold leading-snug" style="color: #10372b;">
                        Sign up your<br />account
                    </p>
                </div>

                <!-- Step 2 — Dimmed -->
                <div class="flex-1 rounded-2xl p-4 lg:p-5" style="background-color: #174f44;">
                    <div
                        class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold mb-3"
                        style="background-color: #1e5f51; color: #84aaa2;"
                    >2</div>
                    <p class="text-xs font-semibold leading-snug" style="color: #84aaa2;">
                        Create<br />Meeting
                    </p>
                </div>

                <!-- Step 3 — Dimmed -->
                <div class="flex-1 rounded-2xl p-4 lg:p-5" style="background-color: #174f44;">
                    <div
                        class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold mb-3"
                        style="background-color: #1e5f51; color: #84aaa2;"
                    >3</div>
                    <p class="text-xs font-semibold leading-snug" style="color: #84aaa2;">
                        Start / Join<br />Meeting
                    </p>
                </div>

            </div>
        </div>

        <!-- RIGHT PANEL                         -->
        <!-- ═══════════════════════════════════ -->
        <div
            class="flex-1 flex items-center justify-center p-8 lg:p-16"
            style="background-color: #191a19;"
        >
            <div class="w-full max-w-sm">

                <!-- Title -->
                <h2 class="text-2xl font-bold mb-1" style="color: #edf7f5;">
                    Sign Up Account
                </h2>
                <p class="text-sm mb-8" style="color: #5b5d5c;">
                    Enter your details below to create your account
                </p>

                <!-- Meeting link flash message -->
                <div
                    v-if="$page.props.flash?.joinMessage"
                    class="mb-5 p-3 rounded-lg text-sm"
                    style="background-color: #1e5f51; border: 1px solid #387064; color: #edf7f5;"
                >
                    {{ $page.props.flash.joinMessage }}
                </div>

                <!-- Form -->
                <Form
                    v-bind="store.form()"
                    :reset-on-success="['password', 'password_confirmation']"
                    v-slot="{ errors, processing }"
                    class="flex flex-col gap-5"
                >
                    <!-- Name -->
                    <div class="flex flex-col gap-1.5">
                        <Label for="name" class="text-sm font-medium" style="color: #edf7f5;">
                            Name
                        </Label>
                        <Input
                            id="name"
                            type="text"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="name"
                            name="name"
                            placeholder="Full name"
                            class="auth-input h-11 rounded-lg text-sm"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <!-- Email -->
                    <div class="flex flex-col gap-1.5">
                        <Label for="email" class="text-sm font-medium" style="color: #edf7f5;">
                            Email address
                        </Label>
                        <Input
                            id="email"
                            type="email"
                            required
                            :tabindex="2"
                            autocomplete="email"
                            name="email"
                            placeholder="email@example.com"
                            class="auth-input h-11 rounded-lg text-sm"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <!-- Password -->
                    <div class="flex flex-col gap-1.5">
                        <Label for="password" class="text-sm font-medium" style="color: #edf7f5;">
                            Password
                        </Label>
                        <Input
                            id="password"
                            type="password"
                            required
                            :tabindex="3"
                            autocomplete="new-password"
                            name="password"
                            placeholder="Password"
                            class="auth-input h-11 rounded-lg text-sm"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <!-- Confirm Password -->
                    <div class="flex flex-col gap-1.5">
                        <Label for="password_confirmation" class="text-sm font-medium" style="color: #edf7f5;">
                            Confirm password
                        </Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            required
                            :tabindex="4"
                            autocomplete="new-password"
                            name="password_confirmation"
                            placeholder="Confirm password"
                            class="auth-input h-11 rounded-lg text-sm"
                        />
                        <InputError :message="errors.password_confirmation" />
                    </div>

                    <!-- Submit Button -->
                    <Button
                        type="submit"
                        class="w-full h-11 mt-1 font-semibold rounded-lg text-sm transition-opacity hover:opacity-90"
                        tabindex="5"
                        :disabled="processing"
                        data-test="register-user-button"
                        style="background-color: #edf7f5; color: #10372b;"
                    >
                        <Spinner v-if="processing" />
                        Create account
                    </Button>

                    <!-- Login link -->
                    <p class="text-center text-sm" style="color: #5b5d5c;">
                        Already have an account?
                        <TextLink
                            :href="login()"
                            class="underline underline-offset-4 ml-1"
                            :tabindex="6"
                            style="color: #84aaa2;"
                        >
                            Log in
                        </TextLink>
                    </p>

                </Form>
            </div>
        </div>
    </div>
</template>

<style scoped>
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

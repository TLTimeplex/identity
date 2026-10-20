<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { login, register } from '@/routes';

const appName = import.meta.env.VITE_APP_NAME || 'Identity';
</script>

<template>
    <div
        class="welcome-bg relative flex min-h-screen items-center justify-center overflow-hidden p-6 text-[#EDEDEC]"
    >
        <main
            class="glow-box relative z-10 flex w-full max-w-300 flex-col items-center justify-center gap-8 rounded-2xl border border-indigo-400/40 bg-[#0a0a14]/70 p-8 backdrop-blur-md sm:w-auto sm:flex-row sm:gap-12 sm:px-16 sm:py-8"
        >
            <img
                src="/favicon.svg"
                alt="Logo"
                class="size-40 shrink-0 rounded-3xl sm:size-44"
            />

            <div
                class="h-px w-full shrink-0 bg-white/20 sm:h-44 sm:w-px"
                aria-hidden="true"
            ></div>

            <div class="flex w-full flex-col items-center text-center sm:w-80">
                <h1 class="text-3xl font-semibold tracking-tight">
                    {{ appName }}
                </h1>
                <p class="mt-2 mb-6 text-sm text-[#A1A09A]">
                    A simple and secure ID service to sign in once and use your
                    account everywhere.
                </p>
                <nav class="flex w-full flex-col gap-3">
                    <Link
                        :href="login()"
                        class="rounded-md bg-white px-5 py-2 text-center text-sm font-medium text-[#1b1b18] transition hover:bg-[#e5e5e5]"
                    >
                        Log in
                    </Link>
                    <Link
                        :href="register()"
                        class="rounded-md border border-white/30 px-5 py-2 text-center text-sm font-medium transition hover:border-white/60"
                    >
                        Register
                    </Link>
                </nav>
            </div>
        </main>
    </div>
</template>

<style scoped>
.welcome-bg {
    background-color: #05070f;
}

.glow-box {
    box-shadow:
        0 0 12px rgba(129, 140, 248, 0.45),
        0 0 36px rgba(129, 140, 248, 0.2);
}

@keyframes gradient-shift {
    from {
        transform: rotate(-45deg) translateX(0);
    }
    to {
        transform: rotate(-45deg) translateX(100vmax);
    }
}

@media (prefers-reduced-motion: reduce) {
    .welcome-bg::before {
        animation: none;
    }
}
</style>

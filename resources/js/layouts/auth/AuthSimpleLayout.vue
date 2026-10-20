<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        v-if="title === 'Log in to your account'"
        class="login-bg flex min-h-svh flex-col items-center justify-center p-6"
    >
        <main
            class="login-glow-box flex w-full max-w-md flex-col items-center gap-6 rounded-2xl border border-indigo-400/40 bg-[#0a0a14]/70 p-6 text-[#EDEDEC] backdrop-blur-md sm:p-8"
        >
            <Link
                :href="home()"
                class="flex flex-col items-center gap-4 font-medium"
                aria-label="Go to home"
            >
                <img
                    src="/favicon.svg"
                    alt="Identity"
                    class="size-16 rounded-2xl"
                />
            </Link>

            <div class="space-y-2 text-center">
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ title }}
                </h1>
                <p class="text-sm text-[#A1A09A]">{{ description }}</p>
            </div>

            <div class="w-full">
                <slot />
            </div>
        </main>
    </div>

    <div
        v-else
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
    >
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-2 font-medium"
                    >
                        <div
                            class="mb-1 flex h-9 w-9 items-center justify-center rounded-md"
                        >
                            <AppLogoIcon
                                class="size-9 fill-current text-[var(--foreground)] dark:text-white"
                            />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-medium">{{ title }}</h1>
                        <p class="text-center text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>

<style scoped>
.login-bg {
    background-color: #05070f;
}

.login-glow-box {
    box-shadow:
        0 0 12px rgba(129, 140, 248, 0.45),
        0 0 36px rgba(129, 140, 248, 0.2);
}
</style>

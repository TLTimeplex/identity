<script setup lang="ts">
import Heading from '@/components/Heading.vue';

export type DashboardApp = {
    id: number;
    name: string;
    description: string;
    icon: string | null;
    url: string;
};

defineProps<{
    apps: DashboardApp[];
}>();
</script>

<template>
    <section>
        <Heading
            title="Applications"
            description="Your available applications"
        />
        <div
            class="grid auto-rows-fr grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4"
        >
            <a
                v-for="app in apps"
                :key="app.id"
                :href="app.url"
                class="flex items-center gap-4 overflow-hidden rounded-xl border border-sidebar-border/70 p-4 transition-colors hover:bg-accent dark:border-sidebar-border"
            >
                <img
                    :src="app.icon || '/favicon.svg'"
                    :alt="app.name"
                    class="size-16 shrink-0 rounded-lg object-contain"
                />
                <div class="min-w-0 flex-1">
                    <div class="truncate text-lg font-semibold">
                        {{ app.name }}
                    </div>
                    <div class="line-clamp-2 text-sm">
                        {{ app.description }}
                    </div>
                    <div
                        class="truncate text-right text-xs text-muted-foreground"
                    >
                        {{ app.url }}
                    </div>
                </div>
            </a>
        </div>
    </section>
</template>

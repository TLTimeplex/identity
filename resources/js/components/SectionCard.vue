<script setup lang="ts">
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <Card
        class="gap-4 border-sidebar-border/70 py-5 shadow-none dark:border-sidebar-border"
    >
        <CardHeader class="px-5">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1.5">
                    <CardTitle class="text-base">{{ title }}</CardTitle>
                    <CardDescription v-if="description">
                        {{ description }}
                    </CardDescription>
                </div>
                <slot name="actions" />
            </div>
        </CardHeader>
        <CardContent class="px-5">
            <slot />
        </CardContent>
    </Card>
</template>

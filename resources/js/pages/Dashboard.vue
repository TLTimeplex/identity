<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ApplicationGrid from '@/components/ApplicationGrid.vue';
import type { DashboardApp } from '@/components/ApplicationGrid.vue';
import { Separator } from '@/components/ui/separator';
import { dashboard } from '@/routes';

defineProps<{
    apps: DashboardApp[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
    >
        <ApplicationGrid :apps="apps" />
        <Separator />
    </div>
</template>

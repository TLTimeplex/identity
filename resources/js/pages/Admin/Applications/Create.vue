<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import ApplicationFields from '@/components/admin/ApplicationFields.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { admin_applications_index, admin_applications_store } from '@/routes';
import type { ApplicationCategory } from '@/types/application';
import { emptyApplicationForm } from '@/types/application';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Applications', href: admin_applications_index() },
            { title: 'Create application', href: '#' },
        ],
    },
});

defineProps<{
    allCategories: ApplicationCategory[];
    allPermissions: string[];
}>();

const form = useForm(emptyApplicationForm());

const save = () => {
    form.post(admin_applications_store().url);
};
</script>

<template>
    <Head title="Create application" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Create application"
                description="Register a new application"
            />
            <Button variant="outline" as-child>
                <Link :href="admin_applications_index()">
                    <ArrowLeft class="size-4" /> Back
                </Link>
            </Button>
        </div>

        <form
            class="rounded-xl border border-sidebar-border/70 p-4 md:p-6 dark:border-sidebar-border"
            @submit.prevent="save"
        >
            <ApplicationFields
                v-model:form="form"
                :errors="form.errors"
                :all-categories="allCategories"
                :all-permissions="allPermissions"
            />
            <div class="mt-6 flex gap-2">
                <Button type="submit" :disabled="form.processing">
                    Create application
                </Button>
                <Button variant="secondary" type="button" as-child>
                    <Link :href="admin_applications_index()">Cancel</Link>
                </Button>
            </div>
        </form>
    </div>
</template>

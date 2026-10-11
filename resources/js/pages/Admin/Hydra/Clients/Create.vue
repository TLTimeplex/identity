<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import HydraClientFields from '@/components/admin/HydraClientFields.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { admin_hydra_clients_index, admin_hydra_clients_store } from '@/routes';
import { emptyHydraClientForm, updateHydraClientForm } from '@/types/hydra';
import type { HydraClientForm } from '@/types/hydra';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Hydra clients', href: admin_hydra_clients_index() },
            { title: 'Add client', href: '#' },
        ],
    },
});

const form = useForm(emptyHydraClientForm());

const updateField = (field: keyof HydraClientForm, value: string | boolean) =>
    updateHydraClientForm(form, field, value);

const save = () => {
    form.post(admin_hydra_clients_store().url);
};
</script>

<template>
    <Head title="Add Hydra client" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Add Hydra client"
                description="Register a new OAuth 2.0 or OpenID Connect client"
            />
            <Button variant="outline" as-child>
                <Link :href="admin_hydra_clients_index()">
                    <ArrowLeft class="size-4" /> Back
                </Link>
            </Button>
        </div>

        <form
            class="rounded-xl border border-sidebar-border/70 p-4 md:p-6 dark:border-sidebar-border"
            @submit.prevent="save"
        >
            <HydraClientFields
                :form="form"
                :errors="form.errors"
                creating
                @update:field="updateField"
            />
            <div class="mt-6 flex gap-2">
                <Button type="submit" :disabled="form.processing">
                    Create client
                </Button>
                <Button variant="secondary" type="button" as-child>
                    <Link :href="admin_hydra_clients_index()">Cancel</Link>
                </Button>
            </div>
        </form>
    </div>
</template>

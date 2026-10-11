<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Pencil, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import HydraClientFields from '@/components/admin/HydraClientFields.vue';
import ConfirmAction from '@/components/ConfirmAction.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    admin_hydra_clients_destroy,
    admin_hydra_clients_index,
    admin_hydra_clients_update,
} from '@/routes';
import { updateHydraClientForm } from '@/types/hydra';
import type { HydraClientForm } from '@/types/hydra';

type HydraClient = HydraClientForm & {
    client_id: string;
    created_at?: string | null;
    updated_at?: string | null;
};

const props = defineProps<{
    client: HydraClient;
    generatedSecret: string | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Hydra clients', href: admin_hydra_clients_index() },
            { title: 'Client details', href: '#' },
        ],
    },
});

const editing = ref(false);
const auth = usePage().props.auth;
const form = useForm<HydraClientForm>({
    client_name: props.client.client_name,
    client_secret: '',
    redirect_uris: props.client.redirect_uris,
    post_logout_redirect_uris: props.client.post_logout_redirect_uris,
    grant_types: props.client.grant_types,
    response_types: props.client.response_types,
    scope: props.client.scope,
    audience: props.client.audience,
    allowed_cors_origins: props.client.allowed_cors_origins,
    contacts: props.client.contacts,
    token_endpoint_auth_method: props.client.token_endpoint_auth_method,
    owner: props.client.owner ?? '',
    client_uri: props.client.client_uri ?? '',
    logo_uri: props.client.logo_uri ?? '',
    policy_uri: props.client.policy_uri ?? '',
    tos_uri: props.client.tos_uri ?? '',
    skip_consent: props.client.skip_consent,
    skip_logout_consent: props.client.skip_logout_consent,
});

const updateField = (field: keyof HydraClientForm, value: string | boolean) =>
    updateHydraClientForm(form, field, value);

const startEditing = () => {
    form.defaults({
        ...form.data(),
        client_secret: '',
    });
    form.reset();
    editing.value = true;
};

const cancelEditing = () => {
    form.reset();
    form.clearErrors();
    editing.value = false;
};

const save = () => {
    form.patch(admin_hydra_clients_update(props.client.client_id).url, {
        preserveScroll: true,
        onSuccess: () => {
            editing.value = false;
        },
    });
};

const remove = () =>
    router.delete(admin_hydra_clients_destroy(props.client.client_id).url);

const formatDate = (value?: string | null) =>
    value
        ? new Date(value).toLocaleString(undefined, {
              dateStyle: 'medium',
              timeStyle: 'short',
          })
        : '—';
</script>

<template>
    <Head :title="client.client_name || client.client_id" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-start justify-between gap-4">
            <Heading
                :title="client.client_name || 'Hydra client'"
                :description="client.client_id"
            />
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" as-child>
                    <Link :href="admin_hydra_clients_index()">
                        <ArrowLeft class="size-4" /> Back
                    </Link>
                </Button>
                <Button
                    v-if="!editing && auth.canEditHydraClients"
                    @click="startEditing"
                >
                    <Pencil class="size-4" /> Edit
                </Button>
                <ConfirmAction
                    v-if="!editing && auth.canDeleteHydraClients"
                    title="Delete this Hydra client?"
                    description="Applications using this client will no longer be able to authenticate."
                    confirm-label="Delete client"
                    @confirm="remove"
                >
                    <template #trigger>
                        <Button variant="destructive">
                            <Trash2 class="size-4" /> Delete
                        </Button>
                    </template>
                </ConfirmAction>
            </div>
        </div>

        <section
            v-if="generatedSecret"
            class="grid gap-2 rounded-xl border border-amber-500/50 bg-amber-500/5 p-4"
        >
            <Label>New client secret — copy it now</Label>
            <code class="rounded-md bg-muted p-3 text-sm break-all">{{
                generatedSecret
            }}</code>
            <p class="text-sm text-muted-foreground">
                Hydra will not return this secret again. It is held briefly in
                your session for display, then discarded. Leaving or refreshing
                this page will hide it.
            </p>
        </section>

        <form
            class="rounded-xl border border-sidebar-border/70 p-4 md:p-6 dark:border-sidebar-border"
            @submit.prevent="save"
        >
            <div class="mb-6 grid gap-2">
                <Label for="client_id">Client ID</Label>
                <code id="client_id" class="font-mono text-sm break-all">{{
                    client.client_id
                }}</code>
            </div>
            <HydraClientFields
                :form="form"
                :errors="form.errors"
                :disabled="!editing"
                @update:field="updateField"
            />
            <div
                class="mt-6 grid gap-2 text-sm text-muted-foreground sm:grid-cols-2"
            >
                <p>Created: {{ formatDate(client.created_at) }}</p>
                <p>Last updated: {{ formatDate(client.updated_at) }}</p>
            </div>
            <div v-if="editing" class="mt-6 flex gap-2">
                <Button type="submit" :disabled="form.processing">
                    Save changes
                </Button>
                <Button
                    type="button"
                    variant="secondary"
                    @click="cancelEditing"
                >
                    Cancel
                </Button>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Pencil, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import ApplicationFields from '@/components/admin/ApplicationFields.vue';
import ConfirmAction from '@/components/ConfirmAction.vue';
import Heading from '@/components/Heading.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    admin_applications_destroy,
    admin_applications_index,
    admin_applications_update,
} from '@/routes';
import type { ApplicationCategory, ApplicationForm } from '@/types/application';

const props = defineProps<{
    application: {
        id: number;
        name: string;
        description: string | null;
        url: string | null;
        icon: string | null;
        is_active: boolean;
        category: string | null;
        required_permission: string | null;
        hydra_client_id: string | null;
        created_at: string;
        updated_at: string;
    };
    allCategories: ApplicationCategory[];
    allPermissions: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Applications', href: admin_applications_index() },
            { title: 'Application details', href: '#' },
        ],
    },
});

const page = usePage();
const canEdit = computed(() => page.props.auth.canEditAdmin);
const canDelete = computed(() => page.props.auth.canDeleteAdmin);

const editing = ref(false);

const initial = (): ApplicationForm => ({
    name: props.application.name,
    description: props.application.description ?? '',
    url: props.application.url ?? '',
    icon: props.application.icon ?? '',
    is_active: props.application.is_active,
    category: props.application.category ?? '',
    required_permission: props.application.required_permission ?? '',
    hydra_client_id: props.application.hydra_client_id ?? '',
});

const form = useForm(initial());

const formatDate = (value: string) =>
    new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });

const startEditing = () => {
    form.defaults(initial());
    form.reset();
    editing.value = true;
};

const cancelEditing = () => {
    form.reset();
    form.clearErrors();
    editing.value = false;
};

const save = () => {
    form.patch(admin_applications_update(props.application.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            editing.value = false;
        },
    });
};
</script>

<template>
    <Head :title="application.name" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-start justify-between gap-4">
            <Heading
                :title="application.name"
                :description="application.description ?? ''"
            />
            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link :href="admin_applications_index()">
                        <ArrowLeft class="size-4" /> Back
                    </Link>
                </Button>
                <Button v-if="canEdit && !editing" @click="startEditing">
                    <Pencil class="size-4" /> Edit
                </Button>
            </div>
        </div>

        <div class="grid gap-4">
            <SectionCard
                title="Details"
                description="Application settings and access requirements"
            >
                <form class="grid gap-6" @submit.prevent="save">
                    <ApplicationFields
                        v-model:form="form"
                        :errors="form.errors"
                        :all-categories="allCategories"
                        :all-permissions="allPermissions"
                        :disabled="!editing"
                    />

                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label>Created</Label>
                            <p class="text-sm text-muted-foreground">
                                {{ formatDate(application.created_at) }}
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label>Last updated</Label>
                            <p class="text-sm text-muted-foreground">
                                {{ formatDate(application.updated_at) }}
                            </p>
                        </div>
                    </div>

                    <div v-if="editing" class="flex gap-2">
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
            </SectionCard>

            <SectionCard
                v-if="canDelete"
                title="Danger zone"
                description="Permanently remove this application"
            >
                <ConfirmAction
                    title="Delete this application?"
                    :description="`${application.name} will be permanently removed.`"
                    confirm-label="Delete application"
                    :confirm-text="application.name"
                    @confirm="
                        router.delete(
                            admin_applications_destroy(application.id).url,
                        )
                    "
                >
                    <template #trigger>
                        <Button variant="destructive">
                            <Trash2 class="size-4" /> Delete application
                        </Button>
                    </template>
                </ConfirmAction>
            </SectionCard>
        </div>
    </div>
</template>

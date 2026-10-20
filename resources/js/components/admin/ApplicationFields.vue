<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Check, ChevronsUpDown, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    admin_applications_categories_destroy,
    admin_applications_categories_store,
} from '@/routes';
import type { ApplicationCategory, ApplicationForm } from '@/types/application';

const form = defineModel<ApplicationForm>('form', { required: true });

defineProps<{
    errors: Partial<Record<keyof ApplicationForm, string>>;
    allCategories: ApplicationCategory[];
    allPermissions: string[];
    disabled?: boolean;
}>();

const page = usePage();
const canEdit = computed(() => page.props.auth.canEditAdmin);
const canDelete = computed(() => page.props.auth.canDeleteAdmin);

const menuOpen = ref(false);
const newCategory = ref('');
const categoryError = ref<string | undefined>();

const addCategory = () => {
    const name = newCategory.value.trim();

    if (!name) {
        return;
    }

    router.post(
        admin_applications_categories_store().url,
        { name },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                form.value.category = name;
                newCategory.value = '';
                categoryError.value = undefined;
                menuOpen.value = false;
            },
            onError: (e) => {
                categoryError.value = e.name;
            },
        },
    );
};

const removeCategory = (category: ApplicationCategory) => {
    if (!window.confirm(`Delete category "${category.name}"?`)) {
        return;
    }

    router.delete(admin_applications_categories_destroy(category.id).url, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            if (form.value.category === category.name) {
                form.value.category = '';
            }
        },
    });
};

const selectClass =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50';
</script>

<template>
    <div class="grid gap-6 md:grid-cols-2">
        <div class="grid gap-2">
            <Label for="name">Name</Label>
            <Input
                id="name"
                v-model="form.name"
                :disabled="disabled"
                required
            />
            <InputError :message="errors.name" />
        </div>
        <div class="grid gap-2">
            <Label for="url">URL</Label>
            <Input
                id="url"
                v-model="form.url"
                type="url"
                placeholder="https://"
                :disabled="disabled"
            />
            <InputError :message="errors.url" />
        </div>
        <div class="grid gap-2 md:col-span-2">
            <Label for="description">Description</Label>
            <Input
                id="description"
                v-model="form.description"
                :disabled="disabled"
            />
            <InputError :message="errors.description" />
        </div>
        <div class="grid gap-2">
            <Label for="icon">Icon</Label>
            <Input id="icon" v-model="form.icon" :disabled="disabled" />
            <InputError :message="errors.icon" />
        </div>
        <div class="grid gap-2">
            <Label for="hydra_client_id">Hydra client ID</Label>
            <Input
                id="hydra_client_id"
                v-model="form.hydra_client_id"
                class="font-mono"
                :disabled="disabled"
            />
            <InputError :message="errors.hydra_client_id" />
        </div>
        <div class="grid gap-2">
            <Label for="category">Category</Label>
            <DropdownMenu v-model:open="menuOpen">
                <DropdownMenuTrigger as-child :disabled="disabled">
                    <Button
                        id="category"
                        type="button"
                        variant="outline"
                        class="w-full justify-between font-normal"
                        :disabled="disabled"
                    >
                        <span
                            :class="{ 'text-muted-foreground': !form.category }"
                        >
                            {{ form.category || 'None' }}
                        </span>
                        <ChevronsUpDown class="size-4 opacity-50" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    align="start"
                    class="w-(--reka-dropdown-menu-trigger-width) min-w-56"
                >
                    <DropdownMenuItem @select="form.category = ''">
                        <Check
                            class="size-4"
                            :class="{ 'opacity-0': form.category !== '' }"
                        />
                        None
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        v-for="c in allCategories"
                        :key="c.id"
                        class="justify-between"
                        @select="form.category = c.name"
                    >
                        <span class="flex items-center gap-2">
                            <Check
                                class="size-4"
                                :class="{
                                    'opacity-0': form.category !== c.name,
                                }"
                            />
                            {{ c.name }}
                        </span>
                        <button
                            v-if="canDelete"
                            type="button"
                            class="rounded p-1 text-muted-foreground hover:text-destructive"
                            :aria-label="`Delete category ${c.name}`"
                            @click.stop.prevent="removeCategory(c)"
                        >
                            <Trash2 class="size-4" />
                        </button>
                    </DropdownMenuItem>
                    <template v-if="canEdit">
                        <DropdownMenuSeparator />
                        <div class="flex gap-2 p-1" @keydown.stop>
                            <Input
                                v-model="newCategory"
                                placeholder="New category"
                                class="h-8"
                                @keydown.enter.prevent="addCategory"
                            />
                            <Button
                                type="button"
                                size="sm"
                                :disabled="!newCategory.trim()"
                                @click="addCategory"
                            >
                                <Plus class="size-4" />
                            </Button>
                        </div>
                    </template>
                </DropdownMenuContent>
            </DropdownMenu>
            <InputError :message="categoryError" />
            <InputError :message="errors.category" />
        </div>
        <div class="grid gap-2">
            <Label for="required_permission">Required permission</Label>
            <select
                id="required_permission"
                v-model="form.required_permission"
                :class="selectClass"
                :disabled="disabled"
            >
                <option value="">None</option>
                <option v-for="p in allPermissions" :key="p" :value="p">
                    {{ p }}
                </option>
            </select>
            <InputError :message="errors.required_permission" />
        </div>
        <div class="flex items-center gap-2 md:col-span-2">
            <Checkbox
                id="is_active"
                :model-value="form.is_active"
                :disabled="disabled"
                @update:model-value="form.is_active = $event === true"
            />
            <Label for="is_active" class="font-normal"
                >Application is active</Label
            >
        </div>
    </div>
</template>

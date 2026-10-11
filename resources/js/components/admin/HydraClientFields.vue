<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { HydraClientForm } from '@/types/hydra';

defineProps<{
    form: HydraClientForm;
    errors: Record<string, string | undefined>;
    disabled?: boolean;
    creating?: boolean;
}>();

const emit = defineEmits<{
    'update:field': [field: keyof HydraClientForm, value: string | boolean];
}>();
</script>

<template>
    <div class="grid gap-6 md:grid-cols-2">
        <div class="grid gap-2">
            <Label for="client_name">Client name</Label>
            <Input
                id="client_name"
                :model-value="form.client_name"
                :disabled="disabled"
                required
                @update:model-value="
                    emit('update:field', 'client_name', String($event))
                "
            />
            <InputError :message="errors.client_name" />
        </div>
        <div class="grid gap-2">
            <Label for="token_endpoint_auth_method">
                Token endpoint authentication
            </Label>
            <Input
                id="token_endpoint_auth_method"
                :model-value="form.token_endpoint_auth_method"
                :disabled="disabled"
                required
                @update:model-value="
                    emit(
                        'update:field',
                        'token_endpoint_auth_method',
                        String($event),
                    )
                "
            />
            <InputError :message="errors.token_endpoint_auth_method" />
        </div>

        <div class="grid gap-2 md:col-span-2">
            <Label for="client_secret">
                {{
                    creating ? 'Client secret (optional)' : 'New client secret'
                }}
            </Label>
            <Input
                id="client_secret"
                :model-value="form.client_secret"
                type="password"
                autocomplete="new-password"
                :disabled="disabled"
                :placeholder="
                    creating
                        ? 'Leave blank to let Hydra generate one'
                        : 'Leave blank to keep the current secret'
                "
                @update:model-value="
                    emit('update:field', 'client_secret', String($event))
                "
            />
            <p class="text-xs text-muted-foreground">
                {{
                    creating
                        ? 'Hydra returns this secret only once. It is held briefly in your session for display, then discarded.'
                        : 'Hydra does not reveal saved secrets. Enter a new value to rotate it; the new secret will be shown once.'
                }}
            </p>
            <InputError :message="errors.client_secret" />
        </div>

        <div
            v-for="field in [
                ['redirect_uris', 'Redirect URIs'],
                ['post_logout_redirect_uris', 'Post-logout redirect URIs'],
                ['grant_types', 'Grant types'],
                ['response_types', 'Response types'],
                ['scope', 'Scopes'],
                ['audience', 'Audience'],
                ['allowed_cors_origins', 'Allowed CORS origins'],
                ['contacts', 'Contacts'],
            ] as const"
            :key="field[0]"
            class="grid gap-2"
        >
            <Label :for="field[0]">{{ field[1] }}</Label>
            <textarea
                :id="field[0]"
                :value="form[field[0]]"
                :disabled="disabled"
                rows="3"
                class="flex min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                :placeholder="
                    field[0] === 'scope'
                        ? 'openid profile email'
                        : 'Enter one value per line'
                "
                @input="
                    emit(
                        'update:field',
                        field[0],
                        ($event.target as HTMLTextAreaElement).value,
                    )
                "
            />
            <InputError :message="errors[field[0]]" />
        </div>

        <div
            v-for="field in [
                ['owner', 'Owner'],
                ['client_uri', 'Client URI'],
                ['logo_uri', 'Logo URI'],
                ['policy_uri', 'Policy URI'],
                ['tos_uri', 'Terms of service URI'],
            ] as const"
            :key="field[0]"
            class="grid gap-2"
        >
            <Label :for="field[0]">{{ field[1] }}</Label>
            <Input
                :id="field[0]"
                :model-value="form[field[0]]"
                :disabled="disabled"
                @update:model-value="
                    emit('update:field', field[0], String($event))
                "
            />
            <InputError :message="errors[field[0]]" />
        </div>

        <div class="flex flex-wrap gap-x-6 gap-y-3 md:col-span-2">
            <div class="flex items-center gap-2">
                <Checkbox
                    id="skip_consent"
                    :model-value="form.skip_consent"
                    :disabled="disabled"
                    @update:model-value="
                        emit('update:field', 'skip_consent', $event === true)
                    "
                />
                <Label for="skip_consent" class="font-normal">
                    Skip consent screen
                </Label>
            </div>
            <div class="flex items-center gap-2">
                <Checkbox
                    id="skip_logout_consent"
                    :model-value="form.skip_logout_consent"
                    :disabled="disabled"
                    @update:model-value="
                        emit(
                            'update:field',
                            'skip_logout_consent',
                            $event === true,
                        )
                    "
                />
                <Label for="skip_logout_consent" class="font-normal">
                    Skip logout consent
                </Label>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, KeyRound, LogOut, Pencil, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmAction from '@/components/ConfirmAction.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import SectionCard from '@/components/SectionCard.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    admin_users_destroy,
    admin_users_index,
    admin_users_passkey_destroy,
    admin_users_passkeys_destroy,
    admin_users_sessions_destroy,
    admin_users_two_factor_destroy,
    admin_users_update,
} from '@/routes';

type UserDetail = {
    id: number;
    name: string;
    email: string;
    oidc_subject: string;
    email_verified_at: string | null;
    two_factor_enabled: boolean;
    two_factor_pending: boolean;
    created_at: string;
    updated_at: string;
    roles: string[];
};

type LogEntry = {
    id: number | string;
    message: string;
    created_at: string;
};

const props = defineProps<{
    user: UserDetail;
    allRoles: string[];
    passkeys: Array<{
        id: number;
        name: string;
        created_at: string;
        last_used_at: string | null;
    }>;
    sessions: { total: number; active: number };
    logs: LogEntry[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Users', href: admin_users_index() },
            { title: 'User details', href: '#' },
        ],
    },
});

const editing = ref(false);

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    verified: props.user.email_verified_at !== null,
    roles: [...props.user.roles],
});

const formatDate = (value: string | null) =>
    value
        ? new Date(value).toLocaleString(undefined, {
              dateStyle: 'medium',
              timeStyle: 'short',
          })
        : 'Never';

const startEditing = () => {
    form.defaults({
        name: props.user.name,
        email: props.user.email,
        verified: props.user.email_verified_at !== null,
        roles: [...props.user.roles],
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
    form.patch(admin_users_update(props.user.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            editing.value = false;
        },
    });
};

const toggleRole = (role: string, checked: boolean | 'indeterminate') => {
    form.roles =
        checked === true
            ? [...new Set([...form.roles, role])]
            : form.roles.filter((r) => r !== role);
};

const shownRoles = computed(() =>
    editing.value ? form.roles : props.user.roles,
);

const remove = (url: string) => router.delete(url, { preserveScroll: true });
</script>

<template>
    <Head :title="user.name" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-start justify-between gap-4">
            <Heading :title="user.name" :description="user.email" />
            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link :href="admin_users_index()">
                        <ArrowLeft class="size-4" /> Back
                    </Link>
                </Button>
                <Button v-if="!editing" @click="startEditing">
                    <Pencil class="size-4" /> Edit
                </Button>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <SectionCard
                title="Details"
                description="Account information and roles"
                class="lg:col-span-2"
            >
                <form class="grid gap-6 md:grid-cols-2" @submit.prevent="save">
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            :disabled="!editing"
                            required
                        />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input
                            id="email"
                            v-model="form.email"
                            type="email"
                            :disabled="!editing"
                            required
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label>Email verification</Label>
                        <div v-if="editing" class="flex items-center gap-2">
                            <Checkbox
                                id="verified"
                                :model-value="form.verified"
                                @update:model-value="
                                    form.verified = $event === true
                                "
                            />
                            <Label for="verified" class="font-normal">
                                Email address is verified
                            </Label>
                        </div>
                        <div v-else class="flex items-center gap-2 text-sm">
                            <Badge
                                :variant="
                                    user.email_verified_at
                                        ? 'default'
                                        : 'secondary'
                                "
                            >
                                {{
                                    user.email_verified_at
                                        ? 'Verified'
                                        : 'Unverified'
                                }}
                            </Badge>
                            <span
                                v-if="user.email_verified_at"
                                class="text-muted-foreground"
                            >
                                {{ formatDate(user.email_verified_at) }}
                            </span>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label>OIDC subject</Label>
                        <p class="font-mono text-sm text-muted-foreground">
                            {{ user.oidc_subject }}
                        </p>
                    </div>

                    <div class="grid gap-2 md:col-span-2">
                        <Label>Roles</Label>
                        <div
                            v-if="editing"
                            class="flex flex-wrap gap-x-6 gap-y-3"
                        >
                            <div
                                v-for="role in allRoles"
                                :key="role"
                                class="flex items-center gap-2"
                            >
                                <Checkbox
                                    :id="`role-${role}`"
                                    :model-value="form.roles.includes(role)"
                                    @update:model-value="
                                        toggleRole(role, $event)
                                    "
                                />
                                <Label
                                    :for="`role-${role}`"
                                    class="font-normal"
                                >
                                    {{ role }}
                                </Label>
                            </div>
                            <span
                                v-if="allRoles.length === 0"
                                class="text-sm text-muted-foreground"
                            >
                                No roles defined.
                            </span>
                        </div>
                        <div v-else class="flex flex-wrap gap-1">
                            <Badge
                                v-for="role in shownRoles"
                                :key="role"
                                variant="secondary"
                            >
                                {{ role }}
                            </Badge>
                            <span
                                v-if="shownRoles.length === 0"
                                class="text-sm text-muted-foreground"
                                >No roles assigned</span
                            >
                        </div>
                        <InputError :message="form.errors.roles" />
                    </div>

                    <div class="grid gap-2">
                        <Label>Created</Label>
                        <p class="text-sm text-muted-foreground">
                            {{ formatDate(user.created_at) }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label>Last updated</Label>
                        <p class="text-sm text-muted-foreground">
                            {{ formatDate(user.updated_at) }}
                        </p>
                    </div>

                    <div v-if="editing" class="flex gap-2 md:col-span-2">
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
                title="Two-factor authentication"
                description="Authenticator app protection"
            >
                <div class="flex items-center justify-between gap-4">
                    <Badge
                        :variant="
                            user.two_factor_enabled ? 'default' : 'secondary'
                        "
                    >
                        {{
                            user.two_factor_enabled
                                ? 'Enabled'
                                : user.two_factor_pending
                                  ? 'Setup unconfirmed'
                                  : 'Disabled'
                        }}
                    </Badge>
                    <ConfirmAction
                        v-if="
                            user.two_factor_enabled || user.two_factor_pending
                        "
                        title="Remove two-factor authentication?"
                        description="The user will no longer be asked for a code at login and must set up two-factor authentication again."
                        @confirm="
                            remove(admin_users_two_factor_destroy(user.id).url)
                        "
                    >
                        <template #trigger>
                            <Button variant="destructive" size="sm">
                                <Trash2 class="size-4" /> Remove 2FA
                            </Button>
                        </template>
                    </ConfirmAction>
                </div>
            </SectionCard>

            <SectionCard
                title="Sessions"
                description="Sessions started by this user"
            >
                <template #actions>
                    <ConfirmAction
                        v-if="sessions.total > 0"
                        title="Sign out of all sessions?"
                        description="The user will be logged out on every device and must sign in again."
                        confirm-label="Sign out everywhere"
                        @confirm="
                            remove(admin_users_sessions_destroy(user.id).url)
                        "
                    >
                        <template #trigger>
                            <Button variant="destructive" size="sm">
                                <LogOut class="size-4" /> Sign out everywhere
                            </Button>
                        </template>
                    </ConfirmAction>
                </template>
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-lg border p-4">
                        <p class="text-2xl font-semibold">
                            {{ sessions.total }}
                        </p>
                        <p class="text-xs text-muted-foreground">Started</p>
                    </div>
                    <div class="rounded-lg border p-4">
                        <p class="text-2xl font-semibold">
                            {{ sessions.active }}
                        </p>
                        <p class="text-xs text-muted-foreground">Still open</p>
                    </div>
                </div>
            </SectionCard>

            <SectionCard
                title="Passkeys"
                description="Passwordless credentials registered by this user"
                class="lg:col-span-2"
            >
                <template #actions>
                    <ConfirmAction
                        v-if="passkeys.length > 0"
                        title="Remove all passkeys?"
                        description="The user will no longer be able to sign in with any of these passkeys."
                        confirm-label="Remove all"
                        @confirm="
                            remove(admin_users_passkeys_destroy(user.id).url)
                        "
                    >
                        <template #trigger>
                            <Button variant="destructive" size="sm">
                                <Trash2 class="size-4" /> Remove all
                            </Button>
                        </template>
                    </ConfirmAction>
                </template>

                <p
                    v-if="passkeys.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    No passkeys registered.
                </p>
                <div v-else class="overflow-hidden rounded-lg border">
                    <div
                        v-for="passkey in passkeys"
                        :key="passkey.id"
                        class="flex items-center justify-between gap-4 border-b p-3 last:border-b-0"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex size-9 items-center justify-center rounded-lg bg-muted"
                            >
                                <KeyRound
                                    class="size-4 text-muted-foreground"
                                />
                            </div>
                            <div class="text-sm leading-tight">
                                <p class="font-medium">{{ passkey.name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    Added {{ formatDate(passkey.created_at) }} ·
                                    Last used
                                    {{ formatDate(passkey.last_used_at) }}
                                </p>
                            </div>
                        </div>
                        <ConfirmAction
                            title="Remove this passkey?"
                            :description="`“${passkey.name}” will no longer work for sign-in.`"
                            @confirm="
                                remove(
                                    admin_users_passkey_destroy({
                                        user: user.id,
                                        passkey: passkey.id,
                                    }).url,
                                )
                            "
                        >
                            <template #trigger>
                                <Button variant="ghost" size="icon">
                                    <Trash2 class="size-4" />
                                </Button>
                            </template>
                        </ConfirmAction>
                    </div>
                </div>
            </SectionCard>

            <SectionCard
                title="Danger zone"
                description="Permanently delete this user, their passkeys, two-factor setup and sessions. This cannot be undone."
                class="border-destructive/50! lg:col-span-2"
            >
                <ConfirmAction
                    title="Delete this user?"
                    :description="`${user.name} will be permanently removed from the system and signed out everywhere.`"
                    confirm-label="Delete user"
                    :confirm-text="user.email"
                    @confirm="remove(admin_users_destroy(user.id).url)"
                >
                    <template #trigger>
                        <Button variant="destructive">
                            <Trash2 class="size-4" /> Delete user
                        </Button>
                    </template>
                </ConfirmAction>
            </SectionCard>

            <SectionCard
                title="Logs"
                description="Activity connected to this user"
                class="lg:col-span-2"
            >
                <p
                    v-if="logs.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    No log entries yet.
                </p>
                <ul v-else class="divide-y rounded-lg border text-sm">
                    <li
                        v-for="log in logs"
                        :key="log.id"
                        class="flex justify-between gap-4 p-3"
                    >
                        <span>{{ log.message }}</span>
                        <span class="text-muted-foreground">
                            {{ formatDate(log.created_at) }}
                        </span>
                    </li>
                </ul>
            </SectionCard>
        </div>
    </div>
</template>

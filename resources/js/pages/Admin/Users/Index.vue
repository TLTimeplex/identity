<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useInitials } from '@/composables/useInitials';
import { admin_users_index } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Users',
                href: admin_users_index(),
            },
        ],
    },
});

defineProps<{
    users: Array<{
        name: string;
        email: string;
        id: number;
        oidc_subject: string;
        created_at: string;
        updated_at: string;
        roles: Array<{
            name: string;
        }>;
    }>;
}>();

const { getInitials } = useInitials();

const formatDate = (value: string) =>
    new Date(value).toLocaleDateString(undefined, { dateStyle: 'medium' });
</script>

<template>
    <Head title="Users" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <Heading
            title="Users"
            description="All registered users and their roles"
        />

        <div
            class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <Table>
                <TableHeader>
                    <TableRow class="hover:bg-transparent">
                        <TableHead>User</TableHead>
                        <TableHead>Roles</TableHead>
                        <TableHead>OIDC subject</TableHead>
                        <TableHead>Created</TableHead>
                        <TableHead>Updated</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="users.length === 0" :colspan="5">
                        No users found.
                    </TableEmpty>
                    <TableRow v-for="user in users" :key="user.id">
                        <TableCell>
                            <div class="flex items-center gap-3">
                                <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
                                    <AvatarFallback
                                        class="rounded-lg text-black dark:text-white"
                                    >
                                        {{ getInitials(user.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="grid text-sm leading-tight">
                                    <span class="font-medium">{{ user.name }}</span>
                                    <span class="text-xs text-muted-foreground">
                                        {{ user.email }}
                                    </span>
                                </div>
                            </div>
                        </TableCell>
                        <TableCell>
                            <div class="flex flex-wrap gap-1">
                                <Badge
                                    v-for="role in user.roles"
                                    :key="role.name"
                                    variant="secondary"
                                >
                                    {{ role.name }}
                                </Badge>
                                <span
                                    v-if="user.roles.length === 0"
                                    class="text-muted-foreground"
                                    >—</span
                                >
                            </div>
                        </TableCell>
                        <TableCell
                            class="max-w-48 truncate font-mono text-xs text-muted-foreground"
                        >
                            {{ user.oidc_subject }}
                        </TableCell>
                        <TableCell class="whitespace-nowrap text-muted-foreground">
                            {{ formatDate(user.created_at) }}
                        </TableCell>
                        <TableCell class="whitespace-nowrap text-muted-foreground">
                            {{ formatDate(user.updated_at) }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>
</template>

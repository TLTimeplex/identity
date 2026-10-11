<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    admin_hydra_clients_create,
    admin_hydra_clients_index,
    admin_hydra_clients_show,
} from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Hydra clients', href: admin_hydra_clients_index() },
        ],
    },
});

defineProps<{
    clients: Array<{
        client_id: string;
        client_name: string;
        scope: string;
        grant_types: string[];
        redirect_uri_count: number;
        created_at: string | null;
    }>;
}>();

const page = usePage();
const canEdit = computed(() => page.props.auth.canEditHydraClients);
</script>

<template>
    <Head title="Hydra clients" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Hydra clients"
                description="OAuth 2.0 and OpenID Connect clients registered with Hydra"
            />
            <Button v-if="canEdit" as-child>
                <Link :href="admin_hydra_clients_create()">
                    <Plus class="size-4" /> Add client
                </Link>
            </Button>
        </div>

        <div
            class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <Table>
                <TableHeader>
                    <TableRow class="hover:bg-transparent">
                        <TableHead>Client</TableHead>
                        <TableHead>Client ID</TableHead>
                        <TableHead>Grant types</TableHead>
                        <TableHead>Redirect URIs</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="clients.length === 0" :colspan="4">
                        No Hydra clients found.
                    </TableEmpty>
                    <TableRow
                        v-for="client in clients"
                        :key="client.client_id"
                        class="cursor-pointer"
                        @click="
                            router.visit(
                                admin_hydra_clients_show(client.client_id),
                            )
                        "
                    >
                        <TableCell>
                            <Link
                                :href="
                                    admin_hydra_clients_show(client.client_id)
                                "
                                class="font-medium hover:underline"
                                @click.stop
                            >
                                {{ client.client_name || 'Unnamed client' }}
                            </Link>
                            <p
                                class="mt-1 max-w-64 truncate text-xs text-muted-foreground"
                            >
                                {{ client.scope || 'No scopes' }}
                            </p>
                        </TableCell>
                        <TableCell class="font-mono text-xs">
                            {{ client.client_id }}
                        </TableCell>
                        <TableCell>
                            <div class="flex flex-wrap gap-1">
                                <Badge
                                    v-for="grant in client.grant_types"
                                    :key="grant"
                                    variant="secondary"
                                >
                                    {{ grant }}
                                </Badge>
                                <span
                                    v-if="client.grant_types.length === 0"
                                    class="text-muted-foreground"
                                    >—</span
                                >
                            </div>
                        </TableCell>
                        <TableCell>{{ client.redirect_uri_count }}</TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>
</template>

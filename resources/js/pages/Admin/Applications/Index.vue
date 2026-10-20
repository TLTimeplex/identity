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
    admin_applications_create,
    admin_applications_index,
    admin_applications_show,
} from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Applications', href: admin_applications_index() },
        ],
    },
});

defineProps<{
    applications: Array<{
        id: number;
        name: string;
        description: string | null;
        url: string | null;
        is_active: boolean;
        category: string | null;
        required_permission: string | null;
        created_at: string;
        updated_at: string;
    }>;
}>();

const page = usePage();
const canEdit = computed(() => page.props.auth.canEditAdmin);

const formatDate = (value: string) =>
    new Date(value).toLocaleDateString(undefined, { dateStyle: 'medium' });
</script>

<template>
    <Head title="Applications" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Applications"
                description="Applications connected to this identity provider"
            />
            <Button v-if="canEdit" as-child>
                <Link :href="admin_applications_create()">
                    <Plus class="size-4" /> Create application
                </Link>
            </Button>
        </div>

        <div
            class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <Table>
                <TableHeader>
                    <TableRow class="hover:bg-transparent">
                        <TableHead>Application</TableHead>
                        <TableHead>Category</TableHead>
                        <TableHead>Required permission</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Created</TableHead>
                        <TableHead>Updated</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="applications.length === 0" :colspan="6">
                        No applications found.
                    </TableEmpty>
                    <TableRow
                        v-for="app in applications"
                        :key="app.id"
                        class="cursor-pointer"
                        @click="router.visit(admin_applications_show(app.id))"
                    >
                        <TableCell>
                            <div class="grid text-sm leading-tight">
                                <Link
                                    :href="admin_applications_show(app.id)"
                                    class="font-medium hover:underline"
                                    @click.stop
                                >
                                    {{ app.name }}
                                </Link>
                                <span class="text-xs text-muted-foreground">
                                    {{ app.url ?? app.description ?? '' }}
                                </span>
                            </div>
                        </TableCell>
                        <TableCell>
                            <Badge v-if="app.category" variant="secondary">
                                {{ app.category }}
                            </Badge>
                            <span v-else class="text-muted-foreground">—</span>
                        </TableCell>
                        <TableCell class="font-mono text-xs">
                            {{ app.required_permission ?? '—' }}
                        </TableCell>
                        <TableCell>
                            <Badge
                                :variant="
                                    app.is_active ? 'default' : 'secondary'
                                "
                            >
                                {{ app.is_active ? 'Active' : 'Inactive' }}
                            </Badge>
                        </TableCell>
                        <TableCell
                            class="whitespace-nowrap text-muted-foreground"
                        >
                            {{ formatDate(app.created_at) }}
                        </TableCell>
                        <TableCell
                            class="whitespace-nowrap text-muted-foreground"
                        >
                            {{ formatDate(app.updated_at) }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>
</template>

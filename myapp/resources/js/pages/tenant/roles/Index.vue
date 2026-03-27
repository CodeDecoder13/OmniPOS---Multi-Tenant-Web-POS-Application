<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Edit, Plus, Shield, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { BreadcrumbItem, GroupedPermissions, Role } from '@/types';
import { useTenant } from '@/composables/useTenant';
import { usePermissions } from '@/composables/usePermissions';

const props = defineProps<{
    roles: Role[];
    groupedPermissions: GroupedPermissions;
}>();

const { tenantUrl } = useTenant();
const { can } = usePermissions();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: tenantUrl('dashboard') },
    { title: 'Roles', href: tenantUrl('roles') },
];

const deleteDialog = ref(false);
const roleToDelete = ref<Role | null>(null);
const deleting = ref(false);

const permissionsDialog = ref(false);
const permissionsRole = ref<Role | null>(null);

function viewPermissions(role: Role) {
    permissionsRole.value = role;
    permissionsDialog.value = true;
}

function roleHasPermission(permissionId: number): boolean {
    // Owner role always has all permissions
    if (permissionsRole.value?.is_system && permissionsRole.value?.slug === 'owner') {
        return true;
    }
    return permissionsRole.value?.permissions?.some(p => p.id === permissionId) ?? false;
}

function isGroupAllSelected(group: string): boolean {
    const groupIds = props.groupedPermissions[group].map((p) => p.id);
    return groupIds.every((id) => roleHasPermission(id));
}

function confirmDelete(role: Role) {
    roleToDelete.value = role;
    deleteDialog.value = true;
}

function deleteRole() {
    if (!roleToDelete.value) return;
    deleting.value = true;
    router.delete(tenantUrl(`roles/${roleToDelete.value.id}`), {
        onFinish: () => {
            deleting.value = false;
            deleteDialog.value = false;
            roleToDelete.value = null;
        },
    });
}
</script>

<template>
    <Head title="Roles" />

    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Roles</h1>
                <Button v-if="can('roles.create')" as-child>
                    <Link :href="tenantUrl('roles/create')">
                        <Plus class="mr-2 h-4 w-4" />
                        Create Role
                    </Link>
                </Button>
            </div>

            <div class="overflow-hidden rounded-xl border bg-white dark:border-gray-800 dark:bg-gray-900">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50">
                            <th class="px-4 py-3 text-left font-medium">Name</th>
                            <th class="px-4 py-3 text-left font-medium">Description</th>
                            <th class="px-4 py-3 text-left font-medium">Type</th>
                            <th class="px-4 py-3 text-center font-medium">Users</th>
                            <th class="px-4 py-3 text-center font-medium">Permissions</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="role in roles"
                            :key="role.id"
                            class="border-b last:border-0 dark:border-gray-800"
                        >
                            <td class="px-4 py-3 font-medium">
                                <div class="flex items-center gap-2">
                                    <Shield class="h-4 w-4 text-muted-foreground" />
                                    {{ role.name }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">{{ role.description || '—' }}</td>
                            <td class="px-4 py-3">
                                <Badge v-if="role.is_system" variant="secondary">System</Badge>
                                <Badge v-else variant="outline">Custom</Badge>
                            </td>
                            <td class="px-4 py-3 text-center">{{ role.tenant_users_count ?? 0 }}</td>
                            <td class="px-4 py-3 text-center">
                                <button
                                    class="cursor-pointer text-primary underline-offset-4 hover:underline"
                                    @click="viewPermissions(role)"
                                >
                                    {{ role.permissions?.length ?? 0 }}
                                </button>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Button v-if="can('roles.edit')" variant="ghost" size="icon" as-child>
                                        <Link :href="tenantUrl(`roles/${role.id}/edit`)">
                                            <Edit class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button
                                        v-if="can('roles.delete') && !role.is_system"
                                        variant="ghost"
                                        size="icon"
                                        @click="confirmDelete(role)"
                                    >
                                        <Trash2 class="h-4 w-4 text-red-500" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Permissions View Dialog -->
        <Dialog v-model:open="permissionsDialog">
            <DialogContent class="max-h-[80vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{{ permissionsRole?.name }} — Permissions</DialogTitle>
                    <DialogDescription>
                        {{ permissionsRole?.permissions?.length ?? 0 }} permissions assigned to this role.
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-4">
                    <div
                        v-for="(permissions, group) in groupedPermissions"
                        :key="group"
                        class="rounded-lg border p-4 dark:border-gray-800"
                    >
                        <div class="mb-3 flex items-center gap-2">
                            <Checkbox
                                :checked="isGroupAllSelected(group as string)"
                                :disabled="true"
                            />
                            <span class="font-medium capitalize">{{ group }}</span>
                        </div>
                        <div class="ml-6 grid gap-2 sm:grid-cols-2">
                            <label
                                v-for="permission in permissions"
                                :key="permission.id"
                                class="flex items-center gap-2 text-sm"
                            >
                                <Checkbox
                                    :checked="roleHasPermission(permission.id)"
                                    :disabled="true"
                                />
                                {{ permission.name }}
                            </label>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="deleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Role</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete the role "{{ roleToDelete?.name }}"? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="deleteDialog = false">Cancel</Button>
                    <Button variant="destructive" @click="deleteRole" :disabled="deleting">
                        {{ deleting ? 'Deleting...' : 'Delete' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </TenantLayout>
</template>

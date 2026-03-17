<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import type { BreadcrumbItem, GroupedPermissions } from '@/types';
import { useTenant } from '@/composables/useTenant';

const props = defineProps<{
    groupedPermissions: GroupedPermissions;
}>();

const { tenantUrl } = useTenant();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: tenantUrl('dashboard') },
    { title: 'Roles', href: tenantUrl('roles') },
    { title: 'Create', href: tenantUrl('roles/create') },
];

const form = useForm({
    name: '',
    description: '',
    permissions: [] as number[],
});

function togglePermission(id: number) {
    const idx = form.permissions.indexOf(id);
    if (idx > -1) {
        form.permissions.splice(idx, 1);
    } else {
        form.permissions.push(id);
    }
}

function toggleGroup(group: string) {
    const groupPerms = props.groupedPermissions[group];
    const groupIds = groupPerms.map((p) => p.id);
    const allSelected = groupIds.every((id) => form.permissions.includes(id));

    if (allSelected) {
        form.permissions = form.permissions.filter((id) => !groupIds.includes(id));
    } else {
        const newIds = groupIds.filter((id) => !form.permissions.includes(id));
        form.permissions.push(...newIds);
    }
}

function isGroupAllSelected(group: string): boolean {
    const groupIds = props.groupedPermissions[group].map((p) => p.id);
    return groupIds.every((id) => form.permissions.includes(id));
}

function submit() {
    form.post(tenantUrl('roles'));
}
</script>

<template>
    <Head title="Create Role" />

    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <h1 class="text-2xl font-bold">Create Role</h1>

            <form @submit.prevent="submit" class="max-w-2xl space-y-6">
                <div>
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        class="mt-1"
                        placeholder="e.g. Branch Manager"
                        required
                    />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">{{ form.errors.name }}</p>
                </div>

                <div>
                    <Label for="description">Description</Label>
                    <Input
                        id="description"
                        v-model="form.description"
                        class="mt-1"
                        placeholder="Optional description"
                    />
                    <p v-if="form.errors.description" class="mt-1 text-sm text-red-500">{{ form.errors.description }}</p>
                </div>

                <div>
                    <Label>Permissions</Label>
                    <p v-if="form.errors.permissions" class="mt-1 text-sm text-red-500">{{ form.errors.permissions }}</p>

                    <div class="mt-3 space-y-4">
                        <div
                            v-for="(perms, group) in groupedPermissions"
                            :key="group"
                            class="rounded-lg border p-4 dark:border-gray-800"
                        >
                            <div class="mb-3 flex items-center gap-2">
                                <Checkbox
                                    :checked="isGroupAllSelected(group as string)"
                                    @update:checked="toggleGroup(group as string)"
                                />
                                <span class="font-medium capitalize">{{ group }}</span>
                            </div>
                            <div class="ml-6 grid gap-2 sm:grid-cols-2">
                                <label
                                    v-for="perm in perms"
                                    :key="perm.id"
                                    class="flex items-center gap-2 text-sm"
                                >
                                    <Checkbox
                                        :checked="form.permissions.includes(perm.id)"
                                        @update:checked="togglePermission(perm.id)"
                                    />
                                    {{ perm.name }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Creating...' : 'Create Role' }}
                    </Button>
                    <Button variant="outline" type="button" as-child>
                        <a :href="tenantUrl('roles')">Cancel</a>
                    </Button>
                </div>
            </form>
        </div>
    </TenantLayout>
</template>

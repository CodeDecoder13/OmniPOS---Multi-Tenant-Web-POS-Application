<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Check, ChevronDown, ChevronRight, Copy, Download, FileUp, RefreshCw, SquarePen, Trash2, Upload, UserPlus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { BreadcrumbItem, PaginatedData, User, ActivityLog } from '@/types';
import type { SharedTenantRole } from '@/types/tenant';
import { useTenant } from '@/composables/useTenant';
import { usePermissions } from '@/composables/usePermissions';
import axios from 'axios';

interface TenantUser extends User {
    tenant_role: SharedTenantRole | null;
    has_pin: boolean;
    branch: { id: number; name: string } | null;
    branch_id: number | null;
    is_active: boolean;
    last_login_at: string | null;
}

interface RoleOption {
    id: number;
    name: string;
    slug: string;
    is_system: boolean;
}

interface BranchOption {
    id: number;
    name: string;
}

const props = defineProps<{
    users: PaginatedData<TenantUser>;
    ownerId: number;
    roles: RoleOption[];
    branches: BranchOption[];
    activityLogs: ActivityLog[];
}>();

const page = usePage();
const { tenantUrl } = useTenant();
const { can } = usePermissions();
const currentUserId = (page.props.auth.user as User).id;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: tenantUrl('dashboard') },
    { title: 'Users', href: tenantUrl('users') },
];

// Filter out owner role from assignable options
const assignableRoles = props.roles.filter((r) => r.slug !== 'owner');

// Add User dialog
const addDialog = ref(false);
const addForm = ref({ name: '', email: '', password: '', role_id: '', branch_id: 'all' });
const adding = ref(false);

function generatePassword(): string {
    const chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%';
    let password = '';
    for (let i = 0; i < 16; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return password;
}

function openAddDialog() {
    addForm.value = { name: '', email: '', password: generatePassword(), role_id: '', branch_id: 'all' };
    addDialog.value = true;
}

function submitAdd() {
    adding.value = true;
    router.post(tenantUrl('users'), {
        name: addForm.value.name,
        email: addForm.value.email,
        password: addForm.value.password,
        role_id: Number(addForm.value.role_id),
        branch_id: addForm.value.branch_id !== 'all' ? Number(addForm.value.branch_id) : null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            createdCredentials.value = { email: addForm.value.email, password: addForm.value.password };
            addDialog.value = false;
            credentialsDialog.value = true;
        },
        onFinish: () => {
            adding.value = false;
        },
    });
}

// Credentials dialog (shown after successful user creation)
const credentialsDialog = ref(false);
const createdCredentials = ref({ email: '', password: '' });
const copiedField = ref<'email' | 'password' | 'both' | null>(null);

async function copyToClipboard(text: string, field: 'email' | 'password' | 'both') {
    await navigator.clipboard.writeText(text);
    copiedField.value = field;
    setTimeout(() => { copiedField.value = null; }, 2000);
}

function copyAllCredentials() {
    const text = `Email: ${createdCredentials.value.email}\nPassword: ${createdCredentials.value.password}`;
    copyToClipboard(text, 'both');
}

// Edit User dialog
const editDialog = ref(false);
const editForm = ref({ id: 0, name: '', email: '', password: '', role_id: '', branch_id: '' });
const editingOwnerRow = ref(false);
const editing = ref(false);

function openEditDialog(user: TenantUser) {
    editingOwnerRow.value = user.id === props.ownerId;
    editForm.value = {
        id: user.id,
        name: user.name,
        email: user.email,
        password: '',
        role_id: String(user.tenant_role?.id ?? ''),
        branch_id: user.branch_id ? String(user.branch_id) : 'all',
    };
    pinForm.value = { userId: user.id, pin: '' };
    editDialog.value = true;
}

function submitEdit() {
    editing.value = true;
    const payload: Record<string, unknown> = {
        name: editForm.value.name,
    };
    if (!editingOwnerRow.value) {
        payload.email = editForm.value.email;
        payload.role_id = Number(editForm.value.role_id);
        payload.branch_id = editForm.value.branch_id !== 'all' ? Number(editForm.value.branch_id) : null;
    }
    if (editForm.value.password) {
        payload.password = editForm.value.password;
    }
    router.put(tenantUrl(`users/${editForm.value.id}`), payload as any, {
        preserveScroll: true,
        onSuccess: () => {
            editDialog.value = false;
        },
        onFinish: () => {
            editing.value = false;
        },
    });
}

// Remove dialog
const removeDialog = ref(false);
const userToRemove = ref<TenantUser | null>(null);
const removing = ref(false);

function confirmRemove(user: TenantUser) {
    userToRemove.value = user;
    removeDialog.value = true;
}

function removeUser() {
    if (!userToRemove.value) return;
    removing.value = true;
    router.delete(tenantUrl(`users/${userToRemove.value.id}`), {
        onFinish: () => {
            removing.value = false;
            removeDialog.value = false;
            userToRemove.value = null;
        },
    });
}

// POS PIN
const pinForm = ref({ userId: 0, pin: '' });
const settingPin = ref(false);

function submitPin() {
    if (!pinForm.value.pin || pinForm.value.pin.length < 4) return;
    settingPin.value = true;
    router.put(tenantUrl(`users/${pinForm.value.userId}/pin`), { pin: pinForm.value.pin }, {
        preserveScroll: true,
        onSuccess: () => {
            pinForm.value.pin = '';
        },
        onFinish: () => {
            settingPin.value = false;
        },
    });
}

// Toggle active
const toggling = ref<number | null>(null);

async function toggleActive(user: TenantUser) {
    if (user.id === props.ownerId) return;
    toggling.value = user.id;
    try {
        await axios.patch(tenantUrl(`users/${user.id}/toggle-active`));
        router.reload();
    } catch {
        // handled by server
    } finally {
        toggling.value = null;
    }
}

// Import dialog
const importDialog = ref(false);
const importStep = ref<'upload' | 'preview' | 'importing' | 'results'>('upload');
const importFile = ref<File | null>(null);
const importValidation = ref<{ valid: any[]; errors: any[] }>({ valid: [], errors: [] });
const importCredentials = ref<{ name: string; email: string; password: string; role: string }[]>([]);
const importLoading = ref(false);

function openImportDialog() {
    importStep.value = 'upload';
    importFile.value = null;
    importValidation.value = { valid: [], errors: [] };
    importCredentials.value = [];
    importDialog.value = true;
}

function handleFileSelect(e: Event) {
    const target = e.target as HTMLInputElement;
    importFile.value = target.files?.[0] ?? null;
}

async function validateImport() {
    if (!importFile.value) return;
    importLoading.value = true;
    try {
        const formData = new FormData();
        formData.append('csv_file', importFile.value);
        const { data } = await axios.post(tenantUrl('users/import/validate'), formData);
        importValidation.value = data;
        importStep.value = 'preview';
    } catch (err: any) {
        importValidation.value = { valid: [], errors: [{ row: 0, message: err.response?.data?.message || 'Validation failed.' }] };
        importStep.value = 'preview';
    } finally {
        importLoading.value = false;
    }
}

async function executeImport() {
    importStep.value = 'importing';
    importLoading.value = true;
    try {
        const { data } = await axios.post(tenantUrl('users/import'), {
            rows: importValidation.value.valid,
        });
        importCredentials.value = data.credentials;
        importStep.value = 'results';
        router.reload();
    } catch (err: any) {
        importStep.value = 'preview';
    } finally {
        importLoading.value = false;
    }
}

function downloadCredentials() {
    const lines = ['Name,Email,Password,Role'];
    for (const c of importCredentials.value) {
        lines.push(`"${c.name}","${c.email}","${c.password}","${c.role}"`);
    }
    const blob = new Blob([lines.join('\n')], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'imported-credentials.csv';
    a.click();
    URL.revokeObjectURL(url);
}

// Activity log
const activityOpen = ref(false);

function formatRelativeTime(dateStr: string): string {
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    if (diffMins < 1) return 'just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `${diffHours}h ago`;
    const diffDays = Math.floor(diffHours / 24);
    if (diffDays < 30) return `${diffDays}d ago`;
    return date.toLocaleDateString();
}

function actionLabel(action: string): string {
    const labels: Record<string, string> = {
        'user.created': 'Created user',
        'user.updated': 'Updated user',
        'user.removed': 'Removed user',
        'user.activated': 'Activated user',
        'user.deactivated': 'Deactivated user',
        'users.imported': 'Imported users',
        'role.created': 'Created role',
        'role.updated': 'Updated role',
        'role.deleted': 'Deleted role',
    };
    return labels[action] || action;
}

function roleBadgeClass(slug: string): string {
    switch (slug) {
        case 'owner': return 'bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-400';
        case 'admin': return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400';
    }
}

</script>

<template>
    <Head title="Users" />

    <TenantLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Team Members</h1>
                <div v-if="currentUserId === ownerId" class="flex gap-2">
                    <Button variant="outline" @click="openImportDialog">
                        <Upload class="mr-2 h-4 w-4" />
                        Import Users
                    </Button>
                    <Button @click="openAddDialog">
                        <UserPlus class="mr-2 h-4 w-4" />
                        Add User
                    </Button>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border bg-white dark:border-gray-800 dark:bg-gray-900">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50">
                            <th class="px-4 py-3 text-left font-medium">Name</th>
                            <th class="px-4 py-3 text-left font-medium">Email</th>
                            <th class="px-4 py-3 text-left font-medium">Role</th>
                            <th class="px-4 py-3 text-left font-medium">Branch</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                            <th class="px-4 py-3 text-left font-medium">Last Login</th>
                            <th class="px-4 py-3 text-left font-medium">PIN</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="user in users.data"
                            :key="user.id"
                            class="border-b last:border-0 dark:border-gray-800"
                            :class="{ 'opacity-50': !user.is_active }"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ user.name }}
                                <span v-if="user.id === ownerId" class="ml-1 text-xs text-muted-foreground">(Owner)</span>
                            </td>
                            <td class="px-4 py-3">{{ user.email }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize"
                                    :class="roleBadgeClass(user.tenant_role?.slug ?? '')"
                                >
                                    {{ user.tenant_role?.name ?? 'Unknown' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="user.branch
                                        ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400'
                                        : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'"
                                >
                                    {{ user.branch?.name ?? 'All Branches' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="user.is_active
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                        : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'"
                                >
                                    {{ user.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground text-xs">
                                {{ user.last_login_at ? formatRelativeTime(user.last_login_at) : 'Never' }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="user.has_pin
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                        : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-500'"
                                >
                                    {{ user.has_pin ? 'Set' : 'Not Set' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Button
                                        v-if="currentUserId === ownerId && user.id !== ownerId"
                                        variant="ghost"
                                        size="sm"
                                        :disabled="toggling === user.id"
                                        @click="toggleActive(user)"
                                        class="text-xs"
                                    >
                                        {{ toggling === user.id ? '...' : (user.is_active ? 'Deactivate' : 'Activate') }}
                                    </Button>
                                    <Button
                                        v-if="currentUserId === ownerId || (user.id !== ownerId && can('users.edit-role'))"
                                        variant="ghost"
                                        size="icon"
                                        @click="openEditDialog(user)"
                                    >
                                        <SquarePen class="h-4 w-4" />
                                    </Button>
                                    <Button
                                        v-if="user.id !== ownerId && user.id !== currentUserId && (currentUserId === ownerId || can('users.remove'))"
                                        variant="ghost"
                                        size="icon"
                                        @click="confirmRemove(user)"
                                    >
                                        <Trash2 class="h-4 w-4 text-red-500" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="users.last_page > 1" class="flex items-center justify-between border-t px-4 py-3 dark:border-gray-800">
                    <p class="text-sm text-muted-foreground">
                        Showing {{ users.from }} to {{ users.to }} of {{ users.total }}
                    </p>
                    <div class="flex gap-1">
                        <template v-for="link in users.links" :key="link.label">
                            <a
                                v-if="link.url"
                                :href="link.url"
                                class="rounded-md px-3 py-1 text-sm"
                                :class="link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-gray-100 dark:hover:bg-gray-800'"
                                v-html="link.label"
                            />
                            <span v-else class="px-3 py-1 text-sm text-muted-foreground" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div v-if="activityLogs.length > 0" class="rounded-xl border bg-white dark:border-gray-800 dark:bg-gray-900">
                <button
                    class="flex w-full items-center justify-between px-4 py-3 text-left font-medium"
                    @click="activityOpen = !activityOpen"
                >
                    <span>Recent Activity ({{ activityLogs.length }})</span>
                    <component :is="activityOpen ? ChevronDown : ChevronRight" class="h-4 w-4" />
                </button>
                <div v-if="activityOpen" class="border-t px-4 py-2 dark:border-gray-800">
                    <div
                        v-for="log in activityLogs"
                        :key="log.id"
                        class="flex items-center justify-between py-2 text-sm border-b last:border-0 dark:border-gray-800"
                    >
                        <div>
                            <span class="font-medium">{{ log.actor?.name ?? 'System' }}</span>
                            <span class="text-muted-foreground"> {{ actionLabel(log.action) }}</span>
                            <span v-if="log.properties?.name" class="font-medium"> "{{ log.properties.name }}"</span>
                            <span v-if="log.properties?.count" class="font-medium"> ({{ log.properties.count }} users)</span>
                        </div>
                        <span class="text-xs text-muted-foreground whitespace-nowrap ml-4">
                            {{ formatRelativeTime(log.created_at) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add User Dialog -->
        <Dialog v-model:open="addDialog">
            <DialogContent @pointer-down-outside="(e: any) => e.preventDefault()">
                <DialogHeader>
                    <DialogTitle>Add User</DialogTitle>
                    <DialogDescription>
                        Create a new user and add them to your organization.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitAdd" class="space-y-4">
                    <div>
                        <Label for="add-name">Name</Label>
                        <Input
                            id="add-name"
                            v-model="addForm.name"
                            type="text"
                            placeholder="Full name"
                            class="mt-1"
                            required
                        />
                        <p v-if="page.props.errors.name" class="mt-1 text-sm text-red-500">{{ page.props.errors.name }}</p>
                    </div>

                    <div>
                        <Label for="add-email">Email</Label>
                        <Input
                            id="add-email"
                            v-model="addForm.email"
                            type="email"
                            placeholder="user@example.com"
                            class="mt-1"
                            required
                        />
                        <p v-if="page.props.errors.email" class="mt-1 text-sm text-red-500">{{ page.props.errors.email }}</p>
                    </div>

                    <div>
                        <Label for="add-password">Password</Label>
                        <div class="mt-1 flex gap-2">
                            <Input
                                id="add-password"
                                v-model="addForm.password"
                                type="text"
                                placeholder="Password"
                                class="flex-1 font-mono text-sm"
                                required
                                readonly
                            />
                            <Button type="button" variant="outline" size="icon" @click="copyToClipboard(addForm.password, 'password')" title="Copy password">
                                <Check v-if="copiedField === 'password'" class="h-4 w-4 text-green-500" />
                                <Copy v-else class="h-4 w-4" />
                            </Button>
                            <Button type="button" variant="outline" size="icon" @click="addForm.password = generatePassword()" title="Generate new password">
                                <RefreshCw class="h-4 w-4" />
                            </Button>
                        </div>
                        <p v-if="page.props.errors.password" class="mt-1 text-sm text-red-500">{{ page.props.errors.password }}</p>
                    </div>

                    <div>
                        <Label for="add-role">Role</Label>
                        <Select v-model="addForm.role_id">
                            <SelectTrigger class="mt-1">
                                <SelectValue placeholder="Select role" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="role in assignableRoles" :key="role.id" :value="String(role.id)">
                                    {{ role.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="page.props.errors.role_id" class="mt-1 text-sm text-red-500">{{ page.props.errors.role_id }}</p>
                    </div>

                    <div>
                        <Label for="add-branch">Branch</Label>
                        <Select v-model="addForm.branch_id">
                            <SelectTrigger class="mt-1">
                                <SelectValue placeholder="All Branches" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Branches</SelectItem>
                                <SelectItem v-for="branch in branches" :key="branch.id" :value="String(branch.id)">
                                    {{ branch.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="page.props.errors.branch_id" class="mt-1 text-sm text-red-500">{{ page.props.errors.branch_id }}</p>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" type="button" @click="addDialog = false">Cancel</Button>
                        <Button type="submit" :disabled="adding || !addForm.role_id || !addForm.password">
                            {{ adding ? 'Creating...' : 'Create User' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Edit User Dialog -->
        <Dialog v-model:open="editDialog">
            <DialogContent @pointer-down-outside="(e: any) => e.preventDefault()">
                <DialogHeader>
                    <DialogTitle>Edit User</DialogTitle>
                    <DialogDescription>
                        {{ editingOwnerRow ? 'Update your name and password.' : 'Update this user\'s information.' }}
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div>
                        <Label for="edit-name">Name</Label>
                        <Input
                            id="edit-name"
                            v-model="editForm.name"
                            type="text"
                            placeholder="Full name"
                            class="mt-1"
                            required
                        />
                        <p v-if="page.props.errors.name" class="mt-1 text-sm text-red-500">{{ page.props.errors.name }}</p>
                    </div>

                    <div v-if="!editingOwnerRow">
                        <Label for="edit-email">Email</Label>
                        <Input
                            id="edit-email"
                            v-model="editForm.email"
                            type="email"
                            placeholder="user@example.com"
                            class="mt-1"
                            required
                        />
                        <p v-if="page.props.errors.email" class="mt-1 text-sm text-red-500">{{ page.props.errors.email }}</p>
                    </div>

                    <div>
                        <Label for="edit-password">New Password</Label>
                        <p class="text-xs text-muted-foreground">Leave empty to keep current password.</p>
                        <div class="mt-1 flex gap-2">
                            <Input
                                id="edit-password"
                                v-model="editForm.password"
                                type="text"
                                placeholder="Enter or generate new password"
                                class="flex-1 font-mono text-sm"
                            />
                            <Button type="button" variant="outline" size="icon" @click="copyToClipboard(editForm.password, 'password')" :disabled="!editForm.password" title="Copy password">
                                <Check v-if="copiedField === 'password'" class="h-4 w-4 text-green-500" />
                                <Copy v-else class="h-4 w-4" />
                            </Button>
                            <Button type="button" variant="outline" size="icon" @click="editForm.password = generatePassword()" title="Generate new password">
                                <RefreshCw class="h-4 w-4" />
                            </Button>
                        </div>
                        <p v-if="page.props.errors.password" class="mt-1 text-sm text-red-500">{{ page.props.errors.password }}</p>
                    </div>

                    <div v-if="!editingOwnerRow">
                        <Label for="edit-role">Role</Label>
                        <Select v-model="editForm.role_id">
                            <SelectTrigger class="mt-1">
                                <SelectValue placeholder="Select role" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="role in assignableRoles" :key="role.id" :value="String(role.id)">
                                    {{ role.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="page.props.errors.role_id" class="mt-1 text-sm text-red-500">{{ page.props.errors.role_id }}</p>
                    </div>

                    <div v-if="!editingOwnerRow">
                        <Label for="edit-branch">Branch</Label>
                        <Select v-model="editForm.branch_id">
                            <SelectTrigger class="mt-1">
                                <SelectValue placeholder="All Branches" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Branches</SelectItem>
                                <SelectItem v-for="branch in branches" :key="branch.id" :value="String(branch.id)">
                                    {{ branch.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="page.props.errors.branch_id" class="mt-1 text-sm text-red-500">{{ page.props.errors.branch_id }}</p>
                    </div>

                    <!-- POS PIN (owner only) -->
                    <div v-if="currentUserId === ownerId" class="rounded-lg border p-3 space-y-2">
                        <Label>POS PIN</Label>
                        <p class="text-xs text-muted-foreground">Set a 4-6 digit PIN for POS terminal login.</p>
                        <div class="flex gap-2">
                            <Input
                                v-model="pinForm.pin"
                                type="text"
                                inputmode="numeric"
                                pattern="[0-9]*"
                                maxlength="6"
                                placeholder="Enter 4-6 digit PIN"
                                class="flex-1 font-mono"
                            />
                            <Button
                                type="button"
                                variant="secondary"
                                :disabled="settingPin || !pinForm.pin || pinForm.pin.length < 4"
                                @click="submitPin"
                            >
                                {{ settingPin ? 'Setting...' : 'Set PIN' }}
                            </Button>
                        </div>
                        <p v-if="page.props.errors.pin" class="text-sm text-red-500">{{ page.props.errors.pin }}</p>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" type="button" @click="editDialog = false">Cancel</Button>
                        <Button type="submit" :disabled="editing || (!editingOwnerRow && !editForm.role_id)">
                            {{ editing ? 'Saving...' : 'Save Changes' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Remove Confirmation Dialog -->
        <Dialog v-model:open="removeDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Remove User</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to remove "{{ userToRemove?.name }}" from this organization?
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="removeDialog = false">Cancel</Button>
                    <Button variant="destructive" @click="removeUser" :disabled="removing">
                        {{ removing ? 'Removing...' : 'Remove' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Credentials Dialog (shown after user creation) -->
        <Dialog v-model:open="credentialsDialog">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>User Created</DialogTitle>
                    <DialogDescription>
                        Save these login credentials. The password cannot be retrieved later.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-3">
                    <div>
                        <Label class="text-xs text-muted-foreground">Email</Label>
                        <div class="mt-1 flex items-center gap-2">
                            <div class="flex-1 rounded-md border bg-muted/50 px-3 py-2 font-mono text-sm">
                                {{ createdCredentials.email }}
                            </div>
                            <Button
                                variant="outline"
                                size="icon"
                                @click="copyToClipboard(createdCredentials.email, 'email')"
                            >
                                <Check v-if="copiedField === 'email'" class="h-4 w-4 text-green-500" />
                                <Copy v-else class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    <div>
                        <Label class="text-xs text-muted-foreground">Password</Label>
                        <div class="mt-1 flex items-center gap-2">
                            <div class="flex-1 rounded-md border bg-muted/50 px-3 py-2 font-mono text-sm">
                                {{ createdCredentials.password }}
                            </div>
                            <Button
                                variant="outline"
                                size="icon"
                                @click="copyToClipboard(createdCredentials.password, 'password')"
                            >
                                <Check v-if="copiedField === 'password'" class="h-4 w-4 text-green-500" />
                                <Copy v-else class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>

                <DialogFooter class="flex-col gap-2 sm:flex-row">
                    <Button variant="outline" class="flex-1" @click="copyAllCredentials">
                        <Check v-if="copiedField === 'both'" class="mr-2 h-4 w-4 text-green-500" />
                        <Copy v-else class="mr-2 h-4 w-4" />
                        {{ copiedField === 'both' ? 'Copied!' : 'Copy All' }}
                    </Button>
                    <Button class="flex-1" @click="credentialsDialog = false">Done</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Import Dialog -->
        <Dialog v-model:open="importDialog">
            <DialogContent class="sm:max-w-2xl" @pointer-down-outside="(e: any) => e.preventDefault()">
                <DialogHeader>
                    <DialogTitle>Import Users</DialogTitle>
                    <DialogDescription>
                        Upload a CSV file to bulk import users. Required columns: name, email, role. Optional: branch.
                    </DialogDescription>
                </DialogHeader>

                <!-- Step 1: Upload -->
                <div v-if="importStep === 'upload'" class="space-y-4">
                    <div class="rounded-lg border-2 border-dashed p-6 text-center">
                        <FileUp class="mx-auto mb-2 h-8 w-8 text-muted-foreground" />
                        <Input type="file" accept=".csv,.txt" @change="handleFileSelect" class="mx-auto max-w-xs" />
                        <p class="mt-2 text-xs text-muted-foreground">CSV format: name, email, role, branch (optional)</p>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="importDialog = false">Cancel</Button>
                        <Button :disabled="!importFile || importLoading" @click="validateImport">
                            {{ importLoading ? 'Validating...' : 'Validate' }}
                        </Button>
                    </DialogFooter>
                </div>

                <!-- Step 2: Preview -->
                <div v-if="importStep === 'preview'" class="space-y-4">
                    <div v-if="importValidation.valid.length > 0" class="space-y-2">
                        <h4 class="text-sm font-medium text-green-700 dark:text-green-400">
                            Valid rows ({{ importValidation.valid.length }})
                        </h4>
                        <div class="max-h-40 overflow-auto rounded border text-xs">
                            <table class="w-full">
                                <thead><tr class="bg-gray-50 dark:bg-gray-800"><th class="px-2 py-1 text-left">Row</th><th class="px-2 py-1 text-left">Name</th><th class="px-2 py-1 text-left">Email</th><th class="px-2 py-1 text-left">Role</th><th class="px-2 py-1 text-left">Branch</th></tr></thead>
                                <tbody>
                                    <tr v-for="row in importValidation.valid" :key="row.row" class="border-t dark:border-gray-800">
                                        <td class="px-2 py-1">{{ row.row }}</td>
                                        <td class="px-2 py-1">{{ row.name }}</td>
                                        <td class="px-2 py-1">{{ row.email }}</td>
                                        <td class="px-2 py-1">{{ row.role_name }}</td>
                                        <td class="px-2 py-1">{{ row.branch_name ?? 'All' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-if="importValidation.errors.length > 0" class="space-y-2">
                        <h4 class="text-sm font-medium text-red-700 dark:text-red-400">
                            Errors ({{ importValidation.errors.length }})
                        </h4>
                        <div class="max-h-32 overflow-auto rounded border border-red-200 bg-red-50 p-2 text-xs dark:border-red-900 dark:bg-red-950">
                            <div v-for="err in importValidation.errors" :key="err.row" class="py-0.5">
                                <span class="font-medium">Row {{ err.row }}:</span> {{ err.message }}
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" @click="importStep = 'upload'">Back</Button>
                        <Button :disabled="importValidation.valid.length === 0" @click="executeImport">
                            Import {{ importValidation.valid.length }} Users
                        </Button>
                    </DialogFooter>
                </div>

                <!-- Step 3: Importing -->
                <div v-if="importStep === 'importing'" class="py-8 text-center">
                    <RefreshCw class="mx-auto mb-2 h-8 w-8 animate-spin text-muted-foreground" />
                    <p class="text-sm text-muted-foreground">Importing users...</p>
                </div>

                <!-- Step 4: Results -->
                <div v-if="importStep === 'results'" class="space-y-4">
                    <p class="text-sm text-green-700 dark:text-green-400">
                        Successfully imported {{ importCredentials.length }} users.
                    </p>
                    <div class="max-h-48 overflow-auto rounded border text-xs">
                        <table class="w-full">
                            <thead><tr class="bg-gray-50 dark:bg-gray-800"><th class="px-2 py-1 text-left">Name</th><th class="px-2 py-1 text-left">Email</th><th class="px-2 py-1 text-left">Password</th><th class="px-2 py-1 text-left">Role</th></tr></thead>
                            <tbody>
                                <tr v-for="cred in importCredentials" :key="cred.email" class="border-t dark:border-gray-800">
                                    <td class="px-2 py-1">{{ cred.name }}</td>
                                    <td class="px-2 py-1">{{ cred.email }}</td>
                                    <td class="px-2 py-1 font-mono">{{ cred.password }}</td>
                                    <td class="px-2 py-1">{{ cred.role }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="downloadCredentials">
                            <Download class="mr-2 h-4 w-4" />
                            Download Credentials
                        </Button>
                        <Button @click="importDialog = false">Done</Button>
                    </DialogFooter>
                </div>
            </DialogContent>
        </Dialog>
    </TenantLayout>
</template>

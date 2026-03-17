<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Check, Copy, RefreshCw, SquarePen, Trash2, UserPlus } from 'lucide-vue-next';
import { ref } from 'vue';
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
import type { BreadcrumbItem, PaginatedData, User } from '@/types';
import type { SharedTenantRole } from '@/types/tenant';
import { useTenant } from '@/composables/useTenant';
import { usePermissions } from '@/composables/usePermissions';

interface TenantUser extends User {
    tenant_role: SharedTenantRole | null;
    has_pin: boolean;
}

interface RoleOption {
    id: number;
    name: string;
    slug: string;
    is_system: boolean;
}

const props = defineProps<{
    users: PaginatedData<TenantUser>;
    ownerId: number;
    roles: RoleOption[];
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
const addForm = ref({ name: '', email: '', password: '', role_id: '' });
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
    addForm.value = { name: '', email: '', password: generatePassword(), role_id: '' };
    addDialog.value = true;
}

function submitAdd() {
    adding.value = true;
    router.post(tenantUrl('users'), {
        name: addForm.value.name,
        email: addForm.value.email,
        password: addForm.value.password,
        role_id: Number(addForm.value.role_id),
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
const editForm = ref({ id: 0, name: '', email: '', password: '', role_id: '' });
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
    }
    if (editForm.value.password) {
        payload.password = editForm.value.password;
    }
    router.put(tenantUrl(`users/${editForm.value.id}`), payload, {
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
                <Button v-if="currentUserId === ownerId" @click="openAddDialog">
                    <UserPlus class="mr-2 h-4 w-4" />
                    Add User
                </Button>
            </div>

            <div class="overflow-hidden rounded-xl border bg-white dark:border-gray-800 dark:bg-gray-900">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50">
                            <th class="px-4 py-3 text-left font-medium">Name</th>
                            <th class="px-4 py-3 text-left font-medium">Email</th>
                            <th class="px-4 py-3 text-left font-medium">Role</th>
                            <th class="px-4 py-3 text-left font-medium">PIN</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="user in users.data"
                            :key="user.id"
                            class="border-b last:border-0 dark:border-gray-800"
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
        </div>

        <!-- Add User Dialog -->
        <Dialog v-model:open="addDialog">
            <DialogContent>
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
            <DialogContent>
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
    </TenantLayout>
</template>

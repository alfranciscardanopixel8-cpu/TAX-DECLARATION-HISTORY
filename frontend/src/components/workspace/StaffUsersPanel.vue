<template>
  <div class="ws-page">
    <WorkspacePageHeader
      module="Administration"
      title="Staff Accounts"
      lead="Create and manage assessor office user accounts."
    >
      <template #actions>
        <q-btn unelevated no-caps color="primary" icon="person_add" label="New user" @click="openCreate" />
      </template>
    </WorkspacePageHeader>

    <div class="ws-card ws-card--flush">
      <q-table flat class="ws-table" :loading="loading" :rows="users" :columns="columns" row-key="id">
        <template #body-cell-role="props">
          <q-td :props="props">{{ roleLabel(props.row.role) }}</q-td>
        </template>
        <template #body-cell-status="props">
          <q-td :props="props">
            <q-badge :color="props.row.status === 'Active' ? 'green-7' : 'blue-grey-6'" :label="props.row.status" />
          </q-td>
        </template>
        <template #body-cell-actions="props">
          <q-td :props="props">
            <q-btn flat round color="primary" icon="edit" @click="openEdit(props.row)" />
          </q-td>
        </template>
      </q-table>
    </div>

    <q-dialog v-model="dialog" persistent>
      <q-card class="ws-dialog-card">
        <q-card-section class="ws-dialog-card__head row items-center justify-between">
          <div class="text-h6">{{ editingId ? 'Edit user' : 'New user' }}</div>
          <q-btn flat round icon="close" color="white" v-close-popup />
        </q-card-section>
        <q-separator />
        <q-card-section>
          <q-form class="ws-form-grid" @submit.prevent="saveUser">
            <q-input v-model="form.name" outlined dense label="Full name" />
            <q-input v-model="form.email" outlined dense label="Email" type="email" />
            <q-input v-model="form.password" outlined dense :label="editingId ? 'New password (optional)' : 'Password'" type="password" />
            <q-select v-model="form.role" outlined dense label="Role" :options="roleOptions" emit-value map-options />
            <q-select v-model="form.status" outlined dense label="Status" :options="['Active', 'Inactive']" />
            <div class="ws-form-grid__actions row justify-end q-gutter-sm">
              <q-btn flat label="Cancel" v-close-popup />
              <q-btn color="primary" icon="save" :label="editingId ? 'Save changes' : 'Create user'" type="submit" :loading="saving" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useQuasar } from 'quasar';
import { createUser, fetchUsers, updateUser } from '../../services/api';
import WorkspacePageHeader from '../../components/layout/WorkspacePageHeader.vue';

const $q = useQuasar();
const loading = ref(false);
const saving = ref(false);
const dialog = ref(false);
const editingId = ref(null);
const users = ref([]);

const roleOptions = [
  { label: 'Administrator', value: 'admin' },
  { label: 'Assessor', value: 'assessor' },
  { label: 'Records staff', value: 'records_staff' },
  { label: 'Viewer', value: 'viewer' }
];

const columns = [
  { name: 'name', label: 'Name', field: 'name', align: 'left' },
  { name: 'email', label: 'Email', field: 'email', align: 'left' },
  { name: 'role', label: 'Role', field: 'role', align: 'left' },
  { name: 'status', label: 'Status', field: 'status', align: 'left' },
  { name: 'actions', label: '', field: 'actions', align: 'right' }
];

const form = reactive({
  name: '',
  email: '',
  password: '',
  role: 'records_staff',
  status: 'Active'
});

function roleLabel(role) {
  return roleOptions.find((option) => option.value === role)?.label || role;
}

function resetForm() {
  Object.assign(form, { name: '', email: '', password: '', role: 'records_staff', status: 'Active' });
}

function openCreate() {
  editingId.value = null;
  resetForm();
  dialog.value = true;
}

function openEdit(user) {
  editingId.value = user.id;
  Object.assign(form, {
    name: user.name,
    email: user.email,
    password: '',
    role: user.role,
    status: user.status
  });
  dialog.value = true;
}

async function loadUsers() {
  loading.value = true;

  try {
    users.value = await fetchUsers();
  } catch {
    $q.notify({ type: 'negative', message: 'Unable to load user accounts.' });
  } finally {
    loading.value = false;
  }
}

async function saveUser() {
  saving.value = true;

  try {
    const payload = { ...form };
    if (editingId.value && !payload.password) {
      delete payload.password;
    }

    if (editingId.value) {
      await updateUser(editingId.value, payload);
      $q.notify({ type: 'positive', message: 'User updated.' });
    } else {
      await createUser(payload);
      $q.notify({ type: 'positive', message: 'User created.' });
    }

    dialog.value = false;
    await loadUsers();
  } catch (error) {
    const message = error.response?.data?.message || 'Unable to save user account.';
    $q.notify({ type: 'negative', message });
  } finally {
    saving.value = false;
  }
}

onMounted(loadUsers);
</script>

<style scoped>
.ws-dialog-card {
  border-radius: 12px;
  width: min(560px, 96vw);
}

.ws-dialog-card__head {
  background: var(--ws-primary-dark, #0f3f46);
  color: #fff;
}

.ws-form-grid {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.ws-form-grid__actions {
  grid-column: span 2;
}

@media (max-width: 600px) {
  .ws-form-grid {
    grid-template-columns: 1fr;
  }

  .ws-form-grid__actions {
    grid-column: span 1;
  }
}
</style>

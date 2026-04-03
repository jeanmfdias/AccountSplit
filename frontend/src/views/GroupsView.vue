<template>
  <div>
    <div class="page-header">
      <h1>Groups</h1>
      <button class="btn btn-primary" @click="showForm = !showForm">
        {{ showForm ? 'Cancel' : '+ New Group' }}
      </button>
    </div>

    <div v-if="showForm" class="card form-card">
      <h2>Create Group</h2>
      <form @submit.prevent="handleCreate">
        <div class="form-row">
          <input
            v-model="newName"
            type="text"
            placeholder="Group name (e.g. Trip to Paris)"
            class="input"
            required
            maxlength="255"
          />
          <button type="submit" class="btn btn-primary" :disabled="creating">
            {{ creating ? 'Creating…' : 'Create' }}
          </button>
        </div>
        <p v-if="createError" class="error-text">{{ createError }}</p>
      </form>
    </div>

    <div v-if="store.loading" class="loading">Loading…</div>
    <p v-else-if="store.error" class="error-text">{{ store.error }}</p>
    <p v-else-if="store.groups.length === 0" class="empty-state">No groups yet. Create one above.</p>

    <div v-else class="groups-list">
      <div v-for="group in store.groups" :key="group.id" class="card group-card">
        <div class="group-info">
          <router-link :to="`/groups/${group.id}`" class="group-name">{{ group.name }}</router-link>
          <span class="group-meta">{{ group.participantCount }} participant{{ group.participantCount !== 1 ? 's' : '' }}</span>
        </div>
        <div class="group-actions">
          <router-link :to="`/groups/${group.id}`" class="btn btn-secondary">View</router-link>
          <button class="btn btn-danger" @click="handleDelete(group.id)">Delete</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useGroupsStore } from '../stores/groups'

const store = useGroupsStore()
const showForm = ref(false)
const newName = ref('')
const creating = ref(false)
const createError = ref<string | null>(null)

onMounted(() => {
  store.load()
})

async function handleCreate() {
  if (!newName.value.trim()) return
  creating.value = true
  createError.value = null
  try {
    await store.create(newName.value.trim())
    newName.value = ''
    showForm.value = false
  } catch {
    createError.value = 'Failed to create group. Please try again.'
  } finally {
    creating.value = false
  }
}

async function handleDelete(id: string) {
  if (!confirm('Delete this group and all its data?')) return
  try {
    await store.remove(id)
  } catch {
    alert('Failed to delete group.')
  }
}
</script>

<style scoped>
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}
h1 { font-size: 1.75rem; }
.form-card { margin-bottom: 1.5rem; }
h2 { margin-bottom: 1rem; font-size: 1.1rem; color: #444; }
.form-row { display: flex; gap: 0.5rem; }
.input {
  flex: 1;
  padding: 0.5rem 0.75rem;
  border: 1px solid #ccc;
  border-radius: 6px;
  font-size: 1rem;
}
.input:focus { outline: none; border-color: #1a73e8; }
.groups-list { display: flex; flex-direction: column; gap: 0.75rem; }
.group-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.group-info { display: flex; flex-direction: column; gap: 0.25rem; }
.group-name {
  font-size: 1.1rem;
  font-weight: 600;
  color: #1a73e8;
  text-decoration: none;
}
.group-name:hover { text-decoration: underline; }
.group-meta { font-size: 0.85rem; color: #888; }
.group-actions { display: flex; gap: 0.5rem; }
.loading { color: #888; padding: 2rem 0; text-align: center; }
.empty-state { color: #888; padding: 2rem 0; text-align: center; }

/* shared card */
.card {
  background: #fff;
  border-radius: 8px;
  padding: 1rem 1.25rem;
  box-shadow: 0 1px 4px rgba(0,0,0,0.08);
}

/* shared buttons */
.btn {
  padding: 0.45rem 1rem;
  border-radius: 6px;
  font-size: 0.9rem;
  border: none;
  cursor: pointer;
  font-weight: 500;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
}
.btn:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-primary { background: #1a73e8; color: #fff; }
.btn-primary:hover:not(:disabled) { background: #1558b0; }
.btn-secondary { background: #e8f0fe; color: #1a73e8; }
.btn-secondary:hover { background: #d2e3fc; }
.btn-danger { background: #fce8e6; color: #c5221f; }
.btn-danger:hover { background: #f5c6c4; }
.error-text { color: #c5221f; font-size: 0.9rem; margin-top: 0.5rem; }
</style>
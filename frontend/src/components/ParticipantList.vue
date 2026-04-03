<template>
  <div>
    <div class="section-header">
      <h2>Participants</h2>
      <button class="btn btn-primary" @click="showAdd = !showAdd">
        {{ showAdd ? 'Cancel' : '+ Add Participant' }}
      </button>
    </div>

    <div v-if="showAdd" class="card form-card">
      <form @submit.prevent="handleAdd">
        <div class="form-row">
          <input
            v-model="newName"
            type="text"
            placeholder="Participant name"
            class="input"
            required
            maxlength="255"
          />
          <button type="submit" class="btn btn-primary" :disabled="saving">
            {{ saving ? 'Adding…' : 'Add' }}
          </button>
        </div>
        <p v-if="addError" class="error-text">{{ addError }}</p>
      </form>
    </div>

    <p v-if="store.participants.length === 0" class="empty-state">No participants yet.</p>

    <div v-else class="list">
      <div v-for="participant in store.participants" :key="participant.id" class="card list-item">
        <template v-if="editingId === participant.id">
          <input v-model="editName" class="input" required />
          <div class="item-actions">
            <button class="btn btn-primary" :disabled="saving" @click="handleEdit(participant.id)">Save</button>
            <button class="btn btn-secondary" @click="cancelEdit">Cancel</button>
          </div>
        </template>
        <template v-else>
          <span class="participant-name">{{ participant.name }}</span>
          <div class="item-actions">
            <button class="btn btn-secondary" @click="startEdit(participant.id, participant.name)">Edit</button>
            <button class="btn btn-danger" @click="handleDelete(participant.id)">Remove</button>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useGroupStore } from '../stores/group'

const props = defineProps<{ groupId: string }>()
const store = useGroupStore()

const showAdd = ref(false)
const newName = ref('')
const saving = ref(false)
const addError = ref<string | null>(null)
const editingId = ref<string | null>(null)
const editName = ref('')

async function handleAdd() {
  if (!newName.value.trim()) return
  saving.value = true
  addError.value = null
  try {
    await store.addParticipant(props.groupId, newName.value.trim())
    newName.value = ''
    showAdd.value = false
  } catch {
    addError.value = 'Failed to add participant.'
  } finally {
    saving.value = false
  }
}

function startEdit(id: string, name: string) {
  editingId.value = id
  editName.value = name
}

function cancelEdit() {
  editingId.value = null
  editName.value = ''
}

async function handleEdit(id: string) {
  if (!editName.value.trim()) return
  saving.value = true
  try {
    await store.editParticipant(props.groupId, id, editName.value.trim())
    cancelEdit()
  } catch {
    alert('Failed to update participant.')
  } finally {
    saving.value = false
  }
}

async function handleDelete(id: string) {
  if (!confirm('Remove this participant?')) return
  try {
    await store.removeParticipant(props.groupId, id)
  } catch {
    alert('Failed to remove participant.')
  }
}
</script>

<style scoped>
.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}
h2 { font-size: 1.25rem; }
.form-card { margin-bottom: 1rem; }
.form-row { display: flex; gap: 0.5rem; }
.list { display: flex; flex-direction: column; gap: 0.5rem; }
.list-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}
.participant-name { font-size: 1rem; }
.item-actions { display: flex; gap: 0.5rem; }
.input {
  flex: 1;
  padding: 0.45rem 0.75rem;
  border: 1px solid #ccc;
  border-radius: 6px;
  font-size: 0.95rem;
}
.input:focus { outline: none; border-color: #1a73e8; }
.empty-state { color: #888; padding: 1rem 0; }

.card {
  background: #fff;
  border-radius: 8px;
  padding: 0.75rem 1rem;
  box-shadow: 0 1px 4px rgba(0,0,0,0.08);
}
.btn {
  padding: 0.4rem 0.85rem;
  border-radius: 6px;
  font-size: 0.875rem;
  border: none;
  cursor: pointer;
  font-weight: 500;
  white-space: nowrap;
}
.btn:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-primary { background: #1a73e8; color: #fff; }
.btn-primary:hover:not(:disabled) { background: #1558b0; }
.btn-secondary { background: #e8f0fe; color: #1a73e8; }
.btn-secondary:hover { background: #d2e3fc; }
.btn-danger { background: #fce8e6; color: #c5221f; }
.btn-danger:hover { background: #f5c6c4; }
.error-text { color: #c5221f; font-size: 0.875rem; margin-top: 0.4rem; }
</style>
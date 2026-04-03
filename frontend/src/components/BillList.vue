<template>
  <div>
    <div class="section-header">
      <h2>Bills</h2>
      <button class="btn btn-primary" @click="toggleAdd">
        {{ showAdd ? 'Cancel' : '+ Add Bill' }}
      </button>
    </div>

    <BillForm
      v-if="showAdd"
      :group-id="groupId"
      :participants="store.participants"
      @saved="handleAdd"
      @cancel="showAdd = false"
    />

    <p v-if="store.bills.length === 0" class="empty-state">No bills yet.</p>

    <div v-else class="list">
      <div v-for="bill in store.bills" :key="bill.id" class="card bill-card">
        <template v-if="editingId === bill.id">
          <BillForm
            :group-id="groupId"
            :participants="store.participants"
            :bill="bill"
            @saved="(p) => handleEdit(bill.id, p)"
            @cancel="cancelEdit"
          />
        </template>
        <template v-else>
          <div class="bill-main">
            <div class="bill-info">
              <span class="bill-description">{{ bill.description }}</span>
              <span class="bill-meta">
                {{ formatCurrency(bill.amountCents) }} · paid by {{ bill.paidBy.name }} ·
                {{ formatDate(bill.date) }} · {{ splitLabel(bill.splitType) }}
              </span>
              <div class="shares-list">
                <span v-for="share in bill.shares" :key="share.participantId" class="share-chip">
                  {{ share.participantName }}: {{ formatCurrency(share.amountCents) }}
                </span>
              </div>
            </div>
            <div class="bill-actions">
              <button class="btn btn-secondary" @click="startEdit(bill.id)">Edit</button>
              <button class="btn btn-danger" @click="handleDelete(bill.id)">Delete</button>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useGroupStore } from '../stores/group'
import BillForm from './BillForm.vue'
import type { BillPayload } from '../api/bills'

const props = defineProps<{ groupId: string }>()
const store = useGroupStore()
const showAdd = ref(false)
const editingId = ref<string | null>(null)

function formatCurrency(cents: number): string {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('pt-BR')
}

function splitLabel(type: string): string {
  return { equal: 'Equal', percentage: 'Percentage', custom: 'Custom' }[type] ?? type
}

function toggleAdd() {
  showAdd.value = !showAdd.value
  if (showAdd.value) editingId.value = null
}

function startEdit(id: string) {
  editingId.value = id
  showAdd.value = false
}

function cancelEdit() {
  editingId.value = null
}

async function handleAdd(payload: BillPayload) {
  try {
    await store.addBill(props.groupId, payload)
    showAdd.value = false
  } catch {
    alert('Failed to add bill.')
  }
}

async function handleEdit(billId: string, payload: BillPayload) {
  try {
    await store.editBill(props.groupId, billId, payload)
    editingId.value = null
  } catch {
    alert('Failed to update bill.')
  }
}

async function handleDelete(id: string) {
  if (!confirm('Delete this bill?')) return
  try {
    await store.removeBill(props.groupId, id)
  } catch {
    alert('Failed to delete bill.')
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
.list { display: flex; flex-direction: column; gap: 0.5rem; }
.bill-card { padding: 0.75rem 1rem; }
.bill-main { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
.bill-info { display: flex; flex-direction: column; gap: 0.3rem; flex: 1; }
.bill-description { font-weight: 600; font-size: 1rem; }
.bill-meta { font-size: 0.85rem; color: #666; }
.shares-list { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-top: 0.3rem; }
.share-chip {
  background: #f0f4ff;
  color: #1a73e8;
  font-size: 0.8rem;
  padding: 0.15rem 0.5rem;
  border-radius: 12px;
}
.bill-actions { display: flex; gap: 0.5rem; flex-shrink: 0; }
.empty-state { color: #888; padding: 1rem 0; }

.card {
  background: #fff;
  border-radius: 8px;
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
.btn-primary { background: #1a73e8; color: #fff; }
.btn-primary:hover { background: #1558b0; }
.btn-secondary { background: #e8f0fe; color: #1a73e8; }
.btn-secondary:hover { background: #d2e3fc; }
.btn-danger { background: #fce8e6; color: #c5221f; }
.btn-danger:hover { background: #f5c6c4; }
</style>
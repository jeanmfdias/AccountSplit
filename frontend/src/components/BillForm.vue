<template>
  <div class="card form-card">
    <h3>{{ bill ? 'Edit Bill' : 'Add Bill' }}</h3>
    <form @submit.prevent="handleSubmit">

      <div class="field">
        <label>Description</label>
        <input v-model="form.description" type="text" class="input" required maxlength="255" placeholder="e.g. Hotel" />
      </div>

      <div class="field">
        <label>Amount (R$)</label>
        <input
          v-model="amountDisplay"
          type="number"
          min="0.01"
          step="0.01"
          class="input"
          required
          placeholder="0,00"
          @input="syncAmount"
        />
      </div>

      <div class="field">
        <label>Paid by</label>
        <select v-model="form.paidByParticipantId" class="input" required>
          <option value="" disabled>Select payer…</option>
          <option v-for="p in participants" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>
      </div>

      <div class="field">
        <label>Date</label>
        <input v-model="form.date" type="date" class="input" required />
      </div>

      <div class="field">
        <label>Split type</label>
        <div class="split-type-group">
          <label v-for="type in splitTypes" :key="type.value" class="radio-label">
            <input v-model="form.splitType" type="radio" :value="type.value" />
            {{ type.label }}
          </label>
        </div>
      </div>

      <div class="field">
        <label>Participants in this bill</label>
        <div class="participants-grid">
          <label v-for="p in participants" :key="p.id" class="check-label">
            <input
              type="checkbox"
              :value="p.id"
              :checked="form.participantIds.includes(p.id)"
              @change="toggleParticipant(p.id)"
            />
            {{ p.name }}
          </label>
        </div>
      </div>

      <!-- Percentage split -->
      <template v-if="form.splitType === 'percentage' && form.participantIds.length > 0">
        <div class="field">
          <label>Percentages (must total 100%)</label>
          <div class="shares-grid">
            <div v-for="pid in form.participantIds" :key="pid" class="share-row">
              <span>{{ nameOf(pid) }}</span>
              <div class="share-input-wrap">
                <input
                  v-model.number="form.percentages[pid]"
                  type="number"
                  min="0"
                  max="100"
                  step="0.01"
                  class="input share-input"
                  @input="onPercentageInput"
                />
                <span class="unit">%</span>
              </div>
            </div>
          </div>
          <p class="sum-hint" :class="{ error: percentageSum !== 100 }">
            Total: {{ percentageSum.toFixed(2) }}% {{ percentageSum === 100 ? '✓' : '(must be 100%)' }}
          </p>
        </div>
      </template>

      <!-- Custom split -->
      <template v-if="form.splitType === 'custom' && form.participantIds.length > 0">
        <div class="field">
          <label>Custom amounts (must total {{ formatCurrency(form.amountCents) }})</label>
          <div class="shares-grid">
            <div v-for="pid in form.participantIds" :key="pid" class="share-row">
              <span>{{ nameOf(pid) }}</span>
              <div class="share-input-wrap">
                <input
                  v-model.number="customAmountDisplay[pid]"
                  type="number"
                  min="0"
                  step="0.01"
                  class="input share-input"
                  @input="onCustomInput(pid)"
                />
                <span class="unit">R$</span>
              </div>
            </div>
          </div>
          <p class="sum-hint" :class="{ error: customSum !== form.amountCents }">
            Total: {{ formatCurrency(customSum) }}
            {{ customSum === form.amountCents ? '✓' : `(must be ${formatCurrency(form.amountCents)})` }}
          </p>
        </div>
      </template>

      <p v-if="formError" class="error-text">{{ formError }}</p>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary" :disabled="saving || !isValid">
          {{ saving ? 'Saving…' : bill ? 'Update Bill' : 'Add Bill' }}
        </button>
        <button type="button" class="btn btn-secondary" @click="$emit('cancel')">Cancel</button>
      </div>

    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, reactive } from 'vue'
import type { Participant } from '../api/participants'
import type { Bill, BillPayload, SplitType } from '../api/bills'

const props = defineProps<{
  groupId: string
  participants: Participant[]
  bill?: Bill | null
}>()

const emit = defineEmits<{
  (e: 'saved', payload: BillPayload): void
  (e: 'cancel'): void
}>()

const splitTypes = [
  { value: 'equal', label: 'Equal' },
  { value: 'percentage', label: 'Percentage' },
  { value: 'custom', label: 'Custom' },
]

function today(): string {
  return new Date().toISOString().slice(0, 10)
}

function formatCurrency(cents: number): string {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)
}

function nameOf(pid: string): string {
  return props.participants.find((p) => p.id === pid)?.name ?? pid
}

// ── form state ────────────────────────────────────────────────────────────────

interface FormState {
  description: string
  amountCents: number
  paidByParticipantId: string
  date: string
  splitType: SplitType
  participantIds: string[]
  percentages: Record<string, number>
  customAmounts: Record<string, number>
}

function buildForm(): FormState {
  if (props.bill) {
    const b = props.bill
    const pidList = b.shares.map((s) => s.participantId)
    const percentages: Record<string, number> = {}
    const customAmounts: Record<string, number> = {}

    if (b.splitType === 'percentage') {
      b.shares.forEach((s) => {
        percentages[s.participantId] = (s.amountCents / b.amountCents) * 100
      })
    } else if (b.splitType === 'custom') {
      b.shares.forEach((s) => {
        customAmounts[s.participantId] = s.amountCents
      })
    }

    return {
      description: b.description,
      amountCents: b.amountCents,
      paidByParticipantId: b.paidBy.id,
      date: b.date.slice(0, 10),
      splitType: b.splitType,
      participantIds: pidList,
      percentages,
      customAmounts,
    }
  }

  return {
    description: '',
    amountCents: 0,
    paidByParticipantId: '',
    date: today(),
    splitType: 'equal',
    participantIds: [],
    percentages: {},
    customAmounts: {},
  }
}

const form = reactive<FormState>(buildForm())
const amountDisplay = ref(props.bill ? (props.bill.amountCents / 100).toFixed(2) : '')
const customAmountDisplay = reactive<Record<string, number>>({})
const saving = ref(false)
const formError = ref<string | null>(null)

// Initialise custom amount display from form
watch(
  () => form.participantIds,
  (ids) => {
    ids.forEach((pid) => {
      if (!(pid in customAmountDisplay)) {
        customAmountDisplay[pid] = form.customAmounts[pid] ? form.customAmounts[pid] / 100 : 0
      }
    })
  },
  { immediate: true },
)

function syncAmount() {
  form.amountCents = Math.round(parseFloat(String(amountDisplay.value)) * 100) || 0
}

function toggleParticipant(id: string) {
  const idx = form.participantIds.indexOf(id)
  if (idx === -1) {
    form.participantIds.push(id)
    if (!(id in form.percentages)) form.percentages[id] = 0
    if (!(id in customAmountDisplay)) customAmountDisplay[id] = 0
  } else {
    form.participantIds.splice(idx, 1)
    delete form.percentages[id]
    delete form.customAmounts[id]
    delete customAmountDisplay[id]
  }
}

function onPercentageInput() {
  // trigger reactivity
}

function onCustomInput(pid: string) {
  form.customAmounts[pid] = Math.round((customAmountDisplay[pid] ?? 0) * 100)
}

// ── validation ────────────────────────────────────────────────────────────────

const percentageSum = computed(() =>
  form.participantIds.reduce((sum, pid) => sum + (form.percentages[pid] ?? 0), 0),
)

const customSum = computed(() =>
  form.participantIds.reduce((sum, pid) => sum + (form.customAmounts[pid] ?? 0), 0),
)

const isValid = computed(() => {
  if (!form.description.trim()) return false
  if (form.amountCents <= 0) return false
  if (!form.paidByParticipantId) return false
  if (!form.date) return false
  if (form.participantIds.length === 0) return false
  if (form.splitType === 'percentage') return Math.abs(percentageSum.value - 100) < 0.01
  if (form.splitType === 'custom') return customSum.value === form.amountCents
  return true
})

// ── submit ────────────────────────────────────────────────────────────────────

async function handleSubmit() {
  if (!isValid.value) return
  saving.value = true
  formError.value = null
  try {
    const payload: BillPayload = {
      description: form.description.trim(),
      amountCents: form.amountCents,
      paidByParticipantId: form.paidByParticipantId,
      date: `${form.date}T00:00:00+00:00`,
      splitType: form.splitType,
      participantIds: form.participantIds,
      customAmounts: form.splitType === 'custom' ? { ...form.customAmounts } : {},
      percentages: form.splitType === 'percentage' ? { ...form.percentages } : {},
    }
    emit('saved', payload)
  } catch (e) {
    formError.value = 'Failed to save bill.'
    console.error(e)
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.form-card {
  background: #fff;
  border-radius: 8px;
  padding: 1.25rem;
  box-shadow: 0 1px 4px rgba(0,0,0,0.08);
  margin-bottom: 1rem;
}
h3 { font-size: 1.1rem; margin-bottom: 1rem; color: #444; }
.field { margin-bottom: 1rem; }
.field > label { display: block; font-size: 0.85rem; color: #555; font-weight: 600; margin-bottom: 0.3rem; }
.input {
  width: 100%;
  padding: 0.45rem 0.75rem;
  border: 1px solid #ccc;
  border-radius: 6px;
  font-size: 0.95rem;
}
.input:focus { outline: none; border-color: #1a73e8; }
.split-type-group { display: flex; gap: 1.5rem; }
.radio-label { display: flex; align-items: center; gap: 0.35rem; cursor: pointer; font-size: 0.9rem; }
.participants-grid { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.check-label { display: flex; align-items: center; gap: 0.3rem; cursor: pointer; font-size: 0.9rem; background: #f5f5f5; padding: 0.3rem 0.6rem; border-radius: 4px; }
.shares-grid { display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem; }
.share-row { display: flex; align-items: center; gap: 0.75rem; }
.share-row > span { min-width: 120px; font-size: 0.9rem; }
.share-input-wrap { display: flex; align-items: center; gap: 0.3rem; }
.share-input { width: 100px; }
.unit { font-size: 0.85rem; color: #666; }
.sum-hint { font-size: 0.8rem; color: #666; margin-top: 0.3rem; }
.sum-hint.error { color: #c5221f; font-weight: 600; }
.form-actions { display: flex; gap: 0.5rem; margin-top: 1.25rem; }
.error-text { color: #c5221f; font-size: 0.875rem; margin-top: 0.4rem; }
.btn {
  padding: 0.45rem 1rem;
  border-radius: 6px;
  font-size: 0.9rem;
  border: none;
  cursor: pointer;
  font-weight: 500;
}
.btn:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-primary { background: #1a73e8; color: #fff; }
.btn-primary:hover:not(:disabled) { background: #1558b0; }
.btn-secondary { background: #e8f0fe; color: #1a73e8; }
.btn-secondary:hover { background: #d2e3fc; }
</style>
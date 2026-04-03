<template>
  <div>
    <div class="section-header">
      <h2>Balance</h2>
      <button class="btn btn-secondary" :disabled="loading" @click="refresh">
        {{ loading ? 'Loading…' : 'Refresh' }}
      </button>
    </div>

    <p v-if="error" class="error-text">{{ error }}</p>

    <div v-if="paidSummary.length > 0" class="card paid-card">
      <h3>Total Paid</h3>
      <table class="balance-table">
        <thead>
          <tr>
            <th>Participant</th>
            <th class="right">Amount Paid</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in paidSummary" :key="p.id">
            <td>{{ p.name }}</td>
            <td class="right paid-amount">{{ formatCurrency(p.totalCents) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <template v-if="balance">
      <div class="card balance-card">
        <h3>Net Balances</h3>
        <p v-if="balance.balances.length === 0" class="empty-state">No data yet. Add some bills first.</p>
        <table v-else class="balance-table">
          <thead>
            <tr>
              <th>Participant</th>
              <th class="right">Net Balance</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="b in balance.balances" :key="b.participantId">
              <td>{{ b.participantName }}</td>
              <td class="right" :class="netClass(b.netCents)">{{ b.formattedNet }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="card transfers-card">
        <h3>Transfers to settle</h3>
        <p v-if="balance.transfers.length === 0" class="empty-state">
          {{ balance.balances.length === 0 ? 'No data yet.' : 'Everyone is settled!' }}
        </p>
        <div v-else class="transfers-list">
          <div v-for="(t, i) in balance.transfers" :key="i" class="transfer-row">
            <span class="transfer-from">{{ t.fromParticipantName }}</span>
            <span class="transfer-arrow">→</span>
            <span class="transfer-to">{{ t.toParticipantName }}</span>
            <span class="transfer-amount">{{ t.formattedAmount }}</span>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useGroupStore } from '../stores/group'

const props = defineProps<{ groupId: string }>()
const store = useGroupStore()
const loading = ref(false)
const error = ref<string | null>(null)

const balance = computed(() => store.balance)

const paidSummary = computed(() => {
  const map = new Map<string, { id: string; name: string; totalCents: number }>()
  for (const bill of store.bills) {
    const key = bill.paidBy.id
    const entry = map.get(key)
    if (entry) {
      entry.totalCents += bill.amountCents
    } else {
      map.set(key, { id: key, name: bill.paidBy.name, totalCents: bill.amountCents })
    }
  }
  return [...map.values()].sort((a, b) => b.totalCents - a.totalCents)
})

function formatCurrency(cents: number): string {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(cents / 100)
}

async function refresh() {
  loading.value = true
  error.value = null
  try {
    await store.loadBalance(props.groupId)
  } catch {
    error.value = 'Failed to load balance.'
  } finally {
    loading.value = false
  }
}

onMounted(() => refresh())

function netClass(cents: number): string {
  if (cents > 0) return 'positive'
  if (cents < 0) return 'negative'
  return 'zero'
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
h3 { font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; color: #444; }
.card {
  background: #fff;
  border-radius: 8px;
  padding: 1rem 1.25rem;
  box-shadow: 0 1px 4px rgba(0,0,0,0.08);
  margin-bottom: 1rem;
}
.balance-table { width: 100%; border-collapse: collapse; font-size: 0.95rem; }
.balance-table th { text-align: left; font-size: 0.8rem; color: #888; padding-bottom: 0.5rem; border-bottom: 1px solid #eee; }
.balance-table td { padding: 0.4rem 0; border-bottom: 1px solid #f5f5f5; }
.right { text-align: right; }
.positive { color: #1e8e3e; font-weight: 600; }
.negative { color: #c5221f; font-weight: 600; }
.zero { color: #888; }
.transfers-list { display: flex; flex-direction: column; gap: 0.5rem; }
.transfer-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #f9f9f9;
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
  font-size: 0.9rem;
}
.transfer-from { font-weight: 600; color: #c5221f; }
.transfer-arrow { color: #888; }
.transfer-to { font-weight: 600; color: #1e8e3e; }
.transfer-amount { margin-left: auto; font-weight: 700; color: #222; }
.paid-amount { font-weight: 600; color: #1a73e8; }
.empty-state { color: #888; padding: 0.5rem 0; font-size: 0.9rem; }
.error-text { color: #c5221f; font-size: 0.875rem; margin-bottom: 0.5rem; }
.btn {
  padding: 0.4rem 0.85rem;
  border-radius: 6px;
  font-size: 0.875rem;
  border: none;
  cursor: pointer;
  font-weight: 500;
}
.btn:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-secondary { background: #e8f0fe; color: #1a73e8; }
.btn-secondary:hover:not(:disabled) { background: #d2e3fc; }
</style>
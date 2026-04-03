<template>
  <div>
    <div class="back-link">
      <router-link to="/">← All Groups</router-link>
    </div>

    <div v-if="store.loading" class="loading">Loading…</div>
    <p v-else-if="store.error" class="error-text">{{ store.error }}</p>

    <template v-else-if="store.group">
      <div class="page-header">
        <h1>{{ store.group.name }}</h1>
      </div>

      <div class="tabs">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          class="tab-btn"
          :class="{ active: activeTab === tab.id }"
          @click="switchTab(tab.id)"
        >
          {{ tab.label }}
        </button>
      </div>

      <div class="tab-content">
        <ParticipantList v-if="activeTab === 'participants'" :group-id="groupId" />
        <BillList v-else-if="activeTab === 'bills'" :group-id="groupId" />
        <BalanceView v-else-if="activeTab === 'balance'" :group-id="groupId" />
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { useGroupStore } from '../stores/group'
import ParticipantList from '../components/ParticipantList.vue'
import BillList from '../components/BillList.vue'
import BalanceView from '../components/BalanceView.vue'

const route = useRoute()
const store = useGroupStore()
const groupId = route.params.id as string

const tabs = [
  { id: 'participants', label: 'Participants' },
  { id: 'bills', label: 'Bills' },
  { id: 'balance', label: 'Balance' },
] as const

type TabId = 'participants' | 'bills' | 'balance'
const activeTab = ref<TabId>('participants')

async function switchTab(tab: TabId) {
  activeTab.value = tab
  if (tab === 'balance') {
    await store.loadBalance(groupId)
  }
}

onMounted(async () => {
  store.reset()
  await store.loadGroup(groupId)
  await Promise.all([
    store.loadParticipants(groupId),
    store.loadBills(groupId),
  ])
})

onUnmounted(() => {
  store.reset()
})
</script>

<style scoped>
.back-link { margin-bottom: 1rem; }
.back-link a { color: #1a73e8; text-decoration: none; font-size: 0.9rem; }
.back-link a:hover { text-decoration: underline; }
.page-header { margin-bottom: 1.5rem; }
h1 { font-size: 1.75rem; }
.loading { color: #888; padding: 2rem 0; text-align: center; }
.error-text { color: #c5221f; }

.tabs {
  display: flex;
  gap: 0;
  border-bottom: 2px solid #e0e0e0;
  margin-bottom: 1.5rem;
}
.tab-btn {
  padding: 0.6rem 1.25rem;
  border: none;
  background: none;
  cursor: pointer;
  font-size: 0.95rem;
  color: #666;
  border-bottom: 2px solid transparent;
  margin-bottom: -2px;
  font-weight: 500;
  transition: color 0.15s, border-color 0.15s;
}
.tab-btn:hover { color: #1a73e8; }
.tab-btn.active { color: #1a73e8; border-bottom-color: #1a73e8; }
</style>
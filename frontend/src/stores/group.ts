import { defineStore } from 'pinia'
import { ref } from 'vue'
import { fetchGroup, type Group } from '../api/groups'
import {
  fetchParticipants,
  createParticipant,
  updateParticipant,
  deleteParticipant,
  type Participant,
} from '../api/participants'
import {
  fetchBills,
  createBill,
  updateBill,
  deleteBill,
  type Bill,
  type BillPayload,
} from '../api/bills'
import { fetchBalance, type GroupBalance } from '../api/balance'

export const useGroupStore = defineStore('group', () => {
  const group = ref<Group | null>(null)
  const participants = ref<Participant[]>([])
  const bills = ref<Bill[]>([])
  const balance = ref<GroupBalance | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function loadGroup(id: string) {
    loading.value = true
    error.value = null
    try {
      group.value = await fetchGroup(id)
    } catch (e) {
      error.value = 'Failed to load group.'
      console.error(e)
    } finally {
      loading.value = false
    }
  }

  async function loadParticipants(groupId: string) {
    participants.value = await fetchParticipants(groupId)
  }

  async function addParticipant(groupId: string, name: string) {
    const p = await createParticipant(groupId, { name })
    participants.value.push(p)
  }

  async function editParticipant(groupId: string, participantId: string, name: string) {
    const p = await updateParticipant(groupId, participantId, { name })
    const idx = participants.value.findIndex((x) => x.id === participantId)
    if (idx !== -1) participants.value[idx] = p
  }

  async function removeParticipant(groupId: string, participantId: string) {
    await deleteParticipant(groupId, participantId)
    participants.value = participants.value.filter((p) => p.id !== participantId)
  }

  async function loadBills(groupId: string) {
    bills.value = await fetchBills(groupId)
  }

  async function addBill(groupId: string, payload: BillPayload) {
    const bill = await createBill(groupId, payload)
    bills.value.push(bill)
  }

  async function editBill(groupId: string, billId: string, payload: BillPayload) {
    const bill = await updateBill(groupId, billId, payload)
    const idx = bills.value.findIndex((b) => b.id === billId)
    if (idx !== -1) bills.value[idx] = bill
  }

  async function removeBill(groupId: string, billId: string) {
    await deleteBill(groupId, billId)
    bills.value = bills.value.filter((b) => b.id !== billId)
  }

  async function loadBalance(groupId: string) {
    balance.value = await fetchBalance(groupId)
  }

  function reset() {
    group.value = null
    participants.value = []
    bills.value = []
    balance.value = null
    error.value = null
  }

  return {
    group,
    participants,
    bills,
    balance,
    loading,
    error,
    loadGroup,
    loadParticipants,
    addParticipant,
    editParticipant,
    removeParticipant,
    loadBills,
    addBill,
    editBill,
    removeBill,
    loadBalance,
    reset,
  }
})
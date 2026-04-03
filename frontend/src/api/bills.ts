import client from './client'

export type SplitType = 'equal' | 'percentage' | 'custom'

export interface BillShare {
  participantId: string
  participantName: string
  amountCents: number
}

export interface Bill {
  id: string
  description: string
  amountCents: number
  paidBy: { id: string; name: string }
  date: string
  splitType: SplitType
  shares: BillShare[]
}

export interface BillPayload {
  description: string
  amountCents: number
  paidByParticipantId: string
  date: string
  splitType: SplitType
  participantIds: string[]
  customAmounts: Record<string, number>
  percentages: Record<string, number>
}

export async function fetchBills(groupId: string): Promise<Bill[]> {
  const { data } = await client.get<Bill[]>(`/groups/${groupId}/bills`)
  return data
}

export async function createBill(groupId: string, payload: BillPayload): Promise<Bill> {
  const { data } = await client.post<Bill>(`/groups/${groupId}/bills`, payload)
  return data
}

export async function updateBill(groupId: string, billId: string, payload: BillPayload): Promise<Bill> {
  const { data } = await client.patch<Bill>(
    `/groups/${groupId}/bills/${billId}`,
    payload,
    { headers: { 'Content-Type': 'application/merge-patch+json' } },
  )
  return data
}

export async function deleteBill(groupId: string, billId: string): Promise<void> {
  await client.delete(`/groups/${groupId}/bills/${billId}`)
}
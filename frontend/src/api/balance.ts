import client from './client'

export interface ParticipantBalance {
  participantId: string
  participantName: string
  netCents: number
  formattedNet: string
}

export interface Transfer {
  fromParticipantId: string
  fromParticipantName: string
  toParticipantId: string
  toParticipantName: string
  amountCents: number
  formattedAmount: string
}

export interface GroupBalance {
  balances: ParticipantBalance[]
  transfers: Transfer[]
}

export async function fetchBalance(groupId: string): Promise<GroupBalance> {
  const { data } = await client.get<GroupBalance>(`/groups/${groupId}/balance`)
  return data
}
import client from './client'

export interface Participant {
  id: string
  name: string
  groupId: string
}

export interface ParticipantPayload {
  name: string
}

export async function fetchParticipants(groupId: string): Promise<Participant[]> {
  const { data } = await client.get<Participant[]>(`/groups/${groupId}/participants`)
  return data
}

export async function createParticipant(groupId: string, payload: ParticipantPayload): Promise<Participant> {
  const { data } = await client.post<Participant>(`/groups/${groupId}/participants`, payload)
  return data
}

export async function updateParticipant(
  groupId: string,
  participantId: string,
  payload: ParticipantPayload,
): Promise<Participant> {
  const { data } = await client.patch<Participant>(
    `/groups/${groupId}/participants/${participantId}`,
    payload,
    { headers: { 'Content-Type': 'application/merge-patch+json' } },
  )
  return data
}

export async function deleteParticipant(groupId: string, participantId: string): Promise<void> {
  await client.delete(`/groups/${groupId}/participants/${participantId}`)
}
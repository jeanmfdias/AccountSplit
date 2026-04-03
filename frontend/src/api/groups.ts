import client from './client'

export interface Group {
  id: string
  name: string
  createdAt: string
  participantCount: number
}

export interface GroupPayload {
  name: string
}

export async function fetchGroups(): Promise<Group[]> {
  const { data } = await client.get<Group[]>('/groups')
  return data
}

export async function fetchGroup(id: string): Promise<Group> {
  const { data } = await client.get<Group>(`/groups/${id}`)
  return data
}

export async function createGroup(payload: GroupPayload): Promise<Group> {
  const { data } = await client.post<Group>('/groups', payload)
  return data
}

export async function updateGroup(id: string, payload: GroupPayload): Promise<Group> {
  const { data } = await client.patch<Group>(`/groups/${id}`, payload, {
    headers: { 'Content-Type': 'application/merge-patch+json' },
  })
  return data
}

export async function deleteGroup(id: string): Promise<void> {
  await client.delete(`/groups/${id}`)
}
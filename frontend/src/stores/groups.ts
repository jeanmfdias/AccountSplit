import { defineStore } from 'pinia'
import { ref } from 'vue'
import {
  fetchGroups,
  createGroup,
  deleteGroup,
  type Group,
} from '../api/groups'

export const useGroupsStore = defineStore('groups', () => {
  const groups = ref<Group[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function load() {
    loading.value = true
    error.value = null
    try {
      groups.value = await fetchGroups()
    } catch (e) {
      error.value = 'Failed to load groups.'
      console.error(e)
    } finally {
      loading.value = false
    }
  }

  async function create(name: string): Promise<Group> {
    const group = await createGroup({ name })
    groups.value.push(group)
    return group
  }

  async function remove(id: string) {
    await deleteGroup(id)
    groups.value = groups.value.filter((g) => g.id !== id)
  }

  return { groups, loading, error, load, create, remove }
})
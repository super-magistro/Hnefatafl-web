// composables/useUserMap.ts
import { computed, ref } from 'vue'
import type { User } from '@/game-types'
import { useMe } from '@/composables/Me'

export function useUserMap() {
  const { user: currentUser, fetchMe } = useMe()
  const users = useState<User[]>('global-users-map', () => [])
  // In pages we already have a users ref, but this composable can accept it via parameter.
  // For simplicity, we will expose a setter.
  const setUsers = (list: User[]) => {
    users.value = list
  }

  const userMap = computed(() => {
    const map = new Map<string, User>()
    users.value.forEach(u => {
      map.set(u['@id'] as string, u)
    })
    return map
  })

  const getUserByIri = (iri: string) => {
    return userMap.value.get(iri) ?? { email: 'En attente...', elo: 1200 } as User
  }

  return { userMap, getUserByIri, setUsers }
}

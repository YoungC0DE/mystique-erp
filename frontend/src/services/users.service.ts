import { http } from './http'

import type { Paginated, User } from '@/types'



export interface UserPayload {

  name: string

  email: string

  password?: string

  is_admin?: boolean

  roles?: string[]

  permissions?: string[]

}



export const usersService = {

  async list(page = 1, perPage = 15): Promise<Paginated<User>> {

    const { data } = await http.get<Paginated<User>>('/users', {

      params: { page, per_page: perPage },

    })

    return data

  },



  async create(payload: UserPayload): Promise<User> {

    const { data } = await http.post<{ data: User }>('/users', payload)

    return data.data

  },



  async update(id: string, payload: Partial<UserPayload>): Promise<User> {

    const { data } = await http.put<{ data: User }>(`/users/${id}`, payload)

    return data.data

  },



  async remove(id: string): Promise<void> {

    await http.delete(`/users/${id}`)

  },

}


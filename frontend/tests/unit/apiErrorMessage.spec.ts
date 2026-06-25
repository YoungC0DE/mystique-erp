import { AxiosError, type AxiosResponse } from 'axios'
import { describe, expect, it } from 'vitest'
import { apiErrorMessage } from '@/services/http'

function axiosError(data: unknown, status = 400): AxiosError {
  const response = {
    data,
    status,
    statusText: '',
    headers: {},
    config: {} as never,
  } as AxiosResponse
  return new AxiosError('request failed', 'ERR_BAD_REQUEST', undefined, undefined, response)
}

describe('apiErrorMessage', () => {
  it('returns the first validation error when present', () => {
    const error = axiosError({
      message: 'Os dados são inválidos.',
      errors: { email: ['O e-mail é obrigatório.', 'segundo'], password: ['curta'] },
    }, 422)

    expect(apiErrorMessage(error)).toBe('O e-mail é obrigatório.')
  })

  it('falls back to the message field when there are no field errors', () => {
    const error = axiosError({ message: 'Não autenticado.' }, 401)

    expect(apiErrorMessage(error)).toBe('Não autenticado.')
  })

  it('uses the provided fallback for non-axios errors', () => {
    expect(apiErrorMessage(new Error('boom'), 'algo deu errado')).toBe('algo deu errado')
  })

  it('uses the default fallback when nothing is informed', () => {
    expect(apiErrorMessage(undefined)).toBe('Ocorreu um erro inesperado.')
  })

  it('ignores empty error arrays and uses the message', () => {
    const error = axiosError({ message: 'Mensagem geral.', errors: { name: [] } }, 422)

    expect(apiErrorMessage(error)).toBe('Mensagem geral.')
  })
})

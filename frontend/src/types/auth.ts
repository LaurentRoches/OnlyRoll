export interface User {
  id: number
  email: string
  pseudo: string
  roles: string[]
  isVerified: boolean
  lastLogin?: string
  createdAt: string
  updatedAt: string
  avatar?: string | null
}

export interface LoginCredentials {
  email: string
  password: string
  rememberMe?: boolean
}

export interface RegisterCredentials {
  pseudo: string
  email: string
  password: string
  confirmPassword: string
}

export interface AuthResponse {
  message: string
  user: User
  token?: string
}

export interface AuthState {
  user: User | null
  token: string | null
  isAuthenticated: boolean
  isLoading: boolean
  error: string | null
}

export interface RegisterResponse {
  message: string
  user: {
    id: number
    email: string
    pseudo: string
  }
}

export interface MeResponse {
  id: number
  email: string
  pseudo: string
  roles: string[]
  isVerified: boolean
  createdAt: string
  updatedAt: string
  lastLogin?: string
  avatar?: string | null
}

export interface DebugLoginResponse {
  success: boolean
  message: string
  user_id: number
  user_email: string
  user_pseudo: string
  user_verified: boolean
  user_roles: string[]
}

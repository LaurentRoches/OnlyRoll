import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { watch } from 'vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // ========== PAGE D'ACCUEIL ==========
    {
      path: '/',
      name: 'home',
      component: () => import('@/views/HomeView.vue'),
      meta: { requiresAuth: false },
    },

    // ========== AUTHENTIFICATION ==========
    {
      path: '/auth',
      component: () => import('@/layouts/AuthLayout.vue'),
      children: [
        {
          path: 'login',
          name: 'login',
          component: () => import('@/views/auth/LoginView.vue'),
          meta: { requiresGuest: true },
        },
        {
          path: 'register',
          name: 'register',
          component: () => import('@/views/auth/RegisterView.vue'),
          meta: { requiresGuest: true },
        },
        {
          path: 'verify-email',
          name: 'verify-email',
          component: () => import('@/views/auth/VerifyEmailView.vue'),
        },
        {
          path: 'forgot-password',
          name: 'forgot-password',
          component: () => import('@/views/auth/ForgotPasswordView.vue'),
          meta: { requiresGuest: true },
        },
        {
          path: 'reset-password',
          name: 'reset-password',
          component: () => import('@/views/auth/ResetPasswordView.vue'),
          meta: { requiresGuest: true },
        },
      ],
    },
    {
      path: '/auth/register-success',
      name: 'register-success',
      component: () => import('@/views/auth/RegisterSuccessView.vue'),
      meta: { requiresGuest: true },
    },

    // ========== DASHBOARD (PROTÉGÉ) ==========
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('@/views/dashboard/DashboardView.vue'),
      meta: { requiresAuth: true },
    },

    // ========== GESTION DES PARTIES (PUBLIC - liste visible sans auth) ==========
    {
      path: '/games',
      name: 'games',
      component: () => import('@/views/games/GameListView.vue'),
      meta: { title: 'Games' },
    },
    {
      path: '/games/:id/play',
      name: 'game-play',
      component: () => import('@/views/games/GamePlayView.vue'),
      meta: { requiresAuth: true, title: 'Game in progress' },
      props: true,
    },

    // ========== WIKI D&D (PUBLIC) ==========
    {
      path: '/wiki',
      children: [
        {
          path: '',
          name: 'wiki.home',
          component: () => import('@/views/wiki/WikiHomeView.vue'),
          meta: { title: 'D&D 5e Wiki' },
        },
        {
          path: ':category',
          name: 'wiki.category',
          component: () => import('@/views/wiki/WikiCategoryView.vue'),
          meta: { title: 'D&D 5e Wiki' },
          props: true,
        },
        {
          path: ':category/:id',
          name: 'wiki.detail',
          component: () => import('@/views/wiki/WikiDetailView.vue'),
          meta: { title: 'D&D 5e Wiki' },
          props: true,
        },
      ],
    },

    // ========== ADMINISTRATION (PROTEGE - ADMIN UNIQUEMENT) ==========
    {
      path: '/admin',
      component: () => import('@/layouts/AdminLayout.vue'),
      meta: { requiresAuth: true, requiresAdmin: true },
      children: [
        {
          path: '',
          name: 'admin',
          component: () => import('@/views/admin/AdminDashboardView.vue'),
          meta: { requiresAuth: true, requiresAdmin: true, title: 'Dashboard' },
        },
        {
          path: 'users',
          name: 'admin-users',
          component: () => import('@/views/admin/AdminUsersView.vue'),
          meta: { requiresAuth: true, requiresAdmin: true, title: 'User Management' },
        },
        {
          path: 'audit-logs',
          name: 'admin-audit-logs',
          component: () => import('@/views/admin/AdminAuditLogsView.vue'),
          meta: { requiresAuth: true, requiresAdmin: true, title: 'Audit Logs' },
        },
      ],
    },

    // ========== PROFIL UTILISATEUR (PROTÉGÉ) ==========
    {
      path: '/profile',
      name: 'profile',
      component: () => import('@/views/profile/ProfileView.vue'),
      meta: { requiresAuth: true, title: 'My Profile' },
    },

    // ========== PAGE 404 ==========
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/views/NotFoundView.vue'),
    },
  ],
})

/**
 * Guard de navigation global
 * Gère l'authentification et les redirections
 */
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  if (authStore.isLoading) {
    await new Promise<void>((resolve) => {
      const unwatch = watch(
        () => authStore.isLoading,
        (loading) => {
          if (!loading) {
            unwatch()
            resolve()
          }
        },
        { immediate: true }
      )
    })
  }

  // ========== VÉRIFICATION DE L'AUTHENTIFICATION ==========

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next({
      name: 'login',
      query: { redirect: to.fullPath },
    })
  }

  if (to.meta.requiresGuest && authStore.isAuthenticated) {
    return next({ name: 'dashboard' })
  }

  if (to.meta.requiresAdmin && !authStore.isAdmin) {
    return next({ name: 'dashboard' })
  }

  next()
})

/**
 * Hook après chaque navigation
 * Utile pour analytics, scroll reset, etc.
 */
router.afterEach((to) => {
  window.scrollTo(0, 0)

  const baseTitle = 'OnlyRoll'
  document.title = to.meta.title ? `${to.meta.title} - ${baseTitle}` : baseTitle
})

export default router

<template>
  <div class="min-h-screen bg-gradient-to-br from-primary-900 via-primary-800 to-secondary-900">
    <DashboardNav />

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-secondary-50 mb-4">{{ t('dashboard.welcome', { pseudo: user?.pseudo }) }}</h1>
        <p class="text-lg text-secondary-400">{{ t('dashboard.subtitle') }}</p>
      </div>

      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
        <DashboardCard
          :title="t('dashboard.cards.myGames.title')"
          :description="t('dashboard.cards.myGames.description')"
          icon="/images/wiki/icons/games_icon.png"
          @click="router.push({ name: 'games', query: { tab: 'my-all' } })"
          :coming-soon="false"
        />

        <DashboardCard
          :title="t('dashboard.cards.wiki.title')"
          :description="t('dashboard.cards.wiki.description')"
          icon="/images/wiki/icons/wiki_icon.png"
          @click="navigateTo('wiki.home')"
          :coming-soon="false"
        />

        <DashboardCard
          :title="t('dashboard.cards.characters.title')"
          :description="t('dashboard.cards.characters.description')"
          icon="/images/wiki/icons/character_sheet_icon.png"
          @click="navigateTo('characters')"
          :coming-soon="true"
        />

        <DashboardCard
          :title="t('dashboard.cards.profile.title')"
          :description="t('dashboard.cards.profile.description')"
          icon="/images/wiki/icons/profil_icon.png"
          @click="navigateTo('profile')"
          :coming-soon="false"
        />

        <DashboardCard
          :title="t('dashboard.cards.quickPlay.title')"
          :description="t('dashboard.cards.quickPlay.description')"
          icon="/images/wiki/icons/flashgame_icon.png"
          @click="navigateTo('games')"
          :coming-soon="false"
        />

        <DashboardCard
          :title="t('dashboard.cards.community.title')"
          :description="t('dashboard.cards.community.description')"
          icon="/images/wiki/icons/multiplayer_icon.png"
          @click="openDiscord"
          :coming-soon="true"
        />
      </div>

      <div class="mt-16 bg-secondary-800/50 rounded-xl p-8 border border-secondary-700">
        <div class="text-center">
          <h2 class="text-xl font-semibold text-secondary-50 mb-4">{{ t('dashboard.devNotice.title') }}</h2>
          <p class="text-secondary-400 mb-6">
            {{ t('dashboard.devNotice.message') }}<br />
            {{ t('dashboard.devNotice.upcoming') }}
          </p>

          <div class="bg-secondary-800 rounded-lg p-4 max-w-md mx-auto">
            <h3 class="text-sm font-medium text-secondary-300 mb-2">{{ t('dashboard.session.title') }}</h3>
            <div class="text-left space-y-1 text-xs text-secondary-400">
              <p><span class="text-secondary-300">{{ t('dashboard.session.id') }}</span> {{ user?.id }}</p>
              <p><span class="text-secondary-300">{{ t('dashboard.session.email') }}</span> {{ user?.email }}</p>
              <p><span class="text-secondary-300">{{ t('dashboard.session.pseudo') }}</span> {{ user?.pseudo }}</p>
              <p><span class="text-secondary-300">{{ t('dashboard.session.roles') }}</span> {{ user?.roles.join(', ') }}</p>
              <p>
                <span class="text-secondary-300">{{ t('dashboard.session.verified') }}</span>
                {{ user?.isVerified ? t('dashboard.session.yes') : t('dashboard.session.no') }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuth } from '@/composables/useAuth'
import DashboardNav from '@/components/dashboard/DashboardNav.vue'
import DashboardCard from '@/components/dashboard/DashboardCard.vue'

const { t } = useI18n()

const router = useRouter()
const { user } = useAuth()

const navigateTo = (routeName: string) => {
  router.push({ name: routeName })
}

const openDiscord = () => {
  window.open('https://discord.gg/your-server', '_blank')
}
</script>

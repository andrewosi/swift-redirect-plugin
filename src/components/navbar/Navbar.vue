<template>
  <va-navbar class="app-layout-navbar">
    <template #left>
      <div class="left">
        <router-link to="/">
          <i class="fa-solid fa-house"></i>
        </router-link>
      </div>
    </template>
    <div class="app-navbar-center d-flex gap-4">
      <Menu />
      <va-button
        href="https://www.buymeacoffee.com/rcc_group"
        class="hidden lg:block"
        icon="fa-regular fa-heart"
        target="_blank"
      >
        {{ t('support-us') }}
      </va-button>
    </div>
    <template #right>
      <app-navbar-actions class="app-navbar__actions" />
    </template>
  </va-navbar>
</template>

<script setup>
  import { computed } from 'vue'
  import { useI18n } from 'vue-i18n'
  import { useColors } from 'vuestic-ui'
  import AppNavbarActions from './components/AppNavbarActions.vue'
  import Menu from './components/Menu.vue'

  const { t } = useI18n()

  const { getColors } = useColors()
  const colors = computed(() => getColors())
</script>

<style lang="scss" scoped>
  .va-navbar {
    box-shadow: var(--va-box-shadow);
    z-index: 2;

    @media screen and (max-width: 950px) {
      .left {
        width: 100%;
      }

      .app-navbar__actions {
        width: 100%;
        display: flex;
        justify-content: space-between;
      }
    }
  }

  .left {
    display: flex;
    align-items: center;

    & > * {
      margin-right: 1.5rem;
    }

    & > *:last-child {
      margin-right: 0;
    }
  }

  .x-flip {
    transform: scaleX(-100%);
  }

  .app-navbar-center {
    display: flex;
    align-items: center;
    height: 1rem;

    @media screen and (max-width: 1200px) {
      &__github-button {
        display: none;
      }
    }

    @media screen and (max-width: 950px) {
      &__text {
        display: none;
      }
    }
  }
</style>

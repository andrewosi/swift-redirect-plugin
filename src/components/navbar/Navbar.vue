<template>
  <va-navbar class="app-layout-navbar">
    <template #left>
      <div class="left">
        <router-link :to="{ name: 'dashboard' }" class="home-link" aria-label="Swift Redirect dashboard">
          <va-icon name="home" size="medium" />
        </router-link>
      </div>
    </template>
    <div class="app-navbar-center">
      <Menu />
      <div class="app-navbar-right">
        <app-navbar-actions class="app-navbar__actions" />
        <va-button
          href="https://www.buymeacoffee.com/rcc_group"
          class="hidden lg:block support-button"
          target="_blank"
          rel="noopener noreferrer"
          color="primary"
        >
          <va-icon name="favorite" size="small" class="support-button__icon" aria-hidden="true" />
          {{ t('support-us') }}
        </va-button>
      </div>
    </div>
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
  .va-navbar.app-layout-navbar {
    display: block!important;
  }
  .va-navbar {
    box-shadow: var(--va-box-shadow);
    z-index: 1;

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
  }

  .x-flip {
    transform: scaleX(-100%);
  }

  .app-navbar-center {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    gap: 1rem;

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

  .app-navbar-right {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-left: auto;
  }

  .home-link {
    display: inline-flex;
    padding: 0.35rem;
    border-radius: 50%;
    color: var(--va-primary);
    transition: background-color 0.2s ease, color 0.2s ease;

    &:hover {
      background-color: rgba(21, 78, 193, 0.1);
    }
  }

  .support-button {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;

    &__icon {
      color: var(--va-white);
    }
  }
</style>

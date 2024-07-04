<template>
  <va-navbar class="app-layout-navbar">
    <template #left>
      <div class="left">
        <router-link to="/">
<!--          <i class="fa-solid fa-house"></i>-->
          <HomeIcon />
        </router-link>
      </div>
    </template>
    <div class="app-navbar-center d-flex gap-4">
      <Menu />
      <!--        icon="fa-regular fa-heart"-->
      <va-button
        href="https://www.buymeacoffee.com/rcc_group"
        class="hidden lg:block"
        target="_blank"
      >
        <HeartIcon style="fill: #fff;"/>
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
  import HeartIcon from 'vue-ionicons/dist/ios-heart.vue'
  import HomeIcon from 'vue-ionicons/dist/ios-home.vue'

  const { t } = useI18n()

  const { getColors } = useColors()
  const colors = computed(() => getColors())
</script>

<style lang="scss" scoped>
  .va-navbar {
    box-shadow: var(--va-box-shadow);
    z-index: 2;

    .navigation-route .ion {
      fill: #154ec1;
    }
    .router-link-active.navigation-route .ion {
      fill: #fff;
    }

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

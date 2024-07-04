<script setup>
import { ref, defineAsyncComponent } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import NavigationRoutes from './NavigationRoutes'
const { t } = useI18n()
import HomeIcon from 'vue-ionicons/dist/ios-home.vue'
import ListIcon from 'vue-ionicons/dist/md-list.vue'
import SettingsIcon from 'vue-ionicons/dist/ios-settings.vue'
import DangerIcon from 'vue-ionicons/dist/ios-warning.vue'


const items = ref(NavigationRoutes.routes)
const accordionValue = ref(Array(items.value.length).fill(false))

function isRouteActive(INavigationRoute) {
  return INavigationRoute.name === useRoute().name
}

</script>

<template>
  <div class="d-flex gap-4 flex-wrap">
    <div v-for="(route, idx) in items" :key="idx" class="d-flex gap-1">
      <router-link
          :style="isRouteActive(route) ? 'background-color: #2c82e0; color: #fff' : ''"
          :to="route.children ? undefined : { name: route.name }"
          class="navigation-route"
      >
        <HomeIcon v-if="idx === 0"/>
        <ListIcon v-if="idx === 1"/>
        <DangerIcon v-if="idx === 2"/>
        <SettingsIcon v-if="idx === 3"/>

        {{ t(route.displayName) }}
        <va-icon v-if="route.children" :name="accordionValue[idx] ? 'expand_less' : 'expand_more'" />
      </router-link>
    </div>
  </div>
</template>

<style scoped>
.navigation-route {
  padding: 10px;
  border-radius: 20px;
  border: none;
  display: flex;
  align-items: center;
  gap: 5px;
}

.navigation-route .ion {
  fill: #154ec1;
}
.router-link-active.navigation-route .ion {
  fill: #fff;
}
</style>

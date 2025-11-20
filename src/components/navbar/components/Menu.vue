<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import NavigationRoutes from './NavigationRoutes'

const { t } = useI18n()
const route = useRoute()

const navigationItems = computed(() =>
  NavigationRoutes.routes.map((navRoute) => ({
    ...navRoute,
    icon: navRoute.meta?.icon ?? '',
  })),
)

const isRouteActive = (navRoute) => navRoute.name === route.name

const linkStyles = (navRoute) => ({
  color: isRouteActive(navRoute) ? 'var(--va-white)' : 'var(--va-primary)',
})

const iconColor = (navRoute) => (isRouteActive(navRoute) ? 'white' : 'primary')
</script>

<template>
  <div class="navigation">
    <router-link
      v-for="navRoute in navigationItems"
      :key="navRoute.name"
      class="navigation-route"
      :class="{ 'navigation-route--active': isRouteActive(navRoute) }"
      :to="navRoute.children ? undefined : { name: navRoute.name }"
      :style="linkStyles(navRoute)"
    >
      <va-icon
        v-if="navRoute.icon"
        :name="navRoute.icon"
        size="small"
        class="navigation-route__icon"
        :class="{ 'navigation-route__icon--active': isRouteActive(navRoute) }"
        aria-hidden="true"
      />
      <span class="navigation-route__label">{{ t(navRoute.displayName) }}</span>
    </router-link>
  </div>
</template>

<style scoped>
.navigation {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.navigation-route {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.35rem 0.9rem;
  border-radius: 999px;
  color: var(--va-primary);
  background-color: transparent;
  text-decoration: none;
  transition: background-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
}

.navigation-route__icon {
  color: var(--va-primary);
  transition: color 0.2s ease;
}

.navigation-route__icon--active {
  color: var(--va-white) !important;
}

.navigation-route__label {
  font-weight: 600;
  white-space: nowrap;
}

.navigation-route:hover {
  background-color: rgba(21, 78, 193, 0.08);
  color: var(--va-primary);
}

.navigation-route--active {
  background-color: var(--va-primary);
  color: var(--va-white);
  box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.08);
}

.navigation-route--active .navigation-route__icon {
  color: var(--va-white);
}
</style>

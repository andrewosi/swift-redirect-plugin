<template>
  <va-dropdown class="color-dropdown pointer" :offset="[13, 8]" stick-to-edges>
    <template #anchor>
      <va-icon-color />
    </template>

    <va-dropdown-content class="color-dropdown__content">
      <div class="color-dropdown__section">
        <va-button-toggle
          v-model="themeStore.currentTheme"
          class="color-dropdown__toggle"
          :options="themeStore.themeOptions"
          outline
          round
          grow
          size="small"
          @update:model-value="themeStore.setTheme"
        />
      </div>
      <div class="color-dropdown__section">
        <VaColorPalette
          v-model="themeStore.primaryColor"
          :palette="palette"
          @update:model-value="themeStore.setPrimaryColor"
        />
      </div>
    </va-dropdown-content>
  </va-dropdown>
</template>

<script setup>
  import VaIconColor from '../../../icons/VaIconColor.vue'
  import { useColors } from 'vuestic-ui'
  import { useThemeStore } from '../../../../stores/theme'

  const { colors } = useColors()
  const themeStore = useThemeStore()

  const palette = ['#2c82e0', '#ef476f', '#ffd166', '#06d6a0', '#8338ec']

  // Синхронізувати primary color з store
  import { watch } from 'vue'
  watch(
    () => themeStore.primaryColor,
    (newColor) => {
      colors.primary = newColor
      document.documentElement.style.setProperty('--va-primary', newColor)
    },
    { immediate: true }
  )
</script>

<style lang="scss" scoped>
  .color-dropdown {
    .va-dropdown__anchor {
      display: inline-block;
      cursor: pointer;
    }

    &__content {
      padding: 1rem;
      min-width: 200px;
    }

    &__section {
      margin-bottom: 1rem;

      &:last-child {
        margin-bottom: 0;
      }
    }

    &__toggle {
      width: 100%;
      display: flex;
      justify-content: stretch;
    }
  }
</style>

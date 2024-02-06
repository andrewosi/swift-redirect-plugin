<template>
  <va-dropdown class="color-dropdown pointer" :offset="[13, 0]" stick-to-edges>
    <template #anchor>
      <va-icon-color />
    </template>

    <va-dropdown-content class="color-dropdown__content pl-8 pr-8 pt-2 pb-2">
      <va-button-toggle
        v-model="currentTheme"
        class="color-dropdown__toggle"
        :options="themeOptions"
        outline
        round
        grow
        size="small"
      />
      <VaColorPalette v-model="colors.primary" :palette="palette" style="margin-top: 10px" />
    </va-dropdown-content>
  </va-dropdown>
</template>

<script setup lang="ts">
  import VaIconColor from '../../../icons/VaIconColor.vue'
  import { useColors } from 'vuestic-ui'
  import { ref, watchEffect, watch, onMounted } from 'vue'

  const { presets, applyPreset, currentPresetName, colors } = useColors()

  const currentTheme = ref('light')
  const currentThemeMode = ref('')

  watchEffect(() => {
    if (currentTheme.value !== 'light') {
      localStorage.setItem('currentTheme', currentTheme.value)
      applyPreset(currentTheme.value)
      currentThemeMode.value = currentTheme.value
    } else if(currentThemeMode.value === 'dark') {
      localStorage.setItem('currentTheme', currentTheme.value)
      applyPreset(currentTheme.value)
    }
  })
  watch(
    () => colors.primary,
    (oldVal, newVal) => {
      if (newVal !== oldVal && colors.primary !== '#3472F0') {
        localStorage.setItem('colorsPrimary', colors.primary)
      }
    },
  )
  const palette = ['#2c82e0', '#ef476f', '#ffd166', '#06d6a0', '#8338ec']
  const themeOptions = Object.keys(presets.value).map((themeName) => ({
    value: themeName,
    label: themeName,
  }))

  onMounted(() => {
    const localStorageTheme = localStorage.getItem('currentTheme')
    const localStorageColor = localStorage.getItem('colorsPrimary')
    if (localStorageTheme) {
      currentTheme.value = localStorageTheme
    }
    if (localStorageColor) {
      colors.primary = localStorageColor
    }
  })

</script>

<style lang="scss" scoped>
  .color-dropdown {
    &__icon {
      position: relative;
      display: flex;
      align-items: center;
    }

    .va-dropdown__anchor {
      display: inline-block;
    }

    &__toggle {
      width: 100%;
      display: flex;
      justify-content: stretch;
    }
  }

  .button-restore {
    display: flex;
    margin: 0.375rem auto;
  }
</style>

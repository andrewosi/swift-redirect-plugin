import { defineStore } from 'pinia'
import { ref, watch } from 'vue'
import { useColors } from 'vuestic-ui'

export const useThemeStore = defineStore('theme', () => {
  const { presets, applyPreset, colors } = useColors()
  
  const currentTheme = ref<string>('light')
  const primaryColor = ref<string>('#2c82e0')

  // Завантажити з localStorage при ініціалізації
  const loadFromStorage = () => {
    const savedTheme = localStorage.getItem('swift-redirect-theme')
    const savedColor = localStorage.getItem('swift-redirect-primary-color')
    
    if (savedTheme) {
      currentTheme.value = savedTheme
      applyPreset(savedTheme)
    }
    
    if (savedColor) {
      primaryColor.value = savedColor
      colors.primary = savedColor
    }
  }

  // Зберегти тему
  const setTheme = (themeName: string) => {
    currentTheme.value = themeName
    applyPreset(themeName)
    localStorage.setItem('swift-redirect-theme', themeName)
  }

  // Зберегти primary колір
  const setPrimaryColor = (color: string) => {
    primaryColor.value = color
    colors.primary = color
    localStorage.setItem('swift-redirect-primary-color', color)
  }

  // Спостерігати за змінами кольору
  watch(
    () => colors.primary,
    (newColor) => {
      if (newColor && newColor !== primaryColor.value) {
        setPrimaryColor(newColor)
      }
    }
  )

  // Ініціалізація
  loadFromStorage()

  return {
    currentTheme,
    primaryColor,
    setTheme,
    setPrimaryColor,
    presets,
    themeOptions: Object.keys(presets.value).map((themeName) => ({
      value: themeName,
      label: themeName,
    })),
  }
})


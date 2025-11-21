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
      
      // Додати клас для темної теми при завантаженні
      const isDarkTheme = savedTheme === 'dark' || savedTheme === 'semi-dark'
      if (isDarkTheme) {
        document.body.classList.add(`va-theme-${savedTheme}`)
        document.documentElement.classList.add(`va-theme-${savedTheme}`)
      }
    }
    
    if (savedColor) {
      primaryColor.value = savedColor
      colors.primary = savedColor
      document.documentElement.style.setProperty('--va-primary', savedColor)
    }
  }

  // Зберегти тему
  const setTheme = (themeName: string) => {
    currentTheme.value = themeName
    applyPreset(themeName)
    
    // Додати/видалити клас для темної теми
    const isDarkTheme = themeName === 'dark' || themeName === 'semi-dark'
    if (isDarkTheme) {
      document.body.classList.add(`va-theme-${themeName}`)
      document.documentElement.classList.add(`va-theme-${themeName}`)
    } else {
      document.body.classList.remove('va-theme-dark', 'va-theme-semi-dark')
      document.documentElement.classList.remove('va-theme-dark', 'va-theme-semi-dark')
    }
    
    // Застосувати primary колір після зміни теми
    if (primaryColor.value) {
      document.documentElement.style.setProperty('--va-primary', primaryColor.value)
    }
    localStorage.setItem('swift-redirect-theme', themeName)
  }

  // Зберегти primary колір
  const setPrimaryColor = (color: string) => {
    primaryColor.value = color
    colors.primary = color
    document.documentElement.style.setProperty('--va-primary', color)
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
  
  // Застосувати primary колір після завантаження (якщо не було завантажено з localStorage)
  if (!localStorage.getItem('swift-redirect-primary-color') && primaryColor.value) {
    document.documentElement.style.setProperty('--va-primary', primaryColor.value)
  }

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


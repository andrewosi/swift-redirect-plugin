import { createI18n } from 'vue-i18n'

const fileNameToLocaleModuleDict = import.meta.globEager('./locales/*.json')

const messages = {}
let locale = document.documentElement.lang.toLowerCase()
switch (true) {
  case locale.includes('de'):
    locale = 'de'
    break
  case locale.includes('en'):
    locale = 'en'
    break
  case locale.includes('es'):
    locale = 'es'
    break
  case locale.includes('fi'):
    locale = 'fi'
    break
  case locale.includes('fr'):
    locale = 'fr'
    break
  case locale.includes('gb'):
    locale = 'gb'
    break
  case locale.includes('it'):
    locale = 'it'
    break
  case locale.includes('jp'):
    locale = 'jp'
    break
  case locale.includes('pt'):
    locale = 'pt'
    break
  case locale.includes('ru'):
    locale = 'ru'
    break
  case locale.includes('uk'):
    locale = 'uk'
    break
  default:
    locale = 'gb'
}

Object.entries(fileNameToLocaleModuleDict)
  .map(([fileName, localeModule]) => {
    const fileNameParts = fileName.split('/')
    const fileNameWithoutPath = fileNameParts[fileNameParts.length - 1]
    const localeName = fileNameWithoutPath.split('.json')[0]

    return [localeName, localeModule.default]
  })
  .forEach((localeNameLocaleMessagesTuple) => {
    messages[localeNameLocaleMessagesTuple[0]] = localeNameLocaleMessagesTuple[1]
  })

export default createI18n({
  legacy: false,
  locale: locale,
  fallbackLocale: 'gb',
  messages,
})

import { ref } from 'vue'
import Resource from './resource'
import { useToast } from 'vue-toastification'

class SwiftRedirectPlugin {
  // Variables declaration and Methods bindings
  constructor() {
    this.toast = useToast()
    this.url = '/wp-admin/admin-ajax.php?action=swift-redirect_admin'
    this.redirectsData = ref([])
    this.totalRedirects = ref(0)
    this.countOfRedirects = ref(0)
    this.hostsList = ref([])
    this.totalLogs = ref(0)
    this.log404 = ref([])
    this.totalLog404 = ref(0)
    this.logs = ref([])
    this.redirectsColumns = ref([
      { key: 'domain', sortable: true, width: '25%' },
      { key: 'key', label: 'path', sortable: true, width: '25%' },
      { key: 'is_regex', sortable: true, width: '80px' },
      { key: 'code', sortable: true, width: '70px' },
      { key: 'is_params', sortable: true, width: '80px' },
      { key: 'target_url', sortable: true, width: '25%' },
      { key: 'count', sortable: true, width: '100px' },
      { key: 'is_enabled', sortable: true, width: '80px' },
      { key: 'actions' },
    ])
    this.logsColumns = ref([
      { key: 'id' },
      { key: 'redirect_from', sortable: true, width: '25%' },
      { key: 'redirect_to', sortable: true, width: '25%' },
      { key: 'user_agent', sortable: true, width: '35%' },
      { key: 'created_at', label: 'date', sortable: true },
    ])
    this.log404Columns = ref([
      { key: 'host', sortable: true, width: '30%' },
      { key: 'request_link', sortable: true, width: '40%' },
      { key: 'count_of_requests', sortable: true },
      { key: 'is_redirect' },
      { key: 'actions' },
    ])
    this.addNewModal = ref(false)
    this.newRedirect = ref({
      domain: this.hostsList.value.length <= 1 ? window.location.hostname : '',
      key: '',
      is_regex: false,
      target_url: '',
      code: 301,
      is_enabled: true,
      is_params: false,
      count_of_redirects: '0',
    })
    this.changedOldRedirects = ref([])
    this.editedItem = ref({})
    this.editModal = ref(false)
    this.selectedRedirects = ref([])
    this.codeOptions = ref([301, 302, 303, 307, 308])
    this.selectedAction = ref({ color: 'primary', action: null })
    this.bulkActions = ref([
      { color: 'success', action: 'activate' },
      { color: 'warning', action: 'deactivate' },
      { color: 'danger', action: 'delete' },
    ])
    this.query = ref({
      limit: 15,
      page: 0,
    })
    this.queryLimitOptions = ref([15, 30, 50, 100])
    this.invalidNewRedirect = ref({
      domain: false,
      key: false,
      target_url: false,
    })
    this.invalidOldRedirect = ref({
      domain: false,
      key: false,
      target_url: false,
    })
    this.validationReasons = ref({
      new: {
        domain: '',
        key: '',
        target_url: '',
      },
      old: {
        domain: '',
        key: '',
        target_url: '',
      },
    })
    this.isLoading = ref(false)

    // Bind methods to the class instance
    this.fetchRedirects = this.fetchRedirects.bind(this)
    this.trimValue = this.trimValue.bind(this)
    this.postRedirects = this.postRedirects.bind(this)
    this.openEditModal = this.openEditModal.bind(this)
    this.updateRedirects = this.updateRedirects.bind(this)
    this.removeRedirect = this.removeRedirect.bind(this)
    this.removeAllRedirects = this.removeAllRedirects.bind(this)
    this.handleBulkActions = this.handleBulkActions.bind(this)
    this.resetNewItem = this.resetNewItem.bind(this)
    this.addNewDomain = this.addNewDomain.bind(this)
    this.validateError = this.validateError.bind(this)
    this.importData = this.importData.bind(this)
    this.fetchLog404 = this.fetchLog404.bind(this)
  }

  setLoading(value) {
    this.isLoading.value = value
  }

  resetNewItem() {
    this.newRedirect.value = {
      domain: this.hostsList.value.length <= 1 ? window.location.hostname : '',
      key: '',
      is_regex: false,
      target_url: '',
      code: 301,
      is_enabled: true,
      is_params: false,
      count_of_redirects: 0,
    }
    this.invalidNewRedirect.value = {
      domain: false,
      key: false,
      target_url: false,
    }
    this.validationReasons.value.new = {
      domain: '',
      key: '',
      target_url: '',
    }
    this.addNewModal.value = false
  }
  resetEditedItem() {
    this.editedItem.value = {}
    this.invalidOldRedirect.value = {
      domain: false,
      key: false,
      target_url: false,
    }
    this.validationReasons.value.old = {
      domain: '',
      key: '',
      target_url: '',
    }
    this.editModal.value = false
  }

  // add new domain name if multiple
  addNewDomain(item) {
    this.hostsList.value.push(item)
  }
  // Validate section
  validateError = (obj, type, scope = 'new') => {
    let isValid = true
    Object.keys(obj).forEach((validationKey) => {
      if (typeof type === 'object' && Object.prototype.hasOwnProperty.call(type, validationKey)) {
        this.trimValue(type, validationKey)
      }
      const reason = this.determineValidationReason(validationKey, type?.[validationKey])
      this.validationReasons.value[scope][validationKey] = reason
      obj[validationKey] = reason !== ''
      if (obj[validationKey]) {
        isValid = false
      }
    })
    return isValid
  }

  determineValidationReason(field, value) {
    const normalised = typeof value === 'string' ? value.trim() : value
    if (!normalised) {
      return 'empty'
    }
    switch (field) {
      case 'domain':
        return this.isDomainValid(normalised) ? '' : 'invalid_domain'
      case 'key':
        return this.isPathValid(normalised) ? '' : 'invalid_path'
      case 'target_url':
        return this.isUrlValid(normalised) ? '' : 'invalid_url'
      default:
        return ''
    }
  }

  isDomainValid(value = '') {
    if (!value) {
      return false
    }
    let candidate = value.toString().trim().toLowerCase()
    try {
      if (!candidate.includes('://')) {
        candidate = `https://${candidate}`
      }
      candidate = new URL(candidate).hostname
    } catch (error) {
      return false
    }
    const domainRegex =
      /^(?=.{1,253}$)(?!-)[a-z0-9-]{1,63}(?<!-)(\.(?!-)[a-z0-9-]{1,63}(?<!-))*$/i
    return domainRegex.test(candidate) || candidate === 'localhost'
  }

  isPathValid(value = '') {
    if (!value) {
      return false
    }
    let candidate = value.toString().trim()
    // Normalize path: remove multiple slashes and dots
    candidate = candidate.replace(/\/{2,}/g, '/').replace(/\.{2,}/g, '.')
    candidate = '/' + candidate.replace(/^\/+|\/+$/g, '')
    candidate = candidate.replace(/\/{2,}/g, '/')
    const pathRegex = /^\/[A-Za-z0-9\-._~/%&=?@:]*$/
    return pathRegex.test(candidate)
  }

  isUrlValid(value = '') {
    if (!value) {
      return false
    }
    try {
      let candidate = value.toString().trim()
      // Add protocol if missing
      if (!/^https?:\/\//i.test(candidate)) {
        candidate = 'https://' + candidate
      }
      new URL(candidate)
      return true
    } catch (error) {
      return false
    }
  }

  getValidationMessage(scope, field) {
    const reason = this.validationReasons.value?.[scope]?.[field]
    switch (reason) {
      case 'invalid_domain':
        return 'tables.error-invalid-domain'
      case 'invalid_path':
        return 'tables.error-invalid-path'
      case 'invalid_url':
        return 'tables.error-invalid-url'
      case 'empty':
        return 'tables.error-empty'
      default:
        return 'tables.error-empty'
    }
  }
  // Sanitise input fields
  trimValue(val, key) {
    let sanitisedVal
    if (val[key]) {
      sanitisedVal = val[key].toString().trim()
      switch (key) {
        case 'domain':
          sanitisedVal = sanitisedVal.toLowerCase().replace(/_/g, '-').replace(/\s+/g, '-')
          // Remove multiple consecutive dots
          sanitisedVal = sanitisedVal.replace(/\.{2,}/g, '.')
          // Remove leading/trailing dots
          sanitisedVal = sanitisedVal.replace(/^\.+|\.+$/g, '')
          if (URL.canParse(sanitisedVal) || URL.canParse('https://' + sanitisedVal)) {
            try {
              const url = URL.canParse(sanitisedVal) ? new URL(sanitisedVal) : new URL('https://' + sanitisedVal)
              sanitisedVal = url.hostname
            } catch (e) {
              // Keep original if URL parsing fails
            }
          }
          val[key] = sanitisedVal
          break
        case 'key':
          sanitisedVal = sanitisedVal.replace(/\s+/g, '')
          // Remove multiple consecutive slashes and dots
          sanitisedVal = sanitisedVal.replace(/\/{2,}/g, '/').replace(/\.{2,}/g, '.')
          if (URL.canParse(sanitisedVal) || URL.canParse('https://example.com' + sanitisedVal)) {
            try {
              const url = URL.canParse(sanitisedVal) ? new URL(sanitisedVal) : new URL('https://example.com' + sanitisedVal)
              sanitisedVal = url.pathname
            } catch (e) {
              // Keep original if URL parsing fails
            }
          }
          sanitisedVal = '/' + sanitisedVal.replace(/^\/+|\/+$/g, '')
          // Normalize multiple slashes to single
          sanitisedVal = sanitisedVal.replace(/\/{2,}/g, '/')
          val[key] = sanitisedVal
          break
        case 'target_url':
          sanitisedVal = sanitisedVal.trim()
          // Add protocol if missing
          if (sanitisedVal && !/^https?:\/\//i.test(sanitisedVal)) {
            sanitisedVal = 'https://' + sanitisedVal
          }
          val[key] = sanitisedVal
          break
      }
    }
  }

  // Post func
  async postRedirects() {
    if (this.validateError(this.invalidNewRedirect.value, this.newRedirect.value, 'new') !== true) {
      return false
    }
    this.setLoading(true)
    try {
      const new_redirects = new Resource()
      const response = await new_redirects.store([this.newRedirect.value])
      if (response.status === 'success') {
        const responseData = await response
        this.redirectsData.value.push(...responseData.data)
        this.resetNewItem()
      }
    } catch (error) {
      return
    } finally {
      this.setLoading(false)
    }
  }
  // Update func
  openEditModal(item) {
    this.editedItem.value = item
    this.editedItem.value.is_regex = Boolean(Number(this.editedItem.value.is_regex))
    this.editedItem.value.is_enabled = Boolean(Number(this.editedItem.value.is_enabled))
    this.editedItem.value.is_params = Boolean(Number(this.editedItem.value.is_params))
    this.editModal.value = true
  }
  async updateRedirects() {
    if (
      this.changedOldRedirects.value.length === 1 &&
      this.validateError(this.invalidOldRedirect.value, this.editedItem.value, 'old') !== true
    ) {
      return false
    } else {
      this.resetEditedItem()
    }
    this.setLoading(true)
    try {
      const updated_redirects = new Resource()
      const response = await updated_redirects.update(this.changedOldRedirects.value)
      if (response.status === 'success') {
        const responseData = await response
        responseData.data.forEach((updatedRedirect) => {
          // find the redirect with the same id in this.redirectsData
          const oldRedirect = this.redirectsData.value.find((old) => old.id === updatedRedirect.id)
          // if the redirect with the same id is found, update its values
          if (oldRedirect) {
            oldRedirect.code = updatedRedirect.code
            oldRedirect.count_of_redirects = updatedRedirect.count_of_redirects
            oldRedirect.domain = updatedRedirect.domain
            oldRedirect.is_enabled = updatedRedirect.is_enabled
            oldRedirect.is_params = updatedRedirect.is_params
            oldRedirect.is_regex = updatedRedirect.is_regex
            oldRedirect.key = updatedRedirect.key
            oldRedirect.target_url = updatedRedirect.target_url
          }
        })
        this.changedOldRedirects.value = []
      }
    } catch (error) {
      console.log(error)
    } finally {
      this.setLoading(false)
    }
  }
  // Destroy func
  async removeRedirect(id) {
    this.setLoading(true)
    try {
      const removeResource = new Resource()
      id = Array.isArray(id) ? id : [id]
      const response = await removeResource.destroy(id)
      if (response.status !== 'success') {
        return
      } else {
        id.map((elem) => {
          this.redirectsData.value = this.redirectsData.value.filter((obj) => obj.id !== elem)
        })
      }
    } catch (error) {
      console.log(error.message)
    } finally {
      this.setLoading(false)
    }
  }

  async removeAllRedirects() {
    this.setLoading(true)
    const allIds = ref([])
    const allRedirects = ref([])
    try {
      const redirects = new Resource()
      // get total number
      const responseTotal = await redirects.list({ limit: 0, page: 0 })
      this.totalRedirects.value = await responseTotal.data.total
      // fetch all Redirects
      const responseRedirects = await redirects.list({ limit: this.totalRedirects.value, page: 0 })
      allRedirects.value = await responseRedirects
    } catch (error) {
      this.toast.error(`Could not fetch the IDs to remove everything: ${error.status}`)
    } finally {
      this.setLoading(false)
    }
    allRedirects.value.data.data.map((elem) => {
      allIds.value.push(elem.id)
    })
    await this.removeRedirect(allIds.value)
  }

  // Handle Bulk actions
  async handleBulkActions() {
    this.setLoading(true)
    const idsToDelete = ref([])
    const selectedCase = this.selectedAction.value.action
    try {
      switch (selectedCase) {
        case 'activate':
          this.selectedRedirects.value.forEach((elem) => {
            elem.is_enabled = 1
            this.changedOldRedirects.value.push(elem)
          })
          break
        case 'deactivate':
          this.selectedRedirects.value.forEach((elem) => {
            elem.is_enabled = 0
            this.changedOldRedirects.value.push(elem)
          })
          break
        case 'delete':
          this.selectedRedirects.value.forEach((elem) => {
            idsToDelete.value.push(elem.id)
          })
          try {
            await this.removeRedirect(idsToDelete.value)
          } catch (error) {
            this.toast.error('Error deleting selected data: ', error.message)
          }
          break
      }
      if (selectedCase !== 'delete') {
        try {
          await this.updateRedirects()
        } catch (error) {
          this.toast.error('Error updating selected data: ', error.message)
        }
      }
    } finally {
      this.setLoading(false)
    }
    this.selectedRedirects.value = []
  }

  async fetchRedirects(query) {
    this.setLoading(true)
    try {
      const redirects = new Resource()
      // fetch Redirects
      const responseRedirects = await redirects.list(query)
      if (!responseRedirects) {
        this.toast.error(`HTTP error! Status: ${responseRedirects.status}`)
      }
      const responsed = responseRedirects.data

      const normalizedData = responsed.data.map((redirect) => ({
        ...redirect,
        count: redirect.count_of_redirects,
      }))

      this.redirectsData.value = normalizedData
      this.countOfRedirects.value = responsed.count_of_redirects
      this.totalRedirects.value = responsed.total
      this.hostsList.value = responsed.hosts_list
    } catch (error) {
      this.toast.error('Error fetching data: ', error.message)
    } finally {
      this.setLoading(false)
    }
  }
  async fetchLogs(query) {
    this.setLoading(true)
    try {
      const redirects = new Resource()
      // fetch Logs
      const responseLogs = await redirects.logs(query)
      if (!responseLogs) {
        console.log(`HTTP error! Status: ${responseLogs.status}`)
      }
      const responsedLogs = responseLogs.data
      this.logs.value = responsedLogs.data
      this.totalLogs.value = responsedLogs.total
    } catch (error) {
      console.log(error)
    } finally {
      this.setLoading(false)
    }
  }
  async fetchLog404(query) {
    this.setLoading(true)
    try {
      const redirects = new Resource()
      // fetch Logs
      const responseLog404 = await redirects.log404(query)
      if (!responseLog404) {
        console.log(`HTTP error! Status: ${responseLog404.status}`)
      }
      const responsedLog404 = responseLog404.data
      this.log404.value = responsedLog404.data
      this.totalLog404.value = responsedLog404.total
    } catch (error) {
      console.log(error)
    } finally {
      this.setLoading(false)
    }
  }
  async importData(file) {
    this.setLoading(true)
    try {
      const resourceData = new Resource()
      const importedData = await resourceData.import(file)
      if (!importedData) {
        console.log(`HTTP error! Status: ${importedData.status}`)
      }
      const responsedImport = await importedData.data
      await this.postRedirects(responsedImport)
    } catch (error) {
      console.log(error)
    } finally {
      this.setLoading(false)
    }
  }
}

export default SwiftRedirectPlugin

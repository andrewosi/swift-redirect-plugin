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
    this.addNewModal.value = false
  }
  resetEditedItem() {
    this.editedItem.value = {}
    this.invalidOldRedirect.value = {
      domain: false,
      key: false,
      target_url: false,
    }
    this.editModal.value = false
  }

  // add new domain name if multiple
  addNewDomain(item) {
    this.hostsList.value.push(item)
  }
  // Validate section
  validateError = (obj, type) => {
    Object.keys(obj).map((validationKey) => {
      type[validationKey] === '' ? (obj[validationKey] = true) : (obj[validationKey] = false)
    })
    if (Object.values(obj).some((elem) => elem === true)) {
      console.log('the validation is invalid')
      return false
    } else {
      return true
    }
  }
  // Sanitise input fields
  trimValue(val, key) {
    let sanitisedVal
    if (val[key]) {
      sanitisedVal = val[key].toString().toLowerCase().trim().replace(/_/g, '-').replace(/\s+/g, '-')
      switch (key) {
        case 'domain':
          if (URL.canParse(sanitisedVal)) {
            sanitisedVal = new URL(sanitisedVal).hostname
          }
          val[key] = sanitisedVal
          break
        case 'key':
          if (URL.canParse(sanitisedVal)) {
            sanitisedVal = new URL(sanitisedVal).pathname
          }
          sanitisedVal = '/' + sanitisedVal
          sanitisedVal = sanitisedVal.replace(/^\/+|\/+$|(\/)+/g, '/')
          val[key] = sanitisedVal
          break
      }
    }
  }

  // Post func
  async postRedirects() {
    if (this.validateError(this.invalidNewRedirect.value, this.newRedirect.value) !== true) {
      return false
    }
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
      this.validateError(this.invalidOldRedirect.value, this.editedItem.value) !== true
    ) {
      return false
    } else {
      this.resetEditedItem()
    }
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
    }
  }
  // Destroy func
  async removeRedirect(id) {
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
    }
  }

  async removeAllRedirects() {
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
    }
    allRedirects.value.data.data.map((elem) => {
      allIds.value.push(elem.id)
    })
    await this.removeRedirect(allIds.value)
  }

  // Handle Bulk actions
  async handleBulkActions() {
    const idsToDelete = ref([])
    const selectedCase = this.selectedAction.value.action
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
    this.selectedRedirects.value = []
  }

  async fetchRedirects(query) {
    try {
      const redirects = new Resource()
      // fetch Redirects
      const responseRedirects = await redirects.list(query)
      if (!responseRedirects) {
        this.toast.error(`HTTP error! Status: ${responseRedirects.status}`)
      }
      const responsed = responseRedirects.data

      this.redirectsData.value = responsed.data
      this.countOfRedirects.value = responsed.count_of_redirects
      this.totalRedirects.value = responsed.total
      this.hostsList.value = responsed.hosts_list
    } catch (error) {
      this.toast.error('Error fetching data: ', error.message)
    }
  }
  async fetchLogs(query) {
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
    }
  }
  async fetchLog404(query) {
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
    }
  }
  async importData(file) {
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
    }
  }
}

export default SwiftRedirectPlugin

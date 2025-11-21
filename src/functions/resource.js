import axios from 'axios'

const headers = { 'Content-Type': 'application/json', 'X-WP-Nonce': admin_app_vars.nonce }

class Resource {
  url = '/wp-admin/admin-ajax.php?action=swift-redirect_admin'

  async list(query) {
    const params = {
      offset: query.page,
      limit: query.limit,
    }
    if (query.search && query.search.trim()) {
      params.search = query.search.trim()
    }
    const response = await axios.get(this.url, {
      params: params,
      headers: headers,
    })
    return response.data
  }

  async logs(query) {
    const params = {
      offset: query.page,
      limit: query.limit,
    }
    if (query.search && query.search.trim()) {
      params.search = query.search.trim()
    }
    const response = await axios.get(`/wp-admin/admin-ajax.php?action=swift-redirect_log`, {
      params: params,
      headers: headers,
    })
    return response.data
  }

  async log404(query) {
    const params = {
      offset: query.page,
      limit: query.limit,
    }
    if (query.search && query.search.trim()) {
      params.search = query.search.trim()
    }
    const response = await axios.get(`/wp-admin/admin-ajax.php?action=swift-redirect_404`, {
      params: params,
      headers: headers,
    })
    return response.data
  }

  async store(resource) {
    const response = await axios.post(this.url, { new_redirects: resource }, { headers: headers })
    return response.data
  }

  async update(resource) {
    const response = await axios.put(this.url, { update_redirects: resource }, { headers: headers })
    return response.data
  }

  async destroy(id) {
    const response = await axios.delete(this.url, { headers: headers, data: { ids_to_remove: id } })
    return response.data
  }

  async export() {
    const today = new Date()
    const yyyy = today.getFullYear()
    let mm = today.getMonth() + 1 // Months start at 0!
    let dd = today.getDate()

    if (dd < 10) dd = '0' + dd
    if (mm < 10) mm = '0' + mm

    const formattedToday = dd + '-' + mm + '-' + yyyy
    axios({
      url: '/wp-admin/admin-ajax.php?action=swift-redirect_export',
      method: 'GET',
      responseType: 'blob',
      headers: headers,
    }).then((response) => {
      var fileURL = window.URL.createObjectURL(new Blob([response.data]))
      var fileLink = document.createElement('a')
      fileLink.href = fileURL
      fileLink.setAttribute('download', 'swift-redirect-' + formattedToday + '.json')
      document.body.appendChild(fileLink)
      fileLink.click()
      return response
    })
  }

  async set_del_row_value(resource) {
    const response = await axios.put(
      '/wp-admin/admin-ajax.php?action=set_swift-redirect_del_tables',
      { new_value: resource },
      { headers: headers },
    )
    return response.data
  }

  async get_del_row_value() {
    const response = await axios.get(`/wp-admin/admin-ajax.php?action=get_swift-redirect_del_tables`, {
      headers: headers,
    })
    return response.data
  }
}

export { Resource as default }

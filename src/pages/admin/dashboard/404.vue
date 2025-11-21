<script setup>
  import axios from 'axios'
  import { onMounted, ref, watch } from 'vue'
  import { useI18n } from 'vue-i18n'
  import { useToast } from 'vue-toastification'
  import SwiftRedirectPlugin from '../../../functions/SwiftRedirectVue'
  import ScrollToTop from '../../../components/other/ScrollToTop.vue'

  const { t } = useI18n()
  const toast = useToast()
  const classInstance = new SwiftRedirectPlugin()

  // variables declaration
  const log404Data = classInstance.log404
  const totalLog404 = classInstance.totalLog404
  const log404Columns = classInstance.log404Columns
  const newRedirect = classInstance.newRedirect
  const query = classInstance.query
  const queryLimitOptions = classInstance.queryLimitOptions

  const codeOptions = classInstance.codeOptions

  // Filter variables
  const filtered = ref([])
  const filter = ref('')
  const searchQuery = ref('')
  
  // Search function
  const performSearch = async () => {
    if (searchQuery.value.trim()) {
      query.value.search = searchQuery.value.trim()
      query.value.page = 0
      currentPage.value = 1
    } else {
      delete query.value.search
      query.value.page = 0
      currentPage.value = 1
    }
    await classInstance.fetchLog404(query.value)
    pages.value = Math.ceil(totalLog404.value / query.value.limit)
  }
  
  const handleSearchKeypress = (event) => {
    if (event.key === 'Enter') {
      performSearch()
    }
  }

  const resetSearch = async () => {
    searchQuery.value = ''
    delete query.value.search
    query.value.page = 0
    currentPage.value = 1
    await classInstance.fetchLog404(query.value)
    pages.value = Math.ceil(totalLog404.value / query.value.limit)
  }

  // Pagination
  const pages = ref(null)
  const currentPage = ref(1)

  watch(
    () => query.value.limit,
    (oldVal, newVal) => {
      if (newVal !== oldVal) {
        query.value.page = 0
        currentPage.value = 1
        classInstance.fetchLog404(query.value)
      }
    },
  )
  const handlePageChange = (event) => {
    currentPage.value = event
    query.value.page = (event - 1) * query.value.limit
    classInstance.fetchLog404(query.value)
  }
  const openModal = ref(false)

  const resetNewRedirect = () => {
    // newRedirect.value = null
    classInstance.resetNewItem()
    openModal.value = false
  }
  const oldLog = ref({})

  const addRedirect = (item) => {
    openModal.value = true
    oldLog.value = item
    newRedirect.value.domain = item.host
    newRedirect.value.key = item.request_link
  }

  const headers = { 'Content-Type': 'application/json', 'X-WP-Nonce': admin_app_vars.nonce }
  const changeStatus404Log = async (new_redirect) => {
    new_redirect.is_redirect = 1
    try {
      const response = await axios.put(
        '/wp-admin/admin-ajax.php?action=swift-redirect_404',
        { add_to_redirects: new_redirect },
        { headers: headers },
      )

      return response.data
    } catch (error) {
      toast.error(error.message, {
        position: 'bottom-right',
      })
    }
  }

  const postRedirectAndDeleteLog = async () => {
    const postResult = await classInstance.postRedirects()
    if (postResult !== false) {
      try {
        await changeStatus404Log(oldLog.value)
        resetNewRedirect()
      } catch (error) {
        toast.error(error.message, {
          position: 'bottom-right',
        })
      }
    }
  }

  onMounted(async () => {
    await classInstance.fetchLog404(query.value)
    filtered.value = log404Data.value
    pages.value = Math.ceil(totalLog404.value / query.value.limit)
  })
</script>

<template>
  <div class="logs has-loading-overlay">
    <va-card>
      <va-card-title>
        <h1>{{ t('tables.headings.log404') }}</h1>
      </va-card-title>
      <VaCardContent class="overflow-auto">
        <div class="flex items-center gap-2">
          <VaInput 
            v-model="searchQuery" 
            :placeholder="t('tables.filter')" 
            @keyup.enter="performSearch"
          />
          <VaButton @click="performSearch">{{ t('tables.headings.search') }}</VaButton>
          <VaButton @click="resetSearch" preset="secondary" outline>{{ t('tables.headings.reset') }}</VaButton>
        </div>
        <VaDataTable
          :items="log404Data"
          :columns="log404Columns"
          :per-page="classInstance.query.limit"
          :current-page="classInstance.query.page"
          striped
          :filter="filter"
          @filtered="filtered = $event.items"
        >
          <template #cell(count_of_requests)="rowIndex">
            <va-badge :text="rowIndex.rowKey.count_of_requests" color="danger" />
          </template>
          <template #cell(is_redirect)="rowIndex">
            <vaChip size="small" :color="rowIndex.rowKey.is_redirect == 1 ? 'success' : 'danger'">{{
              rowIndex.rowKey.is_redirect == 1 ? t('tables.is-redirect') : t('tables.is-not-redirect')
            }}</vaChip>
          </template>
          <template #cell(actions)="rowIndex">
            <vaButton
              v-if="rowIndex.rowKey.is_redirect == 0"
              size="medium"
              style="height: fit-content"
              class="new-btn mb-2 flex justify-self-center btn-font"
              @click="addRedirect(rowIndex.rowKey)"
              >{{ t('tables.add-redirect') }}</vaButton
            >
          </template>
        </VaDataTable>
        <div class="flex column gap-2 justify-center align-end mt-4" style="flex-direction: column">
          <VaPagination
            v-if="classInstance.totalLog404.value > classInstance.query.value.limit"
            v-model="currentPage"
            :pages="pages"
            :visible-pages="3"
            class="justify-end"
            style="width: fit-content"
            @update:modelValue="handlePageChange"
          />
          <VaSelect
            v-model="query.limit"
            :label="t('tables.headings.query-limit')"
            style="max-width: 200px"
            :options="queryLimitOptions"
          >
          </VaSelect>
        </div>
      </VaCardContent>
    </va-card>
    <VaModal
      class="modal-crud"
      :model-value="!!openModal"
      :title="t('tables.headings.add-new')"
      size="small"
      :cancel-text="t('modals.cancel')"
      :ok-text="t('modals.add')"
      @ok="postRedirectAndDeleteLog()"
      @cancel="resetNewRedirect()"
    >
      <VaInput
        v-model="newRedirect.domain"
        class="my-2"
        :label="t('tables.headings.domain')"
        disabled
        :error="classInstance.invalidNewRedirect.value.domain"
        :error-messages="t(classInstance.getValidationMessage('new', 'domain'))"
      />
      <VaInput
        v-model="newRedirect.key"
        class="my-2"
        :label="t('tables.headings.key')"
        :placeholder="t('placeholders.path')"
        disabled
        :error="classInstance.invalidNewRedirect.value.key"
        :error-messages="t(classInstance.getValidationMessage('new', 'key'))"
        @focusout="classInstance.trimValue(newRedirect.key, 'key')"
      />
      <div class="grid grid-cols-2 gap-1 align-center justify-start">
        <VaSelect
          v-model="newRedirect.code"
          class="mt-2"
          :label="t('tables.headings.code')"
          :options="codeOptions"
          style="max-width: 140px"
        >
          <template #content>
            <VaChip class="mr-2" size="small">
              {{ newRedirect.code }}
            </VaChip>
          </template>
        </VaSelect>
        <VaSwitch v-model="newRedirect.is_regex" :label="t('tables.headings.is_regex')" size="small" class="my-2" />
        <VaSwitch v-model="newRedirect.is_params" :label="t('tables.headings.is_params')" size="small" class="my-2" />
        <VaSwitch
          v-model="newRedirect.is_enabled"
          :label="newRedirect.is_enabled === true ? t('tables.headings.is_enabled') : t('tables.headings.disabled')"
          size="small"
          class="my-2"
        />
      </div>
      <VaInput
        v-model="newRedirect.target_url"
        class="my-2"
        :inner-label="false"
        :label="t('tables.headings.target_url')"
        :placeholder="t('placeholders.target_url')"
        :error="classInstance.invalidNewRedirect.value.target_url"
        :error-messages="t(classInstance.getValidationMessage('new', 'target_url'))"
        @focusout="classInstance.trimValue(newRedirect, 'target_url')"
      />
    </VaModal>
    <VaOverlay :model-value="classInstance.isLoading.value" class="loading-overlay" :color="'rgba(0,0,0,0.45)'">
      <VaProgressCircular indeterminate size="large" />
    </VaOverlay>
  </div>
  <ScrollToTop />
</template>

<style>
  .btn-font span {
    font-size: 12px !important;
  }
</style>

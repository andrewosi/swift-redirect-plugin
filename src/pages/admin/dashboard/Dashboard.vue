<script setup>
  import { ref, onMounted, watch } from 'vue'
  import { useI18n } from 'vue-i18n'
  import { useToast } from 'vue-toastification'
  import SwiftRedirectPlugin from '../../../functions/SwiftRedirectVue'
  import ScrollToTop from '../../../components/other/ScrollToTop.vue'
  import IconAdd from 'vue-ionicons/dist/ios-add.vue'
  import IconEdit from 'vue-ionicons/dist/ios-create.vue'
  import IconDelete from 'vue-ionicons/dist/ios-trash.vue'

  const { t } = useI18n()
  const toast = useToast()

  const classInstance = new SwiftRedirectPlugin()

  // redirects variables
  const redirectsData = classInstance.redirectsData // old redirects
  const redirectsColumns = classInstance.redirectsColumns // columns
  const totalRedirects = classInstance.totalRedirects
  const countOfRedirects = classInstance.countOfRedirects
  const codeOptions = classInstance.codeOptions
  const hostsList = classInstance.hostsList

  const changedOldRedirects = classInstance.changedOldRedirects // changed redirects (traced with a watcher)

  // query variables
  const query = classInstance.query
  const queryLimitOptions = classInstance.queryLimitOptions

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
    await classInstance.fetchRedirects(query.value)
    pages.value = Math.ceil(totalRedirects.value / query.value.limit)
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
    await classInstance.fetchRedirects(query.value)
    pages.value = Math.ceil(totalRedirects.value / query.value.limit)
  }

  // bulk actions and selection
  const selectedAction = classInstance.selectedAction
  const bulkActions = classInstance.bulkActions
  const selectedRedirects = classInstance.selectedRedirects
  const itemsSelection = (items) => {
    selectedRedirects.value = items
  }

  // Pagination variables and functions
  const pages = ref(null)
  const currentPage = ref(1)
  watch(
    () => query.value.limit,
    (oldVal, newVal) => {
      if (newVal !== oldVal) {
        query.value.page = 0
        currentPage.value = 1
        pages.value = Math.ceil(totalRedirects.value / query.value.limit)
        classInstance.fetchRedirects(query.value)
      }
    },
  )
  const handlePageChange = (event) => {
    currentPage.value = event
    query.value.page = (event - 1) * query.value.limit
    classInstance.fetchRedirects(query.value)
  }

  // New Redirect variables
  const addNewModal = classInstance.addNewModal
  const newRedirect = classInstance.newRedirect

  // Editing variables and funcs
  const editModal = classInstance.editModal
  const openEditModal = classInstance.openEditModal
  const editedItem = classInstance.editedItem

  const postRedirects = async () => {
    try {
      if(await classInstance.postRedirects()) {
        toast.success(t('success-post'), {
          position: 'bottom-right',
        })
      }
    } catch (error) {
      toast.error(error.message, {
        position: 'bottom-right',
      })
    }
  }

  const editItem = async () => {
    changedOldRedirects.value.push(editedItem.value)
    try {
      await classInstance.updateRedirects()
      toast.success(t('success-update'), {
        position: 'bottom-right',
      })
    } catch {
      changedOldRedirects.value = []
      toast.error(t('error-update'), {
        position: 'bottom-right',
      })
    }
  }

  const removeRedirect = async (id) => {
    try {
      await classInstance.removeRedirect(id)
    } catch {
      toast.error(t('error-delete'), {
        position: 'bottom-right',
      })
    }
  }

  // Other visual funcs
  const tableTextHandler = (slot) => {
    return slot == 1 ? t('tables.on') : t('tables.off')
  }
  function getStatusColor(status) {
    if (status == 1) {
      return 'success'
    } else if (status == 0) {
      return 'danger'
    } else {
      return 'info'
    }
  }

  //Load and display the Redirects
  onMounted(async () => {
    // fetch
    await classInstance.fetchRedirects(query.value)
    filtered.value = redirectsData.value
    pages.value = Math.ceil(totalRedirects.value / query.value.limit)
  })
</script>

<template>
  <div class="dashboard has-loading-overlay">
    <VaCard>
      <VaCardTitle>{{ t('tables.headings.total-redirects-done') }}: {{ countOfRedirects }}</VaCardTitle>
      <VaCardContent class="overflow-auto">
        <div class="flex gap-2 align-center mb-3">
          <VaInput 
            v-model="searchQuery" 
            class="flex-grow-1" 
            :placeholder="t('tables.filter')"
            @keypress="handleSearchKeypress"
          />
          <VaButton @click="performSearch" color="primary">
            {{ t('tables.headings.search') }}
          </VaButton>
          <VaButton @click="resetSearch" preset="secondary" outline>
            {{ t('tables.headings.reset') }}
          </VaButton>
        </div>
        <div v-if="selectedRedirects.length > 10" class="card__bg flex wrap align-start gap-2">
          <div style="max-width: 200px">
            <VaSelect
              v-model="selectedAction"
              :text-by="(option) => (option?.action ? t(`bulk_actions.${option.action}`) : t('bulk_actions.placeholder'))"
              :value-by="(option) => ({ color: option.color, action: option.action })"
              :label="t('tables.headings.bulk-actions')"
              :placeholder="t('bulk_actions.placeholder')"
              :options="bulkActions"
            />
          </div>
          <VaButton size="medium" class="mb-2" style="max-width: 200px; margin-top: 18px;" @click="classInstance.handleBulkActions">{{
            t('tables.headings.apply')
          }}</VaButton>
        </div>
        <VaDataTable
          v-model="selectedRedirects"
          :items="redirectsData"
          :columns="redirectsColumns"
          :per-page="classInstance.query.limit"
          :current-page="classInstance.query.page"
          selectable
          striped
          class="table-crud"
          :selected-color="selectedAction.color"
          @selectionChange="itemsSelection($event.currentSelectedItems)"
        >
          <template #cell(domain)="rowIndex">
            <div style="max-width: 300px; overflow: hidden; overflow-x: auto; line-height: normal">
              {{ rowIndex.rowKey.domain }}
            </div>
          </template>
          <template #cell(path)="rowIndex">
            <div style="max-width: 300px; overflow: hidden; overflow-x: auto; line-height: normal">
              {{ rowIndex.rowKey.key }}
            </div>
          </template>
          <template #cell(is_regex)="rowIndex">
            <va-badge
              :text="rowIndex.rowKey.is_regex == 1 ? t('tables.on') : t('tables.off')"
              :color="getStatusColor(rowIndex.rowKey.is_regex)"
            />
          </template>
          <template #cell(code)="rowIndex">
            <VaChip square size="small">
              {{ rowIndex.rowKey.code }}
            </VaChip>
          </template>
          <template #cell(is_params)="rowIndex">
            <va-badge
              :text="rowIndex.rowKey.is_params == 1 ? t('tables.on') : t('tables.off')"
              :color="getStatusColor(rowIndex.rowKey.is_params)"
            />
          </template>
          <template #cell(target_url)="rowIndex">
            <div style="max-width: 300px; overflow: hidden; overflow-x: auto; line-height: normal">
              {{ rowIndex.rowKey.target_url }}
            </div>
          </template>
          <template #cell(count)="rowIndex">
            <va-badge :text="rowIndex.rowKey.count" color="info" />
          </template>
          <template #cell(is_enabled)="rowIndex">
            <va-badge
              :text="tableTextHandler(rowIndex.rowKey.is_enabled)"
              :color="getStatusColor(rowIndex.rowKey.is_enabled)"
            />
          </template>
          <template #cell(actions)="rowIndex">
            <VaButton preset="plain" icon="edit" @click="openEditModal(rowIndex.rowKey)">  </VaButton>
            <VaButton preset="plain" icon="delete" color="danger" class="ml-3" @click="removeRedirect(rowIndex.rowKey.id)">  </VaButton>
          </template>
        </VaDataTable>

        <div v-if="selectedRedirects.length > 0" class="card__bg flex wrap align-start gap-2">
          <div style="max-width: 200px">
            <VaSelect
              v-model="selectedAction"
              :text-by="(option) => (option?.action ? t(`bulk_actions.${option.action}`) : t('bulk_actions.placeholder'))"
              :value-by="(option) => ({ color: option.color, action: option.action })"
              :label="t('tables.headings.bulk-actions')"
              :placeholder="t('bulk_actions.placeholder')"
              :options="bulkActions"
            />
          </div>
          <VaButton size="medium" class="mb-2" style="max-width: 200px; margin-top: 18px;" @click="classInstance.handleBulkActions">{{
            t('tables.headings.apply')
          }}</VaButton>
        </div>

        <div class="card__bg flex justify-between wrap mt-4" style="margin-left: 0; margin-right: 0">
          <vaButton
            size="medium"
            style="height: fit-content"
            class="new-btn mb-2 flex justify-self-center"
            @click="addNewModal = true"
            ><IconAdd style="fill: #fff; margin-right: 5px;"/>{{ t('tables.add-redirect') }}</vaButton
          >
          <div class="flex gap-2 justify-center align-end" style="flex-direction: column">
            <VaPagination
              v-if="classInstance.totalRedirects.value > classInstance.query.value.limit"
              v-model="currentPage"
              :pages="pages"
              :visible-pages="3"
              class="justify-end"
              style="width: fit-content"
              @update:modelValue="handlePageChange"
            />
            <VaSelect
              v-model="query.limit"
              :label="t('tables.query-limit')"
              style="max-width: 200px"
              :options="queryLimitOptions"
            >
            </VaSelect>
          </div>
        </div>

        <!--   MODAL for new Redirects     -->

        <VaModal
          class="modal-crud"
          :model-value="!!addNewModal"
          :title="t('tables.headings.add-new')"
          size="small"
          close-button
          :cancel-text="t('modals.cancel')"
          :ok-text="t('modals.add')"
          @ok="postRedirects()"
          @cancel="classInstance.resetNewItem()"
        >
          <VaInput
            v-if="hostsList.length === 1"
            v-model="newRedirect.domain"
            class="my-2"
            :label="t('tables.headings.domain')"
            :placeholder="t('placeholders.domain')"
            :error="classInstance.invalidNewRedirect.value.domain"
            :error-messages="t(classInstance.getValidationMessage('new', 'domain'))"
            @focusout="classInstance.trimValue(newRedirect, 'domain')"
          />
          <VaSelect
            v-if="hostsList.length > 1"
            v-model="newRedirect.domain"
            :label="t('tables.select-domain')"
            :options="hostsList"
            :placeholder="t('tables.select-domain')"
            :track-by="(option) => option"
            :error="classInstance.invalidNewRedirect.value.domain"
            :error-messages="t(classInstance.getValidationMessage('new', 'domain'))"
            allow-create="unique"
            @focusout="classInstance.trimValue(newRedirect, 'domain')"
            @create-new="classInstance.addNewDomain"
          />
          <VaInput
            v-model="newRedirect.key"
            class="my-2"
            :label="t('tables.headings.key')"
            :placeholder="t('placeholders.path')"
            :error="classInstance.invalidNewRedirect.value.key"
            :error-messages="t(classInstance.getValidationMessage('new', 'key'))"
            @focusout="classInstance.trimValue(newRedirect, 'key')"
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
            <VaSwitch
              v-model="newRedirect.is_params"
              :label="t('tables.headings.is_params')"
              size="small"
              class="my-2"
            />
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

        <!--   MODAL for Edit    -->
        <VaModal
          class="modal-crud"
          :model-value="!!editModal"
          :title="t('modals.edit-item')"
          size="small"
          close-button
          :cancel-text="t('modals.cancel')"
          :ok-text="t('modals.edit-item')"
          @ok="editItem()"
          @cancel="classInstance.resetEditedItem()"
        >
          <VaInput
            v-if="hostsList.length === 1"
            v-model="editedItem.domain"
            class="my-2"
            :label="t('tables.headings.domain')"
            :placeholder="t('tables.headings.domain')"
            :error="classInstance.invalidOldRedirect.value.domain"
            :error-messages="t(classInstance.getValidationMessage('old', 'domain'))"
            @focusout="classInstance.trimValue(editedItem, 'domain')"
          />
          <VaSelect
            v-if="hostsList.length > 1"
            v-model="editedItem.domain"
            :label="t('tables.select-domain')"
            :options="hostsList"
            :track-by="(option) => option"
            allow-create="unique"
            :placeholder="t('tables.select-domain')"
            :error="classInstance.invalidOldRedirect.value.domain"
            :error-messages="t(classInstance.getValidationMessage('old', 'domain'))"
            @create-new="classInstance.addNewDomain"
          />
          <VaInput
            v-model="editedItem.key"
            class="my-2"
            :label="t('tables.headings.key')"
            :placeholder="t('placeholders.path')"
            :error="classInstance.invalidOldRedirect.value.key"
            :error-messages="t(classInstance.getValidationMessage('old', 'key'))"
            @focusout="classInstance.trimValue(editedItem, 'key')"
          />
          <div class="grid grid-cols-2 gap-1 align-center justify-start">
            <VaSelect
              v-model="editedItem.code"
              class="my-2"
              :label="t('tables.headings.code')"
              :options="codeOptions"
              style="max-width: 140px"
            >
              <template #content>
                <VaChip class="mr-2" size="small">
                  {{ editedItem.code }}
                </VaChip>
              </template>
            </VaSelect>
            <VaSwitch v-model="editedItem.is_regex" :label="t('tables.headings.is_regex')" size="small" class="my-2" />
            <VaSwitch
              v-model="editedItem.is_params"
              :label="t('tables.headings.is_params')"
              size="small"
              class="my-2"
            />
            <VaSwitch
              v-model="editedItem.is_enabled"
              :label="editedItem.is_enabled === true ? t('tables.headings.is_enabled') : t('tables.headings.disabled')"
              size="small"
              class="my-2"
            />
          </div>
          <VaInput
            v-model="editedItem.target_url"
            class="my-2"
            :inner-label="false"
            :label="t('tables.headings.target_url')"
            :placeholder="t('placeholders.target_url')"
            :error="classInstance.invalidOldRedirect.value.target_url"
            :error-messages="t(classInstance.getValidationMessage('old', 'target_url'))"
            @focusout="classInstance.trimValue(editedItem, 'target_url')"
          />
        </VaModal>
      </VaCardContent>
    </VaCard>
    <ScrollToTop />
    <VaOverlay :model-value="classInstance.isLoading.value" class="loading-overlay" :color="'rgba(0,0,0,0.45)'">
      <VaProgressCircular indeterminate size="large" />
    </VaOverlay>
  </div>
</template>

<style lang="scss">
  .dashboard {
    .va-card {
      margin-bottom: 0 !important;
      &__title {
        display: flex;
        justify-content: space-between;
      }
    }
  }
  div.ion svg {
    height: 20px;
    width: 20px;
  }
</style>

<script setup>
  import { onMounted, ref, watch } from 'vue'
  import { useI18n } from 'vue-i18n'
  import SwiftRedirectPlugin from '../../../functions/SwiftRedirectVue'
  import ScrollToTop from '../../../components/other/ScrollToTop.vue'

  const { t } = useI18n()

  const classInstance = new SwiftRedirectPlugin()

  const logsData = classInstance.logs
  const totalLogs = classInstance.totalLogs
  const logsColumns = classInstance.logsColumns

  const query = classInstance.query
  const queryLimitOptions = classInstance.queryLimitOptions

  // Filter variables
  const filtered = ref([])
  const filter = ref('')

  // Pagination
  const pages = ref(null)
  const currentPage = ref(1)
  watch(
    () => query.value.limit,
    (oldVal, newVal) => {
      if (newVal !== oldVal) {
        query.value.page = 0
        currentPage.value = 1
        classInstance.fetchLogs(query.value)
      }
    },
  )
  const handlePageChange = async (page) => {
    currentPage.value = page
    query.value.page = (page - 1) * query.value.limit
    classInstance.fetchLogs(query.value)
  }

  onMounted(async () => {
    await classInstance.fetchLogs(query.value)
    filtered.value = logsData.value
    pages.value = Math.ceil(totalLogs.value / query.value.limit)
  })
</script>

<template>
  <div class="logs has-loading-overlay">
    <va-card>
      <va-card-title>
        <h1>{{ t('tables.headings.logs') }}</h1>
      </va-card-title>
      <VaCardContent class="overflow-auto">
        <VaInput v-model="filter" :placeholder="t('tables.filter')" />
        <VaDataTable
          :items="logsData"
          :columns="logsColumns"
          :per-page="classInstance.query.limit"
          :current-page="classInstance.query.page"
          striped
          :filter="filter"
          @filtered="filtered = $event.items"
        >
        </VaDataTable>
        <div class="flex column gap-2 justify-center align-end mt-4" style="flex-direction: column">
          <VaPagination
            v-if="classInstance.totalLogs.value > classInstance.query.value.limit"
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
    <VaOverlay :model-value="classInstance.isLoading.value" class="loading-overlay" :color="'rgba(0,0,0,0.45)'">
      <VaProgressCircular indeterminate size="large" />
    </VaOverlay>
  </div>
  <ScrollToTop />
</template>

<style scoped>

</style>

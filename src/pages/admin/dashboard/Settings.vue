<script setup>
  import { ref, onMounted, watch } from 'vue'
  import { useI18n } from 'vue-i18n'
  import { useToast } from 'vue-toastification'
  import SwiftRedirectPlugin from '../../../functions/SwiftRedirectVue'
  import ScrollToTop from '../../../components/other/ScrollToTop.vue'
  import Resource from '../../../functions/resource'

  const { t } = useI18n()
  const toast = useToast()
  const classInstance = new SwiftRedirectPlugin()
  const destroyAllModal = ref(false)
  const file = ref('')
  const importDataJson = ref('')
  const delTables = ref('')
  const resource = new Resource()
  // Image location
  const bmc_qr = ref('/wp-content/plugins/swift-redirect/public/images/bmc_qr.png')

  const destroyAllRedirects = async () => {
    try {
      await classInstance.removeAllRedirects()
      toast.error(t('success-destroy-all'), {
        position: 'bottom-right',
      })
    } catch (error) {
      toast.error(error.message, {
        position: 'bottom-right',
      })
    }

    destroyAllModal.value = false
  }

  // Select an Import file
  const uploadFile = () => {
    if (file.value && file.value instanceof Blob) {
      const readFile = file.value
      const reader = new FileReader()

      reader.onload = () => {
        importDataJson.value = JSON.parse(reader.result)
      }

      reader.readAsText(readFile)
    }
  }
  // Upload an Import file
  const importData = async (new_redirects) => {
    try {
      await resource.store(new_redirects)
      file.value = []
      importDataJson.value = ''
      toast.success(t('success-import'), {
        position: 'bottom-right',
      })
    } catch (error) {
      toast.error(error.message, {
        position: 'bottom-right',
      })
    }
  }
  const exportData = async () => {
    await resource.export()
  }
  const setDelTables = async () => {
    await resource.set_del_row_value(Number(delTables.value))
  }

  watch(
    () => file.value,
    (oldVal, newVal) => {
      if (newVal !== oldVal) {
        uploadFile()
      }
    },
  )

  onMounted(async () => {
    const valueInDb = await resource.get_del_row_value()
    if (valueInDb.del_tables === '0' || valueInDb.del_tables === '' || valueInDb.del_tables === null) {
      delTables.value = false
    } else {
      delTables.value = true
    }
  })
</script>

<template>
  <VaCard>
    <VaCardTitle>{{ t('menu.settings') }}</VaCardTitle>
    <VaCardContent class="flex">
      <div class="flex flex-direction-column justify-between">
        <VaFileUpload
          v-model="file"
          dropzone
          type="single"
          file-types="json"
          :upload-button-text="t('tables.headings.upload-file')"
        />
        <VaButton
          v-if="file && file.size > 0"
          color="success"
          style="color: #fff"
          icon-color="#fff"
          icon="fa-solid fa-file-import"
          @click="importData(importDataJson)"
        >
          {{ t('tables.headings.import') }}
        </VaButton>
        <div class="export-data">
          <div class="export-data-text">{{ t('tables.headings.export-text') }}</div>
          <VaButton class="export_btn" icon="fa-solid fa-download" @click="exportData">{{
            t('tables.headings.export')
          }}</VaButton>
        </div>

        <VaButton class="destroy-all" color="danger" icon="delete" @click="destroyAllModal = true">
          {{ t('tables.headings.remove-all-redirects') }}
        </VaButton>
        <VaSwitch v-model="delTables" :label="t('del_tables')" size="small" class="my-2" @click="setDelTables" />
      </div>
      <div class="flex flex-direction-column justify-center align-center">
        <div class="support-us-text">
          {{ t('support-us') }}
        </div>
        <img :src="bmc_qr" alt="buy me a coffee qr" class="bmc_qr" />
      </div>
    </VaCardContent>
  </VaCard>

  <VaModal
    class="modal-crud"
    :model-value="!!destroyAllModal"
    :title="t('modals.sure')"
    :ok-text="t('modals.destroy')"
    size="small"
    close-button
    @ok="destroyAllRedirects()"
    @cancel="destroyAllModal = false"
  >
    <h3 class="va-h3">
      {{ t('modals.destroy-all-prompt') }}
    </h3>
  </VaModal>
  <ScrollToTop />
</template>
<style>
  .support-us-text {
    font-size: 16px;
    font-weight: 700;
  }
  .bmc_qr {
    max-width: 300px;
    height: auto;
    padding: 10px;
    border-radius: 15px;
  }
  .destroy-all {
    width: 100%;
    margin: var(--va-file-upload-margin);
  }
  .export-data {
    position: var(--va-file-upload-position);
    font-family: var(--va-font-family);
    margin: var(--va-file-upload-margin);
    border-radius: var(--va-file-upload-dropzone-border-radius);
    background-color: rgba(21, 79, 193, 0.08);
    justify-content: space-between;
    display: flex;
    align-items: center;
    padding: var(--va-file-upload-dropzone-field-padding);
    transition: height 0.2s;
    overflow: visible;
    flex-wrap: wrap;
  }
  .export-data-text {
    padding-right: var(--va-file-upload-dropzone-field-text-pr);
  }
  .va-file-upload--dropzone .va-file-upload__field {
    justify-content: space-between;
  }
</style>

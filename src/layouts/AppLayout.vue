<template>
  <div class="app-layout">
    <navbar />
    <div class="app-layout__content">
      <div class="app-layout__page">
        <div class="app-layout__page-content">
          <router-view />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
  import Navbar from '../components/navbar/Navbar.vue'
</script>

<style lang="scss">
  $mobileBreakPointPX: 640px;
  $tabletBreakPointPX: 768px;

  .app-layout {
    height: fit-content;
    min-height: fit-content;
    display: flex;
    flex-direction: column;
    &__navbar {
      min-height: 4rem;
    }

    &__content {
      display: flex;
      height: fit-content;
      min-height: fit-content;
      flex: 1;
    }
    &__page {
      flex-grow: 2;
      overflow-y: auto;
      overflow-x: hidden;
      padding: 0;
      margin: 0;
      height: fit-content;
      min-height: fit-content;
    }
    &__page-content {
      padding: 0.5rem;
      @media screen and (min-width: 768px) {
        padding: 1.5rem 2rem;
      }
    }
    .va-data-table__table-td {
      max-width: 300px !important;
      overflow: auto;
    }
  }
  #wpfooter {
    display: none;
  }
</style>

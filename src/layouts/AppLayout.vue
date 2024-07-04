<template>
  <div class="app-layout">
    <navbar />
    <div class="app-layout__content">
      <div class="app-layout__page">
        <div class="p-2 md:px-6 md:py-9">
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
    height: 100vh;
    display: flex;
    flex-direction: column;
    &__navbar {
      min-height: 4rem;
    }

    &__content {
      display: flex;
      height: calc(100vh - 4rem);
      flex: 1;

      @media screen and (max-width: $tabletBreakPointPX) {
        height: calc(100vh - 6.5rem);
      }
    }
    &__page {
      flex-grow: 2;
      overflow-y: scroll;
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

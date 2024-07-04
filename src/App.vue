<template>
  <router-view />
</template>

<style lang="scss">
  @import 'scss/main.scss';

  #app {
    font-family: 'Source Sans Pro', Avenir, Helvetica, Arial, sans-serif;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    color: #2c3e50;
  }
  //:root {
  //  --va-data-table-striped-tr-background-color: #f6f7f7!important;
  //}
  body {
    margin: 0;
  }
  .app-layout__page {
    overflow-y: unset;
  }
  .app-layout__page ::-webkit-scrollbar {
    height: 7px;
  }
  .app-layout__page ::-webkit-scrollbar-track {
    background: var(--va-background-element);
  }
  .app-layout__page ::-webkit-scrollbar-thumb {
    background: var(--va-primary);
  }
  .app-layout__page ::-webkit-scrollbar-thumb:hover {
    background: var(--va-focus);
  }
  #wpcontent {
    padding-left: 0;
  }
  body {
    font-size: 13px;
  }
  input:focus {
    border-color: unset !important;
    box-shadow: unset !important;
    outline: unset !important;
  }
  .new-btn {
    max-width: 300px;
  }
  .new-btn span {
    display: flex;
    justify-content: center;
  }
  .alternate,
  .striped > tbody > :nth-child(odd),
  ul.striped > :nth-child(odd) {
    background-color: unset !important;
  }
  .app-layout__content {
    display: unset;
  }
  .navigation-route {
    font-size: 15px;
    line-height: normal;
  }
  .navigation-route .va-icon {
    font-size: 15px !important;
  }
  .va-navbar.app-layout-navbar {
    box-shadow: unset;
    border-bottom: 1px solid rgb(38 40 36 / 12%);
  }
  .va-card {
    box-shadow: unset !important;
    border: 1px solid rgb(38 40 36 / 12%);
  }
  .va-card .va-button {
    border-radius: var(--va-button-border-radius);
  }
  .va-pagination > :first-child {
    border-top-right-radius: 0 !important;
    border-bottom-right-radius: 0 !important;
  }
  .router-link-active.router-link-exact-active.navigation-route {
    background-color: var(--va-primary) !important;
  }
</style>

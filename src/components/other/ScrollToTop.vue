<script setup>
  import { onMounted } from 'vue'

  onMounted(() => {
    const scrollToTopBtn = document.querySelector('.scroll-to-top-btn')

    window.addEventListener('scroll', () => {
      if (window.scrollY > 100) {
        scrollToTopBtn.classList.add('show')
      } else {
        scrollToTopBtn.classList.remove('show')
      }
    })

    scrollToTopBtn.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' })
    })
  })
</script>

<template>
  <VaButton color="info" gradient class="scroll-to-top-btn"> <i class="fa-solid fa-arrow-up"></i> </VaButton>
</template>

<style scoped>
  .scroll-to-top-btn {
    display: none;
    position: fixed;
    right: 50px;
    bottom: 50px;
  }
  .scroll-to-top-btn.show {
    display: block;
  }
</style>

<script setup>
  import { onMounted } from 'vue'
  import UpIcon from 'vue-ionicons/dist/ios-arrow-up.vue'

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
  <VaButton color="info" gradient class="scroll-to-top-btn"> <UpIcon /> </VaButton>
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
  .scroll-to-top-btn .ion {
    fill: #fff;
  }
</style>

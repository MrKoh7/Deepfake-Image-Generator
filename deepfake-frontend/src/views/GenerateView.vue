<script setup>
import { ref } from 'vue'
import axios from 'axios'

const selected = ref('')
const imageUrl = ref('')

async function generate() {
  imageUrl.value = ''
  const { data } = await axios.post('http://127.0.0.1:8000/api/generate', {
    model: selected.value
  })
  if (data?.status === 'success') {
    imageUrl.value = data.image_url
  } else {
    alert(data?.message || 'Generation failed')
  }
}
</script>

<template>
  <div>
    <select v-model="selected">
      <option disabled value="">-- Select --</option>
      <option value="asian_male">Asian Male</option>
      <!-- add other options later -->
    </select>
    <button :disabled="!selected" @click="generate">Generate</button>

    <div v-if="imageUrl" style="margin-top:1rem">
      <img :src="imageUrl" alt="Generated" style="max-width:100%;height:auto" />
    </div>
  </div>
</template>

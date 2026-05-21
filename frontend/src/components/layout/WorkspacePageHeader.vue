<template>
  <header class="ws-page-header">
    <div class="ws-page-header__text">
      <div class="ws-kicker">{{ module }}</div>
      <h1 class="ws-title">{{ title }}</h1>
      <p v-if="lead" class="ws-lead">{{ lead }}</p>
    </div>
    <div v-if="$slots.actions" class="ws-page-header__actions">
      <slot name="actions" />
    </div>
  </header>
</template>

<script setup>
defineProps({
  module: { type: String, required: true },
  title: { type: String, required: true },
  lead: { type: String, default: '' }
});
</script>

<template>
  <SystemActivityPanel @open-property="openProperty" />
</template>

<script setup>
import { useRouter } from 'vue-router';
import SystemActivityPanel from '../../components/workspace/SystemActivityPanel.vue';

const router = useRouter();

function openProperty(propertyId) {
  router.push({ name: 'workspace-records', query: { propertyId: String(propertyId) } });
}
</script>

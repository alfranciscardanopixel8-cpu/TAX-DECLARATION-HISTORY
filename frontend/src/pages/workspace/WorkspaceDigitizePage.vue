<template>
  <DigitizationQueuePanel @open-property="openProperty" />
</template>

<script setup>
import { useRouter } from 'vue-router';
import DigitizationQueuePanel from '../../components/workspace/DigitizationQueuePanel.vue';

const router = useRouter();

function openProperty(propertyId) {
  router.push({ name: 'workspace-records', query: { propertyId: String(propertyId) } });
}
</script>

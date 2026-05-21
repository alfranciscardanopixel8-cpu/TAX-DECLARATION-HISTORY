<template>
  <div v-if="authLoading" class="login-page boot-page">
    <q-spinner color="primary" size="50px" />
    <p class="login-page-subtitle">Loading records workspace…</p>
  </div>

  <div v-else class="login-page">
    <div class="login-shell">
      <section class="login-hero">
        <div class="login-hero-top">
          <div class="login-hero-badge">PROVINCIAL ASSESSOR</div>
          <div class="login-hero-status">
            <span>Property Records</span>
            <span>Tax Declarations</span>
            <span>Secure Access</span>
          </div>
        </div>

        <div class="login-hero-main">
          <div class="login-hero-copy-block">
            <h1>Tax Declaration & Property Assessment Records System</h1>
            <p class="login-hero-copy">
              A secure workspace for property records, tax declaration management, and assessment operations.
            </p>
          </div>

          <div class="login-hero-orbit">
            <div class="login-hero-orbit-stage">
              <div class="login-hero-orbit-glow"></div>
              <div class="login-hero-orbit-system" aria-hidden="true">
                <div class="login-hero-orbit-ring login-hero-orbit-ring--outer"></div>
                <div class="login-hero-orbit-ring login-hero-orbit-ring--inner"></div>

                <div class="login-hero-orbit-tools">
                  <div
                    v-for="(tool, index) in heroOrbitTools"
                    :key="tool.key"
                    class="login-hero-orbit-tool"
                    :style="{
                      '--orbit-accent': tool.accent,
                      '--orbit-delay': `${index * -3.2}s`,
                    }"
                  >
                    <div class="login-hero-orbit-tool-shell">
                      <div class="login-hero-orbit-tool-face">
                        <q-icon :name="tool.icon" size="24px" />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div
          class="login-hero-grid"
          @mouseenter="pauseHeroCycle"
          @mouseleave="startHeroCycle"
        >
          <button
            v-for="(item, index) in heroHighlights"
            :key="item.key"
            type="button"
            class="login-hero-card"
            :class="{ 'login-hero-card--active': activeHeroCard === index }"
            :style="{ '--login-card-delay': `${index * 120}ms` }"
            @click="setActiveHeroCard(index)"
            @focus="setActiveHeroCard(index)"
          >
            <div class="login-hero-card-head">
              <div class="login-hero-card-icon">
                <q-icon :name="item.icon" size="20px" />
              </div>
              <div class="login-hero-card-tag">{{ item.tag }}</div>
            </div>

            <div class="login-hero-card-title">{{ item.title }}</div>
            <div class="login-hero-card-copy">{{ item.copy }}</div>
          </button>
        </div>

        <div class="login-hero-grid-nav" aria-label="Feature highlights">
          <button
            v-for="(item, index) in heroHighlights"
            :key="`${item.key}-indicator`"
            type="button"
            class="login-hero-grid-dot"
            :class="{ 'login-hero-grid-dot--active': activeHeroCard === index }"
            :aria-label="`Show ${item.title}`"
            @click="setActiveHeroCard(index)"
          ></button>
        </div>
      </section>

      <q-card flat bordered class="login-card">
        <q-card-section class="login-card-head">
          <div class="login-card-brand">
            <div class="login-card-logo">
              <q-icon name="account_balance" size="42px" />
            </div>
            <div class="login-card-brand-copy">
              <div class="login-card-overline">Secure Access</div>
              <div class="login-card-title">Sign in to Records System</div>
              <div class="login-card-copy">
                Use your authorized account to search properties, manage tax declarations, and access assessment records.
              </div>
            </div>
          </div>
        </q-card-section>

        <q-separator class="login-card-divider" />

        <q-card-section class="login-card-body">
          <q-form class="login-form" @submit.prevent="onSubmit">
            <q-input
              v-model="loginForm.email"
              outlined
              dense
              stack-label
              hide-bottom-space
              label="Email"
              type="email"
              autocomplete="username"
              class="login-input"
              :disable="loginLoading"
            >
              <template #prepend>
                <q-icon name="person" />
              </template>
            </q-input>

            <q-input
              v-model="loginForm.password"
              outlined
              dense
              stack-label
              hide-bottom-space
              :type="showPassword ? 'text' : 'password'"
              label="Password"
              autocomplete="current-password"
              class="login-input"
              :disable="loginLoading"
            >
              <template #prepend>
                <q-icon name="lock" />
              </template>
              <template #append>
                <q-icon
                  :name="showPassword ? 'visibility_off' : 'visibility'"
                  class="cursor-pointer"
                  @click="showPassword = !showPassword"
                />
              </template>
            </q-input>

            <q-btn
              type="submit"
              unelevated
              color="primary"
              class="login-submit"
              label="Sign In"
              :loading="loginLoading"
            />
          </q-form>

          <div class="login-footnote">
            Demo: admin@assessor.local / password
          </div>
        </q-card-section>
      </q-card>
    </div>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import { useAuth } from '../composables/useAuth';
import { getStoredToken } from '../services/api';

const $q = useQuasar();
const router = useRouter();
const { authLoading, restoreSession, submitLogin } = useAuth();
const loginLoading = ref(false);
const showPassword = ref(false);
const activeHeroCard = ref(0);

const loginForm = reactive({
  email: 'admin@assessor.local',
  password: 'password'
});

const heroHighlights = [
  {
    key: 'property-search',
    icon: 'search',
    tag: 'Property Search',
    title: 'Property Records',
    copy: 'Search and manage property records by lot number, PIN, TD number, owner, or location.',
  },
  {
    key: 'tax-declarations',
    icon: 'receipt_long',
    tag: 'Tax Management',
    title: 'Tax Declarations',
    copy: 'Track tax declaration history, manage assessments, and maintain property valuations.',
  },
  {
    key: 'documents',
    icon: 'folder',
    tag: 'Document Management',
    title: 'Physical Files',
    copy: 'Digitize documents, track physical file movements, and manage archive locations.',
  },
];

const heroOrbitTools = [
  { key: 'search', icon: 'search', accent: 'rgba(125, 183, 255, 0.42)' },
  { key: 'property', icon: 'home_work', accent: 'rgba(255, 208, 126, 0.4)' },
  { key: 'receipt', icon: 'receipt_long', accent: 'rgba(108, 162, 255, 0.4)' },
  { key: 'folder', icon: 'folder', accent: 'rgba(255, 208, 126, 0.38)' },
  { key: 'calculate', icon: 'calculate', accent: 'rgba(125, 183, 255, 0.4)' },
  { key: 'scanner', icon: 'scanner', accent: 'rgba(255, 208, 126, 0.4)' },
  { key: 'verified', icon: 'verified', accent: 'rgba(125, 183, 255, 0.42)' },
  { key: 'print', icon: 'print', accent: 'rgba(255, 208, 126, 0.4)' },
];

let heroCycleTimer = null;

function setActiveHeroCard(index) {
  activeHeroCard.value = index;
}

function pauseHeroCycle() {
  if (heroCycleTimer) {
    window.clearInterval(heroCycleTimer);
    heroCycleTimer = null;
  }
}

function startHeroCycle() {
  pauseHeroCycle();
  if (typeof window === 'undefined' || heroHighlights.length < 2) return;

  heroCycleTimer = window.setInterval(() => {
    activeHeroCard.value = (activeHeroCard.value + 1) % heroHighlights.length;
  }, 4200);
}

async function onSubmit() {
  loginLoading.value = true;

  try {
    await submitLogin(loginForm.email, loginForm.password);
    await router.replace({ name: 'workspace-dashboard' });
    $q.notify({
      type: getStoredToken() === 'offline-demo-token' ? 'warning' : 'positive',
      message: getStoredToken() === 'offline-demo-token'
        ? 'Signed in with offline demo data. Start the API server for live records.'
        : 'Signed in.'
    });
  } catch (error) {
    if (error.message === 'API_UNAVAILABLE') {
      $q.notify({
        type: 'negative',
        message: 'Cannot reach the API at http://127.0.0.1:8002. Start the backend with: php artisan serve --port=8002',
        timeout: 8000
      });
    } else if (error.response?.status === 422) {
      const message = error.response?.data?.errors?.email?.[0] || error.response?.data?.message;
      $q.notify({ type: 'negative', message: message || 'Incorrect email or password.' });
    } else {
      $q.notify({ type: 'negative', message: 'Unable to sign in. Check the email and password.' });
    }
  } finally {
    loginLoading.value = false;
  }
}

onMounted(async () => {
  await restoreSession();

  if (getStoredToken()) {
    await router.replace({ name: 'workspace-dashboard' });
  }
  
  startHeroCycle();
});

onBeforeUnmount(() => {
  pauseHeroCycle();
});
</script>

<style scoped>
.login-page {
  --login-page-pad-y: clamp(12px, 2vh, 28px);
  --login-page-pad-x: clamp(16px, 2.2vw, 28px);
  --login-hero-pad: clamp(26px, 3vh, 40px);
  --login-card-pad-x: clamp(22px, 2.2vw, 30px);
  height: 100dvh;
  padding: var(--login-page-pad-y) var(--login-page-pad-x);
  overflow: hidden;
  background:
    radial-gradient(circle at top right, rgba(248, 195, 95, 0.18), transparent 24%),
    radial-gradient(circle at left center, rgba(55, 116, 199, 0.16), transparent 30%),
    linear-gradient(180deg, #edf2fb 0%, #d5e0f0 100%);
}

.login-page.boot-page {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16px;
}

.login-shell {
  display: grid;
  height: calc(100dvh - (var(--login-page-pad-y) * 2));
  max-width: 1440px;
  margin: 0 auto;
  grid-template-columns: minmax(0, 1.12fr) minmax(360px, 500px);
  gap: clamp(18px, 2vw, 24px);
  align-items: stretch;
  overflow: hidden;
}

.login-hero {
  position: relative;
  isolation: isolate;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: clamp(14px, 1.9vh, 24px);
  min-height: 0;
  height: 100%;
  padding: var(--login-hero-pad);
  border-radius: clamp(22px, 2vw, 28px);
  border: 1px solid rgba(255, 255, 255, 0.22);
  background: linear-gradient(160deg, #183154 0%, #234786 52%, #305ea7 100%);
  box-shadow: 0 28px 60px rgba(16, 36, 69, 0.24);
  color: #fff;
}

.login-hero::before {
  content: '';
  position: absolute;
  inset: 0;
  background:
    linear-gradient(135deg, rgba(9, 18, 31, 0.9) 0%, rgba(15, 38, 66, 0.76) 34%, rgba(25, 83, 128, 0.42) 100%);
  z-index: -2;
}

.login-hero::after {
  content: '';
  position: absolute;
  top: 0;
  bottom: 0;
  left: -8%;
  width: 116%;
  background:
    radial-gradient(circle at top right, rgba(255, 208, 126, 0.34), transparent 24%),
    linear-gradient(180deg, rgba(7, 16, 28, 0.1) 0%, rgba(7, 16, 28, 0.32) 100%);
  z-index: -1;
}

.login-hero > * {
  position: relative;
  z-index: 1;
}

.login-hero-badge {
  display: inline-flex;
  align-items: center;
  padding: 8px 12px;
  border: 1px solid rgba(255, 255, 255, 0.22);
  border-radius: 999px;
  background: rgba(9, 20, 36, 0.34);
  backdrop-filter: blur(12px);
  font-size: 0.8rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.login-hero-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 18px;
}

.login-hero-status {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 10px;
}

.login-hero-status span {
  display: inline-flex;
  align-items: center;
  padding: 7px 12px;
  border: 1px solid rgba(255, 255, 255, 0.16);
  border-radius: 999px;
  background: rgba(9, 20, 36, 0.2);
  backdrop-filter: blur(10px);
  color: rgba(255, 255, 255, 0.86);
  font-size: 0.76rem;
  font-weight: 700;
  letter-spacing: 0.03em;
}

.login-hero-main {
  width: min(100%, 980px);
  flex: 1;
  display: grid;
  align-content: center;
  gap: clamp(12px, 1.6vh, 20px);
}

.login-hero-copy-block {
  align-self: end;
}

.login-hero h1 {
  max-width: 620px;
  margin: 0;
  font-size: clamp(2rem, 3.2vw, 3.3rem);
  line-height: 1.05;
  text-shadow: 0 12px 30px rgba(7, 16, 28, 0.34);
}

.login-hero-copy {
  max-width: 560px;
  margin: clamp(12px, 1.8vh, 18px) 0 0;
  color: rgba(255, 255, 255, 0.9);
  font-size: 1rem;
  line-height: 1.62;
  text-shadow: 0 10px 24px rgba(7, 16, 28, 0.24);
}

.login-hero-orbit {
  position: relative;
  width: min(100%, 880px);
  margin-top: clamp(16px, 2.3vh, 24px);
}

.login-hero-orbit-stage {
  position: relative;
  display: grid;
  place-items: center;
  min-height: clamp(220px, 29dvh, 360px);
  perspective: 1400px;
  perspective-origin: 50% 54%;
}

.login-hero-orbit-glow {
  position: absolute;
  inset: 50% auto auto 50%;
  width: 760px;
  height: 300px;
  border-radius: 999px;
  background:
    radial-gradient(ellipse, rgba(125, 183, 255, 0.2) 0%, rgba(125, 183, 255, 0.08) 42%, rgba(125, 183, 255, 0) 74%);
  transform: translate(-50%, -50%);
  filter: blur(26px);
  pointer-events: none;
}

.login-hero-orbit-ring {
  position: absolute;
  top: 50%;
  left: 50%;
  border: 1px dashed rgba(255, 255, 255, 0.16);
  border-radius: 999px;
  transform: translate(-50%, -50%) rotateX(72deg);
  clip-path: inset(0 0 42% 0 round 999px);
  pointer-events: none;
}

.login-hero-orbit-ring--outer {
  width: 680px;
  height: 280px;
}

.login-hero-orbit-ring--inner {
  width: 540px;
  height: 210px;
  border-style: solid;
  border-color: rgba(255, 255, 255, 0.08);
}

.login-hero-orbit-system {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 720px;
  height: 300px;
  transform: translate(-50%, -50%);
  transform-style: preserve-3d;
  animation: loginHeroOrbitFloat 8s ease-in-out infinite;
}

.login-hero-orbit-tools {
  position: absolute;
  inset: 0;
  transform-style: preserve-3d;
  overflow: visible;
}

.login-hero-orbit-tool {
  position: absolute;
  top: 50%;
  left: 50%;
  transform-style: preserve-3d;
  animation: loginHeroFrontOrbit 25.6s linear infinite;
  animation-delay: var(--orbit-delay);
  will-change: transform, opacity, filter;
}

.login-hero-orbit-tool-shell {
  position: relative;
  transform: translate(-50%, -50%);
  transform-style: preserve-3d;
}

.login-hero-orbit-tool-face {
  position: relative;
  display: grid;
  place-items: center;
  width: 78px;
  height: 78px;
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 24px;
  background:
    radial-gradient(circle at 30% 26%, rgba(255, 255, 255, 0.28), rgba(255, 255, 255, 0) 44%),
    linear-gradient(180deg, rgba(19, 46, 84, 0.92) 0%, rgba(11, 28, 55, 0.82) 100%);
  backdrop-filter: blur(16px);
  box-shadow:
    0 18px 34px rgba(7, 16, 28, 0.28),
    0 0 0 1px rgba(255, 255, 255, 0.06) inset,
    0 0 40px var(--orbit-accent);
  color: rgba(255, 255, 255, 0.92);
  transform: rotateX(2deg);
  backface-visibility: hidden;
  overflow: hidden;
}

.login-hero-orbit-tool-face::before {
  content: '';
  position: absolute;
  inset: 9px;
  border-radius: 18px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0));
  opacity: 0.8;
  pointer-events: none;
}

.login-hero-orbit-tool-face::after {
  content: '';
  position: absolute;
  inset: auto 12px 8px;
  height: 14px;
  border-radius: 999px;
  background: rgba(7, 16, 28, 0.26);
  filter: blur(10px);
  pointer-events: none;
}

.login-hero-orbit-tool-face :deep(.q-icon) {
  position: relative;
  z-index: 1;
  color: #ffffff;
  font-size: 34px !important;
  text-shadow:
    0 0 16px rgba(125, 183, 255, 0.32),
    0 2px 10px rgba(7, 16, 28, 0.28);
}

.login-hero-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: clamp(8px, 1vh, 12px);
  width: min(100%, 980px);
}

.login-hero-card {
  position: relative;
  overflow: hidden;
  display: grid;
  gap: clamp(8px, 1.1vh, 11px);
  padding: clamp(12px, 1.4vh, 15px);
  border: 0;
  color: inherit;
  text-align: left;
  font: inherit;
  border: 1px solid rgba(255, 255, 255, 0.14);
  border-radius: 18px;
  background: linear-gradient(180deg, rgba(9, 20, 36, 0.34) 0%, rgba(9, 20, 36, 0.22) 100%);
  backdrop-filter: blur(12px);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.06);
  cursor: pointer;
  transition:
    transform 0.3s ease,
    border-color 0.3s ease,
    background 0.3s ease,
    box-shadow 0.3s ease;
  animation: loginHeroCardEnter 0.72s ease both;
  animation-delay: var(--login-card-delay, 0ms);
}

.login-hero-card::before {
  content: '';
  position: absolute;
  inset: 0;
  background:
    radial-gradient(circle at top right, rgba(255, 208, 126, 0.22), transparent 34%),
    linear-gradient(180deg, rgba(125, 183, 255, 0.08), rgba(125, 183, 255, 0));
  opacity: 0;
  transition: opacity 0.3s ease;
}

.login-hero-card:hover,
.login-hero-card:focus-visible,
.login-hero-card--active {
  transform: translateY(-6px);
  border-color: rgba(255, 255, 255, 0.34);
  background: linear-gradient(180deg, rgba(14, 31, 55, 0.66) 0%, rgba(11, 23, 43, 0.44) 100%);
  box-shadow:
    0 18px 34px rgba(7, 16, 28, 0.22),
    inset 0 1px 0 rgba(255, 255, 255, 0.08);
}

.login-hero-card:hover::before,
.login-hero-card:focus-visible::before,
.login-hero-card--active::before {
  opacity: 1;
}

.login-hero-card:focus-visible {
  outline: 2px solid rgba(255, 255, 255, 0.76);
  outline-offset: 3px;
}

.login-hero-card-head {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.login-hero-card-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  border: 1px solid rgba(255, 255, 255, 0.18);
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.08);
  color: #fff2cf;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
}

.login-hero-card-tag {
  display: inline-flex;
  align-items: center;
  padding: 6px 10px;
  border: 1px solid rgba(255, 255, 255, 0.14);
  border-radius: 999px;
  background: rgba(8, 20, 38, 0.26);
  color: rgba(255, 255, 255, 0.74);
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.login-hero-card-title {
  position: relative;
  z-index: 1;
  font-weight: 800;
  font-size: 1.04rem;
  letter-spacing: -0.01em;
}

.login-hero-card-copy {
  position: relative;
  z-index: 1;
  min-height: 0;
  color: rgba(255, 255, 255, 0.78);
  font-size: 0.9rem;
  line-height: 1.55;
}

.login-hero-grid-nav {
  display: flex;
  align-items: center;
  gap: 10px;
}

.login-hero-grid-dot {
  width: 14px;
  height: 12px;
  padding: 0;
  border: 0;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.24);
  cursor: pointer;
  transition: width 0.22s ease, transform 0.22s ease, background-color 0.22s ease, box-shadow 0.22s ease;
}

.login-hero-grid-dot:hover,
.login-hero-grid-dot:focus-visible,
.login-hero-grid-dot--active {
  width: 34px;
  background: #ffd07e;
  box-shadow: 0 0 0 4px rgba(255, 208, 126, 0.18);
}

.login-hero-grid-dot:focus-visible {
  outline: 2px solid rgba(255, 255, 255, 0.72);
  outline-offset: 3px;
}

.login-card {
  position: relative;
  isolation: isolate;
  overflow: hidden;
  display: flex;
  width: 100%;
  min-height: 0;
  height: 100%;
  flex-direction: column;
  justify-content: center;
  border-radius: clamp(22px, 2vw, 28px);
  border: 1px solid rgba(47, 98, 175, 0.16);
  background: linear-gradient(180deg, rgba(252, 254, 255, 0.96) 0%, rgba(235, 243, 252, 0.98) 100%);
  backdrop-filter: blur(20px);
  box-shadow:
    0 28px 60px rgba(16, 36, 69, 0.18),
    0 12px 24px rgba(16, 36, 69, 0.08),
    inset 0 1px 0 rgba(255, 255, 255, 0.7);
}

.login-card::before {
  content: '';
  position: absolute;
  inset: 0 0 auto 0;
  height: 210px;
  background:
    radial-gradient(circle at top right, rgba(47, 98, 175, 0.2), transparent 44%),
    radial-gradient(circle at top left, rgba(255, 208, 126, 0.16), transparent 32%),
    linear-gradient(180deg, rgba(24, 49, 84, 0.08) 0%, rgba(24, 49, 84, 0) 100%);
  z-index: -1;
}

.login-card-head {
  padding: clamp(22px, 2.4vh, 28px) var(--login-card-pad-x) clamp(16px, 1.8vh, 20px);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.38) 0%, rgba(255, 255, 255, 0.08) 100%);
}

.login-card-brand {
  display: grid;
  align-items: center;
  grid-template-columns: auto minmax(0, 1fr);
  gap: 18px;
  width: 100%;
}

.login-card-brand-copy {
  min-width: 0;
}

.login-card-logo {
  width: 76px;
  height: 76px;
  padding: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(47, 98, 175, 0.14);
  border-radius: 22px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(235, 242, 252, 0.92) 100%);
  box-shadow:
    0 14px 28px rgba(24, 49, 84, 0.12),
    inset 0 1px 0 rgba(255, 255, 255, 0.88);
  color: #2f62af;
}

.login-card-divider {
  margin: 0 30px;
  background: linear-gradient(90deg, rgba(47, 98, 175, 0), rgba(47, 98, 175, 0.22), rgba(47, 98, 175, 0));
}

.login-card-overline {
  color: #2f62af;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.login-card-title {
  margin-top: 8px;
  color: #162742;
  font-size: 1.95rem;
  font-weight: 800;
  line-height: 1.08;
  letter-spacing: -0.02em;
}

.login-card-copy {
  margin-top: 10px;
  max-width: 32ch;
  color: #5a7390;
  font-size: 0.94rem;
  line-height: 1.65;
}

.login-card-body {
  display: grid;
  gap: clamp(12px, 1.5vh, 16px);
  padding: clamp(20px, 2.2vh, 24px) var(--login-card-pad-x) clamp(22px, 2.4vh, 28px);
}

.login-form {
  display: grid;
  gap: 16px;
}

.login-input :deep(.q-field__control) {
  min-height: clamp(50px, 5.6vh, 56px);
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.82);
  transition: background-color 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.82);
}

.login-input :deep(.q-field__label) {
  color: #6c82a0;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.login-input :deep(.q-field--focused .q-field__control) {
  background: rgba(255, 255, 255, 0.98);
  box-shadow:
    0 0 0 4px rgba(47, 98, 175, 0.08),
    0 14px 28px rgba(24, 49, 84, 0.1),
    inset 0 1px 0 rgba(255, 255, 255, 0.92);
}

.login-input :deep(.q-field--stack-label .q-field__label) {
  transform: translateY(-36%) scale(1);
}

.login-input :deep(.q-field--stack-label .q-field__native),
.login-input :deep(.q-field--stack-label .q-field__input) {
  padding-top: 10px;
}

.login-input :deep(.q-field__native),
.login-input :deep(.q-field__input) {
  color: #162742;
  font-weight: 700;
}

.login-input :deep(.q-field__prepend),
.login-input :deep(.q-field__append),
.login-input :deep(.q-field__marginal) {
  color: #2f62af;
}

.login-submit {
  min-height: clamp(48px, 5vh, 52px);
  margin-top: 6px;
  border-radius: 16px;
  box-shadow: 0 16px 30px rgba(47, 98, 175, 0.24);
  font-size: 0.95rem;
  font-weight: 800;
  letter-spacing: 0.03em;
}

.login-footnote {
  margin-top: 0;
  padding: 14px 16px;
  border: 1px solid rgba(47, 98, 175, 0.12);
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.52);
  color: #5b6f89;
  font-size: 0.85rem;
  line-height: 1.65;
  text-align: center;
}

@keyframes loginHeroCardEnter {
  from {
    opacity: 0;
    transform: translateY(22px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes loginHeroOrbitFloat {
  0%,
  100% {
    transform: translate(-50%, -50%) translateY(0);
  }

  50% {
    transform: translate(-50%, -50%) translateY(-10px);
  }
}

@keyframes loginHeroFrontOrbit {
  0% {
    opacity: 0.16;
    filter: blur(1.2px);
    z-index: 1;
    transform: translate(332px, 34px) scale(0.54);
  }

  12.5% {
    opacity: 0.34;
    filter: blur(0.8px);
    z-index: 1;
    transform: translate(252px, 18px) scale(0.66);
  }

  25% {
    opacity: 0.58;
    filter: blur(0.2px);
    z-index: 2;
    transform: translate(166px, 8px) scale(0.82);
  }

  37.5% {
    opacity: 0.82;
    filter: blur(0);
    z-index: 3;
    transform: translate(72px, 10px) scale(0.98);
  }

  50% {
    opacity: 1;
    filter: blur(0);
    z-index: 4;
    transform: translate(-18px, 18px) scale(1.2);
  }

  62.5% {
    opacity: 0.84;
    filter: blur(0);
    z-index: 3;
    transform: translate(-120px, 10px) scale(1.02);
  }

  75% {
    opacity: 0.56;
    filter: blur(0.2px);
    z-index: 2;
    transform: translate(-220px, 0) scale(0.82);
  }

  87.5% {
    opacity: 0.32;
    filter: blur(0.8px);
    z-index: 1;
    transform: translate(-304px, 14px) scale(0.66);
  }

  100% {
    opacity: 0.16;
    filter: blur(1.2px);
    z-index: 1;
    transform: translate(-372px, 32px) scale(0.54);
  }
}

@media (max-width: 1200px) {
  .login-shell {
    grid-template-columns: 1fr;
  }

  .login-hero {
    min-height: 0;
  }

  .login-hero-top {
    flex-direction: column;
  }

  .login-hero-status {
    justify-content: flex-start;
  }

  .login-hero-orbit-system {
    width: 620px;
    height: 270px;
  }

  .login-hero-orbit-stage {
    min-height: 360px;
  }

  .login-hero-orbit-ring--outer {
    width: 600px;
    height: 240px;
  }

  .login-hero-orbit-ring--inner {
    width: 474px;
    height: 184px;
  }

  .login-hero-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .login-page {
    --login-page-pad-y: 16px;
    --login-page-pad-x: 16px;
    height: auto;
    min-height: 100dvh;
    overflow: visible;
  }

  .login-shell {
    height: auto;
    min-height: calc(100dvh - (var(--login-page-pad-y) * 2));
    overflow: visible;
  }

  .login-hero,
  .login-card-head,
  .login-card-body {
    padding-left: 20px;
    padding-right: 20px;
  }

  .login-card-brand {
    grid-template-columns: 1fr;
    justify-items: center;
    gap: 14px;
    text-align: center;
  }

  .login-card-copy {
    max-width: none;
  }

  .login-card-divider {
    margin: 0 20px;
  }

  .login-hero {
    min-height: 0;
    padding-top: 28px;
    padding-bottom: 28px;
  }

  .login-hero-orbit-stage {
    min-height: 280px;
  }

  .login-hero-orbit-system {
    width: 390px;
    height: 190px;
  }

  .login-hero-orbit-ring--outer {
    width: 380px;
    height: 152px;
  }

  .login-hero-orbit-ring--inner {
    width: 298px;
    height: 112px;
  }

  .login-hero-orbit-tool-face {
    width: 62px;
    height: 62px;
    border-radius: 20px;
  }

  .login-hero-orbit-tool-face :deep(.q-icon) {
    font-size: 28px !important;
  }

  .login-hero-grid-dot:hover,
  .login-hero-grid-dot:focus-visible,
  .login-hero-grid-dot--active {
    width: 24px;
  }
}
</style>

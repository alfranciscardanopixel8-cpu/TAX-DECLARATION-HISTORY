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
  height: 100dvh;
  padding: 16px;
  overflow: hidden;
  background: var(--c-bg, #f4f6f9);
}

.login-page.boot-page {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
}

.login-page-subtitle {
  color: var(--c-muted, #64748b);
  font-size: 0.8rem;
}

.login-shell {
  display: grid;
  height: calc(100dvh - 32px);
  max-width: 1280px;
  margin: 0 auto;
  grid-template-columns: minmax(0, 1.1fr) minmax(320px, 440px);
  gap: 16px;
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
  gap: 14px;
  min-height: 0;
  height: 100%;
  padding: 24px;
  border-radius: 10px;
  background: #1a2332;
  color: #fff;
}

.login-hero > * {
  position: relative;
  z-index: 1;
}

.login-hero-badge {
  display: inline-flex;
  align-items: center;
  padding: 5px 10px;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.06);
  font-size: 0.62rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.login-hero-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.login-hero-status {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 6px;
}

.login-hero-status span {
  display: inline-flex;
  align-items: center;
  padding: 4px 8px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.05);
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.62rem;
  font-weight: 600;
}


.login-hero-main {
  flex: 1;
  display: grid;
  align-content: center;
  gap: 12px;
}

.login-hero-copy-block {
  align-self: end;
}

.login-hero h1 {
  max-width: 520px;
  margin: 0;
  font-size: clamp(1.3rem, 2.2vw, 1.8rem);
  font-weight: 700;
  line-height: 1.15;
}

.login-hero-copy {
  max-width: 460px;
  margin: 8px 0 0;
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.8rem;
  line-height: 1.5;
}

/* Orbit — simplified, smaller */
.login-hero-orbit {
  position: relative;
  width: min(100%, 700px);
  margin-top: 12px;
}

.login-hero-orbit-stage {
  position: relative;
  display: grid;
  place-items: center;
  min-height: clamp(160px, 22dvh, 260px);
  perspective: 1200px;
  perspective-origin: 50% 54%;
}

.login-hero-orbit-glow {
  position: absolute;
  inset: 50% auto auto 50%;
  width: 500px;
  height: 200px;
  border-radius: 999px;
  background: radial-gradient(ellipse, rgba(44, 82, 130, 0.2) 0%, transparent 60%);
  transform: translate(-50%, -50%);
  filter: blur(20px);
  pointer-events: none;
}

.login-hero-orbit-ring {
  position: absolute;
  top: 50%;
  left: 50%;
  border: 1px dashed rgba(255, 255, 255, 0.1);
  border-radius: 999px;
  transform: translate(-50%, -50%) rotateX(72deg);
  clip-path: inset(0 0 42% 0 round 999px);
  pointer-events: none;
}

.login-hero-orbit-ring--outer { width: 520px; height: 210px; }
.login-hero-orbit-ring--inner { width: 400px; height: 160px; border-style: solid; border-color: rgba(255,255,255,0.05); }


.login-hero-orbit-system {
  position: absolute;
  top: 50%; left: 50%;
  width: 560px; height: 230px;
  transform: translate(-50%, -50%);
  transform-style: preserve-3d;
  animation: loginOrbitFloat 8s ease-in-out infinite;
}

.login-hero-orbit-tools {
  position: absolute;
  inset: 0;
  transform-style: preserve-3d;
  overflow: visible;
}

.login-hero-orbit-tool {
  position: absolute;
  top: 50%; left: 50%;
  transform-style: preserve-3d;
  animation: loginOrbit 25.6s linear infinite;
  animation-delay: var(--orbit-delay);
  will-change: transform, opacity;
}

.login-hero-orbit-tool-shell {
  position: relative;
  transform: translate(-50%, -50%);
  transform-style: preserve-3d;
}

.login-hero-orbit-tool-face {
  display: grid;
  place-items: center;
  width: 52px; height: 52px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  background: rgba(26, 35, 50, 0.85);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25), 0 0 24px var(--orbit-accent);
  color: rgba(255, 255, 255, 0.9);
  backface-visibility: hidden;
}

.login-hero-orbit-tool-face::before,
.login-hero-orbit-tool-face::after { display: none; }

.login-hero-orbit-tool-face :deep(.q-icon) {
  color: #fff;
  font-size: 22px !important;
}


/* Feature cards */
.login-hero-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 8px;
}

.login-hero-card {
  display: grid;
  gap: 6px;
  padding: 10px 12px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.04);
  color: inherit;
  text-align: left;
  font: inherit;
  cursor: pointer;
  transition: background 0.2s ease, border-color 0.2s ease;
}

.login-hero-card::before { display: none; }

.login-hero-card:hover,
.login-hero-card:focus-visible,
.login-hero-card--active {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(255, 255, 255, 0.2);
}

.login-hero-card:focus-visible {
  outline: 2px solid rgba(255, 255, 255, 0.6);
  outline-offset: 2px;
}

.login-hero-card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.login-hero-card-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 30px; height: 30px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.06);
  color: #d4a843;
}

.login-hero-card-tag {
  padding: 3px 7px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 999px;
  background: rgba(0, 0, 0, 0.2);
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.55rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.login-hero-card-title {
  font-weight: 600;
  font-size: 0.78rem;
}

.login-hero-card-copy {
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.7rem;
  line-height: 1.4;
}


/* Navigation dots */
.login-hero-grid-nav {
  display: flex;
  align-items: center;
  gap: 6px;
}

.login-hero-grid-dot {
  width: 8px; height: 8px;
  padding: 0; border: 0;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.2);
  cursor: pointer;
  transition: width 0.2s ease, background 0.2s ease;
}

.login-hero-grid-dot:hover,
.login-hero-grid-dot:focus-visible,
.login-hero-grid-dot--active {
  width: 20px;
  background: #d4a843;
}

/* Login card (right panel) */
.login-card {
  display: flex;
  width: 100%;
  min-height: 0;
  height: 100%;
  flex-direction: column;
  justify-content: center;
  border-radius: 10px;
  border: 1px solid var(--c-border, #e2e6ed);
  background: var(--c-surface, #ffffff);
  box-shadow: var(--shadow, 0 2px 8px rgba(0,0,0,0.08));
}

.login-card::before { display: none; }

.login-card-head {
  padding: 20px 24px 14px;
}

.login-card-brand {
  display: grid;
  align-items: center;
  grid-template-columns: auto minmax(0, 1fr);
  gap: 14px;
  width: 100%;
}

.login-card-brand-copy { min-width: 0; }

.login-card-logo {
  width: 52px; height: 52px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--c-border, #e2e6ed);
  border-radius: 10px;
  background: var(--c-surface-alt, #f8f9fb);
  color: var(--c-primary, #2c5282);
}

.login-card-logo :deep(.q-icon) {
  font-size: 28px !important;
}


.login-card-divider {
  margin: 0 24px;
  background: var(--c-border, #e2e6ed);
}

.login-card-overline {
  color: var(--c-primary, #2c5282);
  font-size: 0.62rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.login-card-title {
  margin-top: 4px;
  color: var(--c-ink, #1e293b);
  font-size: 1.1rem;
  font-weight: 700;
  line-height: 1.2;
}

.login-card-copy {
  margin-top: 6px;
  max-width: 30ch;
  color: var(--c-muted, #64748b);
  font-size: 0.78rem;
  line-height: 1.5;
}

.login-card-body {
  display: grid;
  gap: 12px;
  padding: 16px 24px 20px;
}

.login-form {
  display: grid;
  gap: 12px;
}

.login-input :deep(.q-field__control) {
  min-height: 40px;
  border-radius: 8px;
  background: var(--c-surface, #ffffff);
}

.login-input :deep(.q-field__label) {
  color: var(--c-muted, #64748b);
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.login-input :deep(.q-field--focused .q-field__control) {
  box-shadow: 0 0 0 2px rgba(44, 82, 130, 0.12);
}

.login-input :deep(.q-field--stack-label .q-field__label) {
  transform: translateY(-36%) scale(1);
}

.login-input :deep(.q-field--stack-label .q-field__native),
.login-input :deep(.q-field--stack-label .q-field__input) {
  padding-top: 8px;
}

.login-input :deep(.q-field__native),
.login-input :deep(.q-field__input) {
  color: var(--c-ink, #1e293b);
  font-size: 0.82rem;
  font-weight: 500;
}

.login-input :deep(.q-field__prepend),
.login-input :deep(.q-field__append),
.login-input :deep(.q-field__marginal) {
  color: var(--c-primary, #2c5282);
}


.login-submit {
  min-height: 38px;
  margin-top: 4px;
  border-radius: 8px;
  font-size: 0.8rem;
  font-weight: 700;
}

.login-footnote {
  padding: 8px 12px;
  border: 1px solid var(--c-border, #e2e6ed);
  border-radius: 6px;
  background: var(--c-surface-alt, #f8f9fb);
  color: var(--c-muted, #64748b);
  font-size: 0.72rem;
  text-align: center;
}

/* Keyframes (simplified) */
@keyframes loginOrbitFloat {
  0%, 100% { transform: translate(-50%, -50%) translateY(0); }
  50% { transform: translate(-50%, -50%) translateY(-6px); }
}

@keyframes loginOrbit {
  0%   { opacity: 0.15; transform: translate(260px, 26px) scale(0.5); }
  12.5%{ opacity: 0.3;  transform: translate(190px, 14px) scale(0.62); }
  25%  { opacity: 0.5;  transform: translate(120px, 6px) scale(0.76); }
  37.5%{ opacity: 0.75; transform: translate(50px, 8px) scale(0.9); }
  50%  { opacity: 1;    transform: translate(-14px, 14px) scale(1); }
  62.5%{ opacity: 0.75; transform: translate(-90px, 8px) scale(0.92); }
  75%  { opacity: 0.5;  transform: translate(-160px, 0) scale(0.76); }
  87.5%{ opacity: 0.3;  transform: translate(-230px, 10px) scale(0.62); }
  100% { opacity: 0.15; transform: translate(-280px, 24px) scale(0.5); }
}

@keyframes loginHeroCardEnter {
  from { opacity: 0; transform: translateY(12px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Responsive */
@media (max-width: 1100px) {
  .login-shell { grid-template-columns: 1fr; }
  .login-hero { min-height: 0; }
  .login-hero-top { flex-direction: column; }
  .login-hero-status { justify-content: flex-start; }
  .login-hero-orbit-system { width: 440px; height: 180px; }
  .login-hero-orbit-stage { min-height: 220px; }
  .login-hero-orbit-ring--outer { width: 420px; height: 170px; }
  .login-hero-orbit-ring--inner { width: 320px; height: 130px; }
  .login-hero-grid { grid-template-columns: 1fr; }
}

@media (max-width: 640px) {
  .login-page { height: auto; min-height: 100dvh; overflow: visible; }
  .login-shell { height: auto; min-height: calc(100dvh - 32px); overflow: visible; }
  .login-hero { padding: 16px; }
  .login-card-head, .login-card-body { padding-left: 16px; padding-right: 16px; }
  .login-card-brand { grid-template-columns: 1fr; justify-items: center; text-align: center; gap: 10px; }
  .login-card-copy { max-width: none; }
  .login-card-divider { margin: 0 16px; }
  .login-hero-orbit-stage { min-height: 180px; }
  .login-hero-orbit-system { width: 300px; height: 130px; }
  .login-hero-orbit-ring--outer { width: 280px; height: 110px; }
  .login-hero-orbit-ring--inner { width: 210px; height: 80px; }
  .login-hero-orbit-tool-face { width: 40px; height: 40px; border-radius: 8px; }
  .login-hero-orbit-tool-face :deep(.q-icon) { font-size: 18px !important; }
}
</style>

import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { fetchApiHealth, fetchMe, getStoredToken, login, logout } from '../services/api';

const sessionUser = ref(null);
const authLoading = ref(true);
const apiHealth = reactive({ online: false, status: 'checking' });

let healthTimer;

export function useAuth() {
  const router = useRouter();

  async function checkApiHealth() {
    const health = await fetchApiHealth();
    Object.assign(apiHealth, health);
  }

  async function restoreSession() {
    authLoading.value = true;

    try {
      sessionUser.value = await fetchMe();

      if (sessionUser.value) {
        await checkApiHealth();
      }
    } catch {
      sessionUser.value = null;
    } finally {
      authLoading.value = false;
    }
  }

  async function submitLogin(email, password) {
    sessionUser.value = await login(email, password);
    await checkApiHealth();
    return sessionUser.value;
  }

  async function submitLogout() {
    await logout();
    sessionUser.value = null;
    await router.push({ name: 'login' });
  }

  function startHealthPolling() {
    stopHealthPolling();
    healthTimer = setInterval(() => {
      if (sessionUser.value) {
        checkApiHealth();
      }
    }, 60000);
  }

  function stopHealthPolling() {
    if (healthTimer) {
      clearInterval(healthTimer);
      healthTimer = null;
    }
  }

  function roleLabel(role) {
    return {
      admin: 'Administrator',
      assessor: 'Assessor',
      records_staff: 'Records Staff',
      viewer: 'Viewer'
    }[role] || 'User';
  }

  return {
    sessionUser,
    authLoading,
    apiHealth,
    getStoredToken,
    restoreSession,
    checkApiHealth,
    submitLogin,
    submitLogout,
    startHealthPolling,
    stopHealthPolling,
    roleLabel
  };
}

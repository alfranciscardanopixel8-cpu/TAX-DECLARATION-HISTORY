import { createApp } from 'vue';
import { createPinia } from 'pinia';
import {
  Quasar,
  // Layout
  QLayout,
  QHeader,
  QDrawer,
  QPageContainer,
  QPage,
  QPageSticky,
  QToolbar,
  QToolbarTitle,
  QScrollArea,
  // Buttons
  QBtn,
  QBtnDropdown,
  QBtnToggle,
  // Inputs
  QInput,
  QSelect,
  QFile,
  QForm,
  QToggle,
  QCheckbox,
  QDate,
  QPopupProxy,
  // Display
  QIcon,
  QAvatar,
  QBadge,
  QChip,
  QBanner,
  QCard,
  QCardSection,
  QSeparator,
  QSpace,
  // Lists
  QList,
  QItem,
  QItemSection,
  QItemLabel,
  // Tables
  QTable,
  QTd,
  QTr,
  QTh,
  // Tabs
  QTabs,
  QTab,
  QTabPanels,
  QTabPanel,
  // Overlays
  QDialog,
  QTooltip,
  QMenu,
  // Progress
  QSpinner,
  QInnerLoading,
  // Misc
  QFab,
  QFabAction,
  QPagination,
  // Plugins / directives
  Notify,
  Dialog,
  ClosePopup
} from 'quasar';
import '@quasar/extras/material-icons/material-icons.css';
import 'quasar/dist/quasar.css';
import './styles/workspace.css';
import App from './App.vue';
import router from './router';

createApp(App)
  .use(createPinia())
  .use(router)
  .use(Quasar, {
    plugins: { Notify, Dialog },
    directives: { ClosePopup },
    components: {
      QLayout,
      QHeader,
      QDrawer,
      QPageContainer,
      QPage,
      QPageSticky,
      QToolbar,
      QToolbarTitle,
      QScrollArea,
      QBtn,
      QBtnDropdown,
      QBtnToggle,
      QInput,
      QSelect,
      QFile,
      QForm,
      QToggle,
      QCheckbox,
      QDate,
      QPopupProxy,
      QIcon,
      QAvatar,
      QBadge,
      QChip,
      QBanner,
      QCard,
      QCardSection,
      QSeparator,
      QSpace,
      QList,
      QItem,
      QItemSection,
      QItemLabel,
      QTable,
      QTd,
      QTr,
      QTh,
      QTabs,
      QTab,
      QTabPanels,
      QTabPanel,
      QDialog,
      QTooltip,
      QMenu,
      QSpinner,
      QInnerLoading,
      QFab,
      QFabAction,
      QPagination
    }
  })
  .mount('#app');

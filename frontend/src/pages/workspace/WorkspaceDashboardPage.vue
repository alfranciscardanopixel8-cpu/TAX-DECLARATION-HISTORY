<template>
  <div class="ws-page">
    <WorkspacePageHeader
      module=""
      title="Dashboard"
      lead="Province-wide property and tax declaration overview."
    >
      <template #actions>
        <q-btn outline no-caps color="primary" icon="refresh" label="Refresh" :loading="loading" @click="loadDashboard" />
      </template>
    </WorkspacePageHeader>

    <!-- Top metrics -->
    <section class="dashboard-metrics">
      <div class="metric-card metric-card--primary">
        <div class="metric-icon"><q-icon name="home_work" size="28px" /></div>
        <div class="metric-body">
          <span>Total Properties</span>
          <strong>{{ stats.properties }}</strong>
        </div>
      </div>
      <div class="metric-card">
        <div class="metric-icon"><q-icon name="verified" size="28px" /></div>
        <div class="metric-body">
          <span>Active Tax Declarations</span>
          <strong>{{ stats.activeTaxDeclarations }}</strong>
        </div>
      </div>
      <div class="metric-card">
        <div class="metric-icon"><q-icon name="receipt_long" size="28px" /></div>
        <div class="metric-body">
          <span>Total TD Records</span>
          <strong>{{ stats.taxDeclarations }}</strong>
        </div>
      </div>
      <div class="metric-card">
        <div class="metric-icon"><q-icon name="folder" size="28px" /></div>
        <div class="metric-body">
          <span>Documents</span>
          <strong>{{ stats.documents }}</strong>
        </div>
      </div>
    </section>

    <!-- Value summary -->
    <section class="dashboard-values" v-if="dashboard">
      <div class="value-card">
        <span><q-icon name="payments" size="14px" /> Total Market Value (Active)</span>
        <strong>{{ money(dashboard.total_market_value) }}</strong>
      </div>
      <div class="value-card value-card--primary">
        <span><q-icon name="account_balance" size="14px" /> Total Assessed Value (Active)</span>
        <strong>{{ money(dashboard.total_assessed_value) }}</strong>
      </div>
      <div class="value-card">
        <span><q-icon name="pending_actions" size="14px" /> Pending Approvals</span>
        <strong>{{ dashboard.pending_approvals || 0 }}</strong>
      </div>
    </section>

    <!-- Charts grid -->
    <section class="dashboard-charts" v-if="dashboard">
      <!-- Property Type Donut -->
      <div class="chart-card">
        <div class="chart-header">
          <q-icon name="category" size="18px" />
          <strong>Properties by Type</strong>
        </div>
        <div class="chart-body chart-body--donut" v-if="propertyKindData.length">
          <div class="donut" :style="donutStyle(propertyKindData)">
            <div class="donut-center">
              <strong>{{ propertyKindTotal }}</strong>
              <span>Total</span>
            </div>
          </div>
          <div class="donut-legend">
            <div v-for="item in propertyKindData" :key="item.label" class="legend-item">
              <span class="legend-dot" :style="{ background: item.color }"></span>
              <span class="legend-label">{{ item.label }}</span>
              <strong>{{ item.value }}</strong>
            </div>
          </div>
        </div>
        <div v-else class="empty-state compact">No data</div>
      </div>

      <!-- Classification Bar Chart -->
      <div class="chart-card">
        <div class="chart-header">
          <q-icon name="bar_chart" size="18px" />
          <strong>Properties by Classification</strong>
        </div>
        <div class="chart-body" v-if="classificationData.length">
          <div v-for="item in classificationData" :key="item.label" class="bar-row">
            <span class="bar-label">{{ item.label }}</span>
            <div class="bar-track">
              <div class="bar-fill" :style="{ width: item.percent + '%' }"></div>
            </div>
            <strong class="bar-value">{{ item.value }}</strong>
          </div>
        </div>
        <div v-else class="empty-state compact">No data</div>
      </div>

      <!-- TDs by Year Line Chart (Bar visualization) -->
      <div class="chart-card chart-card--wide">
        <div class="chart-header">
          <q-icon name="timeline" size="18px" />
          <strong>Tax Declarations by Year</strong>
        </div>
        <div class="chart-body chart-body--timeline" v-if="yearData.length">
          <div class="timeline-chart">
            <div v-for="item in yearData" :key="item.label" class="timeline-bar" :title="`${item.label}: ${item.value}`">
              <strong>{{ item.value }}</strong>
              <div class="timeline-bar-fill" :style="{ height: item.percent + '%' }"></div>
              <span>{{ item.label }}</span>
            </div>
          </div>
        </div>
        <div v-else class="empty-state compact">No data</div>
      </div>

      <!-- Top Municipalities -->
      <div class="chart-card">
        <div class="chart-header">
          <q-icon name="place" size="18px" />
          <strong>Top Municipalities</strong>
        </div>
        <div class="chart-body" v-if="municipalityData.length">
          <div v-for="(item, idx) in municipalityData" :key="item.label" class="bar-row">
            <span class="bar-rank">{{ idx + 1 }}</span>
            <span class="bar-label">{{ item.label }}</span>
            <div class="bar-track">
              <div class="bar-fill bar-fill--secondary" :style="{ width: item.percent + '%' }"></div>
            </div>
            <strong class="bar-value">{{ item.value }}</strong>
          </div>
        </div>
        <div v-else class="empty-state compact">No data</div>
      </div>

      <!-- TD Status Pie -->
      <div class="chart-card">
        <div class="chart-header">
          <q-icon name="pie_chart" size="18px" />
          <strong>TD Status Distribution</strong>
        </div>
        <div class="chart-body chart-body--donut" v-if="tdStatusData.length">
          <div class="donut" :style="donutStyle(tdStatusData)">
            <div class="donut-center">
              <strong>{{ tdStatusTotal }}</strong>
              <span>TDs</span>
            </div>
          </div>
          <div class="donut-legend">
            <div v-for="item in tdStatusData" :key="item.label" class="legend-item">
              <span class="legend-dot" :style="{ background: item.color }"></span>
              <span class="legend-label">{{ item.label }}</span>
              <strong>{{ item.value }}</strong>
            </div>
          </div>
        </div>
        <div v-else class="empty-state compact">No data</div>
      </div>
    </section>

    <!-- Recent Activity -->
    <section class="ws-card" v-if="dashboard?.recent_activity?.length">
      <div class="ws-card__title">Recent Activity</div>
      <q-list separator>
        <q-item v-for="log in dashboard.recent_activity" :key="log.id">
          <q-item-section avatar>
            <q-icon name="task_alt" color="primary" />
          </q-item-section>
          <q-item-section>
            <q-item-label>{{ log.description }}</q-item-label>
            <q-item-label caption>{{ log.user?.name || 'System' }} · {{ formatDate(log.created_at) }}</q-item-label>
          </q-item-section>
        </q-item>
      </q-list>
    </section>

    <!-- Quick Access -->
    <section class="ws-card">
      <div class="ws-card__title">Quick access</div>
      <div class="ws-btn-row">
        <q-btn
          v-for="link in quickLinks"
          :key="link.name"
          outline
          no-caps
          color="primary"
          :icon="link.icon"
          :label="link.label"
          :to="{ name: link.name }"
        />
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { fetchDashboard } from '../../services/api';
import WorkspacePageHeader from '../../components/layout/WorkspacePageHeader.vue';

const loading = ref(false);
const dashboard = ref(null);

const COLORS = ['#2f62af', '#16a34a', '#d97706', '#7c3aed', '#0891b2', '#dc2626', '#475569'];

const stats = computed(() => ({
  properties: dashboard.value?.properties ?? 0,
  activeTaxDeclarations: dashboard.value?.active_tax_declarations ?? 0,
  taxDeclarations: dashboard.value?.tax_declarations ?? 0,
  documents: dashboard.value?.documents ?? 0
}));

const propertyKindData = computed(() => buildChartData(dashboard.value?.by_property_kind));
const propertyKindTotal = computed(() => propertyKindData.value.reduce((s, x) => s + x.value, 0));

const classificationData = computed(() => buildChartData(dashboard.value?.by_classification));

const yearData = computed(() => {
  const data = dashboard.value?.tds_by_year || {};
  const entries = Object.entries(data).slice(-10);
  if (!entries.length) return [];
  const max = Math.max(...entries.map(([, v]) => v));
  return entries.map(([year, count]) => ({
    label: year,
    value: count,
    percent: max > 0 ? (count / max) * 100 : 0
  }));
});

const municipalityData = computed(() => buildChartData(dashboard.value?.by_municipality));

const tdStatusData = computed(() => buildChartData(dashboard.value?.tds_by_status));
const tdStatusTotal = computed(() => tdStatusData.value.reduce((s, x) => s + x.value, 0));

const quickLinks = [
  { name: 'workspace-records', label: 'Search Records', icon: 'search' },
  { name: 'workspace-digitize', label: 'Digitization Queue', icon: 'scanner' },
  { name: 'workspace-activity', label: 'Audit Logs', icon: 'fact_check' }
];

function buildChartData(obj) {
  if (!obj) return [];
  const entries = Object.entries(obj).filter(([k]) => k && k !== 'null');
  const max = Math.max(...entries.map(([, v]) => Number(v)), 1);
  return entries.map(([label, value], idx) => ({
    label,
    value: Number(value),
    percent: (Number(value) / max) * 100,
    color: COLORS[idx % COLORS.length]
  }));
}

function donutStyle(data) {
  const total = data.reduce((s, x) => s + x.value, 0);
  if (total === 0) return {};
  let cumulative = 0;
  const stops = data.map((item) => {
    const start = (cumulative / total) * 360;
    cumulative += item.value;
    const end = (cumulative / total) * 360;
    return `${item.color} ${start}deg ${end}deg`;
  });
  return {
    background: `conic-gradient(${stops.join(', ')})`
  };
}

function money(value) {
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 0 }).format(Number(value || 0));
}

function formatDate(value) {
  if (!value) return '';
  const d = new Date(value);
  return d.toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' });
}

async function loadDashboard() {
  loading.value = true;
  try {
    dashboard.value = await fetchDashboard();
  } finally {
    loading.value = false;
  }
}

onMounted(loadDashboard);
</script>

<style scoped>
.dashboard-metrics {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 14px;
}

.metric-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 18px;
  background: #fff;
  border: 1px solid rgba(20, 39, 67, 0.08);
  border-radius: 14px;
  box-shadow: 0 2px 8px rgba(14, 34, 63, 0.04);
  transition: all 0.2s ease;
}

.metric-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(14, 34, 63, 0.08);
}

.metric-card--primary {
  background: linear-gradient(135deg, #183154 0%, #2f62af 100%);
  color: #fff;
  border-color: transparent;
}

.metric-card--primary .metric-icon {
  background: rgba(255, 255, 255, 0.2);
  color: #fff;
}

.metric-card--primary .metric-body span {
  color: rgba(255, 255, 255, 0.8);
}

.metric-card--primary .metric-body strong {
  color: #fff;
}

.metric-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 56px;
  border-radius: 12px;
  background: rgba(47, 98, 175, 0.1);
  color: #2f62af;
  flex-shrink: 0;
}

.metric-body {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.metric-body span {
  font-size: 0.75rem;
  font-weight: 700;
  color: #657892;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.metric-body strong {
  font-size: 1.6rem;
  font-weight: 800;
  color: #162742;
  line-height: 1.1;
}

.dashboard-values {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}

.value-card {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 14px 16px;
  background: #fff;
  border: 1px solid rgba(20, 39, 67, 0.08);
  border-radius: 12px;
}

.value-card span {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.78rem;
  font-weight: 700;
  color: #657892;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.value-card strong {
  font-size: 1.3rem;
  font-weight: 800;
  color: #162742;
}

.value-card--primary {
  background: linear-gradient(135deg, rgba(47, 98, 175, 0.08) 0%, rgba(47, 98, 175, 0.02) 100%);
  border-color: rgba(47, 98, 175, 0.2);
}

.value-card--primary span { color: #2f62af; }
.value-card--primary strong { color: #1e3f78; }

.dashboard-charts {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.chart-card {
  background: #fff;
  border: 1px solid rgba(20, 39, 67, 0.08);
  border-radius: 14px;
  overflow: hidden;
}

.chart-card--wide {
  grid-column: span 2;
}

.chart-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  background: rgba(247, 250, 255, 0.6);
  border-bottom: 1px solid rgba(20, 39, 67, 0.06);
  font-size: 0.85rem;
  color: #2f62af;
}

.chart-header .q-icon { color: #2f62af; }

.chart-body {
  padding: 16px;
}

.bar-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
}

.bar-row:last-child { margin-bottom: 0; }

.bar-rank {
  width: 18px;
  height: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(47, 98, 175, 0.1);
  color: #2f62af;
  border-radius: 4px;
  font-size: 0.7rem;
  font-weight: 800;
  flex-shrink: 0;
}

.bar-label {
  flex: 0 0 130px;
  font-size: 0.85rem;
  font-weight: 700;
  color: #162742;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.bar-track {
  flex: 1;
  height: 12px;
  background: rgba(20, 39, 67, 0.06);
  border-radius: 6px;
  overflow: hidden;
}

.bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #2f62af, #245ea8);
  border-radius: 6px;
  transition: width 0.5s ease;
}

.bar-fill--secondary {
  background: linear-gradient(90deg, #16a34a, #22c55e);
}

.bar-value {
  flex: 0 0 50px;
  text-align: right;
  font-size: 0.9rem;
  font-weight: 800;
  color: #162742;
}

.chart-body--donut {
  display: flex;
  gap: 20px;
  align-items: center;
  padding: 20px;
}

.donut {
  width: 140px;
  height: 140px;
  border-radius: 50%;
  position: relative;
  flex-shrink: 0;
}

.donut::before {
  content: '';
  position: absolute;
  top: 22px;
  left: 22px;
  right: 22px;
  bottom: 22px;
  background: #fff;
  border-radius: 50%;
}

.donut-center {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 1;
}

.donut-center strong {
  font-size: 1.5rem;
  font-weight: 800;
  color: #162742;
  line-height: 1;
}

.donut-center span {
  font-size: 0.75rem;
  color: #657892;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin-top: 2px;
}

.donut-legend {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.85rem;
}

.legend-dot {
  width: 12px;
  height: 12px;
  border-radius: 3px;
  flex-shrink: 0;
}

.legend-label {
  flex: 1;
  color: #162742;
}

.legend-item strong {
  color: #162742;
  font-weight: 800;
}

.timeline-chart {
  display: flex;
  align-items: flex-end;
  gap: 8px;
  height: 180px;
  padding: 16px 8px 4px;
}

.timeline-bar {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  min-width: 0;
  height: 100%;
  position: relative;
}

.timeline-bar strong {
  font-size: 0.78rem;
  font-weight: 800;
  color: #162742;
  margin-bottom: 4px;
}

.timeline-bar-fill {
  width: 100%;
  max-width: 40px;
  background: linear-gradient(180deg, #2f62af, #1e3f78);
  border-radius: 6px 6px 0 0;
  transition: height 0.5s ease;
  margin: auto auto 0;
}

.timeline-bar span {
  font-size: 0.78rem;
  font-weight: 700;
  color: #657892;
  text-align: center;
  margin-top: 4px;
}

@media (max-width: 1024px) {
  .dashboard-metrics {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .dashboard-charts {
    grid-template-columns: 1fr;
  }

  .chart-card--wide {
    grid-column: span 1;
  }

  .dashboard-values {
    grid-template-columns: 1fr;
  }
}
</style>

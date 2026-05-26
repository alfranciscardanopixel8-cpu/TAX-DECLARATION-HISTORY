<template>
  <div class="ws-page">



    <section class="ws-card ws-filters-compact">
        <div class="search-row">
          <div class="search-bar-wrapper">
            <q-input v-model="keyword" outlined dense debounce="350" label="Search TD, ARP, PIN, lot, survey, title, owner, barangay" class="filter-search" @focus="showSuggestions = true" @blur="hideSuggestionsLater">
              <template #prepend>
                <q-icon name="search" />
              </template>
              <template #append>
                <q-btn v-if="keyword" flat dense round icon="clear" size="sm" @click="keyword = ''" />
                <span class="search-shortcut-hint">Ctrl+K</span>
                <q-btn flat dense no-caps :icon="showAdvancedFilters ? 'expand_less' : 'tune'" :label="showAdvancedFilters ? 'Hide filters' : 'Filters'" color="primary" @click="showAdvancedFilters = !showAdvancedFilters" />
              </template>
            </q-input>

            <!-- Search Suggestions Dropdown -->
            <div v-if="showSuggestions && (recentSearches.length || searchSuggestions.length)" class="search-suggestions">
            <div v-if="!keyword && recentSearches.length" class="search-suggestion-section">
              <div class="search-suggestion-header">
                <q-icon name="schedule" size="14px" /> Recent Searches
                <q-btn flat dense no-caps size="sm" label="Clear" @mousedown.prevent="clearRecentSearches" />
              </div>
              <button
                v-for="(item, idx) in recentSearches"
                :key="`r-${idx}`"
                type="button"
                class="search-suggestion-item"
                @mousedown.prevent="applySearchSuggestion(item)"
              >
                <q-icon name="history" size="14px" />
                <span>{{ item }}</span>
              </button>
            </div>
            <div v-if="keyword && searchSuggestions.length" class="search-suggestion-section">
              <div class="search-suggestion-header">
                <q-icon name="lightbulb" size="14px" /> Top Matches
              </div>
              <button
                v-for="prop in searchSuggestions"
                :key="prop.id"
                type="button"
                class="search-suggestion-item search-suggestion-item--match"
                @mousedown.prevent="selectSuggestion(prop)"
              >
                <q-icon :name="propertyKindIcon[prop.property_kind] || 'home_work'" size="16px" />
                <div class="search-suggestion-content">
                  <strong>{{ prop.lot_number || prop.pin }}</strong>
                  <span>{{ prop.barangay }}, {{ prop.municipality }}</span>
                </div>
                <span v-if="prop.tax_declarations?.[0]?.owner?.name" class="search-suggestion-meta">{{ prop.tax_declarations[0].owner.name }}</span>
              </button>
            </div>
          </div>
        </div>
        <div class="search-row-actions">
          <q-btn v-if="canManage" unelevated no-caps dense color="primary" icon="add" label="New Property" @click="entryDialog = true" />
          <q-btn outline no-caps dense color="primary" icon="refresh" @click="loadRecords">
            <q-tooltip>Refresh</q-tooltip>
          </q-btn>
          <q-btn outline no-caps dense color="primary" icon="table_view" @click="downloadCsv">
            <q-tooltip>Export CSV</q-tooltip>
          </q-btn>
          <q-btn v-if="sessionUser?.can_approve_records" outline no-caps dense color="primary" icon="backup" @click="downloadBackup">
            <q-tooltip>Backup</q-tooltip>
          </q-btn>
        </div>
        </div>

        <div class="ws-filters-row" v-if="showAdvancedFilters">
          <q-select v-model="filters.property_kind" outlined dense clearable label="Property Type" :options="['Land', 'Building', 'Machinery']" />
          <q-input v-model="filters.lot_number" outlined dense debounce="350" label="Lot No." clearable />
          <q-select v-model="municipality" outlined dense clearable label="Municipality" :options="options.municipalities" />
          <q-select v-model="filters.classification" outlined dense clearable label="Class" :options="options.classifications" />
          <q-select v-model="filters.status" outlined dense clearable label="Status" :options="options.statuses" />
          <q-select v-model="filters.td_status" outlined dense clearable label="TD Status" :options="options.statuses" />
          <q-input v-model="filters.owner" outlined dense debounce="350" label="Owner" />
          <q-input v-model="filters.barangay" outlined dense debounce="350" label="Barangay" />
          <q-input v-model.number="filters.year_from" outlined dense type="number" label="Year from" />
          <q-input v-model.number="filters.year_to" outlined dense type="number" label="Year to" />
        </div>
      </section>

    <section v-if="recentProperties.length" class="recent-bar">
      <span class="recent-bar-label"><q-icon name="history" size="14px" /> Recent:</span>
      <q-chip
        v-for="item in recentProperties"
        :key="item.id"
        clickable
        dense
        size="sm"
        color="blue-grey-1"
        text-color="primary"
        @click="openRecentProperty(item.id)"
      >
        {{ item.lot_number || item.pin }}
      </q-chip>
    </section>

    <div class="ws-content-grid">
      <div class="ws-card ws-card--flush">
        <q-table
          flat
          class="ws-table"
          title="Property Records"
          :loading="loading"
          :rows="records"
          :columns="columns"
          row-key="id"
          :pagination="{ rowsPerPage: 10 }"
          :rows-per-page-options="[10, 20, 50, 0]"
          binary-state-sort
          @row-click="selectRecord"
        >
          <template #top-right>
            <div class="table-tools">
              <q-badge outline color="white" :label="`${records.length} result(s)`" />
            </div>
          </template>

          <template #body-cell-td="props">
            <q-td :props="props">
              <div class="text-weight-medium">{{ currentTd(props.row)?.td_number || 'No active TD' }}</div>
              <div class="text-caption text-blue-grey-6">{{ currentTd(props.row)?.arp_number }}</div>
            </q-td>
          </template>

          <template #body-cell-pin="props">
            <q-td :props="props">
              <div class="text-weight-medium">{{ props.row.pin }}</div>
            </q-td>
          </template>

          <template #body-cell-property="props">
            <q-td :props="props">
              <div class="text-weight-medium">{{ props.row.lot_number || 'No lot number' }}</div>
              <div class="text-caption text-blue-grey-6">{{ props.row.title_number || props.row.survey_number || 'No title/survey' }}</div>
            </q-td>
          </template>

          <template #body-cell-owner="props">
            <q-td :props="props">{{ currentTd(props.row)?.owner?.name || 'Unassigned' }}</q-td>
          </template>

          <template #body-cell-location="props">
            <q-td :props="props">{{ props.row.barangay }}, {{ props.row.municipality }}</q-td>
          </template>

          <template #body-cell-area="props">
            <q-td :props="props">
              {{ numberFormat(props.row.land_area) }} {{ props.row.unit_of_measure || 'sqm' }}
            </q-td>
          </template>

          <template #body-cell-values="props">
            <q-td :props="props">
              <div class="text-weight-medium">{{ money(currentTd(props.row)?.assessed_value) }}</div>
              <div class="text-caption text-blue-grey-6">{{ money(currentTd(props.row)?.market_value) }} market</div>
            </q-td>
          </template>

          <template #body-cell-status="props">
            <q-td :props="props">
              <q-badge :color="statusColor(props.row.status)" :label="props.row.status" />
            </q-td>
          </template>

        </q-table>
      </div>

      <div v-if="searchMeta" class="search-pagination ws-card row items-center justify-between">
          <div class="text-caption text-blue-grey-7">
            Page {{ searchMeta.current_page }} of {{ searchMeta.last_page }} · {{ searchMeta.total }} properties
          </div>
          <div class="row items-center q-gutter-sm">
            <q-select
              v-model="searchPerPage"
              dense
              outlined
              emit-value
              map-options
              :options="[{ label: '25', value: 25 }, { label: '50', value: 50 }, { label: '100', value: 100 }]"
              style="min-width: 72px"
              @update:model-value="onSearchPerPageChange"
            />
            <q-btn outline dense color="primary" icon="chevron_left" label="Previous" :disable="searchPage <= 1" @click="changeSearchPage(searchPage - 1)" />
            <q-btn outline dense color="primary" icon-right="chevron_right" label="Next" :disable="searchPage >= searchMeta.last_page" @click="changeSearchPage(searchPage + 1)" />
          </div>
        </div>

      <aside ref="recordPanel" class="ws-record-panel profile-panel" v-if="selected">
        <div class="record-back-bar">
          <q-btn flat dense no-caps color="negative" icon="close" label="Close Record" @click="closeProperty" />
        </div>
        <div class="ws-record-panel__header profile-header">
          <div class="header-content">
            <div class="property-icon">
              <q-icon :name="propertyKindIcon[selected.property_kind] || 'home_work'" size="32px" />
            </div>
            <div>
              <div class="ws-kicker">{{ selected.property_kind || 'Property' }} Record</div>
              <div class="text-h6 text-weight-bold">{{ selected.lot_number || selected.pin }}</div>
              <div class="text-body2 text-blue-grey-7">{{ selected.pin }} · {{ selected.barangay }}, {{ selected.municipality }}</div>
            </div>
          </div>
          <q-badge :color="statusColor(selected.status)" :label="selected.status" class="status-badge" />
        </div>

        <div class="jacket-toolbar">
          <div class="jacket-toolbar-group">
            <q-btn v-if="canManage" outline no-caps color="primary" icon="edit" label="Edit" @click="openEditPropertyDialog" />
            <q-btn v-if="canApprove && selected.status !== 'Active'" outline no-caps color="positive" icon="verified" label="Approve" @click="approveSelectedProperty" />
            <q-btn outline no-caps color="primary" icon="print" label="Print" @click="printRecord" />
            <q-btn outline no-caps color="primary" icon="download" label="Export" @click="exportRecord" />
            <q-btn v-if="canDeleteProperty" outline no-caps color="negative" icon="delete_forever" label="Delete Property" @click="deleteSelectedProperty" />
          </div>
        </div>

        <q-separator />

        <div class="record-jacket">
          <div class="jacket-metric">
            <q-icon name="person" class="metric-icon" />
            <div>
              <span>Current Owner</span>
              <strong>{{ currentTd(selected)?.owner?.name || 'No active owner' }}</strong>
            </div>
          </div>
          <div class="jacket-metric">
            <q-icon name="receipt_long" class="metric-icon" />
            <div>
              <span>Active TD</span>
              <strong>{{ currentTd(selected)?.td_number || 'None' }}</strong>
            </div>
          </div>
          <div class="jacket-metric">
            <q-icon name="calculate" class="metric-icon" />
            <div>
              <span>Assessed Value</span>
              <strong>{{ money(currentTd(selected)?.assessed_value) }}</strong>
            </div>
          </div>
          <div class="jacket-metric">
            <q-icon name="history" class="metric-icon" />
            <div>
              <span>TD Count</span>
              <strong>{{ dossierCounts.tax_declarations }}</strong>
            </div>
          </div>
        </div>

          <section class="jacket-section td-navigator">
            <div class="section-head">
              <div class="section-head-content">
                <q-icon name="receipt_long" size="24px" color="primary" />
                <h3 class="section-title">Tax Declarations</h3>
                <q-badge outline color="primary" :label="`${taxDeclarationTimeline.length} total`" />
              </div>
              <div class="section-head-actions" v-if="canManage">
                <q-btn unelevated dense no-caps color="primary" icon="post_add" label="Add TD" @click="openDeclarationDialog" />
                <q-btn outline dense no-caps color="primary" icon="upload_file" label="Add File" @click="openDocumentDialog" />
                <q-btn v-if="selectedTdEntry" outline dense no-caps color="primary" icon="calculate" label="Add Assessment" @click="openAssessmentDialogForTd" />
              </div>
            </div>

            <div v-if="!taxDeclarationTimeline.length" class="empty-state">No tax declarations on record.</div>
            <div v-else class="td-layout">
              <div class="td-list">
                <button
                  v-for="(entry, index) in taxDeclarationTimeline"
                  :key="entry.tax_declaration?.id ?? index"
                  type="button"
                  class="td-card"
                  :class="{
                    'td-card--active': selectedTdId === entry.tax_declaration?.id,
                    'td-card--current': entry.tax_declaration.status === 'Active'
                  }"
                  @click="selectTd(entry)"
                >
                  <div class="td-card-year">
                    <span class="td-card-year-label">Year</span>
                    <strong>{{ entry.tax_declaration.effectivity_year }}</strong>
                  </div>
                  <div class="td-card-body">
                    <div class="td-card-top">
                      <strong class="td-card-number">{{ entry.tax_declaration.td_number }}</strong>
                      <span v-if="entry.tax_declaration.status === 'Active'" class="td-card-pill td-card-pill--active">ACTIVE</span>
                      <span v-else-if="entry.tax_declaration.status === 'Superseded'" class="td-card-pill td-card-pill--superseded">SUPERSEDED</span>
                      <span v-else-if="entry.tax_declaration.status === 'Cancelled'" class="td-card-pill td-card-pill--cancelled">CANCELLED</span>
                      <span v-else class="td-card-pill">{{ entry.tax_declaration.status }}</span>
                    </div>
                    <div class="td-card-meta">
                      <span><q-icon name="swap_horiz" size="12px" /> {{ entry.tax_declaration.transaction_type }}</span>
                      <span><q-icon name="folder" size="12px" /> {{ entry.document_count }} files</span>
                    </div>
                    <div class="td-card-owner">
                      <q-icon name="person" size="12px" />
                      {{ entry.tax_declaration.owner?.name || 'No owner' }}
                    </div>
                  </div>
                </button>
              </div>

              <div class="td-detail-panel" v-if="selectedTdEntry">
                <div class="td-detail-header">
                  <div>
                    <div class="section-kicker">Tax Declaration / FAAS</div>
                    <h4 class="td-detail-title">{{ selectedTdEntry.tax_declaration.td_number }}</h4>
                    <p class="td-detail-meta">
                      ARP No. {{ selectedTdEntry.tax_declaration.arp_number || '—' }}
                      · Effectivity {{ selectedTdEntry.tax_declaration.effectivity_year }}
                    </p>
                  </div>
                  <div class="td-detail-header-badges">
                    <span class="status-indicator" :class="`status-indicator--${statusKey(selectedTdEntry.tax_declaration.status)}`">
                      <span class="status-indicator-dot"></span>
                      {{ selectedTdEntry.tax_declaration.status }}
                    </span>
                    <span class="status-indicator status-indicator--neutral">
                      <q-icon name="swap_horiz" size="14px" />
                      {{ selectedTdEntry.tax_declaration.transaction_type || 'N/A' }}
                    </span>
                    <span v-if="selectedTdEntry.tax_declaration.approved_at" class="status-indicator status-indicator--approved">
                      <q-icon name="verified" size="14px" />
                      Approved {{ dateFormat(selectedTdEntry.tax_declaration.approved_at) }}
                    </span>
                  </div>
                </div>

                <!-- TD Tabs -->
                <q-tabs v-model="tdDetailTab" dense align="left" active-color="primary" class="td-tabs" narrow-indicator>
                  <q-tab name="info" icon="info" label="Info" />
                  <q-tab name="assessment" icon="calculate" label="Assessment" />
                  <q-tab name="docs" icon="folder" label="Documents" />
                  <q-tab name="trail" icon="history" label="Trail" />
                </q-tabs>

                <q-tab-panels v-model="tdDetailTab" animated class="td-tab-panels">
                  <!-- Tab: Info -->
                  <q-tab-panel name="info" class="td-tab-content">
                    <!-- VALUE HIGHLIGHT BANNER (most important info first) -->
                    <div class="faas-value-banner">
                      <div class="faas-banner-block">
                        <span>Market Value</span>
                        <strong>{{ money(selectedTdEntry.tax_declaration.market_value) }}</strong>
                      </div>
                      <div class="faas-banner-divider"></div>
                      <div class="faas-banner-block faas-banner-block--operator">
                        <q-icon name="close" size="20px" />
                      </div>
                      <div class="faas-banner-block">
                        <span>Assessment Level</span>
                        <strong>{{ selectedTdEntry.tax_declaration.assessment_level || 0 }}%</strong>
                      </div>
                      <div class="faas-banner-block faas-banner-block--operator">
                        <q-icon name="drag_handle" size="20px" />
                      </div>
                      <div class="faas-banner-block faas-banner-block--primary">
                        <span>Assessed Value</span>
                        <strong>{{ money(selectedTdEntry.tax_declaration.assessed_value) }}</strong>
                      </div>
                      <div class="faas-banner-divider"></div>
                      <div class="faas-banner-block">
                        <span>Taxability</span>
                        <strong :class="selectedTdEntry.tax_declaration.taxable === false ? 'text-grey-7' : 'text-positive'">
                          {{ selectedTdEntry.tax_declaration.taxable === false ? 'EXEMPT' : 'TAXABLE' }}
                        </strong>
                      </div>
                    </div>

                    <!-- GROUPED INFO CARDS -->
                    <div class="faas-info-grid">
                      <!-- OWNER CARD -->
                      <div class="faas-info-card">
                        <div class="faas-info-card-head">
                          <q-icon name="person" size="16px" />
                          <strong>Owner / Claimant</strong>
                          <q-btn v-if="selectedTdEntry.tax_declaration.owner?.id" flat dense no-caps size="sm" icon="open_in_new" label="View All" class="owner-view-all" @click="openOwnerDetail(selectedTdEntry.tax_declaration.owner.id)" />
                        </div>
                        <div class="faas-info-card-body">
                          <div class="faas-info-row">
                            <span>Name</span>
                            <strong>{{ selectedTdEntry.tax_declaration.owner?.name || '—' }}</strong>
                          </div>
                          <div class="faas-info-row">
                            <span>Address</span>
                            <strong>{{ selectedTdEntry.tax_declaration.owner?.address || '—' }}</strong>
                          </div>
                        </div>
                      </div>

                      <!-- PROPERTY ID CARD -->
                      <div class="faas-info-card">
                        <div class="faas-info-card-head">
                          <q-icon name="fingerprint" size="16px" />
                          <strong>Property Identification</strong>
                        </div>
                        <div class="faas-info-card-body">
                          <div class="faas-info-row">
                            <span>PIN</span>
                            <strong class="faas-mono">{{ selected.pin || '—' }}</strong>
                          </div>
                          <div class="faas-info-row">
                            <span>Lot No.</span>
                            <strong>{{ selected.lot_number || '—' }}</strong>
                          </div>
                          <div class="faas-info-row">
                            <span>Title No.</span>
                            <strong>{{ selected.title_number || '—' }}</strong>
                          </div>
                        </div>
                      </div>

                      <!-- LOCATION CARD -->
                      <div class="faas-info-card">
                        <div class="faas-info-card-head">
                          <q-icon name="place" size="16px" />
                          <strong>Location</strong>
                        </div>
                        <div class="faas-info-card-body">
                          <div class="faas-info-row">
                            <span>Barangay</span>
                            <strong>{{ selected.barangay || '—' }}</strong>
                          </div>
                          <div class="faas-info-row">
                            <span>Municipality</span>
                            <strong>{{ selected.municipality || '—' }}</strong>
                          </div>
                          <div class="faas-info-row">
                            <span>Province</span>
                            <strong>{{ selected.province || '—' }}</strong>
                          </div>
                        </div>
                      </div>

                      <!-- CLASSIFICATION CARD -->
                      <div class="faas-info-card">
                        <div class="faas-info-card-head">
                          <q-icon name="category" size="16px" />
                          <strong>Classification &amp; Use</strong>
                        </div>
                        <div class="faas-info-card-body">
                          <div class="faas-info-row">
                            <span>Class</span>
                            <strong>{{ selectedTdEntry.tax_declaration.classification || selected.classification || '—' }}</strong>
                          </div>
                          <div class="faas-info-row">
                            <span>Actual Use</span>
                            <strong>{{ selectedTdEntry.tax_declaration.actual_use || selected.actual_use || '—' }}</strong>
                          </div>
                          <div class="faas-info-row">
                            <span>Area</span>
                            <strong>{{ numberFormat(selected.land_area) }} {{ selected.unit_of_measure || 'sqm' }}</strong>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- PREVIOUS TD (only if exists) -->
                    <div v-if="selectedTdEntry.tax_declaration.previous_tax_declaration" class="faas-info-card faas-info-card--accent q-mt-md">
                      <div class="faas-info-card-head">
                        <q-icon name="history" size="16px" />
                        <strong>Cancels / Supersedes</strong>
                      </div>
                      <div class="faas-info-card-body faas-info-card-body--row">
                        <div class="faas-info-row">
                          <span>Previous TD</span>
                          <strong>{{ selectedTdEntry.tax_declaration.previous_tax_declaration.td_number }}</strong>
                        </div>
                        <div class="faas-info-row">
                          <span>Previous Owner</span>
                          <strong>{{ selectedTdEntry.tax_declaration.previous_tax_declaration.owner?.name || '—' }}</strong>
                        </div>
                      </div>
                    </div>

                    <!-- MEMORANDA -->
                    <div v-if="selectedTdEntry.tax_declaration.memoranda" class="faas-memo-card q-mt-md">
                      <div class="faas-memo-card-head">
                        <q-icon name="sticky_note_2" size="16px" />
                        <strong>Memoranda</strong>
                      </div>
                      <p>{{ selectedTdEntry.tax_declaration.memoranda }}</p>
                    </div>
                  </q-tab-panel>

                  <!-- Tab: Assessment -->
                  <!-- Tab: Assessment -->
                  <q-tab-panel name="assessment" class="td-tab-content">
                    <div v-if="selectedTdEntry.assessment_records?.length" class="assessment-list">
                      <article v-for="record in selectedTdEntry.assessment_records" :key="record.id" class="assess-card">
                        <header class="assess-card-head">
                          <div class="assess-card-title">
                            <q-icon :name="assessmentTypeIcon(record.assessment_type)" size="20px" />
                            <strong>{{ record.assessment_type }}</strong>
                          </div>
                          <div class="assess-card-pills">
                            <span class="td-card-pill" :class="record.taxable ? 'td-card-pill--active' : 'td-card-pill--superseded'">
                              {{ record.taxable ? 'TAXABLE' : 'EXEMPT' }}
                            </span>
                          </div>
                        </header>

                        <!-- Computation row at top -->
                        <div class="assess-formula">
                          <div class="assess-formula-block">
                            <span>Market Value</span>
                            <strong>{{ money(record.market_value) }}</strong>
                          </div>
                          <q-icon name="close" size="16px" class="assess-formula-op" />
                          <div class="assess-formula-block">
                            <span>Level</span>
                            <strong>{{ record.assessment_level || 0 }}%</strong>
                          </div>
                          <q-icon name="drag_handle" size="16px" class="assess-formula-op" />
                          <div class="assess-formula-block assess-formula-block--primary">
                            <span>Assessed Value</span>
                            <strong>{{ money(record.assessed_value) }}</strong>
                          </div>
                        </div>

                        <!-- Computation breakdown -->
                        <div class="assess-section">
                          <div class="assess-section-label">Computation Breakdown</div>
                          <div class="assess-rows">
                            <div class="assess-row"><span>Area</span><strong>{{ numberFormat(record.area) }} {{ record.unit_of_measure }}</strong></div>
                            <div class="assess-row"><span>Unit Value</span><strong>{{ money(record.unit_value) }}</strong></div>
                            <div class="assess-row"><span>Base Market Value</span><strong>{{ money(record.base_market_value) }}</strong></div>
                            <div class="assess-row" v-if="record.adjustment"><span>Adjustment</span><strong>{{ money(record.adjustment) }}</strong></div>
                            <div class="assess-row" v-if="record.depreciation_rate"><span>Depreciation</span><strong>{{ record.depreciation_rate }}%</strong></div>
                          </div>
                        </div>

                        <!-- Building details (if Building) -->
                        <div v-if="record.assessment_type === 'Building' && record.extra_attributes" class="assess-section">
                          <div class="assess-section-label"><q-icon name="apartment" size="14px" /> Building Details</div>
                          <div class="assess-rows">
                            <div class="assess-row" v-if="record.extra_attributes.kind_of_building"><span>Kind</span><strong>{{ record.extra_attributes.kind_of_building }}</strong></div>
                            <div class="assess-row" v-if="record.extra_attributes.structural_type"><span>Structure</span><strong>{{ record.extra_attributes.structural_type }}</strong></div>
                            <div class="assess-row" v-if="record.extra_attributes.building_condition"><span>Condition</span><strong>{{ record.extra_attributes.building_condition }}</strong></div>
                            <div class="assess-row" v-if="record.extra_attributes.building_age"><span>Age</span><strong>{{ record.extra_attributes.building_age }} yrs</strong></div>
                            <div class="assess-row" v-if="record.extra_attributes.number_of_storeys"><span>Storeys</span><strong>{{ record.extra_attributes.number_of_storeys }}</strong></div>
                            <div class="assess-row" v-if="record.extra_attributes.total_floor_area"><span>Floor Area</span><strong>{{ numberFormat(record.extra_attributes.total_floor_area) }} sqm</strong></div>
                            <div class="assess-row" v-if="record.extra_attributes.roof_type"><span>Roof</span><strong>{{ record.extra_attributes.roof_type }}</strong></div>
                            <div class="assess-row" v-if="record.extra_attributes.flooring_material"><span>Flooring</span><strong>{{ record.extra_attributes.flooring_material }}</strong></div>
                            <div class="assess-row" v-if="record.extra_attributes.wall_material"><span>Walls</span><strong>{{ record.extra_attributes.wall_material }}</strong></div>
                            <div class="assess-row" v-if="record.extra_attributes.building_permit_no"><span>Permit No.</span><strong>{{ record.extra_attributes.building_permit_no }}</strong></div>
                            <div class="assess-row" v-if="record.extra_attributes.cct_number"><span>CCT</span><strong>{{ record.extra_attributes.cct_number }}</strong></div>
                            <div class="assess-row" v-if="record.extra_attributes.date_constructed"><span>Constructed</span><strong>{{ dateFormat(record.extra_attributes.date_constructed) }}</strong></div>
                            <div class="assess-row" v-if="record.extra_attributes.date_completed"><span>Completed</span><strong>{{ dateFormat(record.extra_attributes.date_completed) }}</strong></div>
                            <div class="assess-row" v-if="record.extra_attributes.date_occupied"><span>Occupied</span><strong>{{ dateFormat(record.extra_attributes.date_occupied) }}</strong></div>
                          </div>
                        </div>

                        <!-- Machinery details (if Machinery) -->
                        <div v-if="record.assessment_type === 'Machinery' && record.extra_attributes" class="assess-section">
                          <div class="assess-section-label"><q-icon name="precision_manufacturing" size="14px" /> Machinery Details</div>
                          <div class="assess-rows">
                            <div class="assess-row" v-if="record.extra_attributes.kind_of_machinery"><span>Kind</span><strong>{{ record.extra_attributes.kind_of_machinery }}</strong></div>
                            <div class="assess-row" v-if="record.extra_attributes.brand"><span>Brand</span><strong>{{ record.extra_attributes.brand }}</strong></div>
                            <div class="assess-row" v-if="record.extra_attributes.model"><span>Model</span><strong>{{ record.extra_attributes.model }}</strong></div>
                            <div class="assess-row" v-if="record.extra_attributes.capacity"><span>Capacity</span><strong>{{ record.extra_attributes.capacity }}</strong></div>
                            <div class="assess-row" v-if="record.extra_attributes.acquisition_cost"><span>Acquisition</span><strong>{{ money(record.extra_attributes.acquisition_cost) }}</strong></div>
                            <div class="assess-row" v-if="record.extra_attributes.replacement_cost"><span>Replacement</span><strong>{{ money(record.extra_attributes.replacement_cost) }}</strong></div>
                            <div class="assess-row" v-if="record.extra_attributes.date_acquired"><span>Acquired</span><strong>{{ dateFormat(record.extra_attributes.date_acquired) }}</strong></div>
                            <div class="assess-row" v-if="record.extra_attributes.economic_life"><span>Economic Life</span><strong>{{ record.extra_attributes.economic_life }} yrs</strong></div>
                            <div class="assess-row" v-if="record.extra_attributes.years_used"><span>Years Used</span><strong>{{ record.extra_attributes.years_used }}</strong></div>
                            <div class="assess-row" v-if="record.extra_attributes.depreciation_percent"><span>Depreciation</span><strong>{{ record.extra_attributes.depreciation_percent }}%</strong></div>
                          </div>
                        </div>

                        <!-- Land details (if Land) -->
                        <div v-if="record.assessment_type === 'Land' && record.extra_attributes && (record.extra_attributes.sub_classification || record.extra_attributes.boundary_north || record.extra_attributes.boundary_south || record.extra_attributes.boundary_east || record.extra_attributes.boundary_west)" class="assess-section">
                          <div class="assess-section-label"><q-icon name="terrain" size="14px" /> Land Details</div>
                          <div v-if="record.extra_attributes.sub_classification" class="assess-rows">
                            <div class="assess-row"><span>Sub-Classification</span><strong>{{ record.extra_attributes.sub_classification }}</strong></div>
                          </div>
                          <div v-if="record.extra_attributes.boundary_north || record.extra_attributes.boundary_south || record.extra_attributes.boundary_east || record.extra_attributes.boundary_west" class="assess-boundaries">
                            <div class="boundary-cell boundary-cell--n"><span>N</span><strong>{{ record.extra_attributes.boundary_north || '—' }}</strong></div>
                            <div class="boundary-cell boundary-cell--w"><span>W</span><strong>{{ record.extra_attributes.boundary_west || '—' }}</strong></div>
                            <div class="boundary-cell boundary-cell--center"><q-icon name="explore" size="20px" /></div>
                            <div class="boundary-cell boundary-cell--e"><span>E</span><strong>{{ record.extra_attributes.boundary_east || '—' }}</strong></div>
                            <div class="boundary-cell boundary-cell--s"><span>S</span><strong>{{ record.extra_attributes.boundary_south || '—' }}</strong></div>
                          </div>
                        </div>

                        <p v-if="record.notes" class="assess-notes">
                          <q-icon name="info" size="14px" /> {{ record.notes }}
                        </p>

                        <footer class="assess-card-foot" v-if="canManage">
                          <q-btn outline dense no-caps color="primary" icon="edit" label="Edit" @click="openAssessmentEditDialog(selectedTdEntry.tax_declaration, record)" />
                          <q-btn outline dense no-caps color="negative" icon="delete" label="Remove" @click="removeAssessmentRecord(selectedTdEntry.tax_declaration, record)" />
                        </footer>
                      </article>
                    </div>
                    <div v-else class="empty-state compact">No assessment lines for this TD.</div>
                  </q-tab-panel>

                  <!-- Tab: Documents -->
                  <q-tab-panel name="docs" class="td-tab-content">
                    <div v-if="selectedTdEntry.documents?.length" class="docs-list">
                      <article v-for="document in selectedTdEntry.documents" :key="document.id" class="doc-card">
                        <div class="doc-card-icon">
                          <q-icon :name="documentTypeIcon(document.document_type)" size="24px" />
                        </div>
                        <div class="doc-card-body">
                          <div class="doc-card-top">
                            <strong>{{ document.document_type }}</strong>
                            <span class="td-card-pill" :class="docStatusPillClass(document.physical_copy_status)">
                              {{ document.physical_copy_status || 'On File' }}
                            </span>
                          </div>
                          <div class="doc-card-meta">
                            <span><q-icon name="tag" size="12px" /> {{ document.reference_number || document.file_name || '—' }}</span>
                            <span v-if="document.issued_at"><q-icon name="event" size="12px" /> {{ dateFormat(document.issued_at) }}</span>
                          </div>
                          <div class="doc-card-location" v-if="documentLocationLine(document)">
                            <q-icon name="inventory_2" size="12px" /> {{ documentLocationLine(document) }}
                          </div>
                        </div>
                        <div class="doc-card-actions">
                          <q-btn flat round dense color="primary" icon="visibility" @click="viewDocument(document)"><q-tooltip>View</q-tooltip></q-btn>
                          <q-btn flat round dense color="primary" icon="download" @click="downloadDocument(document)"><q-tooltip>Download</q-tooltip></q-btn>
                          <q-btn v-if="canManage" flat round dense color="primary" icon="edit" @click="openEditDocumentDialog(document)"><q-tooltip>Edit</q-tooltip></q-btn>
                          <q-btn v-if="canDeleteDocument" flat round dense color="negative" icon="delete" @click="confirmDeleteDocument(document)"><q-tooltip>Delete</q-tooltip></q-btn>
                        </div>
                      </article>
                    </div>
                    <div v-else class="empty-state compact">No documents linked to this TD.</div>
                  </q-tab-panel>

                  <!-- Tab: Trail -->
                  <q-tab-panel name="trail" class="td-tab-content">
                    <div v-if="selectedTdEntry.data_entry_events?.length" class="trail-timeline">
                      <div v-for="(log, index) in selectedTdEntry.data_entry_events" :key="log.id" class="trail-event">
                        <div class="trail-marker">
                          <q-icon :name="trailEventIcon(log.action)" size="14px" />
                        </div>
                        <div class="trail-content">
                          <div class="trail-title">{{ log.description }}</div>
                          <div class="trail-meta">
                            <span><q-icon name="person" size="12px" /> {{ log.user?.name || 'System' }}</span>
                            <span><q-icon name="schedule" size="12px" /> {{ dateFormat(log.created_at) }}</span>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div v-else class="empty-state compact">No data entry events for this TD.</div>
                  </q-tab-panel>
                </q-tab-panels>

                <!-- Actions -->
                <div class="td-detail-footer" v-if="canApprove || canManage">
                  <q-btn v-if="canManage" outline dense no-caps color="primary" icon="edit" label="Edit TD" @click="openEditDeclarationDialog(selectedTdEntry.tax_declaration)" />
                  <q-btn v-if="canApprove && selectedTdEntry.tax_declaration.status !== 'Active'" unelevated dense no-caps color="positive" icon="verified" label="Approve" @click="approveDeclaration(selectedTdEntry.tax_declaration)" />
                  <q-btn v-if="canManage && selectedTdEntry.tax_declaration.status !== 'Cancelled'" outline dense no-caps color="negative" icon="block" label="Cancel" @click="archiveDeclaration(selectedTdEntry.tax_declaration)" />
                  <q-btn v-if="canDeleteTd" outline dense no-caps color="negative" icon="delete_forever" label="Delete" @click="deleteDeclaration(selectedTdEntry.tax_declaration)" />
                </div>
              </div>

              <div v-else class="td-detail-panel td-detail-panel--empty">
                <q-icon name="touch_app" size="40px" color="blue-grey-4" />
                <strong>Select a tax declaration</strong>
                <span>Choose a TD from the list to view assessments, documents, and audit history.</span>
              </div>
            </div>
          </section>

          <!-- Compact bottom section: activity drawer button -->
          <div class="record-bottom-strip">
            <div class="bottom-strip-actions">
              <q-btn outline dense no-caps color="primary" icon="history" label="View Activity Log" @click="activityDrawer = true" />
            </div>
          </div>
        </aside>

      <section class="ws-empty ws-record-panel" v-else>
        <div class="empty-icon-wrapper">
          <q-icon name="folder_open" size="64px" />
        </div>
        <div>
          <span> Click on any property record from the search results to view complete details.</span>
        </div>
      </section>
      </div>

    <!-- Quick Actions FAB (bottom-left) -->
    <q-page-sticky v-if="selected && canManage" position="bottom-left" :offset="[24, 24]">
      <q-fab icon="bolt" color="primary" direction="up" vertical-actions-align="left">
        <q-fab-action color="primary" icon="post_add" label="Add TD" label-position="right" external-label @click="openDeclarationDialog" />
        <q-fab-action color="primary" icon="upload_file" label="Add File" label-position="right" external-label @click="openDocumentDialog" />
        <q-fab-action v-if="selectedTdEntry" color="primary" icon="calculate" label="Add Assessment" label-position="right" external-label @click="openAssessmentDialogForTd" />
        <q-fab-action color="primary" icon="edit" label="Edit Property" label-position="right" external-label @click="openEditPropertyDialog" />
        <q-fab-action color="primary" icon="print" label="Print" label-position="right" external-label @click="printRecord" />
      </q-fab>
    </q-page-sticky>

    <!-- Back to Top FAB (bottom-right) -->
    <q-page-sticky v-if="selected" position="bottom-right" :offset="[24, 24]">
      <q-btn fab icon="arrow_upward" color="primary" @click="scrollToSearch">
        <q-tooltip anchor="top middle" self="bottom middle">Back to Search</q-tooltip>
      </q-btn>
    </q-page-sticky>

    <!-- Owner Detail Dialog -->
    <q-dialog v-model="ownerDialog">
      <q-card class="owner-card">
        <q-card-section class="owner-card-header">
          <div class="owner-card-title">
            <q-icon name="person" size="24px" />
            <div>
              <strong>{{ ownerDetail?.owner?.name || 'Owner Details' }}</strong>
              <span v-if="ownerDetail?.owner?.address">{{ ownerDetail.owner.address }}</span>
            </div>
          </div>
          <q-btn flat round icon="close" color="white" v-close-popup />
        </q-card-section>

        <q-card-section v-if="ownerDetail" class="owner-card-stats">
          <div class="owner-stat">
            <span>Total Properties</span>
            <strong>{{ ownerDetail.stats.total_properties }}</strong>
          </div>
          <div class="owner-stat">
            <span>All TDs</span>
            <strong>{{ ownerDetail.stats.total_tax_declarations }}</strong>
          </div>
          <div class="owner-stat">
            <span>Active TDs</span>
            <strong>{{ ownerDetail.stats.active_tax_declarations }}</strong>
          </div>
          <div class="owner-stat owner-stat--primary">
            <span>Total Assessed Value</span>
            <strong>{{ money(ownerDetail.stats.total_assessed_value) }}</strong>
          </div>
        </q-card-section>

        <q-card-section v-if="ownerDetail" class="owner-card-body">
          <div class="owner-properties-title">Properties Owned</div>
          <div v-if="!ownerDetail.properties.length" class="empty-state compact">No properties found.</div>
          <div v-else class="owner-properties">
            <article v-for="prop in ownerDetail.properties" :key="prop.id" class="owner-property-card" @click="goToProperty(prop.id)">
              <div class="owner-property-icon">
                <q-icon :name="propertyKindIcon[prop.property_kind] || 'home_work'" size="24px" />
              </div>
              <div class="owner-property-body">
                <div class="owner-property-top">
                  <strong>{{ prop.lot_number || prop.pin }}</strong>
                  <span class="td-card-pill" :class="prop.status === 'Active' ? 'td-card-pill--active' : ''">{{ prop.status }}</span>
                </div>
                <div class="owner-property-meta">
                  <span><q-icon name="fingerprint" size="12px" /> {{ prop.pin }}</span>
                  <span><q-icon name="place" size="12px" /> {{ prop.barangay }}, {{ prop.municipality }}</span>
                  <span><q-icon name="straighten" size="12px" /> {{ numberFormat(prop.land_area) }} {{ prop.unit_of_measure }}</span>
                </div>
                <div class="owner-property-tds">
                  <q-icon name="receipt_long" size="12px" />
                  <strong>{{ prop.tax_declarations.length }}</strong>
                  TD{{ prop.tax_declarations.length > 1 ? 's' : '' }}
                </div>
              </div>
              <q-icon name="chevron_right" size="20px" class="owner-property-arrow" />
            </article>
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Activity Log Drawer -->
    <q-dialog v-model="activityDrawer" position="right">
      <q-card class="activity-drawer">
        <q-card-section class="activity-drawer-header">
          <div class="activity-drawer-title">
            <q-icon name="history" size="22px" />
            <strong>Activity Log</strong>
          </div>
          <div class="activity-drawer-actions">
            <q-btn v-if="selected" flat dense no-caps color="white" icon="download" label="Export" @click="exportActivityCsv" />
            <q-btn flat round icon="close" color="white" v-close-popup />
          </div>
        </q-card-section>
        <q-card-section class="activity-drawer-body">
          <q-list bordered separator v-if="filteredDataEntryTimeline.length" class="ui-list">
            <q-item v-for="log in filteredDataEntryTimeline" :key="log.id">
              <q-item-section avatar><q-icon color="primary" name="task_alt" /></q-item-section>
              <q-item-section>
                <q-item-label>{{ log.description }}</q-item-label>
                <q-item-label caption>{{ log.user?.name || 'System' }} · {{ dateFormat(log.created_at) }}</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
          <div v-else class="empty-state compact">No activity recorded yet.</div>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="movementHistoryDialog">
      <q-card class="entry-card compact-card">
        <q-card-section class="row items-center justify-between">
          <div>
            <div class="text-h6">Physical movement history</div>
            <div class="text-caption text-blue-grey-7">{{ selectedDocument?.reference_number || selectedDocument?.file_name }}</div>
          </div>
          <q-btn flat round icon="close" v-close-popup />
        </q-card-section>
        <q-separator />
        <q-card-section>
          <q-inner-loading :showing="movementHistoryLoading" />
          <q-list v-if="movementHistory.length" bordered separator>
            <q-item v-for="movement in movementHistory" :key="movement.id">
              <q-item-section>
                <q-item-label>{{ movement.movement_type }} · {{ movement.to_location || movement.from_location }}</q-item-label>
                <q-item-label caption>{{ movement.user?.name || 'System' }} · {{ dateFormat(movement.moved_at || movement.created_at) }}</q-item-label>
                <q-item-label caption>{{ movement.remarks || 'No remarks' }}</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
          <div v-else class="empty-state compact">No movement history recorded.</div>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="entryDialog" persistent>
      <q-card class="entry-card entry-card--wide">
        <q-card-section class="entry-card-header">
          <div class="entry-card-header-content">
            <q-icon :name="propertyKindIcon[form.property_kind]" size="28px" color="white" />
            <div>
              <div class="entry-card-title">New {{ form.property_kind }} Property</div>
              <div class="entry-card-subtitle">Register a new {{ form.property_kind.toLowerCase() }} record with initial tax declaration</div>
            </div>
          </div>
          <q-btn flat round icon="close" color="white" aria-label="Close" v-close-popup />
        </q-card-section>

        <q-card-section class="entry-card-body">
          <q-form @submit.prevent="saveEntry">
            <!-- Property Kind Selector -->
            <div class="form-section">
              <div class="form-section-title">
                <q-icon name="category" size="18px" />
                Property Type
              </div>
              <div class="property-kind-selector">
                <button
                  type="button"
                  class="kind-card"
                  :class="{ 'kind-card--active': form.property_kind === 'Land' }"
                  @click="form.property_kind = 'Land'"
                >
                  <q-icon name="landscape" size="32px" />
                  <strong>Land</strong>
                  <span>Lot, parcel, agricultural area</span>
                </button>
                <button
                  type="button"
                  class="kind-card"
                  :class="{ 'kind-card--active': form.property_kind === 'Building' }"
                  @click="form.property_kind = 'Building'"
                >
                  <q-icon name="apartment" size="32px" />
                  <strong>Building</strong>
                  <span>House, commercial structure</span>
                </button>
                <button
                  type="button"
                  class="kind-card"
                  :class="{ 'kind-card--active': form.property_kind === 'Machinery' }"
                  @click="form.property_kind = 'Machinery'"
                >
                  <q-icon name="precision_manufacturing" size="32px" />
                  <strong>Machinery</strong>
                  <span>Equipment, installations</span>
                </button>
              </div>
            </div>

            <!-- Property Identification -->
            <div class="form-section">
              <div class="form-section-title">
                <q-icon name="location_on" size="18px" />
                Property Identification
              </div>
              <div class="form-grid form-grid--3col">
                <q-input v-model="form.pin" outlined dense label="PIN (Property Index No.)" hint="Format: XXX-XX-XXX-XXX-XXX" maxlength="18" @update:model-value="onPinInput(form, 'pin')" />
                <q-input v-if="form.property_kind === 'Land'" v-model="form.lot_number" outlined dense label="Lot Number" />
                <q-input v-if="form.property_kind === 'Land'" v-model="form.survey_number" outlined dense label="Survey Number" />
                <q-input v-if="form.property_kind === 'Land'" v-model="form.title_number" outlined dense label="Title Number (OCT/TCT)" />
                <q-input v-if="form.property_kind !== 'Land'" v-model="form.land_pin_reference" outlined dense label="Land PIN (where this is located)" />
                <q-input v-model="form.barangay" outlined dense label="Barangay" />
                <q-select v-model="form.municipality" outlined dense label="Municipality" :options="options.municipalities" />
              </div>
            </div>

            <!-- LAND-specific fields -->
            <div v-if="form.property_kind === 'Land'" class="form-section">
              <div class="form-section-title">
                <q-icon name="landscape" size="18px" />
                Land Details
              </div>
              <div class="form-grid form-grid--3col">
                <q-select v-model="form.classification" outlined dense label="Classification" :options="options.classifications" />
                <q-select v-model="form.extra.sub_classification" outlined dense clearable label="Sub-Classification" :options="landSubClassifications" />
                <q-input v-model="form.actual_use" outlined dense label="Actual Use" />
                <q-input v-model.number="form.land_area" type="number" outlined dense label="Land Area" />
                <q-select v-model="form.unit_of_measure" outlined dense label="Unit" :options="['sqm', 'hectares']" />
              </div>
              <div class="form-section-subtitle q-mt-md">Boundaries</div>
              <div class="form-grid form-grid--3col">
                <q-input v-model="form.extra.boundary_north" outlined dense label="North" />
                <q-input v-model="form.extra.boundary_east" outlined dense label="East" />
                <q-input v-model="form.extra.boundary_south" outlined dense label="South" />
                <q-input v-model="form.extra.boundary_west" outlined dense label="West" />
              </div>
            </div>

            <!-- BUILDING-specific fields -->
            <div v-if="form.property_kind === 'Building'" class="form-section">
              <div class="form-section-title">
                <q-icon name="apartment" size="18px" />
                Building Details
              </div>
              <div class="form-grid form-grid--3col">
                <q-select v-model="form.extra.kind_of_building" outlined dense label="Kind of Building" :options="['Residential', 'Commercial', 'Industrial', 'Agricultural', 'Institutional', 'Mixed Use']" />
                <q-select v-model="form.extra.structural_type" outlined dense label="Structural Type" :options="['Concrete', 'Semi-Concrete', 'Wood', 'Mixed', 'Steel', 'Other']" />
                <q-select v-model="form.extra.building_condition" outlined dense label="Condition" :options="['Excellent', 'Good', 'Fair', 'Poor', 'Dilapidated']" />
                <q-input v-model="form.extra.building_permit_no" outlined dense label="Building Permit No." />
                <q-input v-model="form.extra.cct_number" outlined dense label="CCT No. (Condo)" />
                <q-input v-model="form.actual_use" outlined dense label="Actual Use" />
                <q-input v-model="form.extra.date_constructed" outlined dense type="date" label="Date Constructed" />
                <q-input v-model="form.extra.date_completed" outlined dense type="date" label="Date Completed" />
                <q-input v-model="form.extra.date_occupied" outlined dense type="date" label="Date Occupied" />
                <q-input v-model.number="form.extra.number_of_storeys" outlined dense type="number" label="No. of Storeys" />
                <q-input v-model.number="form.extra.total_floor_area" outlined dense type="number" label="Total Floor Area (sqm)" />
                <q-select v-model="form.extra.roof_type" outlined dense label="Roofing" :options="['G.I. Sheet', 'Concrete Slab', 'Tile', 'Asphalt Shingle', 'Nipa', 'Other']" />
              </div>
            </div>

            <!-- MACHINERY-specific fields -->
            <div v-if="form.property_kind === 'Machinery'" class="form-section">
              <div class="form-section-title">
                <q-icon name="precision_manufacturing" size="18px" />
                Machinery Details
              </div>
              <div class="form-grid form-grid--3col">
                <q-input v-model="form.extra.kind_of_machinery" outlined dense label="Kind of Machinery" />
                <q-input v-model="form.extra.brand" outlined dense label="Brand" />
                <q-input v-model="form.extra.model" outlined dense label="Model" />
                <q-input v-model="form.extra.capacity" outlined dense label="Capacity / Output" />
                <q-input v-model="form.extra.date_acquired" outlined dense type="date" label="Date Acquired" />
                <q-input v-model.number="form.extra.economic_life" outlined dense type="number" label="Economic Life (yrs)" />
                <q-input v-model.number="form.extra.acquisition_cost" outlined dense type="number" label="Acquisition Cost (₱)" prefix="₱" />
                <q-input v-model.number="form.extra.replacement_cost" outlined dense type="number" label="Replacement Cost (₱)" prefix="₱" />
                <q-input v-model="form.actual_use" outlined dense label="Actual Use" />
              </div>
            </div>

            <!-- Owner Information -->
            <div class="form-section">
              <div class="form-section-title">
                <q-icon name="person" size="18px" />
                Owner / Claimant
              </div>
              <div class="form-grid">
                <q-input v-model="form.owner.name" outlined dense label="Owner / Claimant Name" />
                <q-input v-model="form.owner.address" outlined dense label="Owner Address" />
              </div>
            </div>

            <!-- Initial Tax Declaration -->
            <div class="form-section">
              <div class="form-section-title">
                <q-icon name="receipt_long" size="18px" />
                Initial Tax Declaration
              </div>
              <div class="form-grid form-grid--3col">
                <q-input v-model="form.tax_declaration.td_number" outlined dense label="TD Number" :rules="[v => !!v || 'Required']" class="required-field" />
                <q-input v-model="form.tax_declaration.arp_number" outlined dense label="ARP Number" />
                <q-input v-model.number="form.tax_declaration.effectivity_year" type="number" outlined dense label="Effectivity Year" />
                <q-select v-model="form.tax_declaration.transaction_type" outlined dense label="Transaction Type" :options="newPropertyTransactionTypes" />
                <q-select v-model="form.tax_declaration.status" outlined dense label="TD Status" :options="options.statuses" />
              </div>
            </div>

            <!-- Assessment Values -->
            <div class="form-section">
              <div class="form-section-title">
                <q-icon name="calculate" size="18px" />
                Assessment Values
              </div>
              <div class="form-grid form-grid--3col">
                <q-input v-model.number="form.tax_declaration.market_value" type="number" outlined dense label="Market Value (₱)" prefix="₱" />
                <q-input v-model.number="form.tax_declaration.assessed_value" type="number" outlined dense label="Assessed Value (₱)" prefix="₱" />
                <q-input v-model.number="form.tax_declaration.assessment_level" type="number" outlined dense label="Assessment Level (%)" suffix="%" />
              </div>
            </div>

            <!-- Remarks -->
            <div class="form-section">
              <div class="form-section-title">
                <q-icon name="notes" size="18px" />
                Remarks / Memoranda
              </div>
              <q-input v-model="form.remarks" outlined dense type="textarea" label="Property remarks or notes" autogrow />
            </div>

            <div class="form-actions">
              <span v-if="draftSaved" class="draft-saved-badge">
                <q-icon name="cloud_done" size="14px" /> Draft saved
              </span>
              <q-btn flat no-caps label="Cancel" v-close-popup />
              <q-btn unelevated no-caps color="primary" icon="save" :label="`Save ${form.property_kind}`" type="submit" :loading="saving" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="editPropertyDialog" persistent>
      <q-card class="entry-card entry-card--wide">
        <q-card-section class="entry-card-header">
          <div class="entry-card-header-content">
            <q-icon name="edit_location_alt" size="28px" color="white" />
            <div>
              <div class="entry-card-title">Edit Property Master Record</div>
              <div class="entry-card-subtitle">{{ selected?.pin }} · {{ selected?.lot_number }}</div>
            </div>
          </div>
          <q-btn flat round icon="close" color="white" aria-label="Close" v-close-popup />
        </q-card-section>

        <q-card-section class="entry-card-body">
          <q-form @submit.prevent="savePropertyEdit">
            <div class="form-section">
              <div class="form-section-title"><q-icon name="location_on" size="18px" />Property Identification</div>
              <div class="form-grid form-grid--3col">
                <q-input v-model="editPropertyForm.pin" outlined dense label="PIN (Property Index Number)" hint="Format: XXX-XX-XXX-XXX-XXX" maxlength="18" @update:model-value="onPinInput(editPropertyForm, 'pin')" />
                <q-input v-model="editPropertyForm.lot_number" outlined dense label="Lot Number" />
                <q-input v-model="editPropertyForm.survey_number" outlined dense label="Survey Number" />
                <q-input v-model="editPropertyForm.title_number" outlined dense label="Title Number (OCT/TCT)" />
                <q-input v-model="editPropertyForm.barangay" outlined dense label="Barangay" />
                <q-select v-model="editPropertyForm.municipality" outlined dense label="Municipality" :options="options.municipalities" />
              </div>
            </div>
            <div class="form-section">
              <div class="form-section-title"><q-icon name="landscape" size="18px" />Property Details</div>
              <div class="form-grid form-grid--3col">
                <q-select v-model="editPropertyForm.classification" outlined dense label="Classification" :options="options.classifications" />
                <q-input v-model="editPropertyForm.actual_use" outlined dense label="Actual Use" />
                <q-input v-model.number="editPropertyForm.land_area" type="number" outlined dense label="Land Area" />
                <q-input v-model="editPropertyForm.unit_of_measure" outlined dense label="Unit of Measure" />
                <q-select v-model="editPropertyForm.status" outlined dense label="Status" :options="options.statuses" />
              </div>
            </div>
            <div class="form-section">
              <div class="form-section-title"><q-icon name="notes" size="18px" />Remarks</div>
              <q-input v-model="editPropertyForm.remarks" outlined dense type="textarea" label="Property remarks" autogrow />
            </div>
            <div class="form-actions">
              <q-btn flat no-caps label="Cancel" v-close-popup />
              <q-btn unelevated no-caps color="primary" icon="save" label="Save Changes" type="submit" :loading="saving" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="declarationDialog" persistent>
      <q-card class="entry-card entry-card--wide">
        <q-card-section class="entry-card-header">
          <div class="entry-card-header-content">
            <q-icon name="receipt_long" size="28px" color="white" />
            <div>
              <div class="entry-card-title">{{ declarationDialogTitle }}</div>
              <div class="entry-card-subtitle">{{ selected?.pin }} · {{ selected?.lot_number }} · {{ selected?.municipality }}</div>
              <div v-if="!editingDeclarationId && currentTd(selected)" class="entry-card-subtitle">Supersedes: {{ currentTd(selected)?.td_number }} ({{ currentTd(selected)?.owner?.name }})</div>
            </div>
          </div>
          <q-btn flat round icon="close" color="white" aria-label="Close" v-close-popup />
        </q-card-section>

        <q-card-section class="entry-card-body">
          <q-form @submit.prevent="saveDeclaration">
            <!-- Owner Information -->
            <div class="form-section">
              <div class="form-section-title">
                <q-icon name="person" size="18px" />
                Owner / Claimant
                <span v-if="declarationForm.transaction_type === 'Transfer'" class="form-section-hint">(Enter the NEW owner for this transfer)</span>
              </div>
              <q-banner v-if="declarationForm.transaction_type === 'Transfer' && currentTd(selected)" dense rounded class="q-mb-sm" style="background: rgba(47, 98, 175, 0.06); border: 1px solid rgba(47, 98, 175, 0.14); border-radius: 10px;">
                <template #avatar><q-icon name="swap_horiz" color="primary" /></template>
                <span style="font-size: 0.85rem; color: #162742;">
                  <strong>Previous Owner:</strong> {{ currentTd(selected)?.owner?.name || 'Unknown' }}
                  <span v-if="currentTd(selected)?.owner?.address"> · {{ currentTd(selected)?.owner?.address }}</span>
                </span>
              </q-banner>
              <div class="form-grid">
                <q-input v-model="declarationForm.owner.name" outlined dense label="New Owner / Claimant Name" :rules="[v => !!v || 'Required']" class="required-field" :hint="declarationForm.transaction_type === 'Transfer' ? 'Enter the new owner (buyer/transferee)' : ''" />
                <q-input v-model="declarationForm.owner.address" outlined dense label="New Owner Address" />
              </div>
            </div>

            <!-- TD Identification -->
            <div class="form-section">
              <div class="form-section-title">
                <q-icon name="badge" size="18px" />
                TD Identification
              </div>
              <div class="form-grid form-grid--3col">
                <q-input v-model="declarationForm.td_number" outlined dense label="TD Number" :rules="[v => !!v || 'Required']" class="required-field" />
                <q-input v-model="declarationForm.arp_number" outlined dense label="ARP Number" />
                <q-input v-model.number="declarationForm.effectivity_year" type="number" outlined dense label="Effectivity Year" :rules="[v => !!v || 'Required']" class="required-field" />
              </div>
            </div>

            <!-- Transaction & Status -->
            <div class="form-section">
              <div class="form-section-title">
                <q-icon name="swap_horiz" size="18px" />
                Transaction & Status
              </div>
              <div class="form-grid form-grid--3col">
                <q-select v-model="declarationForm.transaction_type" outlined dense label="Transaction Type" :options="declarationTransactionTypes" />
                <q-select v-model="declarationForm.status" outlined dense label="TD Status" :options="options.statuses" />
                <q-select v-model="declarationForm.classification" outlined dense label="Classification" :options="options.classifications" />
              </div>
            </div>

            <!-- Appraisal & Assessment -->
            <div class="form-section">
              <div class="form-section-title">
                <q-icon name="calculate" size="18px" />
                Appraisal & Assessment
              </div>
              <div class="form-grid form-grid--3col">
                <q-input v-model="declarationForm.actual_use" outlined dense label="Actual Use" />
                <q-input v-model.number="declarationForm.market_value" type="number" outlined dense label="Market Value (₱)" prefix="₱" />
                <q-input v-model.number="declarationForm.assessed_value" type="number" outlined dense label="Assessed Value (₱)" prefix="₱" />
                <q-input v-model.number="declarationForm.assessment_level" type="number" outlined dense label="Assessment Level (%)" suffix="%" />
              </div>
            </div>

            <!-- Memoranda -->
            <div class="form-section">
              <div class="form-section-title">
                <q-icon name="notes" size="18px" />
                Memoranda
              </div>
              <q-input v-model="declarationForm.memoranda" outlined dense type="textarea" label="Memoranda / Notes" autogrow />
            </div>

            <div class="form-actions">
              <q-btn flat no-caps label="Cancel" v-close-popup />
              <q-btn unelevated no-caps color="primary" icon="save" :label="declarationSubmitLabel" type="submit" :loading="saving" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="documentDialog" persistent>
      <q-card class="entry-card entry-card--wide">
        <q-card-section class="entry-card-header">
          <div class="entry-card-header-content">
            <q-icon name="upload_file" size="28px" color="white" />
            <div>
              <div class="entry-card-title">Register Document / Physical Record</div>
              <div class="entry-card-subtitle">{{ selected?.pin }} · {{ selected?.lot_number }}</div>
            </div>
          </div>
          <q-btn flat round icon="close" color="white" aria-label="Close" v-close-popup />
        </q-card-section>

        <q-card-section class="entry-card-body">
          <q-form @submit.prevent="saveDocument">
            <div class="form-section">
              <div class="form-section-title"><q-icon name="link" size="18px" />Document Information</div>
              <div class="form-grid form-grid--3col">
                <q-select v-model="documentForm.tax_declaration_id" outlined dense clearable emit-value map-options label="Related TD" :options="taxDeclarationOptions" />
                <q-select v-model="documentForm.document_type" outlined dense label="Document Type" :options="options.documentTypes" />
                <q-input v-model="documentForm.reference_number" outlined dense label="Reference Number" />
                <q-input v-model="documentForm.issued_at" outlined dense type="date" label="Issued Date" />
              </div>
            </div>
            <div class="form-section">
              <div class="form-section-title"><q-icon name="attach_file" size="18px" />File Upload</div>
              <q-file v-model="documentForm.file" outlined dense label="Scanned file (optional)">
                <template #prepend><q-icon name="attach_file" /></template>
              </q-file>
              <q-input v-model="documentForm.notes" outlined dense type="textarea" label="Notes" autogrow class="q-mt-sm" />
            </div>
            <div class="form-actions">
              <q-btn flat no-caps label="Cancel" v-close-popup />
              <q-btn unelevated no-caps color="primary" icon="save" label="Save Document" type="submit" :loading="saving" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="editDocumentDialog" persistent>
      <q-card class="entry-card entry-card--wide">
        <q-card-section class="entry-card-header">
          <div class="entry-card-header-content">
            <q-icon name="edit_note" size="28px" color="white" />
            <div>
              <div class="entry-card-title">Edit Document / Physical Record</div>
              <div class="entry-card-subtitle">{{ selectedDocument?.reference_number || selectedDocument?.file_name }}</div>
            </div>
          </div>
          <q-btn flat round icon="close" color="white" aria-label="Close" v-close-popup />
        </q-card-section>

        <q-card-section class="entry-card-body">
          <q-form @submit.prevent="saveDocumentEdit">
            <div class="form-section">
              <div class="form-section-title"><q-icon name="link" size="18px" />Document Information</div>
              <div class="form-grid form-grid--3col">
                <q-select v-model="editDocumentForm.tax_declaration_id" outlined dense clearable emit-value map-options label="Related TD" :options="taxDeclarationOptions" />
                <q-select v-model="editDocumentForm.document_type" outlined dense label="Document Type" :options="options.documentTypes" />
                <q-input v-model="editDocumentForm.reference_number" outlined dense label="Reference Number" />
                <q-input v-model="editDocumentForm.issued_at" outlined dense type="date" label="Issued Date" />
              </div>
            </div>
            <div class="form-section">
              <div class="form-section-title"><q-icon name="notes" size="18px" />Notes</div>
              <q-input v-model="editDocumentForm.notes" outlined dense type="textarea" label="Notes" autogrow />
            </div>
            <div class="form-actions">
              <q-btn flat no-caps label="Cancel" v-close-popup />
              <q-btn unelevated no-caps color="primary" icon="save" label="Save Changes" type="submit" :loading="saving" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="movementDialog" persistent>
      <q-card class="entry-card">
        <q-card-section class="entry-card-header">
          <div class="entry-card-header-content">
            <q-icon name="sync_alt" size="28px" color="white" />
            <div>
              <div class="entry-card-title">Physical Record Movement</div>
              <div class="entry-card-subtitle">{{ selectedDocument?.reference_number || selectedDocument?.file_name }}</div>
            </div>
          </div>
          <q-btn flat round icon="close" color="white" aria-label="Close" v-close-popup />
        </q-card-section>

        <q-card-section class="entry-card-body">
          <q-form @submit.prevent="saveMovement">
            <div class="form-section">
              <div class="form-section-title"><q-icon name="swap_horiz" size="18px" />Movement Details</div>
              <div class="form-grid">
                <q-select v-model="movementForm.movement_type" outlined dense label="Movement Type" :options="movementTypes" />
                <q-select v-model="movementForm.to_status" outlined dense label="New Physical Status" :options="options.physicalStatuses" />
                <q-input v-model="movementForm.to_location" outlined dense label="New Storage Location" />
                <q-input v-model="movementForm.to_box_number" outlined dense label="New Box Number" />
                <q-input v-model="movementForm.to_folder_number" outlined dense label="New Folder Number" />
                <q-input v-model="movementForm.custodian" outlined dense label="Custodian" />
                <q-input v-model="movementForm.released_to" outlined dense label="Released To / Borrower" />
                <q-input v-model="movementForm.movement_date" outlined dense type="date" label="Movement Date" />
                <q-input v-model="movementForm.expected_return_at" outlined dense type="date" label="Expected Return" />
                <q-input v-model="movementForm.returned_at" outlined dense type="date" label="Returned Date" />
              </div>
            </div>
            <div class="form-section">
              <div class="form-section-title"><q-icon name="notes" size="18px" />Remarks</div>
              <q-input v-model="movementForm.remarks" outlined dense type="textarea" label="Remarks" autogrow />
            </div>
            <div class="form-actions">
              <q-btn flat no-caps label="Cancel" v-close-popup />
              <q-btn unelevated no-caps color="primary" icon="save" label="Save Movement" type="submit" :loading="saving" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="assessmentDialog" persistent>
      <q-card class="entry-card entry-card--wide">
        <q-card-section class="entry-card-header">
          <div class="entry-card-header-content">
            <q-icon name="calculate" size="28px" color="white" />
            <div>
              <div class="entry-card-title">{{ assessmentDialogTitle }}</div>
              <div class="entry-card-subtitle">{{ selected?.pin }} · {{ selected?.lot_number }}</div>
            </div>
          </div>
          <q-btn flat round icon="close" color="white" aria-label="Close" v-close-popup />
        </q-card-section>

        <q-card-section class="entry-card-body">
          <q-form @submit.prevent="saveAssessment">
            <div class="form-section">
              <div class="form-section-title"><q-icon name="link" size="18px" />Assessment Link</div>
              <div class="form-grid">
                <q-select v-model="assessmentForm.tax_declaration_id" outlined dense emit-value map-options label="Related TD" :options="taxDeclarationOptions" />
                <q-select v-model="assessmentForm.assessment_type" outlined dense label="Assessment Type" :options="options.assessmentTypes" />
              </div>
            </div>
            <div class="form-section">
              <div class="form-section-title"><q-icon name="category" size="18px" />Classification & Use</div>
              <div class="form-grid form-grid--3col">
                <q-select v-model="assessmentForm.classification" outlined dense label="Classification" :options="options.classifications" />
                <q-input v-model="assessmentForm.actual_use" outlined dense label="Actual Use" />
                <q-select v-model="assessmentForm.taxable" outlined dense emit-value map-options label="Taxable" :options="taxableOptions" />
              </div>
            </div>
            <div class="form-section">
              <div class="form-section-title"><q-icon name="straighten" size="18px" />Area & Unit Value</div>
              <div class="form-grid form-grid--3col">
                <q-input v-model.number="assessmentForm.area" outlined dense type="number" label="Area" />
                <q-input v-model="assessmentForm.unit_of_measure" outlined dense label="Unit of Measure" />
                <q-input v-model.number="assessmentForm.unit_value" outlined dense type="number" label="Unit Value (₱)" prefix="₱" />
              </div>
            </div>
            <div class="form-section">
              <div class="form-section-title"><q-icon name="calculate" size="18px" />Computation</div>
              <div class="form-grid form-grid--3col">
                <q-input v-model.number="assessmentForm.base_market_value" outlined dense type="number" label="Base Market Value (₱)" prefix="₱" />
                <q-input v-model.number="assessmentForm.adjustment" outlined dense type="number" label="Adjustment (₱)" prefix="₱" />
                <q-input v-model.number="assessmentForm.depreciation_rate" outlined dense type="number" label="Depreciation (%)" suffix="%" />
                <q-input v-model.number="assessmentForm.market_value" outlined dense type="number" label="Market Value (₱)" prefix="₱" />
                <q-input v-model.number="assessmentForm.assessment_level" outlined dense type="number" label="Assessment Level (%)" suffix="%" />
                <q-input v-model.number="assessmentForm.assessed_value" outlined dense type="number" label="Assessed Value (₱)" prefix="₱" />
              </div>
            </div>

            <!-- BUILDING-SPECIFIC FIELDS -->
            <div v-if="assessmentForm.assessment_type === 'Building'" class="form-section">
              <div class="form-section-title"><q-icon name="apartment" size="18px" />Building Details</div>
              <div class="form-grid form-grid--3col">
                <q-select v-model="assessmentForm.extra_attributes.kind_of_building" outlined dense label="Kind of Building" :options="['Residential', 'Commercial', 'Industrial', 'Agricultural', 'Institutional', 'Mixed Use']" />
                <q-select v-model="assessmentForm.extra_attributes.structural_type" outlined dense label="Structural Type" :options="['Concrete', 'Semi-Concrete', 'Wood', 'Mixed', 'Steel', 'Other']" />
                <q-select v-model="assessmentForm.extra_attributes.building_condition" outlined dense label="Condition" :options="['Excellent', 'Good', 'Fair', 'Poor', 'Dilapidated']" />
                <q-input v-model="assessmentForm.extra_attributes.building_permit_no" outlined dense label="Building Permit No." />
                <q-input v-model="assessmentForm.extra_attributes.cct_number" outlined dense label="CCT No. (Condominium)" />
                <q-input v-model.number="assessmentForm.extra_attributes.building_age" outlined dense type="number" label="Building Age (years)" />
                <q-input v-model="assessmentForm.extra_attributes.date_constructed" outlined dense type="date" label="Date Constructed" />
                <q-input v-model="assessmentForm.extra_attributes.date_completed" outlined dense type="date" label="Date Completed" />
                <q-input v-model="assessmentForm.extra_attributes.date_occupied" outlined dense type="date" label="Date Occupied" />
              </div>
              <div class="form-section-subtitle q-mt-md">Floor Areas</div>
              <div class="form-grid form-grid--3col">
                <q-input v-model.number="assessmentForm.extra_attributes.number_of_storeys" outlined dense type="number" label="No. of Storeys" />
                <q-input v-model.number="assessmentForm.extra_attributes.total_floor_area" outlined dense type="number" label="Total Floor Area (sqm)" />
                <q-input v-model.number="assessmentForm.extra_attributes.area_1f" outlined dense type="number" label="Area 1st Floor (sqm)" />
                <q-input v-model.number="assessmentForm.extra_attributes.area_2f" outlined dense type="number" label="Area 2nd Floor (sqm)" />
                <q-input v-model.number="assessmentForm.extra_attributes.area_3f" outlined dense type="number" label="Area 3rd Floor (sqm)" />
                <q-input v-model.number="assessmentForm.extra_attributes.area_4f" outlined dense type="number" label="Area 4th Floor (sqm)" />
              </div>
              <div class="form-section-subtitle q-mt-md">Construction Materials</div>
              <div class="form-grid form-grid--3col">
                <q-select v-model="assessmentForm.extra_attributes.roof_type" outlined dense label="Roofing" :options="['G.I. Sheet', 'Concrete Slab', 'Tile', 'Asphalt Shingle', 'Nipa', 'Aluminum', 'Other']" />
                <q-select v-model="assessmentForm.extra_attributes.flooring_material" outlined dense label="Flooring" :options="['Tiles', 'Concrete', 'Wood', 'Vinyl', 'Marble', 'Other']" />
                <q-select v-model="assessmentForm.extra_attributes.wall_material" outlined dense label="Wall Partition" :options="['Concrete Hollow Block', 'Plywood', 'Wood', 'Drywall', 'Steel', 'Glass', 'Other']" />
                <q-input v-model="assessmentForm.extra_attributes.construction_materials" outlined dense label="Other Materials" class="form-grid-span-3" />
              </div>
            </div>

            <!-- MACHINERY-SPECIFIC FIELDS -->
            <div v-if="assessmentForm.assessment_type === 'Machinery'" class="form-section">
              <div class="form-section-title"><q-icon name="precision_manufacturing" size="18px" />Machinery Details</div>
              <div class="form-grid form-grid--3col">
                <q-input v-model="assessmentForm.extra_attributes.kind_of_machinery" outlined dense label="Kind of Machinery" />
                <q-input v-model="assessmentForm.extra_attributes.brand" outlined dense label="Brand" />
                <q-input v-model="assessmentForm.extra_attributes.model" outlined dense label="Model" />
                <q-input v-model="assessmentForm.extra_attributes.capacity" outlined dense label="Capacity / Output" />
                <q-input v-model="assessmentForm.extra_attributes.date_acquired" outlined dense type="date" label="Date Acquired" />
                <q-input v-model.number="assessmentForm.extra_attributes.economic_life" outlined dense type="number" label="Economic Life (years)" />
              </div>
              <div class="form-section-subtitle q-mt-md">Valuation</div>
              <div class="form-grid form-grid--3col">
                <q-input v-model.number="assessmentForm.extra_attributes.acquisition_cost" outlined dense type="number" label="Acquisition Cost (₱)" prefix="₱" />
                <q-input v-model.number="assessmentForm.extra_attributes.replacement_cost" outlined dense type="number" label="Replacement Cost (₱)" prefix="₱" />
                <q-input v-model.number="assessmentForm.extra_attributes.years_used" outlined dense type="number" label="Years Used" />
                <q-input v-model.number="assessmentForm.extra_attributes.remaining_life" outlined dense type="number" label="Remaining Life (years)" />
                <q-input v-model.number="assessmentForm.extra_attributes.depreciation_percent" outlined dense type="number" label="Depreciation (%)" suffix="%" />
              </div>
            </div>

            <!-- LAND-SPECIFIC FIELDS -->
            <div v-if="assessmentForm.assessment_type === 'Land'" class="form-section">
              <div class="form-section-title"><q-icon name="terrain" size="18px" />Land Details</div>
              <div class="form-grid">
                <q-select v-model="assessmentForm.extra_attributes.sub_classification" outlined dense clearable label="Sub-Classification" :options="landSubClassifications" />
              </div>
              <div class="form-section-subtitle q-mt-md">Boundaries (for FAAS)</div>
              <div class="form-grid form-grid--3col">
                <q-input v-model="assessmentForm.extra_attributes.boundary_north" outlined dense label="North" />
                <q-input v-model="assessmentForm.extra_attributes.boundary_east" outlined dense label="East" />
                <q-input v-model="assessmentForm.extra_attributes.boundary_south" outlined dense label="South" />
                <q-input v-model="assessmentForm.extra_attributes.boundary_west" outlined dense label="West" />
              </div>
            </div>

            <div class="form-section">
              <div class="form-section-title"><q-icon name="notes" size="18px" />Notes</div>
              <q-input v-model="assessmentForm.notes" outlined dense type="textarea" label="Assessment Notes" autogrow />
            </div>
            <div class="form-actions">
              <q-btn flat no-caps label="Cancel" v-close-popup />
              <q-btn unelevated no-caps color="primary" icon="save" :label="assessmentSubmitLabel" type="submit" :loading="saving" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Document Preview Dialog -->
    <q-dialog v-model="previewDialog" maximized @hide="closePreview">
      <q-card class="preview-card">
        <q-card-section class="preview-header">
          <div class="preview-header-info">
            <q-icon name="visibility" size="24px" color="white" />
            <div>
              <strong>{{ previewDocument?.document_type }}</strong>
              <span>{{ previewDocument?.reference_number || previewDocument?.file_name }}</span>
            </div>
          </div>
          <div class="preview-header-actions">
            <q-btn flat dense no-caps color="white" icon="download" label="Download" @click="downloadDocument(previewDocument)" />
            <q-btn flat round icon="close" color="white" @click="closePreview" />
          </div>
        </q-card-section>
        <q-card-section class="preview-body">
          <iframe v-if="previewUrl && previewDocument?.mime_type === 'application/pdf'" :src="previewUrl" class="preview-frame" />
          <img v-else-if="previewUrl && previewDocument?.mime_type?.startsWith('image/')" :src="previewUrl" class="preview-image" />
          <div v-else class="preview-unsupported">
            <q-icon name="description" size="64px" color="blue-grey-4" />
            <strong>Preview not available</strong>
            <span>This file type cannot be previewed. Click Download to save it.</span>
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Keyboard Shortcuts Help -->
    <q-dialog v-model="shortcutsDialog">
      <q-card class="shortcuts-card">
        <q-card-section class="shortcuts-header">
          <q-icon name="keyboard" size="22px" />
          <strong>Keyboard Shortcuts</strong>
          <q-btn flat round dense icon="close" v-close-popup color="white" class="q-ml-auto" />
        </q-card-section>
        <q-card-section class="shortcuts-body">
          <div class="shortcut-row">
            <kbd>Ctrl</kbd> + <kbd>K</kbd>
            <span>Focus the search bar</span>
          </div>
          <div class="shortcut-row">
            <kbd>Esc</kbd>
            <span>Close the property panel</span>
          </div>
          <div class="shortcut-row">
            <kbd>↑</kbd> / <kbd>↓</kbd>
            <span>Navigate Tax Declarations list</span>
          </div>
          <div class="shortcut-row">
            <kbd>?</kbd>
            <span>Show this help</span>
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Confirmation Dialog -->
    <q-dialog v-model="confirmDialog" persistent>
      <q-card class="confirm-card">
        <div class="confirm-icon-area" :class="`confirm-icon-area--${confirmData.type}`">
          <q-icon :name="confirmData.icon" size="48px" />
        </div>
        <q-card-section class="confirm-body">
          <h3 class="confirm-title">{{ confirmData.title }}</h3>
          <p class="confirm-message">{{ confirmData.message }}</p>
          <div v-if="confirmData.detail" class="confirm-detail">
            <q-icon name="info" size="16px" />
            <span>{{ confirmData.detail }}</span>
          </div>
        </q-card-section>
        <q-card-section class="confirm-actions">
          <q-btn flat no-caps label="Cancel" color="blue-grey-7" v-close-popup />
          <q-btn
            unelevated
            no-caps
            color="primary"
            :icon="confirmData.confirmIcon || 'check'"
            :label="confirmData.confirmLabel || 'Confirm'"
            @click="confirmData.onConfirm(); confirmDialog = false;"
          />
        </q-card-section>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useQuasar } from 'quasar';
import { useAuth } from '../composables/useAuth';
import WorkspacePageHeader from '../components/layout/WorkspacePageHeader.vue';
import {
  addAssessment,
  addDocument,
  addTaxDeclaration,
  approveProperty,
  approveTaxDeclaration,
  archiveProperty,
  archiveTaxDeclaration,
  archiveDocument,
  createProperty,
  downloadBackupJson,
  downloadDocumentFile,
  viewDocumentFile,
  downloadPropertiesCsv,
  fetchDashboard,
  fetchProperties,
  fetchPropertyDossier,
  fetchOwnerDetail,
  fetchReferences,
  movePhysicalRecord,
  removeAssessment,
  updateDocument,
  updateProperty,
  updateTaxDeclaration,
  updateAssessment,
  fetchDocumentMovements,
  downloadPropertyDossierExport,
  downloadPropertyActivityCsv
} from '../services/api';
import { referenceOptions } from '../data/mockRecords';
import { loadRecentProperties, rememberProperty } from '../utils/recentProperties';
const $q = useQuasar();
const route = useRoute();
const { sessionUser } = useAuth();
const keyword = ref('');
const municipality = ref(null);
const records = ref([]);
const selected = ref(null);
const dossier = ref(null);
const dashboard = ref(null);
const loading = ref(false);
const saving = ref(false);
const recordPanel = ref(null);
const profileTab = ref('property');
const tdDetailTab = ref('info');
const selectedTdId = ref(null);
const entryDialog = ref(false);
const draftSaved = ref(false);
const DRAFT_KEY = 'assessor_property_draft';
const DECLARATION_DRAFT_KEY = 'assessor_declaration_draft';
const editPropertyDialog = ref(false);
const declarationDialog = ref(false);
const documentDialog = ref(false);
const editDocumentDialog = ref(false);
const assessmentDialog = ref(false);
const movementDialog = ref(false);
const selectedDocument = ref(null);
const editingDeclarationId = ref(null);
const searchPage = ref(1);
const searchPerPage = ref(25);
const searchMeta = ref(null);
const auditTdFilter = ref(null);
const recentProperties = ref(loadRecentProperties());
const movementHistoryDialog = ref(false);
const movementHistoryLoading = ref(false);
const movementHistory = ref([]);
const editingAssessmentId = ref(null);
const showAdvancedFilters = ref(false);
const activityDrawer = ref(false);
const shortcutsDialog = ref(false);
const showSuggestions = ref(false);
const ownerDialog = ref(false);
const ownerDetail = ref(null);
const RECENT_SEARCHES_KEY = 'assessor_recent_searches';
const recentSearches = ref(JSON.parse(localStorage.getItem(RECENT_SEARCHES_KEY) || '[]'));
const filters = reactive({
  lot_number: '',
  property_kind: null,
  classification: null,
  status: null,
  td_status: null,
  document_type: null,
  physical_copy_status: null,
  owner: '',
  barangay: '',
  storage_location: '',
  year_from: null,
  year_to: null
});

const canManage = computed(() => Boolean(sessionUser.value?.can_manage_records));
const canApprove = computed(() => Boolean(sessionUser.value?.can_approve_records));
const userPermissions = computed(() => sessionUser.value?.permissions || []);
const canDeleteProperty = computed(() => userPermissions.value.includes('property.delete'));
const canDeleteTd = computed(() => userPermissions.value.includes('td.delete'));
const canDeleteDocument = computed(() => userPermissions.value.includes('document.delete'));

const options = reactive({
  municipalities: [...referenceOptions.municipalities],
  classifications: [...referenceOptions.classifications],
  statuses: [...referenceOptions.statuses],
  transactionTypes: [...referenceOptions.transactionTypes],
  documentTypes: [...referenceOptions.documentTypes],
  assessmentTypes: [...referenceOptions.assessmentTypes],
  physicalStatuses: [...referenceOptions.physicalStatuses]
});

const taxableOptions = [
  { label: 'Taxable', value: true },
  { label: 'Exempt / Non-taxable', value: false }
];

const landSubClassifications = [
  'Residential — Subdivision',
  'Residential — Non-Subdivision',
  'Agricultural — Riceland',
  'Agricultural — Cornland',
  'Agricultural — Coconutland',
  'Agricultural — Sugarland',
  'Agricultural — Pasture',
  'Agricultural — Fishpond',
  'Agricultural — Orchard',
  'Commercial — Prime',
  'Commercial — Secondary',
  'Industrial — Heavy',
  'Industrial — Light',
  'Special — Cultural',
  'Special — Hospital',
  'Special — School',
  'Mineral'
];

const movementTypes = [
  'Initial Entry',
  'Location Update',
  'Released',
  'Returned',
  'Marked Missing',
  'Archived'
];

function currentTd(property) {
  const declarations = property?.tax_declarations || [];
  return declarations.find((td) => td.status === 'Active') || declarations[0];
}

function tdForSelectedProperty() {
  const declarations = selected.value?.tax_declarations || [];

  if (selectedTdId.value) {
    return declarations.find((td) => td.id === selectedTdId.value) || currentTd(selected.value);
  }

  return currentTd(selected.value);
}

const propertyKindIcon = {
  Land: 'landscape',
  Building: 'apartment',
  Machinery: 'precision_manufacturing'
};

const transactionTypesByKind = {
  Land: ['Original', 'Transfer', 'General Revision', 'Revision', 'Subdivision', 'Consolidation', 'Reclassification', 'Correction', 'Physical Change', 'Dispute/Conflict', 'Reassessment'],
  Building: ['Original', 'Transfer', 'General Revision', 'Revision', 'New Construction', 'Renovation/Improvement', 'Demolition', 'Damage/Depreciation', 'Reclassification', 'Correction', 'Reassessment'],
  Machinery: ['Original', 'Transfer', 'General Revision', 'Revision', 'New Installation', 'Replacement', 'Disposal/Removal', 'Correction', 'Reassessment']
};

const blankForm = () => ({
  property_kind: 'Land',
  pin: '',
  lot_number: '',
  survey_number: '',
  title_number: '',
  land_pin_reference: '',
  barangay: '',
  municipality: null,
  province: 'Sample Province',
  classification: 'Residential',
  actual_use: '',
  land_area: null,
  unit_of_measure: 'sqm',
  status: 'Draft',
  remarks: '',
  owner: {
    name: '',
    address: ''
  },
  extra: {
    sub_classification: '',
    boundary_north: '',
    boundary_south: '',
    boundary_east: '',
    boundary_west: '',
    kind_of_building: '',
    structural_type: '',
    building_condition: '',
    building_permit_no: '',
    cct_number: '',
    date_constructed: '',
    date_completed: '',
    date_occupied: '',
    number_of_storeys: null,
    total_floor_area: null,
    roof_type: '',
    kind_of_machinery: '',
    brand: '',
    model: '',
    capacity: '',
    date_acquired: '',
    economic_life: null,
    acquisition_cost: null,
    replacement_cost: null
  },
  tax_declaration: {
    td_number: '',
    arp_number: '',
    effectivity_year: new Date().getFullYear(),
    actual_use: '',
    market_value: 0,
    assessed_value: 0,
    assessment_level: 20,
    status: 'Draft',
    transaction_type: 'Original',
    memoranda: ''
  }
});

const blankEditPropertyForm = () => ({
  pin: selected.value?.pin || '',
  property_index_number: selected.value?.property_index_number || '',
  property_kind: selected.value?.property_kind || 'Land',
  lot_number: selected.value?.lot_number || '',
  survey_number: selected.value?.survey_number || '',
  title_number: selected.value?.title_number || '',
  land_pin_reference: selected.value?.land_pin_reference || '',
  barangay: selected.value?.barangay || '',
  municipality: selected.value?.municipality || null,
  province: selected.value?.province || 'Sample Province',
  classification: selected.value?.classification || 'Residential',
  actual_use: selected.value?.actual_use || '',
  land_area: selected.value?.land_area || null,
  unit_of_measure: selected.value?.unit_of_measure || 'sqm',
  status: selected.value?.status || 'Draft',
  remarks: selected.value?.remarks || '',
  extra: { ...(selected.value?.extra_attributes || {}) }
});

const blankDeclarationForm = () => ({
  owner: {
    name: '',
    address: ''
  },
  td_number: '',
  arp_number: '',
  effectivity_year: new Date().getFullYear(),
  classification: selected.value?.classification || 'Residential',
  actual_use: selected.value?.actual_use || '',
  market_value: 0,
  assessed_value: 0,
  assessment_level: 20,
  status: 'Draft',
  transaction_type: 'Revision',
  memoranda: '',
  assessment: {
    assessment_type: 'Land',
    area: selected.value?.land_area || 0,
    unit_of_measure: selected.value?.unit_of_measure || 'sqm',
    unit_value: 0,
    base_market_value: 0,
    adjustment: 0,
    depreciation_rate: null,
    taxable: true,
    notes: ''
  }
});

const blankDocumentForm = () => {
  const td = tdForSelectedProperty();

  return {
  tax_declaration_id: selectedTdId.value || td?.id || null,
  document_type: 'Tax Declaration',
  reference_number: td?.td_number || '',
  issued_at: '',
  physical_copy_status: 'On File',
  storage_location: '',
  shelf_number: '',
  box_number: '',
  folder_number: '',
  custodian: 'Assessment Records Section',
  received_at: '',
  released_at: '',
  returned_at: '',
  notes: '',
  file: null
  };
};

const blankEditDocumentForm = () => ({
  tax_declaration_id: selectedDocument.value?.tax_declaration_id || null,
  document_type: selectedDocument.value?.document_type || 'Tax Declaration',
  reference_number: selectedDocument.value?.reference_number || '',
  issued_at: dateInputValue(selectedDocument.value?.issued_at),
  physical_copy_status: selectedDocument.value?.physical_copy_status || 'On File',
  storage_location: selectedDocument.value?.storage_location || '',
  shelf_number: selectedDocument.value?.shelf_number || '',
  box_number: selectedDocument.value?.box_number || '',
  folder_number: selectedDocument.value?.folder_number || '',
  custodian: selectedDocument.value?.custodian || 'Assessment Records Section',
  received_at: dateInputValue(selectedDocument.value?.received_at),
  released_at: dateInputValue(selectedDocument.value?.released_at),
  returned_at: dateInputValue(selectedDocument.value?.returned_at),
  notes: selectedDocument.value?.notes || ''
});

const blankAssessmentForm = () => {
  const td = tdForSelectedProperty();

  return {
    tax_declaration_id: selectedTdId.value || td?.id || null,
    assessment_type: 'Land',
    classification: td?.classification || selected.value?.classification || 'Residential',
    actual_use: td?.actual_use || selected.value?.actual_use || '',
    area: selected.value?.land_area || null,
    unit_of_measure: selected.value?.unit_of_measure || 'sqm',
    unit_value: 0,
    base_market_value: Number(td?.market_value || 0),
    adjustment: 0,
    depreciation_rate: null,
    market_value: Number(td?.market_value || 0),
    assessment_level: Number(td?.assessment_level || 20),
    assessed_value: Number(td?.assessed_value || 0),
    taxable: true,
    notes: '',
    extra_attributes: {
      // Building fields
      kind_of_building: '',
      structural_type: '',
      building_permit_no: '',
      building_age: null,
      building_condition: '',
      cct_number: '',
      date_constructed: '',
      date_completed: '',
      date_occupied: '',
      number_of_storeys: null,
      total_floor_area: null,
      area_1f: null,
      area_2f: null,
      area_3f: null,
      area_4f: null,
      construction_materials: '',
      roof_type: '',
      flooring_material: '',
      wall_material: '',
      // Machinery fields
      kind_of_machinery: '',
      brand: '',
      model: '',
      capacity: '',
      date_acquired: '',
      acquisition_cost: null,
      replacement_cost: null,
      economic_life: null,
      years_used: null,
      remaining_life: null,
      depreciation_percent: null,
      // Land fields (optional sub-classification)
      sub_classification: '',
      // Boundaries (for FAAS)
      boundary_north: '',
      boundary_south: '',
      boundary_east: '',
      boundary_west: ''
    }
  };
};

const blankMovementForm = () => ({
  movement_type: 'Location Update',
  to_status: selectedDocument.value?.physical_copy_status || 'On File',
  to_location: selectedDocument.value?.storage_location || '',
  to_box_number: selectedDocument.value?.box_number || '',
  to_folder_number: selectedDocument.value?.folder_number || '',
  released_to: '',
  custodian: selectedDocument.value?.custodian || 'Assessment Records Section',
  movement_date: new Date().toISOString().slice(0, 10),
  expected_return_at: '',
  returned_at: '',
  remarks: ''
});

const form = reactive(blankForm());
const editPropertyForm = reactive(blankEditPropertyForm());
const declarationForm = reactive(blankDeclarationForm());
const documentForm = reactive(blankDocumentForm());
const editDocumentForm = reactive(blankEditDocumentForm());
const assessmentForm = reactive(blankAssessmentForm());
const movementForm = reactive(blankMovementForm());

const columns = [
  { name: 'td', label: 'Current TD', field: 'td', align: 'left', sortable: true },
  { name: 'pin', label: 'PIN', field: 'pin', align: 'left', sortable: true },
  { name: 'property', label: 'Property', field: 'lot_number', align: 'left', sortable: true },
  { name: 'owner', label: 'Owner', field: 'owner', align: 'left' },
  { name: 'location', label: 'Location', field: 'location', align: 'left' },
  { name: 'classification', label: 'Class', field: 'classification', align: 'left', sortable: true },
  { name: 'area', label: 'Area', field: 'land_area', align: 'right', sortable: true },
  { name: 'values', label: 'Values', field: 'values', align: 'right' },
  { name: 'status', label: 'Status', field: 'status', align: 'left', sortable: true },
];

const stats = computed(() => {
  const localTaxDeclarations = records.value.reduce((sum, property) => sum + (property.tax_declarations?.length || 0), 0);
  const localDocuments = records.value.reduce((sum, property) => sum + (property.documents?.length || 0), 0);

  return {
    properties: dashboard.value?.properties ?? records.value.length,
    activeTaxDeclarations: dashboard.value?.active_tax_declarations ?? records.value.filter((property) => currentTd(property)?.status === 'Active').length,
    taxDeclarations: dashboard.value?.tax_declarations ?? localTaxDeclarations,
    documents: dashboard.value?.documents ?? localDocuments
  };
});

const taxHistory = computed(() => dossier.value?.tax_declaration_history || selected.value?.tax_declarations || []);
const taxDeclarationTimeline = computed(() => {
  if (dossier.value?.tax_declaration_timeline?.length) {
    return dossier.value.tax_declaration_timeline;
  }

  return (taxHistory.value || []).map((td) => ({
    tax_declaration: td,
    assessment_records: td.assessment_records || selected.value?.assessment_records?.filter((record) => record.tax_declaration_id === td.id) || [],
    documents: (selected.value?.documents || []).filter((document) => document.tax_declaration_id === td.id),
    document_count: (selected.value?.documents || []).filter((document) => document.tax_declaration_id === td.id).length,
    assessment_count: (td.assessment_records || []).length,
    data_entry_events: (dossier.value?.data_entry_timeline || selected.value?.activity_logs || []).filter((log) => log.tax_declaration_id === td.id)
  }));
});

const selectedTdEntry = computed(() => {
  if (!selectedTdId.value) {
    return null;
  }

  return taxDeclarationTimeline.value.find((entry) => entry.tax_declaration?.id === selectedTdId.value) || null;
});

function selectTd(entry) {
  selectedTdId.value = entry?.tax_declaration?.id ?? null;
}

function pickDefaultTd() {
  const timeline = taxDeclarationTimeline.value;

  if (!timeline.length) {
    selectedTdId.value = null;
    return;
  }

  const active = timeline.find((entry) => entry.tax_declaration?.status === 'Active');
  selectedTdId.value = (active || timeline[0]).tax_declaration?.id ?? null;
}

function openAssessmentDialogForTd() {
  if (!selectedTdEntry.value?.tax_declaration) {
    return;
  }

  openAssessmentDialog(selectedTdEntry.value.tax_declaration);
}

const pendingDigitization = computed(() => dossier.value?.pending_digitization || (selected.value?.documents || []).filter((document) => needsDigitization(document)));
const ownerHistory = computed(() => dossier.value?.owner_history || []);
const valuationSummary = computed(() => dossier.value?.valuation_summary || {});
const dataEntryTimeline = computed(() => dossier.value?.data_entry_timeline || selected.value?.activity_logs || []);
const dossierCounts = computed(() => dossier.value?.counts || {
  tax_declarations: selected.value?.tax_declarations?.length || 0,
  assessment_records: selected.value?.assessment_records?.length || 0,
  documents: selected.value?.documents?.length || 0,
  digitized_documents: 0,
  pending_digitization: pendingDigitization.value.length,
  owners: 0,
  audit_events: selected.value?.activity_logs?.length || 0
});
const auditTdOptions = computed(() => [
  { label: 'All activity', value: null },
  ...taxHistory.value.map((td) => ({ label: td.td_number, value: td.id }))
]);
const filteredDataEntryTimeline = computed(() => {
  if (!auditTdFilter.value) {
    return dataEntryTimeline.value;
  }

  return dataEntryTimeline.value.filter((log) => log.tax_declaration_id === auditTdFilter.value);
});

const assessmentGroups = computed(() => {
  const declarations = taxHistory.value || [];

  return declarations
    .map((td) => ({
      td,
      records: td.assessment_records || selected.value?.assessment_records?.filter((record) => record.tax_declaration_id === td.id) || []
    }))
    .filter((group) => group.records.length);
});

const documentGroupsByTd = computed(() => {
  const groups = (taxDeclarationTimeline.value || []).map((entry) => ({
    key: `td-${entry.tax_declaration.id}`,
    label: `${entry.tax_declaration.td_number} (${entry.tax_declaration.effectivity_year})`,
    documents: entry.documents || []
  })).filter((group) => group.documents.length);

  const propertyDocuments = dossier.value?.property_documents
    || (selected.value?.documents || []).filter((document) => !document.tax_declaration_id);

  if (propertyDocuments.length) {
    groups.push({
      key: 'property-level',
      label: 'Property-level / Unlinked',
      documents: propertyDocuments
    });
  }

  return groups;
});

const taxDeclarationOptions = computed(() => taxHistory.value.map((td) => ({
  label: `${td.td_number} (${td.effectivity_year})`,
  value: td.id
})));

const declarationDialogTitle = computed(() => editingDeclarationId.value ? 'Edit Tax Declaration' : 'Add Tax Declaration');
const declarationSubmitLabel = computed(() => editingDeclarationId.value ? 'Save TD Changes' : 'Add To History');
const assessmentDialogTitle = computed(() => (editingAssessmentId.value ? 'Edit Assessment Detail' : 'Add Assessment Detail'));

const newPropertyTransactionTypes = computed(() => transactionTypesByKind[form.property_kind] || transactionTypesByKind.Land);
const declarationTransactionTypes = computed(() => transactionTypesByKind[selected.value?.property_kind] || transactionTypesByKind.Land);

// Search suggestions: top 5 matches from current results
const searchSuggestions = computed(() => {
  if (!keyword.value || keyword.value.length < 2) return [];
  return records.value.slice(0, 5);
});

function hideSuggestionsLater() {
  // Delay so click can register
  setTimeout(() => {
    showSuggestions.value = false;
  }, 200);
}

function applySearchSuggestion(term) {
  keyword.value = term;
  showSuggestions.value = false;
}

function selectSuggestion(prop) {
  showSuggestions.value = false;
  saveRecentSearch(keyword.value);
  selectRecord(null, prop);
}

function saveRecentSearch(term) {
  if (!term || term.length < 2) return;
  let recent = [...recentSearches.value];
  recent = recent.filter((t) => t !== term);
  recent.unshift(term);
  recent = recent.slice(0, 5);
  recentSearches.value = recent;
  try {
    localStorage.setItem(RECENT_SEARCHES_KEY, JSON.stringify(recent));
  } catch {
    // ignore
  }
}

function clearRecentSearches() {
  recentSearches.value = [];
  try {
    localStorage.removeItem(RECENT_SEARCHES_KEY);
  } catch {
    // ignore
  }
}

async function openOwnerDetail(ownerId) {
  if (!ownerId) return;
  try {
    ownerDetail.value = await fetchOwnerDetail(ownerId);
    ownerDialog.value = true;
  } catch (error) {
    notifyError(error, 'Unable to load owner details.');
  }
}

async function goToProperty(propertyId) {
  ownerDialog.value = false;
  await openPropertyById(propertyId);
}
const assessmentSubmitLabel = computed(() => (editingAssessmentId.value ? 'Save Changes' : 'Save Assessment'));

function searchParams() {
  const params = {
    search: keyword.value,
    municipality: municipality.value,
    page: searchPage.value,
    per_page: searchPerPage.value,
    ...filters
  };

  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== null && value !== undefined && value !== '')
  );
}

function matchLabel(match) {
  const labels = {
    lot_number: 'Lot',
    pin: 'PIN',
    td_number: 'TD',
    arp_number: 'ARP',
    owner: 'Owner',
    document: 'Document',
    barangay: 'Barangay',
    survey_number: 'Survey',
    title_number: 'Title',
    property_index_number: 'PIN Index'
  };

  return `${labels[match.matched_on] || 'Match'}: ${match.matched_value}`;
}

function needsDigitization(document) {
  return Boolean(document?.needs_digitization)
    || document?.physical_copy_status === 'For Scanning'
    || String(document?.file_path || '').startsWith('pending-upload/');
}

function linkedTdNumber(taxDeclarationId) {
  return taxHistory.value.find((td) => td.id === taxDeclarationId)?.td_number || 'Unknown TD';
}

function changeSearchPage(page) {
  searchPage.value = page;
  loadRecords();
}

function onSearchPerPageChange() {
  searchPage.value = 1;
  loadRecords();
}

async function loadReferences() {
  const references = await fetchReferences();

  if (!references) return;

  options.classifications = references.classifications || options.classifications;
  options.statuses = references.statuses || options.statuses;
  options.transactionTypes = references.transaction_types || options.transactionTypes;
  options.documentTypes = references.document_types || options.documentTypes;
  options.assessmentTypes = references.assessment_types || options.assessmentTypes;
  options.physicalStatuses = references.physical_copy_statuses || options.physicalStatuses;
}

function syncMunicipalities(properties) {
  const fromRecords = properties.map((property) => property.municipality).filter(Boolean);
  options.municipalities = [...new Set([...options.municipalities, ...fromRecords])].sort();
}

async function loadDossier(propertyId) {
  const data = await fetchPropertyDossier(propertyId);

  if (data?.property) {
    dossier.value = data;
    selected.value = data.property;
    recentProperties.value = rememberProperty(data.property);
    await nextTick();
    pickDefaultTd();
    return;
  }

  dossier.value = null;
  selected.value = records.value.find((property) => property.id === propertyId) || null;
  selectedTdId.value = null;
}

async function loadRecords() {
  if (!sessionUser.value) return;

  loading.value = true;

  try {
    const previousSelectedId = selected.value?.id;
    const propertyResult = await fetchProperties(searchParams());

    records.value = propertyResult.items || propertyResult;
    searchMeta.value = propertyResult.meta || null;
    syncMunicipalities(records.value);

    const candidate = records.value.find((property) => property.id === previousSelectedId)
      || (records.value.length === 1 && keyword.value ? records.value[0] : null);

    if (candidate) {
      await loadDossier(candidate.id);
    } else if (!previousSelectedId) {
      selected.value = null;
      dossier.value = null;
      selectedTdId.value = null;
    }
  } catch (error) {
    console.error('Failed to load records', error);
    $q.notify({ type: 'negative', message: 'Unable to load property records. Check that the API is running.' });
  } finally {
    loading.value = false;
  }
}

// Dashboard totals are independent of the search — refresh on demand only.
async function refreshDashboard() {
  if (!sessionUser.value) return;
  try {
    dashboard.value = await fetchDashboard();
  } catch (error) {
    console.warn('Dashboard refresh failed', error);
  }
}


async function openPropertyById(propertyId) {
  if (!propertyId) return;
  try {
    await loadDossier(propertyId);
    await nextTick();
    // Scroll directly to the Tax Declarations section (skip the metrics/header)
    const tdSection = document.querySelector('.td-navigator');
    const target = tdSection || recordPanel.value?.$el || recordPanel.value;
    if (target?.scrollIntoView) {
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  } catch {
    $q.notify({ type: 'negative', message: 'Property not found. It may have been created in demo mode.' });
  }
}

async function openRecentProperty(propertyId) {
  await openPropertyById(propertyId);
}

function scrollToSearch() {
  const searchInput = document.querySelector('.filter-search input');
  if (searchInput) {
    searchInput.focus();
    searchInput.select();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
}

function closeProperty() {
  selected.value = null;
  dossier.value = null;
  scrollToSearch();
}

async function selectRecord(_, row) {
  if (!row?.id) return;

  await loadDossier(row.id);
  await nextTick();
  // Scroll directly to the Tax Declarations section
  const tdSection = document.querySelector('.td-navigator');
  const target = tdSection || recordPanel.value?.$el || recordPanel.value;
  if (target?.scrollIntoView) {
    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}

function clearFilters() {
  keyword.value = '';
  municipality.value = null;
  searchPage.value = 1;
  Object.assign(filters, {
    lot_number: '',
    property_kind: null,
    classification: null,
    status: null,
    td_status: null,
    document_type: null,
    physical_copy_status: null,
    owner: '',
    barangay: '',
    storage_location: '',
    year_from: null,
    year_to: null
  });
  loadRecords();
}

function openEditPropertyDialog() {
  if (!selected.value) return;

  Object.assign(editPropertyForm, blankEditPropertyForm());
  editPropertyDialog.value = true;
}

async function savePropertyEdit() {
  if (!selected.value) return;

  saving.value = true;

  try {
    const updated = await updateProperty(selected.value.id, JSON.parse(JSON.stringify(editPropertyForm)));
    await replaceSelected(updated);
    editPropertyDialog.value = false;
    await loadRecords();
    $q.notify({ type: 'positive', message: 'Property master record updated.' });
  } catch (error) {
    notifyError(error, 'Unable to update property record.');
  } finally {
    saving.value = false;
  }
}

async function approveSelectedProperty() {
  if (!selected.value) return;

  showConfirm({
    title: 'Approve Property',
    message: `Set property ${selected.value.lot_number || selected.value.pin} as Active?`,
    detail: 'This will mark the property status as Active.',
    type: 'success',
    icon: 'verified',
    confirmLabel: 'Approve',
    confirmIcon: 'verified',
    onConfirm: async () => {
      saving.value = true;
      try {
        const updated = await approveProperty(selected.value.id);
        await replaceSelected(updated);
        await loadRecords();
        $q.notify({ type: 'positive', message: 'Property record approved.' });
      } catch (error) {
        notifyError(error, 'Unable to approve property record.');
      } finally {
        saving.value = false;
      }
    }
  });
}

async function archiveSelectedProperty() {
  if (!selected.value) return;

  saving.value = true;
  try {
    const updated = await archiveProperty(selected.value.id);
    await replaceSelected(updated);
    await loadRecords();
    $q.notify({ type: 'positive', message: 'Property record archived.' });
  } catch (error) {
    notifyError(error, 'Unable to archive property record.');
  } finally {
    saving.value = false;
  }
}

async function deleteSelectedProperty() {
  if (!selected.value) return;

  showConfirm({
    title: 'Delete Property',
    message: `Permanently delete ${selected.value.lot_number || selected.value.pin}?`,
    detail: 'This will remove ALL tax declarations, assessment records, documents, and activity logs. This cannot be undone.',
    type: 'danger',
    icon: 'delete_forever',
    confirmLabel: 'Delete Permanently',
    confirmIcon: 'delete_forever',
    onConfirm: async () => {
      saving.value = true;
      try {
        await archiveProperty(selected.value.id);
        selected.value = null;
        dossier.value = null;
        await loadRecords();
        $q.notify({ type: 'positive', message: 'Property permanently deleted.' });
      } catch (error) {
        $q.notify({ type: 'negative', message: error?.response?.data?.message || 'Unable to delete property.' });
      } finally {
        saving.value = false;
      }
    }
  });
}

async function replaceSelected(updatedProperty) {
  if (!updatedProperty?.id) return;

  const index = records.value.findIndex((property) => property.id === updatedProperty.id);

  if (index >= 0) {
    records.value.splice(index, 1, updatedProperty);
  } else {
    records.value.unshift(updatedProperty);
  }

  try {
    await loadDossier(updatedProperty.id);
  } catch {
    // If dossier fetch fails, use the data we already have
    selected.value = updatedProperty;
  }
}

async function saveEntry() {
  saving.value = true;

  try {
    // Build payload with property_kind, ensure lot_number has a fallback for non-Land
    const payload = JSON.parse(JSON.stringify(form));
    if (payload.property_kind !== 'Land' && !payload.lot_number) {
      payload.lot_number = payload.land_pin_reference || `${payload.property_kind}-${payload.pin || Date.now()}`;
    }
    const created = await createProperty(payload);
    await replaceSelected(created);
    entryDialog.value = false;
    Object.assign(form, blankForm());
    localStorage.removeItem(DRAFT_KEY);
    await loadRecords();
    $q.notify({ type: 'positive', message: `${payload.property_kind} record saved.` });
  } catch (error) {
    const errors = error?.response?.data?.errors;
    if (errors) {
      $q.notify({ type: 'negative', message: Object.values(errors).flat()[0], timeout: 6000 });
    } else {
      $q.notify({ type: 'negative', message: error?.response?.data?.message || 'Unable to save property. Check required fields and unique PIN/TD number.', timeout: 6000 });
    }
  } finally {
    saving.value = false;
  }
}

function openDeclarationDialog() {
  editingDeclarationId.value = null;
  Object.assign(declarationForm, blankDeclarationForm());
  declarationDialog.value = true;
}

function openEditDeclarationDialog(td) {
  editingDeclarationId.value = td.id;
  Object.assign(declarationForm, {
    owner: {
      name: td.owner?.name || '',
      address: td.owner?.address || ''
    },
    td_number: td.td_number || '',
    arp_number: td.arp_number || '',
    effectivity_year: td.effectivity_year || new Date().getFullYear(),
    classification: td.classification || selected.value?.classification || 'Residential',
    actual_use: td.actual_use || selected.value?.actual_use || '',
    market_value: Number(td.market_value || 0),
    assessed_value: Number(td.assessed_value || 0),
    assessment_level: Number(td.assessment_level || 0),
    status: td.status || 'Draft',
    transaction_type: td.transaction_type || 'Revision',
    memoranda: td.memoranda || ''
  });
  declarationDialog.value = true;
}

async function saveDeclaration() {
  if (!selected.value) return;

  saving.value = true;

  try {
    const payload = JSON.parse(JSON.stringify(declarationForm));
    const isEditing = Boolean(editingDeclarationId.value);

    // Strip the assessment block on update — backend update endpoint doesn't handle it
    if (isEditing) {
      delete payload.assessment;
    }

    const updated = isEditing
      ? await updateTaxDeclaration(selected.value.id, editingDeclarationId.value, payload)
      : await addTaxDeclaration(selected.value.id, payload);

    if (!updated) {
      throw new Error('Empty response');
    }

    await replaceSelected(updated);
    declarationDialog.value = false;
    editingDeclarationId.value = null;
    profileTab.value = 'property';
    await loadRecords();
    $q.notify({ type: 'positive', message: isEditing ? 'Tax declaration updated.' : 'Tax declaration saved.' });
  } catch (error) {
    console.error('TD save error:', error, error?.response?.data);
    if (error?.message === 'Network Error' || error?.code === 'ERR_NETWORK') {
      $q.notify({ type: 'negative', message: 'Cannot reach the API server. Make sure the backend is running on port 8002.', timeout: 8000 });
    } else {
      notifyError(error, 'Unable to save tax declaration.');
    }
  } finally {
    saving.value = false;
  }
}

async function approveDeclaration(td) {
  if (!selected.value || !td?.id) return;

  showConfirm({
    title: 'Approve Tax Declaration',
    message: `Approve TD ${td.td_number}?`,
    detail: 'This will set it as Active and supersede the current active TD.',
    type: 'success',
    icon: 'verified',
    confirmLabel: 'Approve TD',
    confirmIcon: 'verified',
    onConfirm: async () => {
      saving.value = true;
      try {
        const updated = await approveTaxDeclaration(selected.value.id, td.id);
        await replaceSelected(updated);
        profileTab.value = 'property';
        await loadRecords();
        $q.notify({ type: 'positive', message: 'Tax declaration approved.' });
      } catch (error) {
        const msg = error?.response?.data?.message || error?.message || 'Unable to approve tax declaration.';
        $q.notify({ type: 'negative', message: msg, timeout: 6000 });
      } finally {
        saving.value = false;
      }
    }
  });
}

async function archiveDeclaration(td) {
  if (!selected.value || !td?.id) return;

  showConfirm({
    title: 'Cancel Tax Declaration',
    message: `Cancel TD ${td.td_number}?`,
    detail: 'This will mark it as Cancelled. The record will be preserved for audit purposes.',
    type: 'warning',
    icon: 'block',
    confirmLabel: 'Cancel TD',
    confirmIcon: 'block',
    onConfirm: async () => {
      saving.value = true;
      try {
        const updated = await archiveTaxDeclaration(selected.value.id, td.id);
        await replaceSelected(updated);
        profileTab.value = 'property';
        await loadRecords();
        $q.notify({ type: 'positive', message: 'Tax declaration cancelled.' });
      } catch (error) {
        notifyError(error, 'Unable to cancel tax declaration.');
      } finally {
        saving.value = false;
      }
    }
  });
}

async function deleteDeclaration(td) {
  if (!selected.value || !td?.id) return;

  showConfirm({
    title: 'Delete Tax Declaration',
    message: `Permanently delete TD ${td.td_number}?`,
    detail: 'This will also remove its assessment records and linked documents. This action cannot be undone.',
    type: 'danger',
    icon: 'delete_forever',
    confirmLabel: 'Delete TD',
    confirmIcon: 'delete_forever',
    onConfirm: async () => {
      saving.value = true;
      try {
        const updated = await archiveTaxDeclaration(selected.value.id, td.id);
        await replaceSelected(updated);
        selectedTdId.value = null;
        profileTab.value = 'property';
        await loadRecords();
        $q.notify({ type: 'positive', message: 'Tax declaration deleted.' });
      } catch (error) {
        $q.notify({ type: 'negative', message: error?.response?.data?.message || 'Unable to delete tax declaration.' });
      } finally {
        saving.value = false;
      }
    }
  });
}

function openDocumentDialog() {
  Object.assign(documentForm, blankDocumentForm());
  documentDialog.value = true;
}

function openDocumentDialogForScanning() {
  Object.assign(documentForm, {
    ...blankDocumentForm(),
    physical_copy_status: 'For Scanning',
    file: null
  });
  documentDialog.value = true;
}

function openAssessmentEditDialog(td, record) {
  editingAssessmentId.value = record.id;
  const blank = blankAssessmentForm();
  // Merge extra_attributes properly so all fields are defined
  const mergedExtras = {
    ...blank.extra_attributes,
    ...(record.extra_attributes || {})
  };
  Object.assign(assessmentForm, {
    ...blank,
    ...record,
    extra_attributes: mergedExtras,
    tax_declaration_id: td.id
  });
  assessmentDialog.value = true;
}

function openAssessmentDialog(td = null) {
  editingAssessmentId.value = null;
  Object.assign(assessmentForm, blankAssessmentForm());

  if (td?.id) {
    assessmentForm.tax_declaration_id = td.id;
    assessmentForm.classification = td.classification || assessmentForm.classification;
    assessmentForm.actual_use = td.actual_use || assessmentForm.actual_use;
    assessmentForm.base_market_value = Number(td.market_value || 0);
    assessmentForm.market_value = Number(td.market_value || 0);
    assessmentForm.assessment_level = Number(td.assessment_level || 20);
    assessmentForm.assessed_value = Number(td.assessed_value || 0);
  }

  assessmentDialog.value = true;
}

async function openMovementHistoryDialog(document) {
  selectedDocument.value = document;
  movementHistoryDialog.value = true;
  movementHistoryLoading.value = true;
  movementHistory.value = [];

  try {
    movementHistory.value = await fetchDocumentMovements(document.id);
  } catch (error) {
    notifyError(error, 'Unable to load movement history.');
  } finally {
    movementHistoryLoading.value = false;
  }
}

function openMovementDialog(document) {
  selectedDocument.value = document;
  Object.assign(movementForm, blankMovementForm());
  movementDialog.value = true;
}

function openEditDocumentDialog(document) {
  selectedDocument.value = document;
  Object.assign(editDocumentForm, blankEditDocumentForm());
  editDocumentDialog.value = true;
}

async function saveDocument() {
  if (!selected.value) return;

  saving.value = true;

  try {
    const updated = await addDocument(selected.value.id, documentForm);

    if (updated) {
      await replaceSelected(updated);
      documentDialog.value = false;
      profileTab.value = 'documents';
      await loadRecords();
      $q.notify({ type: 'positive', message: 'Document registered.' });
    } else {
      $q.notify({ type: 'negative', message: 'Unable to register document.' });
    }
  } catch (error) {
    const errors = error?.response?.data?.errors;
    if (errors) {
      $q.notify({ type: 'negative', message: Object.values(errors).flat()[0], timeout: 6000 });
    } else {
      $q.notify({ type: 'negative', message: error?.response?.data?.message || 'Unable to register document.', timeout: 6000 });
    }
  } finally {
    saving.value = false;
  }
}

async function saveDocumentEdit() {
  if (!selectedDocument.value) return;

  saving.value = true;

  try {
    const updated = await updateDocument(selectedDocument.value.id, JSON.parse(JSON.stringify(editDocumentForm)));
    await replaceSelected(updated);
    editDocumentDialog.value = false;
    profileTab.value = 'documents';
    await loadRecords();
    $q.notify({ type: 'positive', message: 'Document record updated.' });
  } catch (error) {
    notifyError(error, 'Unable to update document record.');
  } finally {
    saving.value = false;
  }
}

async function archiveSelectedDocument(document) {
  saving.value = true;

  try {
    const updated = await archiveDocument(document.id);
    await replaceSelected(updated);
    profileTab.value = 'documents';
    await loadRecords();
    $q.notify({ type: 'positive', message: 'Document record archived.' });
  } catch (error) {
    notifyError(error, 'Unable to archive document record.');
  } finally {
    saving.value = false;
  }
}

function confirmDeleteDocument(document) {
  showConfirm({
    title: 'Delete Document',
    message: `Delete "${document.document_type}${document.reference_number ? ' - ' + document.reference_number : ''}"?`,
    detail: 'The file and its movement history will be removed. This action cannot be undone.',
    type: 'danger',
    icon: 'delete_forever',
    confirmLabel: 'Delete File',
    confirmIcon: 'delete_forever',
    onConfirm: () => archiveSelectedDocument(document)
  });
}

async function saveMovement() {
  if (!selected.value || !selectedDocument.value) return;

  saving.value = true;

  try {
    const updated = await movePhysicalRecord(selected.value.id, selectedDocument.value.id, JSON.parse(JSON.stringify(movementForm)));
    await replaceSelected(updated);
    movementDialog.value = false;
    profileTab.value = 'documents';
    await loadRecords();
    $q.notify({ type: 'positive', message: 'Physical movement recorded.' });
  } catch (error) {
    notifyError(error, 'Unable to record physical movement.');
  } finally {
    saving.value = false;
  }
}

async function saveAssessment() {
  if (!selected.value || !assessmentForm.tax_declaration_id) return;

  saving.value = true;
  const editing = editingAssessmentId.value;

  try {
    const payload = JSON.parse(JSON.stringify(assessmentForm));
    const updated = editing
      ? await updateAssessment(selected.value.id, assessmentForm.tax_declaration_id, editing, payload)
      : await addAssessment(selected.value.id, assessmentForm.tax_declaration_id, payload);

    if (updated) {
      await replaceSelected(updated);
      assessmentDialog.value = false;
      editingAssessmentId.value = null;
      profileTab.value = 'property';
      await loadRecords();
      $q.notify({ type: 'positive', message: editing ? 'Assessment updated.' : 'Assessment detail saved.' });
    } else {
      $q.notify({ type: 'negative', message: 'Unable to save assessment detail.' });
    }
  } catch (error) {
    const errors = error?.response?.data?.errors;
    if (errors) {
      $q.notify({ type: 'negative', message: Object.values(errors).flat()[0], timeout: 6000 });
    } else {
      $q.notify({ type: 'negative', message: error?.response?.data?.message || 'Unable to save assessment detail.', timeout: 6000 });
    }
  } finally {
    saving.value = false;
  }
}

async function removeAssessmentRecord(td, record) {
  if (!selected.value || !td?.id || !record?.id) return;

  showConfirm({
    title: 'Remove Assessment',
    message: `Remove this ${record.assessment_type} assessment line?`,
    detail: 'This action cannot be undone.',
    type: 'danger',
    icon: 'delete',
    confirmLabel: 'Remove',
    confirmIcon: 'delete',
    onConfirm: async () => {
      saving.value = true;
      try {
        const updated = await removeAssessment(selected.value.id, td.id, record.id);
        await replaceSelected(updated);
        profileTab.value = 'property';
        await loadRecords();
        $q.notify({ type: 'positive', message: 'Assessment record removed.' });
      } catch (error) {
        notifyError(error, 'Unable to remove assessment record.');
      } finally {
        saving.value = false;
      }
    }
  });
}

async function downloadDocument(document) {
  try {
    await downloadDocumentFile(document);
  } catch {
    $q.notify({ type: 'negative', message: 'The scanned file is not available yet.' });
  }
}

const previewDialog = ref(false);
const previewUrl = ref('');
const previewDocument = ref(null);

const confirmDialog = ref(false);
const confirmData = reactive({
  title: '',
  message: '',
  detail: '',
  type: 'danger',
  icon: 'warning',
  confirmLabel: 'Confirm',
  confirmIcon: 'check',
  onConfirm: () => {}
});

function showConfirm({ title, message, detail, type, icon, confirmLabel, confirmIcon, onConfirm }) {
  Object.assign(confirmData, {
    title: title || 'Confirm',
    message: message || 'Are you sure?',
    detail: detail || '',
    type: type || 'danger',
    icon: icon || (type === 'danger' ? 'warning' : type === 'success' ? 'check_circle' : 'help'),
    confirmLabel: confirmLabel || 'Confirm',
    confirmIcon: confirmIcon || 'check',
    onConfirm: onConfirm || (() => {})
  });
  confirmDialog.value = true;
}

async function viewDocument(doc) {
  try {
    previewDocument.value = doc;
    const url = await viewDocumentFile(doc);
    previewUrl.value = url;
    previewDialog.value = true;
  } catch {
    $q.notify({ type: 'negative', message: 'The scanned file is not available yet. Upload a scan first.' });
  }
}

function closePreview() {
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value);
  }
  previewUrl.value = '';
  previewDocument.value = null;
  previewDialog.value = false;
}

async function downloadCsv() {
  try {
    await downloadPropertiesCsv();
  } catch (error) {
    notifyError(error, 'Unable to download CSV export.');
  }
}

async function downloadBackup() {
  try {
    await downloadBackupJson();
  } catch (error) {
    notifyError(error, 'Unable to download backup export.');
  }
}

async function exportRecord() {
  if (!selected.value?.id) return;

  try {
    await downloadPropertyDossierExport(selected.value.id);
    $q.notify({ type: 'positive', message: 'Dossier opened in new tab. Use Print to save as PDF.', timeout: 4000 });
  } catch (error) {
    notifyError(error, 'Unable to export property dossier.');
  }
}

async function exportActivityCsv() {
  if (!selected.value?.id) return;

  try {
    await downloadPropertyActivityCsv(selected.value.id);
    $q.notify({ type: 'positive', message: 'Activity log exported.' });
  } catch (error) {
    notifyError(error, 'Unable to export activity log.');
  }
}

function printRecord() {
  if (!selected.value) return;

  const documentRows = (dossier.value?.property_documents || [])
    .concat(...(taxDeclarationTimeline.value || []).flatMap((entry) => entry.documents || []))
    .map((document) => `
    <tr>
      <td>${escapeHtml(document.document_type)}</td>
      <td>${escapeHtml(document.reference_number || document.file_name || '')}</td>
      <td>${escapeHtml(document.physical_copy_status || '')}</td>
      <td>${escapeHtml(document.storage_location || '')}</td>
    </tr>
  `).join('');

  const rows = taxHistory.value.map((td) => `
    <tr>
      <td>${escapeHtml(td.td_number)}</td>
      <td>${td.effectivity_year}</td>
      <td>${escapeHtml(td.owner?.name || '')}</td>
      <td>${escapeHtml(td.transaction_type || '')}</td>
      <td>${escapeHtml(td.status || '')}</td>
      <td>${money(td.assessed_value)}</td>
    </tr>
  `).join('');

  const printWindow = window.open('', '_blank');
  printWindow.document.write(`
    <!doctype html>
    <html>
      <head>
        <title>Property Record ${escapeHtml(selected.value.pin)}</title>
        <style>
          body { font-family: Arial, sans-serif; color: #1f2933; margin: 32px; }
          h1 { margin-bottom: 4px; }
          .meta { color: #52616f; margin-bottom: 24px; }
          table { border-collapse: collapse; width: 100%; margin-top: 16px; }
          th, td { border: 1px solid #c8d2dd; padding: 8px; text-align: left; font-size: 12px; }
          th { background: #edf2f7; }
        </style>
      </head>
      <body>
        <h1>Provincial Assessor Property Record</h1>
        <div class="meta">${escapeHtml(selected.value.pin)} | ${escapeHtml(selected.value.lot_number)} | ${escapeHtml(selected.value.barangay)}, ${escapeHtml(selected.value.municipality)}</div>
        <strong>Current Owner:</strong> ${escapeHtml(currentTd(selected.value)?.owner?.name || 'No active owner')}<br>
        <strong>Current TD:</strong> ${escapeHtml(currentTd(selected.value)?.td_number || 'None')}
        <table>
          <thead>
            <tr>
              <th>TD Number</th>
              <th>Year</th>
              <th>Owner</th>
              <th>Transaction</th>
              <th>Status</th>
              <th>Assessed Value</th>
            </tr>
          </thead>
          <tbody>${rows}</tbody>
        </table>
        <h2 style="margin-top:28px;font-size:16px;">Documents</h2>
        <table>
          <thead>
            <tr>
              <th>Type</th>
              <th>Reference</th>
              <th>Physical Status</th>
              <th>Location</th>
            </tr>
          </thead>
          <tbody>${documentRows || '<tr><td colspan="4">No documents</td></tr>'}</tbody>
        </table>
      </body>
    </html>
  `);
  printWindow.document.close();
  printWindow.focus();
  printWindow.print();
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function statusColor(status) {
  return {
    Active: 'indigo-9',
    Draft: 'blue-grey-6',
    'For Review': 'indigo-7',
    Superseded: 'blue-grey-5',
    Cancelled: 'blue-grey-4',
    Archived: 'blue-grey-4'
  }[status] || 'indigo-8';
}

function statusKey(status) {
  return {
    Active: 'active',
    Draft: 'draft',
    'For Review': 'review',
    Superseded: 'superseded',
    Cancelled: 'cancelled',
    Archived: 'archived'
  }[status] || 'neutral';
}

function assessmentTypeIcon(type) {
  return {
    Land: 'landscape',
    Building: 'apartment',
    Machinery: 'precision_manufacturing',
    Improvement: 'construction',
    Special: 'star'
  }[type] || 'article';
}

function documentTypeIcon(type) {
  return {
    'Tax Declaration': 'receipt_long',
    'Deed of Sale': 'gavel',
    'Transfer Certificate of Title': 'verified',
    'Survey Plan': 'map',
    FAAS: 'description',
    Certification: 'verified_user',
    'Owner Request': 'mail'
  }[type] || 'description';
}

function docStatusPillClass(status) {
  return {
    'On File': 'td-card-pill--active',
    'For Scanning': '',
    Released: 'td-card-pill--superseded',
    Returned: 'td-card-pill--active',
    Missing: 'td-card-pill--cancelled',
    Archived: 'td-card-pill--superseded'
  }[status] || '';
}

function trailEventIcon(action) {
  if (!action) return 'task_alt';
  if (action.includes('approved')) return 'verified';
  if (action.includes('cancelled') || action.includes('archived')) return 'block';
  if (action.includes('created') || action.includes('added')) return 'add_circle';
  if (action.includes('updated') || action.includes('edited')) return 'edit';
  if (action.includes('digitized') || action.includes('scanned')) return 'scanner';
  if (action.includes('moved')) return 'sync_alt';
  return 'task_alt';
}

function physicalStatusColor(status) {
  return {
    'On File': 'indigo-9',
    'For Scanning': 'indigo-7',
    Released: 'blue-grey-6',
    Returned: 'indigo-8',
    Missing: 'blue-grey-5',
    Archived: 'blue-grey-5'
  }[status] || 'indigo-8';
}

function roleLabel(role) {
  return {
    admin: 'Administrator',
    assessor: 'Assessor',
    records_staff: 'Records Staff',
    viewer: 'Viewer'
  }[role] || 'User';
}

function assessmentCount(property) {
  return (property?.assessment_records || []).length
    || (property?.tax_declarations || []).reduce((sum, td) => sum + (td.assessment_records?.length || 0), 0);
}

function documentLocationLine(document) {
  const parts = [
    document.storage_location,
    document.shelf_number,
    document.box_number,
    document.folder_number
  ].filter(Boolean);

  return parts.length ? parts.join(' / ') : 'No physical location recorded';
}

function money(value) {
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0));
}

// PIN Auto-formatter: 14 digits → XXX-XX-XXX-XXX-XXX
function formatPin(value) {
  if (!value) return '';
  const digits = String(value).replace(/\D/g, '').slice(0, 14);
  const parts = [];
  if (digits.length > 0) parts.push(digits.slice(0, 3));
  if (digits.length > 3) parts.push(digits.slice(3, 5));
  if (digits.length > 5) parts.push(digits.slice(5, 8));
  if (digits.length > 8) parts.push(digits.slice(8, 11));
  if (digits.length > 11) parts.push(digits.slice(11, 14));
  return parts.join('-');
}

function onPinInput(formObj, fieldName) {
  const formatted = formatPin(formObj[fieldName]);
  if (formObj[fieldName] !== formatted) {
    formObj[fieldName] = formatted;
  }
}

// Extract the most useful error message from an axios error
function errorMessage(error, fallback = 'Operation failed.') {
  // Network/connection issues
  if (error?.code === 'ERR_NETWORK' || error?.message === 'Network Error') {
    return 'Cannot reach the server. Please check your connection or try again.';
  }
  if (error?.code === 'ECONNABORTED') {
    return 'The server took too long to respond. Please try again.';
  }

  const status = error?.response?.status;

  // Auth errors
  if (status === 401) {
    return 'Your session has expired. Please log in again.';
  }
  if (status === 403) {
    return 'You do not have permission to perform this action.';
  }
  if (status === 404) {
    return 'The requested record was not found.';
  }
  if (status === 422) {
    // Validation errors
    const errors = error?.response?.data?.errors;
    if (errors) {
      const firstError = Object.values(errors).flat()[0];
      return firstError || 'Please check your input and try again.';
    }
    return error?.response?.data?.message || 'Please check your input and try again.';
  }
  if (status === 429) {
    return 'Too many requests. Please wait a moment and try again.';
  }
  if (status >= 500) {
    return 'A server error occurred. Please try again or contact support.';
  }

  // Generic server message (clean, no stack traces)
  const serverMessage = error?.response?.data?.message;
  if (serverMessage && !serverMessage.includes('Stack trace') && !serverMessage.includes('SQLSTATE')) {
    return serverMessage;
  }

  return fallback;
}

// Show notification with the actual error from a caught error
function notifyError(error, fallback) {
  $q.notify({ type: 'negative', message: errorMessage(error, fallback), timeout: 5000 });
}

function numberFormat(value) {
  return new Intl.NumberFormat('en-PH').format(Number(value || 0));
}

function dateInputValue(value) {
  if (!value) return '';

  return new Date(value).toISOString().slice(0, 10);
}

function dateFormat(value) {
  if (!value) return 'No date';

  return new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium' }).format(new Date(value));
}

async function bootstrapRecords() {
  await loadReferences();
  // Records list and dashboard totals are independent — fire in parallel.
  await Promise.all([loadRecords(), refreshDashboard()]);

  const propertyId = Number(route.query.propertyId);
  if (propertyId) {
    await openPropertyById(propertyId);
  }
}

onMounted(bootstrapRecords);

// Keyboard shortcuts
function handleKeyboardShortcut(e) {
  // Don't intercept when typing in inputs
  const tag = e.target?.tagName;
  const isTyping = tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || e.target?.isContentEditable;

  // Ctrl+K or Cmd+K to focus search
  if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
    e.preventDefault();
    const searchInput = document.querySelector('.filter-search input');
    if (searchInput) {
      searchInput.focus();
      searchInput.select();
    }
    return;
  }

  // ? to show keyboard shortcuts help (when not typing)
  if (!isTyping && e.key === '?') {
    e.preventDefault();
    shortcutsDialog.value = true;
    return;
  }

  // Escape to close property panel (but only when no dialogs are open)
  if (e.key === 'Escape' && selected.value && !entryDialog.value && !declarationDialog.value && !documentDialog.value && !editPropertyDialog.value && !assessmentDialog.value) {
    selected.value = null;
    dossier.value = null;
    return;
  }

  // Arrow keys for TD list navigation (when not typing)
  if (!isTyping && selected.value && taxDeclarationTimeline.value.length > 0) {
    const currentIdx = taxDeclarationTimeline.value.findIndex(
      (entry) => entry.tax_declaration?.id === selectedTdId.value
    );

    if (e.key === 'ArrowDown' || e.key === 'j') {
      e.preventDefault();
      const next = currentIdx < taxDeclarationTimeline.value.length - 1 ? currentIdx + 1 : 0;
      selectTd(taxDeclarationTimeline.value[next]);
    } else if (e.key === 'ArrowUp' || e.key === 'k') {
      e.preventDefault();
      const prev = currentIdx > 0 ? currentIdx - 1 : taxDeclarationTimeline.value.length - 1;
      selectTd(taxDeclarationTimeline.value[prev]);
    }
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeyboardShortcut);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyboardShortcut);
});

let searchDebounceTimer = null;

// Auto-save draft for New Property form
let draftSaveTimer = null;
watch(() => entryDialog.value, (open) => {
  if (open) {
    // Try to restore draft when dialog opens
    try {
      const draft = localStorage.getItem(DRAFT_KEY);
      if (draft) {
        const parsed = JSON.parse(draft);
        if (parsed && Object.keys(parsed).length > 0) {
          $q.notify({
            type: 'info',
            message: 'Draft restored from your last session.',
            timeout: 3000,
            actions: [{
              label: 'Discard',
              color: 'white',
              handler: () => {
                Object.assign(form, blankForm());
                localStorage.removeItem(DRAFT_KEY);
              }
            }]
          });
          Object.assign(form, parsed);
        }
      }
    } catch {
      // ignore
    }
  }
});

watch(() => form, () => {
  if (!entryDialog.value) return;
  clearTimeout(draftSaveTimer);
  draftSaveTimer = setTimeout(() => {
    try {
      localStorage.setItem(DRAFT_KEY, JSON.stringify(form));
      draftSaved.value = true;
      setTimeout(() => { draftSaved.value = false; }, 2000);
    } catch {
      // ignore
    }
  }, 1000);
}, { deep: true });

watch(() => ({
  keyword: keyword.value,
  municipality: municipality.value,
  ...filters
}), () => {
  if (sessionUser.value) {
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
      loadRecords();
      // Save successful searches with at least 2 chars
      if (keyword.value && keyword.value.length >= 2) {
        saveRecentSearch(keyword.value);
      }
    }, 400);
  }
}, { deep: true });

</script>

<style scoped>
.login-page {
  align-items: center;
  background: linear-gradient(160deg, #e8f0ef 0%, #f6f8f8 45%, #eef3f2 100%);
  display: flex;
  justify-content: center;
  min-height: 100vh;
  padding: 32px 20px;
}

.login-page.boot-page {
  flex-direction: column;
  gap: 16px;
}

.login-page-inner {
  max-width: 440px;
  width: 100%;
}

.login-page-header {
  align-items: center;
  display: flex;
  gap: 16px;
  margin-bottom: 24px;
}

.login-page-logo {
  align-items: center;
  background: #e1f3ef;
  border: 1px solid #b9ded7;
  border-radius: 12px;
  color: #0f766e;
  display: flex;
  flex-shrink: 0;
  height: 56px;
  justify-content: center;
  width: 56px;
}

.login-page-kicker {
  color: #0f766e;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.04em;
  margin: 0 0 4px;
  text-transform: uppercase;
}

.login-page h1 {
  font-size: 1.75rem;
  font-weight: 700;
  line-height: 1.2;
  margin: 0 0 8px;
}

.login-page-subtitle {
  color: #65727f;
  font-size: 14px;
  line-height: 1.5;
  margin: 0;
}

.login-card {
  border-radius: 12px !important;
  box-shadow: 0 20px 48px rgba(15, 63, 70, 0.12) !important;
  width: 100%;
}

.login-card-title {
  font-size: 1.15rem;
  font-weight: 700;
  margin: 0 0 16px;
}

.login-form {
  display: grid;
  gap: 14px;
}

.login-submit {
  margin-top: 4px;
  width: 100%;
}

.login-hint {
  color: #65727f;
  font-size: 12px;
  margin: 14px 0 0;
  text-align: center;
}

.app-header {
  background: #18324a !important;
}

.app-toolbar {
  min-height: 64px;
  padding: 0 20px;
}

.header-brand-mark {
  background: #d7b46a !important;
  color: #18324a !important;
}

.workspace-nav {
  background: #fff;
  border: 1px solid #d8e2ec;
  border-radius: 12px;
}

.recent-band {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.recent-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.workspace-page {
  --ui-bg: #dfe7f5;
  --ui-surface: rgba(255, 255, 255, 0.96);
  --ui-surface-soft: rgba(247, 250, 255, 0.98);
  --ui-surface-strong: #edf2fb;
  --ui-border: rgba(20, 39, 67, 0.11);
  --ui-border-strong: rgba(20, 39, 67, 0.18);
  --ui-primary: #2f62af;
  --ui-primary-strong: #183154;
  --ui-primary-soft: rgba(47, 98, 175, 0.08);
  --ui-ink: #162742;
  --ui-muted: #657892;
  --ui-success: #237a57;
  --ui-violet: #6657a8;
  --ui-amber: #a66321;
  --ui-danger: #b42318;
  background:
    radial-gradient(circle at top right, rgba(67, 116, 199, 0.15), transparent 24%),
    linear-gradient(180deg, #edf2fb 0%, #dae4f4 100%);
  color: var(--ui-ink);
  min-height: 100vh;
}

.workspace-shell {
  max-width: 1560px;
  margin: 0 auto;
  padding: 24px;
}


.toolbar-band,
.search-band,
.filter-band {
  background: var(--ui-surface);
  border: 1px solid var(--ui-border);
  border-radius: 8px;
  box-shadow: 0 14px 32px rgba(15, 63, 70, 0.06);
}

.toolbar-band {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  padding: 18px;
}

.brand-block,
.toolbar-actions {
  align-items: center;
  display: flex;
  gap: 12px;
  min-width: 0;
}

.brand-mark {
  align-items: center;
  background: var(--ui-primary-soft);
  border: 1px solid #b9ded7;
  border-radius: 8px;
  color: var(--ui-primary);
  display: flex;
  height: 52px;
  justify-content: center;
  width: 52px;
  flex: 0 0 auto;
}

.search-band {
  margin-top: 14px;
  display: grid;
  grid-template-columns: minmax(240px, 1fr) minmax(180px, 260px) minmax(180px, 260px);
  gap: 16px;
  padding: 14px;
}

.filter-band {
  display: grid;
  grid-template-columns: repeat(5, minmax(145px, 1fr)) repeat(3, minmax(160px, 1fr)) repeat(2, minmax(110px, 0.7fr));
  gap: 10px;
  margin-top: 10px;
  align-items: center;
  padding: 14px;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.metric-tile {
  align-items: center;
  background: var(--ui-surface);
  border: 1px solid var(--ui-border);
  border-radius: 8px;
  box-shadow: 0 10px 24px rgba(15, 63, 70, 0.05);
  display: flex;
  gap: 12px;
  min-width: 0;
  padding: 16px;
}

.metric-icon {
  align-items: center;
  background: var(--ui-primary-soft);
  border-radius: 8px;
  color: var(--ui-primary);
  display: flex;
  flex: 0 0 auto;
  font-size: 25px;
  height: 44px;
  justify-content: center;
  width: 44px;
}

.metric-icon.success {
  background: #e4f4ea;
  color: var(--ui-success);
}

.metric-icon.violet {
  background: #eceafb;
  color: var(--ui-violet);
}

.metric-icon.amber {
  background: #fff0dc;
  color: var(--ui-amber);
}

.metric-tile span,
.detail-grid span,
.record-jacket span {
  color: var(--ui-muted);
  font-size: 12px;
  font-weight: 600;
}

.metric-tile strong {
  color: var(--ui-ink);
  font-size: 26px;
  line-height: 1;
}

.content-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 14px;
  margin-top: 14px;
  align-items: start;
}

.records-table {
  border-radius: 12px;
  overflow: hidden;
}

.profile-panel {
  border-radius: 12px;
  overflow: visible;
}

.record-back-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 16px;
  background: rgba(247, 250, 255, 0.95);
  border-bottom: 1px solid rgba(20, 39, 67, 0.08);
  position: sticky;
  top: 0;
  z-index: 10;
  backdrop-filter: blur(8px);
}

.record-bottom-strip {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 16px 20px;
  border-top: 1px solid rgba(20, 39, 67, 0.08);
  background: rgba(247, 250, 255, 0.6);
}

.bottom-strip-section {
  background: #fff;
  border: 1px solid rgba(20, 39, 67, 0.08);
  border-radius: 12px;
  padding: 12px;
}

.bottom-strip-head {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
  font-size: 0.85rem;
  color: #2f62af;
}

.bottom-strip-actions {
  display: flex;
  justify-content: flex-end;
  padding-top: 4px;
}

.activity-drawer {
  width: 480px;
  max-width: 100vw;
  height: 100vh;
  display: flex;
  flex-direction: column;
  border-radius: 0;
}

.activity-drawer-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  background: linear-gradient(90deg, #183154 0%, #245ea8 54%, #2f76d4 100%);
  color: #fff;
}

.activity-drawer-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 1.05rem;
  font-weight: 800;
}

.activity-drawer-actions {
  display: flex;
  align-items: center;
  gap: 6px;
}

.activity-drawer-body {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
}

.records-table {
  border-color: var(--ui-border);
  box-shadow: 0 18px 42px rgba(14, 34, 63, 0.16);
  border-radius: 22px;
  overflow: hidden;
}

.records-table :deep(.q-table__top) {
  background: linear-gradient(90deg, #183154 0%, #245ea8 54%, #2f76d4 100%);
  color: white;
  min-height: 64px;
  padding: 16px 20px;
  box-shadow: 0 12px 28px rgba(15, 37, 74, 0.28);
}

.records-table :deep(.q-table__title) {
  font-size: 1.1rem;
  font-weight: 800;
  letter-spacing: 0.02em;
}

.records-table :deep(thead tr) {
  background: linear-gradient(180deg, #f0f4fa 0%, #e6ecf6 100%);
}

.records-table :deep(th) {
  color: #183154;
  font-size: 0.8rem;
  font-weight: 800;
  height: 48px;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.records-table :deep(td) {
  border-color: rgba(20, 39, 67, 0.06);
  color: var(--ui-ink);
  height: 64px;
  font-size: 0.95rem;
}

.records-table :deep(tbody tr) {
  cursor: pointer;
  transition: all 0.2s ease;
}

.records-table :deep(tbody tr:hover) {
  background: rgba(47, 98, 175, 0.06);
}

.records-table :deep(.q-table__bottom) {
  border-top: 1px solid var(--ui-border);
  color: var(--ui-muted);
  min-height: 52px;
}

.table-tools,
.document-side {
  display: flex;
  align-items: center;
  flex-wrap: nowrap;
  gap: 8px;
}

.document-side :deep(.q-btn) {
  flex: 0 0 auto;
  width: auto;
}

.profile-panel {
  background: var(--ui-surface);
  border: 1px solid rgba(20, 39, 67, 0.11);
  border-radius: 22px;
  box-shadow: 0 18px 42px rgba(14, 34, 63, 0.16);
  backdrop-filter: blur(18px);
}

.profile-panel :deep(.q-item__section--side) {
  flex: 0 0 auto;
  min-width: 0;
}

.profile-header {
  background: linear-gradient(90deg, #183154 0%, #245ea8 54%, #2f76d4 100%);
  border-bottom: 1px solid rgba(20, 39, 67, 0.08);
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  padding: 22px 24px;
  color: #fff;
  position: relative;
  overflow: hidden;
  box-shadow: 0 12px 28px rgba(15, 37, 74, 0.28);
}

.profile-header::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -10%;
  width: 300px;
  height: 300px;
  background: radial-gradient(circle, rgba(255, 208, 126, 0.22), transparent 44%);
  border-radius: 50%;
  z-index: 0;
}

.profile-header .header-content {
  z-index: 1;
}

.profile-header .ws-kicker {
  color: rgba(255, 255, 255, 0.9);
  font-weight: 800;
  letter-spacing: 0.12em;
  background: none;
  padding: 0;
  box-shadow: none;
}

.profile-header .text-h6 {
  color: #fff;
  font-weight: 900;
}

.profile-header .text-body2 {
  color: rgba(255, 255, 255, 0.85) !important;
}

.profile-header .status-badge {
  z-index: 1;
}

.jacket-toolbar {
  align-items: center;
  background: linear-gradient(180deg, rgba(247, 250, 255, 0.98) 0%, rgba(239, 245, 253, 0.96) 100%);
  border-bottom: 1px solid rgba(20, 39, 67, 0.08);
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  justify-content: space-between;
  padding: 16px 24px;
}

.jacket-toolbar-group {
  align-items: center;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.jacket-toolbar :deep(.q-btn),
.jacket-toolbar-group :deep(.q-btn),
.toolbar-actions :deep(.q-btn),
.inline-actions :deep(.q-btn),
.search-pagination :deep(.q-btn),
.document-side :deep(.q-btn) {
  flex: 0 0 auto;
  width: auto;
}

.jacket-toolbar :deep(.q-btn__content),
.toolbar-actions :deep(.q-btn__content),
.inline-actions :deep(.q-btn__content),
.document-side :deep(.q-btn__content) {
  flex: 0 0 auto;
  flex-grow: 0 !important;
  width: auto;
}

.jacket-toolbar :deep(.q-btn) {
  min-width: 0;
}

.profile-panel :deep(.q-tab-panels) {
  background: linear-gradient(180deg, rgba(247, 250, 255, 0.98) 0%, rgba(239, 245, 253, 0.96) 100%);
  padding: 24px;
}

.profile-panel :deep(.q-panel) {
  background: transparent;
  padding: 0;
}

.profile-panel :deep(.q-tab) {
  font-weight: 700;
  transition: all 0.3s ease;
}

.profile-panel :deep(.q-tab--active) {
  background: rgba(47, 98, 175, 0.1);
  border-radius: 8px 8px 0 0;
}

.jacket-section {
  border: 1px solid rgba(20, 39, 67, 0.08);
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.96);
  margin: 20px;
  padding: 24px;
  box-shadow: 0 10px 24px rgba(21, 43, 78, 0.08);
}

.section-head {
  align-items: center;
  display: flex;
  gap: 12px;
  justify-content: space-between;
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid rgba(20, 39, 67, 0.08);
  flex-wrap: wrap;
}

.section-head-content {
  display: flex;
  align-items: center;
  gap: 12px;
}

.section-head-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.section-title {
  font-size: 1.1rem;
  font-weight: 800;
  line-height: 1.3;
  margin: 0;
  color: #162742;
}

.td-layout {
  display: grid;
  gap: 14px;
  grid-template-columns: minmax(220px, 280px) minmax(0, 1fr);
}

.td-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-height: none;
  overflow: visible;
  background: transparent;
  border: none;
  box-shadow: none;
  padding: 4px;
}

.td-card {
  display: flex;
  align-items: stretch;
  gap: 12px;
  width: 100%;
  padding: 0;
  border: 1.5px solid rgba(20, 39, 67, 0.1);
  border-radius: 12px;
  background: #fff;
  cursor: pointer;
  font: inherit;
  text-align: left;
  transition: all 0.2s ease;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(14, 34, 63, 0.04);
}

.td-card:hover {
  border-color: rgba(47, 98, 175, 0.4);
  box-shadow: 0 4px 12px rgba(14, 34, 63, 0.1);
  transform: translateY(-1px);
}

.td-card:focus-visible {
  outline: 2px solid #2f62af;
  outline-offset: 2px;
}

.td-card--active {
  border-color: #2f62af;
  background: linear-gradient(135deg, rgba(47, 98, 175, 0.06) 0%, rgba(47, 98, 175, 0.02) 100%);
  box-shadow: 0 4px 16px rgba(47, 98, 175, 0.18);
}

.td-card--current {
  border-left-width: 4px;
  border-left-color: #16a34a;
}

.td-card--current.td-card--active {
  border-left-color: #2f62af;
}

.td-card-year {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 12px 14px;
  background: linear-gradient(180deg, #f0f4fa 0%, #e6ecf6 100%);
  border-right: 1px solid rgba(20, 39, 67, 0.08);
  min-width: 70px;
}

.td-card--active .td-card-year {
  background: linear-gradient(180deg, rgba(47, 98, 175, 0.12) 0%, rgba(47, 98, 175, 0.06) 100%);
}

.td-card-year-label {
  font-size: 0.65rem;
  font-weight: 700;
  color: #657892;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin-bottom: 2px;
}

.td-card-year strong {
  font-size: 1.3rem;
  font-weight: 800;
  color: #162742;
  line-height: 1;
}

.td-card-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 10px 14px 10px 0;
  min-width: 0;
}

.td-card-top {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.td-card-number {
  font-size: 0.92rem;
  font-weight: 800;
  color: #162742;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.td-card-pill {
  font-size: 0.62rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  padding: 2px 7px;
  border-radius: 4px;
  background: rgba(20, 39, 67, 0.08);
  color: #657892;
}

.td-card-pill--active {
  background: rgba(22, 163, 74, 0.12);
  color: #166534;
}

.td-card-pill--superseded {
  background: rgba(101, 120, 146, 0.12);
  color: #475569;
}

.td-card-pill--cancelled {
  background: rgba(180, 35, 24, 0.1);
  color: #991b1b;
}

.td-card-meta {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  color: #657892;
  font-size: 0.78rem;
  font-weight: 600;
}

.td-card-meta span {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.td-card-owner {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 0.82rem;
  font-weight: 700;
  color: #162742;
  margin-top: 2px;
}

.td-card-owner .q-icon {
  color: #2f62af;
}

.td-list-item--active {
  background: rgba(47, 98, 175, 0.08) !important;
  border-left: 4px solid #2f62af;
}

.td-list-badges {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.td-detail-panel {
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(244, 248, 253, 0.98) 100%);
  border: 1px solid rgba(20, 39, 67, 0.08);
  border-radius: 16px;
  display: flex;
  flex-direction: column;
  gap: 0;
  max-height: none;
  overflow: visible;
  padding: 0;
  box-shadow: 0 10px 24px rgba(21, 43, 78, 0.08);
}

.td-detail-panel--empty {
  align-items: center;
  color: var(--ui-muted);
  justify-content: center;
  text-align: center;
}

.td-detail-panel--empty span {
  font-size: 13px;
  margin-top: 4px;
}

.td-detail-header {
  align-items: flex-start;
  border-bottom: 1px solid rgba(20, 39, 67, 0.08);
  display: flex;
  gap: 12px;
  justify-content: space-between;
  padding: 18px 20px;
  margin-bottom: 0;
}

.td-detail-title {
  font-size: 1.2rem;
  font-weight: 800;
  line-height: 1.2;
  margin: 4px 0 0;
  color: #162742;
}

.td-detail-meta {
  color: var(--ui-muted);
  font-size: 12px;
  margin: 4px 0 0;
}

.info-grid {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.info-cell {
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(244, 248, 253, 0.98) 100%);
  border: 1px solid rgba(20, 39, 67, 0.08);
  border-left: 3px solid #2f62af;
  border-radius: 14px;
  display: grid;
  gap: 8px;
  min-width: 0;
  padding: 14px 16px;
  box-shadow: 0 4px 12px rgba(21, 43, 78, 0.06);
  transition: all 0.3s ease;
}

.info-cell:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 24px rgba(14, 34, 63, 0.12);
  border-left-color: #1e3f78;
}

.info-cell span {
  color: #657892;
  font-size: 0.73rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.info-cell strong {
  font-size: 0.95rem;
  font-weight: 700;
  color: #162742;
  overflow-wrap: anywhere;
}

.info-grid--3col {
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

/* FAAS Section Styles */
.faas-section {
  background: rgba(255, 255, 255, 0.96);
  border: 1px solid rgba(20, 39, 67, 0.08);
  border-radius: 16px;
  padding: 18px;
  box-shadow: 0 4px 12px rgba(21, 43, 78, 0.06);
}

.faas-section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.82rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #2f62af;
  margin-bottom: 14px;
  padding-bottom: 10px;
  border-bottom: 1px solid rgba(20, 39, 67, 0.08);
}

.faas-summary-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.faas-summary-card {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 14px 16px;
  border: 1px solid rgba(20, 39, 67, 0.08);
  border-radius: 14px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(244, 248, 253, 0.98) 100%);
}

.faas-summary-card span {
  color: #657892;
  font-size: 0.73rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.faas-summary-card .faas-value {
  color: #162742;
  font-size: 1.2rem;
  font-weight: 800;
}

.faas-summary-card--highlight {
  background: linear-gradient(135deg, rgba(47, 98, 175, 0.08) 0%, rgba(47, 98, 175, 0.04) 100%);
  border-color: rgba(47, 98, 175, 0.18);
}

.faas-summary-card--highlight .faas-value {
  color: #2f62af;
}

.faas-memo {
  color: #657892;
  font-size: 0.92rem;
  line-height: 1.6;
  margin: 0;
  padding: 12px 14px;
  background: rgba(244, 248, 253, 0.6);
  border-radius: 12px;
  border: 1px solid rgba(20, 39, 67, 0.06);
}

.faas-assessment-card {
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(244, 248, 253, 0.98) 100%);
  border: 1px solid rgba(20, 39, 67, 0.08);
  border-left: 4px solid #2f62af;
  border-radius: 14px;
  padding: 16px;
  box-shadow: 0 4px 12px rgba(21, 43, 78, 0.06);
}

.faas-assessment-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 12px;
  padding-bottom: 10px;
  border-bottom: 1px solid rgba(20, 39, 67, 0.06);
}

.faas-assessment-head strong {
  font-size: 0.95rem;
  font-weight: 800;
  color: #162742;
}

.faas-assessment-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
}

.faas-assessment-grid div {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.faas-assessment-grid span {
  color: #657892;
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.faas-assessment-grid strong {
  color: #162742;
  font-size: 0.88rem;
  font-weight: 700;
}

.faas-assessment-notes {
  margin: 10px 0 0;
  padding: 10px 12px;
  background: rgba(244, 248, 253, 0.6);
  border-radius: 10px;
  color: #657892;
  font-size: 0.85rem;
  line-height: 1.5;
}

.faas-extra-info {
  margin-top: 14px;
  padding: 12px 14px;
  border-radius: 10px;
  background: rgba(47, 98, 175, 0.04);
  border: 1px solid rgba(47, 98, 175, 0.12);
}

.faas-extra-title {
  font-size: 0.75rem;
  font-weight: 800;
  color: #2f62af;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin-bottom: 10px;
}

.faas-extra-value {
  font-weight: 700;
  color: #162742;
  text-transform: none;
  letter-spacing: 0;
}

.td-detail-header-badges {
  display: flex;
  flex-direction: row;
  gap: 6px;
  align-items: center;
  flex-wrap: wrap;
}

.td-detail-header-badges .q-badge {
  font-size: 0.7rem;
  padding: 4px 10px;
  font-weight: 800;
  border-radius: 4px;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  box-shadow: none;
}

.td-tabs {
  border-bottom: 1px solid rgba(20, 39, 67, 0.08);
  background: rgba(247, 250, 255, 0.6);
  padding: 0 16px;
}

.td-tabs .q-tab {
  min-height: 44px;
  font-weight: 700;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.td-tab-panels {
  background: transparent;
  flex: 1;
}

.td-tab-content {
  padding: 20px;
}

.faas-kv-list {
  display: grid;
  gap: 1px;
  background: rgba(20, 39, 67, 0.04);
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid rgba(20, 39, 67, 0.06);
}

.faas-kv {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 16px;
  background: #fff;
}

.faas-kv span {
  color: #657892;
  font-size: 0.85rem;
  font-weight: 700;
}

.faas-kv strong {
  color: #162742;
  font-size: 0.95rem;
  font-weight: 700;
  text-align: right;
}

.faas-values-strip {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
  margin-top: 16px;
}

.faas-val-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 14px;
  border-radius: 12px;
  background: rgba(244, 248, 253, 0.8);
  border: 1px solid rgba(20, 39, 67, 0.06);
}

.faas-val-item span {
  color: #657892;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.faas-val-item strong {
  color: #162742;
  font-size: 1.1rem;
  font-weight: 800;
}

.faas-val-item--primary {
  background: rgba(47, 98, 175, 0.08);
  border-color: rgba(47, 98, 175, 0.16);
}

.faas-val-item--primary strong {
  color: #2f62af;
}

.faas-memo-box {
  margin-top: 16px;
  padding: 14px 16px;
  border-radius: 12px;
  background: rgba(244, 248, 253, 0.6);
  border: 1px solid rgba(20, 39, 67, 0.06);
}

.faas-memo-box span {
  display: block;
  color: #657892;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin-bottom: 8px;
}

.faas-memo-box p {
  margin: 0;
  color: #162742;
  font-size: 0.92rem;
  line-height: 1.6;
}

/* ========== Enhanced FAAS Info Tab ========== */

.faas-value-banner {
  display: flex;
  align-items: center;
  gap: 0;
  padding: 16px 20px;
  border-radius: 14px;
  background: linear-gradient(135deg, #f0f4fa 0%, #e6ecf6 100%);
  border: 1px solid rgba(20, 39, 67, 0.08);
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.faas-banner-block {
  flex: 1;
  min-width: 110px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 0 12px;
}

.faas-banner-block span {
  font-size: 0.7rem;
  font-weight: 700;
  color: #657892;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.faas-banner-block strong {
  font-size: 1.05rem;
  font-weight: 800;
  color: #162742;
  line-height: 1.2;
}

.faas-banner-block--primary {
  background: linear-gradient(135deg, rgba(47, 98, 175, 0.12) 0%, rgba(47, 98, 175, 0.06) 100%);
  border: 1px solid rgba(47, 98, 175, 0.18);
  border-radius: 10px;
  padding: 10px 14px;
}

.faas-banner-block--primary span {
  color: #2f62af;
}

.faas-banner-block--primary strong {
  font-size: 1.2rem;
  color: #1e3f78;
}

.faas-banner-block--operator {
  flex: 0 0 24px;
  min-width: 24px;
  align-items: center;
  color: #657892;
  padding: 0;
}

.faas-banner-divider {
  width: 1px;
  height: 36px;
  background: rgba(20, 39, 67, 0.14);
  margin: 0 8px;
}

.faas-info-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.faas-info-card {
  background: #fff;
  border: 1px solid rgba(20, 39, 67, 0.08);
  border-radius: 12px;
  overflow: hidden;
}

.faas-info-card--accent {
  background: linear-gradient(135deg, rgba(47, 98, 175, 0.04) 0%, rgba(47, 98, 175, 0.01) 100%);
  border-color: rgba(47, 98, 175, 0.18);
}

.faas-info-card-head {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  background: rgba(247, 250, 255, 0.6);
  border-bottom: 1px solid rgba(20, 39, 67, 0.06);
  font-size: 0.78rem;
  font-weight: 800;
  color: #2f62af;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.faas-info-card-head .q-icon {
  color: #2f62af;
}

.faas-info-card-body {
  padding: 12px 14px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.faas-info-card-body--row {
  flex-direction: row;
  flex-wrap: wrap;
  gap: 16px;
}

.faas-info-card-body--row .faas-info-row {
  flex: 1;
  min-width: 180px;
}

.faas-info-row {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.faas-info-row span {
  font-size: 0.7rem;
  font-weight: 700;
  color: #657892;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.faas-info-row strong {
  font-size: 0.92rem;
  font-weight: 700;
  color: #162742;
  line-height: 1.4;
  word-break: break-word;
}

.faas-mono {
  font-family: 'Courier New', Courier, monospace;
  letter-spacing: 0.04em;
  font-size: 0.88rem !important;
}

.faas-memo-card {
  border: 1px solid rgba(217, 119, 6, 0.18);
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(254, 252, 232, 0.8) 0%, rgba(255, 251, 235, 0.6) 100%);
  padding: 14px 16px;
}

.faas-memo-card-head {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.78rem;
  font-weight: 800;
  color: #92400e;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin-bottom: 8px;
}

.faas-memo-card-head .q-icon {
  color: #d97706;
}

.faas-memo-card p {
  margin: 0;
  color: #451a03;
  font-size: 0.92rem;
  line-height: 1.6;
  font-style: italic;
}

@media (max-width: 700px) {
  .faas-info-grid {
    grid-template-columns: 1fr;
  }

  .faas-banner-divider,
  .faas-banner-block--operator {
    display: none;
  }
}

.td-detail-footer {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  padding: 14px 20px;
  border-top: 1px solid rgba(20, 39, 67, 0.06);
}

/* Confirmation Dialog */
.confirm-card {
  width: min(420px, 90vw);
  border-radius: 22px;
  overflow: hidden;
  box-shadow: 0 28px 60px rgba(17, 39, 72, 0.28);
  text-align: center;
}

.confirm-icon-area {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 32px 24px 20px;
  color: #fff;
}

.confirm-icon-area--danger {
  background: linear-gradient(135deg, #1e3f78 0%, #2f62af 100%);
}

.confirm-icon-area--warning {
  background: linear-gradient(135deg, #245ea8 0%, #3b82f6 100%);
}

.confirm-icon-area--success {
  background: linear-gradient(135deg, #183154 0%, #2f62af 100%);
}

.confirm-icon-area--info {
  background: linear-gradient(90deg, #183154 0%, #245ea8 54%, #2f76d4 100%);
}

.confirm-body {
  padding: 24px 28px 16px;
}

.confirm-title {
  margin: 0 0 10px;
  font-size: 1.3rem;
  font-weight: 800;
  color: #162742;
}

.confirm-message {
  margin: 0;
  font-size: 0.95rem;
  color: #657892;
  line-height: 1.5;
}

.confirm-detail {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  margin-top: 14px;
  padding: 12px 14px;
  border-radius: 12px;
  background: rgba(47, 98, 175, 0.06);
  border: 1px solid rgba(47, 98, 175, 0.14);
  text-align: left;
}

.confirm-detail .q-icon {
  color: #2f62af;
  flex-shrink: 0;
  margin-top: 2px;
}

.confirm-detail span {
  font-size: 0.82rem;
  color: #1e3f78;
  line-height: 1.5;
}

.confirm-actions {
  display: flex;
  justify-content: center;
  gap: 12px;
  padding: 8px 28px 24px;
}

.confirm-actions .q-btn {
  min-width: 130px;
  min-height: 42px;
  border-radius: 12px;
  font-weight: 700;
}

/* Document Preview */
.preview-card {
  display: flex;
  flex-direction: column;
  height: 100%;
  border-radius: 0;
}

.preview-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 20px;
  background: linear-gradient(90deg, #183154 0%, #245ea8 54%, #2f76d4 100%);
  color: #fff;
}

.preview-header-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.preview-header-info strong {
  display: block;
  font-size: 1rem;
  font-weight: 800;
}

.preview-header-info span {
  display: block;
  font-size: 0.82rem;
  color: rgba(255, 255, 255, 0.75);
}

.preview-header-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.preview-body {
  flex: 1;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #1a1a1a;
}

.preview-frame {
  width: 100%;
  height: 100%;
  border: none;
}

.preview-image {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

.preview-unsupported {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 48px;
  text-align: center;
  color: #94a3b8;
}

.preview-unsupported strong {
  font-size: 1.1rem;
  color: #e2e8f0;
}

.preview-unsupported span {
  font-size: 0.9rem;
}

@media (max-width: 600px) {
  .faas-values-strip {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 900px) {
  .info-grid--3col {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .faas-summary-grid {
    grid-template-columns: 1fr;
  }

  .faas-assessment-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .form-grid--3col {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 600px) {
  .form-grid,
  .form-grid--3col {
    grid-template-columns: 1fr;
  }
}

.ui-block {
  background: rgba(255, 255, 255, 0.96);
  border: 1px solid rgba(20, 39, 67, 0.08);
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 4px 12px rgba(21, 43, 78, 0.06);
}

.ui-block-head {
  align-items: center;
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  justify-content: space-between;
  margin-bottom: 14px;
  padding-bottom: 12px;
  border-bottom: 1px solid rgba(20, 39, 67, 0.08);
}

.ui-block-title {
  font-size: 0.85rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #2f62af;
}

.ui-block-body {
  color: var(--ui-muted);
  font-size: 13px;
  margin: 0;
}

.ui-stack {
  display: grid;
  gap: 8px;
}

.ui-row-card {
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(244, 248, 253, 0.98) 100%);
  border: 1px solid rgba(20, 39, 67, 0.08);
  border-left: 4px solid #2f62af;
  border-radius: 14px;
  padding: 14px 16px;
  box-shadow: 0 4px 12px rgba(21, 43, 78, 0.06);
  transition: all 0.3s ease;
}

.ui-row-card:hover {
  transform: translateX(4px);
  box-shadow: 0 10px 24px rgba(14, 34, 63, 0.12);
  border-left-color: #1e3f78;
}

.ui-row-card-head {
  align-items: center;
  display: flex;
  gap: 8px;
  justify-content: space-between;
}

.ui-row-card-meta {
  color: var(--ui-muted);
  display: flex;
  flex-wrap: wrap;
  font-size: 12px;
  gap: 8px;
  margin-top: 6px;
}

.ui-row-card p {
  color: var(--ui-muted);
  font-size: 12px;
  margin: 6px 0 0;
}

.ui-row-actions {
  margin-top: 8px;
}

.ui-list {
  border-radius: 8px;
  border: 1px solid var(--ui-border);
  overflow: hidden;
}

.empty-state.compact {
  padding: 14px;
  font-size: 13px;
}

.jacket-tabs {
  background: linear-gradient(180deg, rgba(247, 250, 255, 0.98) 0%, rgba(239, 245, 253, 0.96) 100%);
  padding: 0 20px;
  border-top: 1px solid rgba(20, 39, 67, 0.08);
}

.td-detail-actions {
  background: transparent;
  border-bottom: none;
  border-top: 1px solid var(--ui-border);
  margin-top: auto;
  padding: 10px 0 0;
}

.section-kicker {
  color: #2f62af;
  font-size: 0.73rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.record-jacket,
.detail-grid {
  padding: 18px;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.record-jacket {
  grid-template-columns: repeat(4, minmax(0, 1fr));
  border-top: none;
  border-bottom: none;
  background: linear-gradient(180deg, rgba(247, 250, 255, 0.98) 0%, rgba(239, 245, 253, 0.96) 100%);
  padding: 12px 18px;
}

.jacket-metric {
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(244, 248, 253, 0.98) 100%);
  border: 1px solid rgba(20, 39, 67, 0.08);
  border-radius: 12px;
  padding: 10px 12px;
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 10px;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(21, 43, 78, 0.06);
}

.jacket-metric:hover {
  transform: translateY(-4px);
  box-shadow: 0 18px 42px rgba(14, 34, 63, 0.16);
  border-color: rgba(47, 98, 175, 0.18);
}

.jacket-metric .metric-icon {
  font-size: 24px;
  color: var(--ws-blue);
  background: rgba(47, 98, 175, 0.12);
  padding: 8px;
  border-radius: 10px;
  width: fit-content;
  flex-shrink: 0;
}

.jacket-metric span {
  color: var(--ws-muted);
  font-size: 0.65rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  display: block;
}

.jacket-metric strong {
  color: var(--ws-ink);
  font-size: 0.95rem;
  font-weight: 800;
  display: block;
  line-height: 1.2;
}

.detail-grid {
  border: 1px solid rgba(20, 39, 67, 0.08);
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.96);
  margin-bottom: 16px;
  box-shadow: 0 10px 24px rgba(21, 43, 78, 0.08);
}

.detail-grid div,
.record-jacket div {
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(244, 248, 253, 0.98) 100%);
  border: 1px solid rgba(20, 39, 67, 0.08);
  border-radius: 14px;
  display: grid;
  gap: 6px;
  min-width: 0;
  padding: 14px;
  box-shadow: 0 4px 12px rgba(21, 43, 78, 0.06);
}

.detail-grid strong,
.record-jacket strong {
  color: var(--ui-ink);
  min-width: 0;
  overflow-wrap: anywhere;
}

.mini-counts {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.history-list,
.document-groups,
.assessment-groups,
.assessment-lines {
  display: grid;
  gap: 10px;
}

.timeline-expansion,
.timeline-body,
.search-pagination,
.history-item,
.remarks-box,
.document-group,
.assessment-group,
.assessment-line {
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(244, 248, 253, 0.98) 100%);
  border: 1px solid rgba(20, 39, 67, 0.08);
  border-left: 4px solid #2f62af;
  border-radius: 14px;
  padding: 18px;
  box-shadow: 0 4px 12px rgba(21, 43, 78, 0.06);
  transition: all 0.3s ease;
}

.remarks-box:hover,
.document-group:hover,
.history-item:hover {
  transform: translateX(2px);
  box-shadow: 0 10px 24px rgba(14, 34, 63, 0.12);
}

.assessment-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.history-heading,
.history-meta,
.document-group-title {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  align-items: flex-start;
}

.history-heading div {
  display: grid;
  gap: 2px;
}

.history-heading span,
.history-meta,
.remarks-box {
  color: var(--ui-muted);
}

.history-meta {
  flex-wrap: wrap;
  margin-top: 8px;
  font-size: 12px;
  row-gap: 6px;
}

.panel-lead {
  color: var(--ui-muted);
  font-size: 13px;
  margin: 0 0 12px;
}

.timeline-block-title {
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.02em;
  margin-bottom: 6px;
  text-transform: uppercase;
}

.timeline-mini-row {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  font-size: 12px;
  margin-bottom: 4px;
}

.timeline-list {
  border-radius: 8px;
  overflow: hidden;
}

.history-item p {
  margin: 8px 0 0;
  color: var(--ui-muted);
}

.inline-actions {
  border-top: 1px solid var(--ui-border);
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
  margin-top: 10px;
  padding-top: 8px;
}

.document-group-title {
  align-items: center;
  border-bottom: 1px solid var(--ui-border);
  margin-bottom: 8px;
  padding-bottom: 8px;
}

.empty-state {
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(244, 248, 253, 0.98) 100%);
  border: 2px dashed rgba(47, 98, 175, 0.24);
  border-radius: 16px;
  color: var(--ui-muted);
  padding: 24px;
  text-align: center;
  box-shadow: 0 4px 12px rgba(21, 43, 78, 0.06);
  position: relative;
  overflow: hidden;
}

.empty-state::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, #183154 0%, #2f62af 50%, #183154 100%);
  background-size: 200% 100%;
  animation: shimmer 2s linear infinite;
}

@keyframes shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}

.empty-state.compact {
  padding: 16px;
  font-size: 13px;
}

.empty-record-panel {
  align-items: center;
  background: var(--ui-surface);
  border: 1px dashed var(--ui-border-strong);
  border-radius: 8px;
  color: var(--ui-muted);
  display: flex;
  gap: 12px;
  padding: 22px;
}

.empty-record-panel div {
  display: grid;
  gap: 2px;
}

.entry-card {
  width: min(980px, 96vw);
  border-radius: 22px;
  overflow: hidden;
  border: 1px solid rgba(20, 39, 67, 0.11);
  box-shadow: 0 28px 60px rgba(17, 39, 72, 0.22);
}

.entry-card--wide {
  width: min(1080px, 96vw);
}

.entry-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 20px 24px;
  background: linear-gradient(90deg, #183154 0%, #245ea8 54%, #2f76d4 100%);
  color: #fff;
}

.entry-card-header-content {
  display: flex;
  align-items: center;
  gap: 14px;
}

.entry-card-title {
  font-size: 1.2rem;
  font-weight: 800;
  letter-spacing: 0.01em;
}

.entry-card-subtitle {
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.85rem;
  margin-top: 2px;
}

.entry-card-body {
  padding: 24px;
  max-height: 70vh;
  overflow-y: auto;
}

.form-section {
  margin-bottom: 20px;
  padding-bottom: 20px;
  border-bottom: 1px solid rgba(20, 39, 67, 0.06);
}

.form-section:last-of-type {
  border-bottom: none;
  margin-bottom: 12px;
  padding-bottom: 0;
}

.form-section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.8rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #2f62af;
  margin-bottom: 14px;
}

.form-section-hint {
  font-size: 0.72rem;
  font-weight: 600;
  color: #d97706;
  text-transform: none;
  letter-spacing: 0;
}

.form-section-subtitle {
  display: block;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: #657892;
  margin-bottom: 8px;
  padding-top: 4px;
  border-top: 1px dashed rgba(20, 39, 67, 0.1);
  padding-top: 12px;
}

.property-kind-selector {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}

.kind-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 18px 16px;
  border: 2px solid rgba(20, 39, 67, 0.12);
  border-radius: 14px;
  background: #fff;
  color: #657892;
  cursor: pointer;
  font: inherit;
  text-align: center;
  transition: all 0.2s ease;
}

.kind-card:hover {
  border-color: rgba(47, 98, 175, 0.5);
  background: rgba(47, 98, 175, 0.04);
}

.kind-card--active {
  border-color: #2f62af;
  background: linear-gradient(135deg, rgba(47, 98, 175, 0.08) 0%, rgba(47, 98, 175, 0.02) 100%);
  color: #162742;
  box-shadow: 0 4px 12px rgba(47, 98, 175, 0.18);
}

.kind-card .q-icon {
  color: #657892;
}

.kind-card--active .q-icon {
  color: #2f62af;
}

.kind-card strong {
  font-size: 1rem;
  font-weight: 800;
  color: #162742;
}

.kind-card span {
  font-size: 0.78rem;
  color: #657892;
}

@media (max-width: 600px) {
  .property-kind-selector {
    grid-template-columns: 1fr;
  }
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.form-grid--3col {
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding-top: 16px;
  border-top: 1px solid rgba(20, 39, 67, 0.08);
}

.form-actions .q-btn {
  min-width: 140px;
  min-height: 44px;
  border-radius: 12px;
  font-weight: 800;
}

.compact-card {
  width: min(760px, 96vw);
}

.entry-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.span-2 {
  grid-column: span 2;
}

.workspace-page :deep(.bg-primary) {
  background: var(--ui-primary) !important;
}

.workspace-page :deep(.text-primary) {
  color: var(--ui-primary) !important;
}

.workspace-page :deep(.q-btn) {
  border-radius: 10px;
  font-weight: 700;
  min-height: 38px;
  transition: all 0.3s ease;
}

.workspace-page :deep(.q-btn:hover) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(15, 118, 110, 0.2);
}

.workspace-page :deep(.q-btn--round) {
  border-radius: 50%;
}

.workspace-page :deep(.q-field--outlined .q-field__control) {
  background: #ffffff;
  border-radius: 8px;
}

.workspace-page :deep(.q-field--outlined .q-field__control::before) {
  border-color: var(--ui-border);
}

.workspace-page :deep(.q-field--outlined.q-field--focused .q-field__control::after) {
  border-color: var(--ui-primary);
  border-width: 1px;
}

.workspace-page :deep(.q-field__label),
.workspace-page :deep(.text-blue-grey-7),
.workspace-page :deep(.text-blue-grey-6) {
  color: var(--ui-muted) !important;
}

.workspace-page :deep(.q-badge) {
  border-radius: 20px;
  font-weight: 700;
  min-height: 24px;
  padding: 5px 12px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.workspace-page :deep(.q-badge:hover) {
  transform: scale(1.05);
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
}

.workspace-page :deep(.q-tabs) {
  background: var(--ui-surface);
  color: var(--ui-muted);
  padding: 0 12px;
}

.workspace-page :deep(.q-tab) {
  min-height: 48px;
}

.workspace-page :deep(.q-tab__label) {
  font-size: 12px;
  font-weight: 700;
}

.workspace-page :deep(.q-tab-panels) {
  background: var(--ui-surface-soft);
}

.workspace-page :deep(.q-tab-panel) {
  padding: 16px;
}

.workspace-page :deep(.q-list--bordered) {
  border-color: var(--ui-border);
  border-radius: 8px;
  overflow: hidden;
}

.workspace-page :deep(.q-item) {
  background: var(--ui-surface);
  min-height: 66px;
}

.workspace-page :deep(.q-item:not(:last-child)) {
  border-bottom-color: var(--ui-border);
}

.workspace-page :deep(.q-dialog .q-card__section:first-child) {
  background: var(--ui-primary-strong);
  color: white;
}

.workspace-page :deep(.q-dialog .q-card__section:first-child .text-blue-grey-7) {
  color: #d7e7e4 !important;
}

@media (max-width: 1180px) {
  .filter-band {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .record-jacket {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .jacket-metric {
    padding: 14px;
  }
}

@media (max-width: 980px) {
  .summary-grid,
  .search-band,
  .entry-grid,
  .record-jacket,
  .detail-grid {
    grid-template-columns: 1fr;
  }

  .filter-band {
    grid-template-columns: 1fr;
  }

  .span-2 {
    grid-column: span 1;
  }

  .toolbar-band {
    align-items: flex-start;
    flex-direction: column;
  }

  .brand-block,
  .toolbar-actions {
    align-items: center;
    width: 100%;
  }

  .toolbar-actions {
    flex-wrap: wrap;
  }

  .jacket-metric {
    flex-direction: row;
    align-items: center;
  }
}

@media (max-width: 640px) {
  .workspace-shell {
    padding: 14px;
  }

  .summary-grid {
    gap: 10px;
  }

  .metric-tile,
  .search-band,
  .filter-band,
  .profile-header,
  .jacket-toolbar,
  .jacket-section {
    padding: 12px;
  }

  .td-layout {
    grid-template-columns: 1fr;
  }

  .td-list,
  .td-detail-panel {
    max-height: none;
  }

  .brand-mark {
    height: 44px;
    width: 44px;
  }

}
</style>

<template>
  <div class="ws-page">
    <WorkspacePageHeader
      module="Records"
      title="Property & Tax Declaration Search"
      lead="Search by lot, PIN, TD, owner, or location."
    >
      <template #actions>
        <div class="ws-btn-row">
          <q-btn outline no-caps color="primary" icon="refresh" label="Refresh" @click="loadRecords" />
          <q-btn outline no-caps color="primary" icon="table_view" label="Export CSV" @click="downloadCsv" />
          <q-btn v-if="sessionUser?.can_approve_records" outline no-caps color="primary" icon="backup" label="Backup" @click="downloadBackup" />
          <q-btn v-if="canManage" unelevated no-caps color="primary" icon="add" label="New Property" @click="entryDialog = true" />
        </div>
      </template>
    </WorkspacePageHeader>




    <section class="ws-card ws-filter-grid">
        <q-input v-model="keyword" outlined dense debounce="350" label="Search TD, ARP, PIN, lot, survey, title, owner, barangay">
          <template #prepend>
            <q-icon name="search" />
          </template>
        </q-input>

        <q-input v-model="filters.lot_number" outlined dense debounce="350" label="Lot number" clearable />

        <q-select
          v-model="municipality"
          outlined
          dense
          clearable
          label="Municipality"
          :options="options.municipalities"
        />
      </section>

    <section class="ws-card ws-filter-grid ws-filter-grid--wide">
        <q-select v-model="filters.classification" outlined dense clearable label="Class" :options="options.classifications" />
        <q-select v-model="filters.status" outlined dense clearable label="Property Status" :options="options.statuses" />
        <q-select v-model="filters.td_status" outlined dense clearable label="TD Status" :options="options.statuses" />
        <q-select v-model="filters.document_type" outlined dense clearable label="Document" :options="options.documentTypes" />
        <q-select v-model="filters.physical_copy_status" outlined dense clearable label="Physical Status" :options="options.physicalStatuses" />
        <q-input v-model="filters.owner" outlined dense debounce="350" label="Owner filter" />
        <q-input v-model="filters.barangay" outlined dense debounce="350" label="Barangay filter" />
        <q-input v-model="filters.storage_location" outlined dense debounce="350" label="Archive location" />
        <q-input v-model.number="filters.year_from" outlined dense type="number" label="Year from" />
        <q-input v-model.number="filters.year_to" outlined dense type="number" label="Year to" />
      </section>

    <section v-if="recentProperties.length" class="ws-card">
      <div class="ws-card__title">Recently opened</div>
      <div class="ws-chip-row">
          <q-chip
            v-for="item in recentProperties"
            :key="item.id"
            clickable
            outline
            color="primary"
            icon="history"
            @click="openRecentProperty(item.id)"
          >
            {{ item.lot_number || item.pin }} · {{ item.municipality }}
          </q-chip>
        </div>
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
              <div class="text-caption text-blue-grey-6">{{ props.row.property_index_number }}</div>
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

          <template #body-cell-counts="props">
            <q-td :props="props">
              <div class="mini-counts">
                <q-badge outline color="primary" :label="`${props.row.tax_declarations?.length || 0} TD`" />
                <q-badge outline color="teal-7" :label="`${assessmentCount(props.row)} assess`" />
                <q-badge outline color="blue-grey-7" :label="`${props.row.documents?.length || 0} docs`" />
              </div>
            </q-td>
          </template>

          <template #body-cell-match="props">
            <q-td :props="props">
              <q-badge v-if="props.row.search_match" outline color="teal-7" :label="matchLabel(props.row.search_match)" />
              <span v-else class="text-caption text-blue-grey-5">—</span>
            </q-td>
          </template>

          <template #body-cell-status="props">
            <q-td :props="props">
              <q-badge :color="statusColor(props.row.status)" :label="props.row.status" />
            </q-td>
          </template>

          <template #body-cell-open="props">
            <q-td :props="props">
              <q-btn flat round color="primary" icon="folder_open" aria-label="Open property record" @click.stop="selectRecord(null, props.row)">
                <q-tooltip>Open property record</q-tooltip>
              </q-btn>
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
        <div class="ws-record-panel__header profile-header">
          <div class="header-content">
            <div class="property-icon">
              <q-icon name="home_work" size="32px" />
            </div>
            <div>
              <div class="ws-kicker">Property Record</div>
              <div class="text-h6 text-weight-bold">{{ selected.lot_number }}</div>
              <div class="text-body2 text-blue-grey-7">{{ selected.pin }} · {{ selected.barangay }}, {{ selected.municipality }}</div>
            </div>
          </div>
          <q-badge :color="statusColor(selected.status)" :label="selected.status" class="status-badge" />
        </div>

        <div class="jacket-toolbar">
          <div class="jacket-toolbar-group">
            <q-btn v-if="canManage" unelevated no-caps color="primary" icon="post_add" label="Add TD" @click="openDeclarationDialog" />
            <q-btn v-if="canManage" outline no-caps color="primary" icon="upload_file" label="Add File" @click="openDocumentDialog" />
            <q-btn v-if="canManage && selectedTdEntry" outline no-caps color="primary" icon="calculate" label="Add Assessment" @click="openAssessmentDialogForTd" />
          </div>
          <div class="jacket-toolbar-group">
            <q-btn v-if="canManage" outline no-caps color="primary" icon="edit" label="Edit" @click="openEditPropertyDialog" />
            <q-btn v-if="canApprove && selected.status !== 'Active'" outline no-caps color="positive" icon="verified" label="Approve" @click="approveSelectedProperty" />
            <q-btn outline no-caps color="primary" icon="print" label="Print" @click="printRecord" />
            <q-btn outline no-caps color="primary" icon="download" label="Export" @click="exportRecord" />
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
            <q-icon name="description" class="metric-icon" />
            <div>
              <span>Current TD</span>
              <strong>{{ currentTd(selected)?.td_number || 'None' }}</strong>
            </div>
          </div>
          <div class="jacket-metric">
            <q-icon name="history" class="metric-icon" />
            <div>
              <span>TD History</span>
              <strong>{{ dossierCounts.tax_declarations }}</strong>
            </div>
          </div>
          <div class="jacket-metric">
            <q-icon name="folder" class="metric-icon" />
            <div>
              <span>Documents</span>
              <strong>{{ dossierCounts.documents }}</strong>
            </div>
          </div>
          <div class="jacket-metric">
            <q-icon name="cloud_done" class="metric-icon" />
            <div>
              <span>Digitized</span>
              <strong>{{ dossierCounts.digitized_documents ?? 0 }}</strong>
            </div>
          </div>
          <div class="jacket-metric">
            <q-icon name="calculate" class="metric-icon" />
            <div>
              <span>Assessment Lines</span>
              <strong>{{ dossierCounts.assessment_records }}</strong>
            </div>
          </div>
        </div>

          <section class="jacket-section td-navigator">
            <div class="section-head">
              <div class="section-head-content">
                <q-icon name="receipt_long" size="24px" color="primary" />
                <h3 class="section-title">Tax Declarations</h3>
              </div>
              <q-badge outline color="primary" :label="`${taxDeclarationTimeline.length} total`" />
            </div>

            <div v-if="!taxDeclarationTimeline.length" class="empty-state">No tax declarations on record.</div>
            <div v-else class="td-layout">
              <q-list bordered separator class="td-list">
                <q-item
                  v-for="(entry, index) in taxDeclarationTimeline"
                  :key="entry.tax_declaration?.id ?? index"
                  clickable
                  :active="selectedTdId === entry.tax_declaration?.id"
                  active-class="td-list-item--active"
                  class="td-list-item"
                  @click="selectTd(entry)"
                >
                  <q-item-section>
                    <q-item-label class="text-weight-bold">{{ entry.tax_declaration.td_number }}</q-item-label>
                    <q-item-label caption>{{ entry.tax_declaration.effectivity_year }} · {{ entry.tax_declaration.transaction_type }}</q-item-label>
                    <q-item-label caption>{{ entry.tax_declaration.owner?.name || 'No owner' }}</q-item-label>
                  </q-item-section>
                  <q-item-section side top>
                    <div class="td-list-badges">
                      <q-badge :color="statusColor(entry.tax_declaration.status)" :label="entry.tax_declaration.status" />
                      <q-badge outline color="primary" :label="`${entry.document_count} files`" />
                    </div>
                  </q-item-section>
                </q-item>
              </q-list>

              <div class="td-detail-panel" v-if="selectedTdEntry">
                <div class="td-detail-header">
                  <div>
                    <div class="section-kicker">Tax Declaration</div>
                    <h4 class="td-detail-title">{{ selectedTdEntry.tax_declaration.td_number }}</h4>
                    <p class="td-detail-meta">
                      {{ selectedTdEntry.tax_declaration.effectivity_year }}
                      · {{ selectedTdEntry.tax_declaration.transaction_type }}
                      · ARP {{ selectedTdEntry.tax_declaration.arp_number || '—' }}
                    </p>
                  </div>
                  <q-badge :color="statusColor(selectedTdEntry.tax_declaration.status)" :label="selectedTdEntry.tax_declaration.status" />
                </div>

                <div class="info-grid">
                  <div class="info-cell">
                    <span>Owner</span>
                    <strong>{{ selectedTdEntry.tax_declaration.owner?.name || '—' }}</strong>
                  </div>
                  <div class="info-cell">
                    <span>Address</span>
                    <strong>{{ selectedTdEntry.tax_declaration.owner?.address || '—' }}</strong>
                  </div>
                  <div class="info-cell">
                    <span>Classification</span>
                    <strong>{{ selectedTdEntry.tax_declaration.classification || selected.classification }}</strong>
                  </div>
                  <div class="info-cell">
                    <span>Actual use</span>
                    <strong>{{ selectedTdEntry.tax_declaration.actual_use || selected.actual_use || '—' }}</strong>
                  </div>
                  <div class="info-cell">
                    <span>Market value</span>
                    <strong>{{ money(selectedTdEntry.tax_declaration.market_value) }}</strong>
                  </div>
                  <div class="info-cell">
                    <span>Assessed value</span>
                    <strong>{{ money(selectedTdEntry.tax_declaration.assessed_value) }}</strong>
                  </div>
                  <div class="info-cell">
                    <span>Assessment level</span>
                    <strong>{{ selectedTdEntry.tax_declaration.assessment_level || 0 }}%</strong>
                  </div>
                  <div class="info-cell">
                    <span>Taxable</span>
                    <strong>{{ selectedTdEntry.tax_declaration.taxable === false ? 'Exempt' : 'Taxable' }}</strong>
                  </div>
                </div>

                <div class="ui-block">
                  <div class="ui-block-title">Memoranda</div>
                  <p class="ui-block-body">{{ selectedTdEntry.tax_declaration.memoranda || 'No memoranda recorded.' }}</p>
                </div>

                <div class="ui-block">
                  <div class="ui-block-head">
                    <div class="ui-block-title">Assessments</div>
                    <q-badge outline color="teal-7" :label="`${selectedTdEntry.assessment_count} line(s)`" />
                  </div>
                  <div v-if="selectedTdEntry.assessment_records?.length" class="ui-stack">
                    <article v-for="record in selectedTdEntry.assessment_records" :key="record.id" class="ui-row-card">
                      <div class="ui-row-card-head">
                        <strong>{{ record.assessment_type }}</strong>
                        <q-badge :color="record.taxable ? 'green-7' : 'blue-grey-6'" :label="record.taxable ? 'Taxable' : 'Exempt'" />
                      </div>
                      <div class="ui-row-card-meta">
                        <span>{{ numberFormat(record.area) }} {{ record.unit_of_measure }}</span>
                        <span>{{ money(record.market_value) }} market</span>
                        <span>{{ money(record.assessed_value) }} assessed</span>
                      </div>
                      <p v-if="record.notes">{{ record.notes }}</p>
                      <div class="ui-row-actions" v-if="canManage">
                        <q-btn v-if="canManage" outline dense no-caps color="primary" icon="edit" label="Edit" @click="openAssessmentEditDialog(selectedTdEntry.tax_declaration, record)" />
                        <q-btn outline dense no-caps color="negative" icon="delete" label="Remove" @click="removeAssessmentRecord(selectedTdEntry.tax_declaration, record)" />
                      </div>
                    </article>
                  </div>
                  <div v-else class="empty-state compact">No assessment lines for this TD.</div>
                </div>

                <div class="ui-block">
                  <div class="ui-block-head">
                    <div class="ui-block-title">Documents &amp; scans</div>
                    <q-badge outline color="primary" :label="`${selectedTdEntry.document_count} file(s)`" />
                  </div>
                  <q-list v-if="selectedTdEntry.documents?.length" bordered separator class="ui-list">
                    <q-item v-for="document in selectedTdEntry.documents" :key="document.id">
                      <q-item-section avatar><q-icon color="primary" name="description" /></q-item-section>
                      <q-item-section>
                        <q-item-label>{{ document.document_type }}</q-item-label>
                        <q-item-label caption>{{ document.reference_number || document.file_name }}</q-item-label>
                        <q-item-label caption>{{ documentLocationLine(document) }}</q-item-label>
                      </q-item-section>
                      <q-item-section side>
                        <div class="document-side">
                          <q-badge :color="physicalStatusColor(document.physical_copy_status)" :label="document.physical_copy_status || 'On File'" />
                          <q-btn v-if="needsDigitization(document) && canManage" flat round dense color="primary" icon="scanner" @click="openDigitizeDialog(document)" />
                          <q-btn v-else flat round dense color="primary" icon="download" @click="downloadDocument(document)" />
                          <q-btn v-if="canManage" flat round dense color="primary" icon="edit" @click="openEditDocumentDialog(document)" />
                        </div>
                      </q-item-section>
                    </q-item>
                  </q-list>
                  <div v-else class="empty-state compact">No documents linked to this TD.</div>
                </div>

                <div class="ui-block">
                  <div class="ui-block-head">
                    <div class="ui-block-title">Data entry trail</div>
                  </div>
                  <q-list v-if="selectedTdEntry.data_entry_events?.length" bordered separator dense class="ui-list">
                    <q-item v-for="log in selectedTdEntry.data_entry_events" :key="log.id">
                      <q-item-section avatar><q-icon name="task_alt" color="primary" /></q-item-section>
                      <q-item-section>
                        <q-item-label>{{ log.description }}</q-item-label>
                        <q-item-label caption>{{ log.user?.name || 'System' }} · {{ dateFormat(log.created_at) }}</q-item-label>
                      </q-item-section>
                    </q-item>
                  </q-list>
                  <div v-else class="empty-state compact">No data entry events for this TD.</div>
                </div>

                <div class="jacket-toolbar td-detail-actions" v-if="canApprove || canManage">
                  <div class="jacket-toolbar-group">
                    <q-btn v-if="canManage" outline no-caps color="primary" icon="edit" label="Edit TD" @click="openEditDeclarationDialog(selectedTdEntry.tax_declaration)" />
                    <q-btn v-if="canApprove && selectedTdEntry.tax_declaration.status !== 'Active'" unelevated no-caps color="positive" icon="verified" label="Approve TD" @click="approveDeclaration(selectedTdEntry.tax_declaration)" />
                    <q-btn v-if="canManage && selectedTdEntry.tax_declaration.status !== 'Cancelled'" outline no-caps color="negative" icon="block" label="Cancel TD" @click="archiveDeclaration(selectedTdEntry.tax_declaration)" />
                  </div>
                </div>
              </div>

              <div v-else class="td-detail-panel td-detail-panel--empty">
                <q-icon name="touch_app" size="40px" color="blue-grey-4" />
                <strong>Select a tax declaration</strong>
                <span>Choose a TD from the list to view assessments, documents, and audit history.</span>
              </div>
            </div>
          </section>

          <q-tabs v-model="profileTab" dense align="left" active-color="primary" class="jacket-tabs">
            <q-tab name="property" icon="home_work" label="Property" />
            <q-tab name="documents" icon="folder" label="All Files" />
            <q-tab name="audit" icon="verified_user" label="Activity" />
          </q-tabs>

          <q-separator />

          <q-tab-panels v-model="profileTab" animated>
            <q-tab-panel name="property">
              <div class="detail-grid">
                <div>
                  <span>Title Number</span>
                  <strong>{{ selected.title_number || 'None' }}</strong>
                </div>
                <div>
                  <span>Survey Number</span>
                  <strong>{{ selected.survey_number || 'None' }}</strong>
                </div>
                <div>
                  <span>Classification</span>
                  <strong>{{ selected.classification }}</strong>
                </div>
                <div>
                  <span>Actual Use</span>
                  <strong>{{ selected.actual_use || 'None' }}</strong>
                </div>
                <div>
                  <span>Land Area</span>
                  <strong>{{ numberFormat(selected.land_area) }} {{ selected.unit_of_measure }}</strong>
                </div>
                <div>
                  <span>Current Assessed Value</span>
                  <strong>{{ money(currentTd(selected)?.assessed_value) }}</strong>
                </div>
                <div>
                  <span>Earliest TD Year</span>
                  <strong>{{ valuationSummary.earliest_effectivity_year || 'None' }}</strong>
                </div>
                <div>
                  <span>Latest TD Year</span>
                  <strong>{{ valuationSummary.latest_effectivity_year || 'None' }}</strong>
                </div>
              </div>
              <div class="remarks-box">{{ selected.remarks || 'No property remarks recorded.' }}</div>

              <div class="ui-block q-mt-md">
                <div class="ui-block-head">
                  <div class="ui-block-title">Owner history</div>
                </div>
                <q-list v-if="ownerHistory.length" bordered separator dense class="ui-list">
                  <q-item v-for="(owner, index) in ownerHistory" :key="`${owner.td_number}-${index}`">
                    <q-item-section avatar><q-icon color="primary" name="person" /></q-item-section>
                    <q-item-section>
                      <q-item-label>{{ owner.owner_name || 'Unknown' }}</q-item-label>
                      <q-item-label caption>{{ owner.td_number }} · {{ owner.effectivity_year }} · {{ owner.transaction_type }}</q-item-label>
                      <q-item-label caption>{{ owner.owner_address || 'No address' }}</q-item-label>
                    </q-item-section>
                    <q-item-section side>
                      <q-badge outline :color="statusColor(owner.status)" :label="owner.status" />
                    </q-item-section>
                  </q-item>
                </q-list>
                <div v-else class="empty-state compact">No owner history.</div>
              </div>

              <div class="ui-block q-mt-md" v-if="pendingDigitization.length">
                <div class="ui-block-head">
                  <div class="ui-block-title">Awaiting digitization</div>
                  <q-badge color="amber-8" :label="`${pendingDigitization.length} pending`" />
                </div>
                <q-list bordered separator dense class="ui-list">
                  <q-item v-for="document in pendingDigitization" :key="document.id">
                    <q-item-section avatar><q-icon color="amber-8" name="inventory_2" /></q-item-section>
                    <q-item-section>
                      <q-item-label>{{ document.document_type }}</q-item-label>
                      <q-item-label caption>{{ document.reference_number || document.file_name }}</q-item-label>
                      <q-item-label caption v-if="document.tax_declaration_id">TD: {{ linkedTdNumber(document.tax_declaration_id) }}</q-item-label>
                    </q-item-section>
                    <q-item-section side>
                      <q-btn v-if="canManage" unelevated dense no-caps color="primary" icon="scanner" label="Scan" @click="openDigitizeDialog(document)" />
                    </q-item-section>
                  </q-item>
                </q-list>
              </div>
            </q-tab-panel>





            <q-tab-panel name="documents">
              <p class="panel-lead">All supporting files grouped by tax declaration, plus property-level documents.</p>
              <div v-if="documentGroupsByTd.length" class="document-groups">
                <section v-for="group in documentGroupsByTd" :key="group.key" class="document-group">
                  <div class="document-group-title">
                    <strong>{{ group.label }}</strong>
                    <q-badge outline color="primary" :label="group.documents.length" />
                  </div>
                  <q-list bordered separator>
                    <q-item v-for="document in group.documents" :key="document.id">
                      <q-item-section avatar>
                        <q-icon color="primary" name="picture_as_pdf" />
                      </q-item-section>
                      <q-item-section>
                        <q-item-label>{{ document.reference_number || document.file_name }}</q-item-label>
                        <q-item-label caption>{{ document.file_name }} | {{ dateFormat(document.issued_at) }}</q-item-label>
                        <q-item-label caption>
                          {{ documentLocationLine(document) }}
                        </q-item-label>
                      </q-item-section>
                      <q-item-section side>
                        <div class="document-side">
                          <q-badge :color="physicalStatusColor(document.physical_copy_status)" :label="document.physical_copy_status || 'On File'" />
                          <q-btn flat round color="primary" icon="timeline" aria-label="Movement history" @click="openMovementHistoryDialog(document)">
                            <q-tooltip>Movement history</q-tooltip>
                          </q-btn>
                          <q-btn v-if="canManage" flat round color="primary" icon="sync_alt" aria-label="Move physical record" @click="openMovementDialog(document)">
                            <q-tooltip>Record physical movement</q-tooltip>
                          </q-btn>
                          <q-btn v-if="canManage" flat round color="primary" icon="edit" aria-label="Edit document record" @click="openEditDocumentDialog(document)">
                            <q-tooltip>Edit document record</q-tooltip>
                          </q-btn>
                          <q-btn v-if="canManage" flat round color="negative" icon="archive" aria-label="Archive document record" @click="archiveSelectedDocument(document)">
                            <q-tooltip>Archive document record</q-tooltip>
                          </q-btn>
                          <q-btn v-if="needsDigitization(document) && canManage" flat round color="primary" icon="scanner" aria-label="Digitize document" @click="openDigitizeDialog(document)">
                            <q-tooltip>Upload scan</q-tooltip>
                          </q-btn>
                          <q-btn v-else flat round color="primary" icon="download" aria-label="Download document" @click="downloadDocument(document)">
                            <q-tooltip>Download document</q-tooltip>
                          </q-btn>
                        </div>
                      </q-item-section>
                    </q-item>
                  </q-list>
                </section>
              </div>
              <div v-else class="empty-state">No documents registered yet.</div>
            </q-tab-panel>

            <q-tab-panel name="audit">
              <div class="ui-block">
                <div class="ui-block-head">
                  <div class="ui-block-title">Activity log</div>
                  <div class="row items-center q-gutter-sm">
                    <q-btn v-if="selected" outline dense no-caps color="primary" icon="download" label="Export CSV" @click="exportActivityCsv" />
                    <q-select
                      v-model="auditTdFilter"
                      outlined
                      dense
                      clearable
                      emit-value
                      map-options
                      label="Filter by TD"
                      :options="auditTdOptions"
                      style="min-width: 200px"
                    />
                  </div>
                </div>
              <q-list bordered separator v-if="filteredDataEntryTimeline.length" class="ui-list">
                <q-item v-for="log in filteredDataEntryTimeline" :key="log.id">
                  <q-item-section avatar>
                    <q-icon color="primary" name="task_alt" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>{{ log.description }}</q-item-label>
                    <q-item-label caption>
                      {{ log.action }} | {{ log.user?.name || 'System' }} | {{ dateFormat(log.created_at) }}
                    </q-item-label>
                    <q-item-label caption v-if="log.tax_declaration">
                      TD: {{ log.tax_declaration.td_number }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
                <div v-else class="empty-state compact">No data entry events recorded yet.</div>
              </div>
            </q-tab-panel>
          </q-tab-panels>
        </aside>

      <section class="ws-empty ws-record-panel" v-else>
        <div class="empty-icon-wrapper">
          <q-icon name="folder_open" size="64px" />
        </div>
        <div>
          <strong>No property selected</strong>
          <span>Click on any property record from the search results to view complete details.</span>
        </div>
      </section>
      </div>

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
      <q-card class="entry-card">
        <q-card-section class="row items-center justify-between">
          <div class="text-h6">New Property Entry</div>
          <q-btn flat round icon="close" aria-label="Close" v-close-popup />
        </q-card-section>

        <q-separator />

        <q-card-section>
          <q-form class="entry-grid" @submit.prevent="saveEntry">
            <q-input v-model="form.pin" outlined dense label="PIN" />
            <q-input v-model="form.lot_number" outlined dense label="Lot number" />
            <q-input v-model="form.survey_number" outlined dense label="Survey number" />
            <q-input v-model="form.title_number" outlined dense label="Title number" />
            <q-input v-model="form.barangay" outlined dense label="Barangay" />
            <q-select v-model="form.municipality" outlined dense label="Municipality" :options="options.municipalities" />
            <q-select v-model="form.classification" outlined dense label="Classification" :options="options.classifications" />
            <q-input v-model.number="form.land_area" type="number" outlined dense label="Land area" />
            <q-input v-model="form.owner.name" outlined dense label="Owner name" />
            <q-input v-model="form.owner.address" outlined dense label="Owner address" />
            <q-input v-model="form.tax_declaration.td_number" outlined dense label="TD number" />
            <q-input v-model="form.tax_declaration.arp_number" outlined dense label="ARP number" />
            <q-input v-model.number="form.tax_declaration.effectivity_year" type="number" outlined dense label="Effectivity year" />
            <q-input v-model.number="form.tax_declaration.market_value" type="number" outlined dense label="Market value" />
            <q-input v-model.number="form.tax_declaration.assessed_value" type="number" outlined dense label="Assessed value" />
            <q-select v-model="form.tax_declaration.status" outlined dense label="TD Status" :options="options.statuses" />
            <q-select v-model="form.tax_declaration.transaction_type" outlined dense label="Transaction" :options="options.transactionTypes" />
            <q-input v-model="form.remarks" outlined dense type="textarea" label="Remarks" class="span-2" />

            <div class="span-2 row justify-end q-gutter-sm">
              <q-btn flat label="Cancel" v-close-popup />
              <q-btn color="primary" icon="save" label="Save Property" type="submit" :loading="saving" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="editPropertyDialog" persistent>
      <q-card class="entry-card">
        <q-card-section class="row items-center justify-between">
          <div>
            <div class="text-h6">Edit Property Master Record</div>
            <div class="text-caption text-blue-grey-7">{{ selected?.pin }} | {{ selected?.lot_number }}</div>
          </div>
          <q-btn flat round icon="close" aria-label="Close" v-close-popup />
        </q-card-section>

        <q-separator />

        <q-card-section>
          <q-form class="entry-grid" @submit.prevent="savePropertyEdit">
            <q-input v-model="editPropertyForm.pin" outlined dense label="PIN" />
            <q-input v-model="editPropertyForm.property_index_number" outlined dense label="Property index number" />
            <q-input v-model="editPropertyForm.lot_number" outlined dense label="Lot number" />
            <q-input v-model="editPropertyForm.survey_number" outlined dense label="Survey number" />
            <q-input v-model="editPropertyForm.title_number" outlined dense label="Title number" />
            <q-input v-model="editPropertyForm.barangay" outlined dense label="Barangay" />
            <q-select v-model="editPropertyForm.municipality" outlined dense label="Municipality" :options="options.municipalities" />
            <q-select v-model="editPropertyForm.classification" outlined dense label="Classification" :options="options.classifications" />
            <q-input v-model="editPropertyForm.actual_use" outlined dense label="Actual use" />
            <q-input v-model.number="editPropertyForm.land_area" type="number" outlined dense label="Land area" />
            <q-input v-model="editPropertyForm.unit_of_measure" outlined dense label="Unit" />
            <q-select v-model="editPropertyForm.status" outlined dense label="Status" :options="options.statuses" />
            <q-input v-model="editPropertyForm.remarks" outlined dense type="textarea" label="Remarks" class="span-2" />

            <div class="span-2 row justify-end q-gutter-sm">
              <q-btn flat label="Cancel" v-close-popup />
              <q-btn color="primary" icon="save" label="Save Changes" type="submit" :loading="saving" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="declarationDialog" persistent>
      <q-card class="entry-card">
        <q-card-section class="row items-center justify-between">
          <div>
            <div class="text-h6">{{ declarationDialogTitle }}</div>
            <div class="text-caption text-blue-grey-7">{{ selected?.pin }} | {{ selected?.lot_number }}</div>
          </div>
          <q-btn flat round icon="close" aria-label="Close" v-close-popup />
        </q-card-section>

        <q-separator />

        <q-card-section>
          <q-form class="entry-grid" @submit.prevent="saveDeclaration">
            <q-input v-model="declarationForm.owner.name" outlined dense label="Owner name" />
            <q-input v-model="declarationForm.owner.address" outlined dense label="Owner address" />
            <q-input v-model="declarationForm.td_number" outlined dense label="TD number" />
            <q-input v-model="declarationForm.arp_number" outlined dense label="ARP number" />
            <q-input v-model.number="declarationForm.effectivity_year" type="number" outlined dense label="Effectivity year" />
            <q-select v-model="declarationForm.status" outlined dense label="Status" :options="options.statuses" />
            <q-select v-model="declarationForm.transaction_type" outlined dense label="Transaction" :options="options.transactionTypes" />
            <q-select v-model="declarationForm.classification" outlined dense label="Classification" :options="options.classifications" />
            <q-input v-model.number="declarationForm.market_value" type="number" outlined dense label="Market value" />
            <q-input v-model.number="declarationForm.assessed_value" type="number" outlined dense label="Assessed value" />
            <q-input v-model.number="declarationForm.assessment_level" type="number" outlined dense label="Assessment level %" />
            <q-input v-model="declarationForm.actual_use" outlined dense label="Actual use" />
            <q-input v-model="declarationForm.memoranda" outlined dense type="textarea" label="Memoranda" class="span-2" />

            <div class="span-2 row justify-end q-gutter-sm">
              <q-btn flat label="Cancel" v-close-popup />
              <q-btn color="primary" icon="save" :label="declarationSubmitLabel" type="submit" :loading="saving" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="documentDialog" persistent>
      <q-card class="entry-card compact-card">
        <q-card-section class="row items-center justify-between">
          <div>
            <div class="text-h6">Register Document / Physical Record</div>
            <div class="text-caption text-blue-grey-7">{{ selected?.pin }} | {{ selected?.lot_number }}</div>
          </div>
          <q-btn flat round icon="close" aria-label="Close" v-close-popup />
        </q-card-section>

        <q-separator />

        <q-card-section>
          <q-form class="entry-grid" @submit.prevent="saveDocument">
            <q-select
              v-model="documentForm.tax_declaration_id"
              outlined
              dense
              clearable
              emit-value
              map-options
              label="Related TD"
              :options="taxDeclarationOptions"
            />
            <q-select v-model="documentForm.document_type" outlined dense label="Document type" :options="options.documentTypes" />
            <q-input v-model="documentForm.reference_number" outlined dense label="Reference number" />
            <q-input v-model="documentForm.issued_at" outlined dense type="date" label="Issued date" />
            <q-select v-model="documentForm.physical_copy_status" outlined dense label="Physical status" :options="options.physicalStatuses" />
            <q-input v-model="documentForm.storage_location" outlined dense label="Storage location" />
            <q-input v-model="documentForm.shelf_number" outlined dense label="Shelf number" />
            <q-input v-model="documentForm.box_number" outlined dense label="Box number" />
            <q-input v-model="documentForm.folder_number" outlined dense label="Folder number" />
            <q-input v-model="documentForm.custodian" outlined dense label="Custodian / borrower" />
            <q-input v-model="documentForm.received_at" outlined dense type="date" label="Received date" />
            <q-input v-model="documentForm.released_at" outlined dense type="date" label="Released date" />
            <q-input v-model="documentForm.returned_at" outlined dense type="date" label="Returned date" />
            <q-banner dense rounded class="span-2 bg-blue-1 text-blue-10">
              Leave the file empty to index an existing physical record first. Upload the scan later from the Digitize tab.
            </q-banner>
            <q-file v-model="documentForm.file" outlined dense label="Scanned file (optional)" class="span-2">
              <template #prepend>
                <q-icon name="attach_file" />
              </template>
            </q-file>
            <q-input v-model="documentForm.notes" outlined dense type="textarea" label="Notes" class="span-2" />

            <div class="span-2 row justify-end q-gutter-sm">
              <q-btn flat label="Cancel" v-close-popup />
              <q-btn color="primary" icon="save" label="Save Document Record" type="submit" :loading="saving" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="editDocumentDialog" persistent>
      <q-card class="entry-card compact-card">
        <q-card-section class="row items-center justify-between">
          <div>
            <div class="text-h6">Edit Document / Physical Record</div>
            <div class="text-caption text-blue-grey-7">{{ selectedDocument?.reference_number || selectedDocument?.file_name }}</div>
          </div>
          <q-btn flat round icon="close" aria-label="Close" v-close-popup />
        </q-card-section>

        <q-separator />

        <q-card-section>
          <q-form class="entry-grid" @submit.prevent="saveDocumentEdit">
            <q-select
              v-model="editDocumentForm.tax_declaration_id"
              outlined
              dense
              clearable
              emit-value
              map-options
              label="Related TD"
              :options="taxDeclarationOptions"
            />
            <q-select v-model="editDocumentForm.document_type" outlined dense label="Document type" :options="options.documentTypes" />
            <q-input v-model="editDocumentForm.reference_number" outlined dense label="Reference number" />
            <q-input v-model="editDocumentForm.issued_at" outlined dense type="date" label="Issued date" />
            <q-select v-model="editDocumentForm.physical_copy_status" outlined dense label="Physical status" :options="options.physicalStatuses" />
            <q-input v-model="editDocumentForm.storage_location" outlined dense label="Storage location" />
            <q-input v-model="editDocumentForm.shelf_number" outlined dense label="Shelf number" />
            <q-input v-model="editDocumentForm.box_number" outlined dense label="Box number" />
            <q-input v-model="editDocumentForm.folder_number" outlined dense label="Folder number" />
            <q-input v-model="editDocumentForm.custodian" outlined dense label="Custodian / borrower" />
            <q-input v-model="editDocumentForm.received_at" outlined dense type="date" label="Received date" />
            <q-input v-model="editDocumentForm.released_at" outlined dense type="date" label="Released date" />
            <q-input v-model="editDocumentForm.returned_at" outlined dense type="date" label="Returned date" />
            <q-input v-model="editDocumentForm.notes" outlined dense type="textarea" label="Notes" class="span-2" />

            <div class="span-2 row justify-end q-gutter-sm">
              <q-btn flat label="Cancel" v-close-popup />
              <q-btn color="primary" icon="save" label="Save Document Changes" type="submit" :loading="saving" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="movementDialog" persistent>
      <q-card class="entry-card compact-card">
        <q-card-section class="row items-center justify-between">
          <div>
            <div class="text-h6">Physical Record Movement</div>
            <div class="text-caption text-blue-grey-7">
              {{ selectedDocument?.reference_number || selectedDocument?.file_name }}
            </div>
          </div>
          <q-btn flat round icon="close" aria-label="Close" v-close-popup />
        </q-card-section>

        <q-separator />

        <q-card-section>
          <q-form class="entry-grid" @submit.prevent="saveMovement">
            <q-select v-model="movementForm.movement_type" outlined dense label="Movement type" :options="movementTypes" />
            <q-select v-model="movementForm.to_status" outlined dense label="New physical status" :options="options.physicalStatuses" />
            <q-input v-model="movementForm.to_location" outlined dense label="New storage location" />
            <q-input v-model="movementForm.to_box_number" outlined dense label="New box number" />
            <q-input v-model="movementForm.to_folder_number" outlined dense label="New folder number" />
            <q-input v-model="movementForm.custodian" outlined dense label="Custodian" />
            <q-input v-model="movementForm.released_to" outlined dense label="Released to / borrower" />
            <q-input v-model="movementForm.movement_date" outlined dense type="date" label="Movement date" />
            <q-input v-model="movementForm.expected_return_at" outlined dense type="date" label="Expected return" />
            <q-input v-model="movementForm.returned_at" outlined dense type="date" label="Returned date" />
            <q-input v-model="movementForm.remarks" outlined dense type="textarea" label="Remarks" class="span-2" />

            <div class="span-2 row justify-end q-gutter-sm">
              <q-btn flat label="Cancel" v-close-popup />
              <q-btn color="primary" icon="save" label="Save Movement" type="submit" :loading="saving" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="digitizeDialog" persistent>
      <q-card class="entry-card compact-card">
        <q-card-section class="row items-center justify-between">
          <div>
            <div class="text-h6">Digitize Physical Record</div>
            <div class="text-caption text-blue-grey-7">
              {{ selectedDocument?.document_type }} | {{ selectedDocument?.reference_number || selectedDocument?.file_name }}
            </div>
          </div>
          <q-btn flat round icon="close" aria-label="Close" v-close-popup />
        </q-card-section>

        <q-separator />

        <q-card-section>
          <q-form class="entry-grid" @submit.prevent="saveDigitization">
            <q-file v-model="digitizeForm.file" outlined dense label="Scanned file (PDF or image)" class="span-2" accept=".pdf,.jpg,.jpeg,.png,.tif,.tiff">
              <template #prepend>
                <q-icon name="scanner" />
              </template>
            </q-file>
            <q-input v-model="digitizeForm.ocr_text" outlined dense type="textarea" label="OCR text (searchable)" class="span-2" hint="Paste extracted text from the scan for full-text search." />
            <q-input v-model="digitizeForm.notes" outlined dense type="textarea" label="Digitization notes" class="span-2" />

            <div class="span-2 row justify-end q-gutter-sm">
              <q-btn flat label="Cancel" v-close-popup />
              <q-btn color="primary" icon="scanner" label="Upload & Digitize" type="submit" :loading="saving" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="assessmentDialog" persistent>
      <q-card class="entry-card compact-card">
        <q-card-section class="row items-center justify-between">
          <div>
            <div class="text-h6">{{ assessmentDialogTitle }}</div>
            <div class="text-caption text-blue-grey-7">{{ selected?.pin }} | {{ selected?.lot_number }}</div>
          </div>
          <q-btn flat round icon="close" aria-label="Close" v-close-popup />
        </q-card-section>

        <q-separator />

        <q-card-section>
          <q-form class="entry-grid" @submit.prevent="saveAssessment">
            <q-select
              v-model="assessmentForm.tax_declaration_id"
              outlined
              dense
              emit-value
              map-options
              label="Related TD"
              :options="taxDeclarationOptions"
            />
            <q-select v-model="assessmentForm.assessment_type" outlined dense label="Assessment type" :options="options.assessmentTypes" />
            <q-select v-model="assessmentForm.classification" outlined dense label="Classification" :options="options.classifications" />
            <q-input v-model="assessmentForm.actual_use" outlined dense label="Actual use" />
            <q-input v-model.number="assessmentForm.area" outlined dense type="number" label="Area" />
            <q-input v-model="assessmentForm.unit_of_measure" outlined dense label="Unit" />
            <q-input v-model.number="assessmentForm.unit_value" outlined dense type="number" label="Unit value" />
            <q-input v-model.number="assessmentForm.base_market_value" outlined dense type="number" label="Base market value" />
            <q-input v-model.number="assessmentForm.adjustment" outlined dense type="number" label="Adjustment" />
            <q-input v-model.number="assessmentForm.depreciation_rate" outlined dense type="number" label="Depreciation %" />
            <q-input v-model.number="assessmentForm.market_value" outlined dense type="number" label="Market value" />
            <q-input v-model.number="assessmentForm.assessment_level" outlined dense type="number" label="Assessment level %" />
            <q-input v-model.number="assessmentForm.assessed_value" outlined dense type="number" label="Assessed value" />
            <q-select
              v-model="assessmentForm.taxable"
              outlined
              dense
              emit-value
              map-options
              label="Taxable"
              :options="taxableOptions"
            />
            <q-input v-model="assessmentForm.notes" outlined dense type="textarea" label="Notes" class="span-2" />

            <div class="span-2 row justify-end q-gutter-sm">
              <q-btn flat label="Cancel" v-close-popup />
              <q-btn color="primary" icon="save" :label="assessmentSubmitLabel" type="submit" :loading="saving" />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue';
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
  downloadPropertiesCsv,
  fetchDashboard,
  fetchProperties,
  fetchPropertyDossier,
  fetchReferences,
  movePhysicalRecord,
  digitizeDocument,
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
const selectedTdId = ref(null);
const entryDialog = ref(false);
const editPropertyDialog = ref(false);
const declarationDialog = ref(false);
const documentDialog = ref(false);
const editDocumentDialog = ref(false);
const assessmentDialog = ref(false);
const movementDialog = ref(false);
const digitizeDialog = ref(false);
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
const filters = reactive({
  lot_number: '',
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

const blankForm = () => ({
  pin: '',
  lot_number: '',
  survey_number: '',
  title_number: '',
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
  tax_declaration: {
    td_number: '',
    arp_number: '',
    effectivity_year: new Date().getFullYear(),
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
  lot_number: selected.value?.lot_number || '',
  survey_number: selected.value?.survey_number || '',
  title_number: selected.value?.title_number || '',
  barangay: selected.value?.barangay || '',
  municipality: selected.value?.municipality || null,
  province: selected.value?.province || 'Sample Province',
  classification: selected.value?.classification || 'Residential',
  actual_use: selected.value?.actual_use || '',
  land_area: selected.value?.land_area || null,
  unit_of_measure: selected.value?.unit_of_measure || 'sqm',
  status: selected.value?.status || 'Draft',
  remarks: selected.value?.remarks || ''
});

const blankDeclarationForm = () => ({
  owner: {
    name: currentTd(selected.value)?.owner?.name || '',
    address: currentTd(selected.value)?.owner?.address || ''
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
  memoranda: ''
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
    notes: ''
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
const digitizeForm = reactive({
  file: null,
  notes: '',
  ocr_text: ''
});

const columns = [
  { name: 'match', label: 'Match', field: 'match', align: 'left' },
  { name: 'td', label: 'Current TD', field: 'td', align: 'left', sortable: true },
  { name: 'pin', label: 'PIN', field: 'pin', align: 'left', sortable: true },
  { name: 'property', label: 'Property', field: 'lot_number', align: 'left', sortable: true },
  { name: 'owner', label: 'Owner', field: 'owner', align: 'left' },
  { name: 'location', label: 'Location', field: 'location', align: 'left' },
  { name: 'classification', label: 'Class', field: 'classification', align: 'left', sortable: true },
  { name: 'area', label: 'Area', field: 'land_area', align: 'right', sortable: true },
  { name: 'values', label: 'Values', field: 'values', align: 'right' },
  { name: 'counts', label: 'Indexed', field: 'counts', align: 'left' },
  { name: 'status', label: 'Status', field: 'status', align: 'left', sortable: true },
  { name: 'open', label: '', field: 'open', align: 'center' }
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
    const [propertyResult, totals] = await Promise.all([
      fetchProperties(searchParams()),
      fetchDashboard()
    ]);

    records.value = propertyResult.items || propertyResult;
    searchMeta.value = propertyResult.meta || null;
    syncMunicipalities(records.value);
    dashboard.value = totals;

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


async function openPropertyById(propertyId) {
  if (!propertyId) return;
await loadDossier(propertyId);
  await nextTick();
  const panel = recordPanel.value?.$el || recordPanel.value;
  panel?.scrollIntoView?.({ behavior: 'smooth', block: 'start' });
}

async function openRecentProperty(propertyId) {
  await openPropertyById(propertyId);
}

async function selectRecord(_, row) {
  if (!row?.id) return;

  await loadDossier(row.id);
  await nextTick();
  const panel = recordPanel.value?.$el || recordPanel.value;
  panel?.scrollIntoView?.({ behavior: 'smooth', block: 'start' });
}

function clearFilters() {
  keyword.value = '';
  municipality.value = null;
  searchPage.value = 1;
  Object.assign(filters, {
    lot_number: '',
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
  } catch {
    $q.notify({ type: 'negative', message: 'Unable to update property record.' });
  } finally {
    saving.value = false;
  }
}

async function approveSelectedProperty() {
  if (!selected.value) return;

  saving.value = true;
  try {
    const updated = await approveProperty(selected.value.id);
    await replaceSelected(updated);
    await loadRecords();
    $q.notify({ type: 'positive', message: 'Property record approved.' });
  } catch {
    $q.notify({ type: 'negative', message: 'Unable to approve property record.' });
  } finally {
    saving.value = false;
  }
}

async function archiveSelectedProperty() {
  if (!selected.value) return;

  saving.value = true;
  try {
    const updated = await archiveProperty(selected.value.id);
    await replaceSelected(updated);
    await loadRecords();
    $q.notify({ type: 'positive', message: 'Property record archived.' });
  } catch {
    $q.notify({ type: 'negative', message: 'Unable to archive property record.' });
  } finally {
    saving.value = false;
  }
}

async function replaceSelected(updatedProperty) {
  const index = records.value.findIndex((property) => property.id === updatedProperty.id);

  if (index >= 0) {
    records.value.splice(index, 1, updatedProperty);
  } else {
    records.value.unshift(updatedProperty);
  }

  await loadDossier(updatedProperty.id);
}

async function saveEntry() {
  saving.value = true;

  try {
    const created = await createProperty(JSON.parse(JSON.stringify(form)));
    await replaceSelected(created);
    entryDialog.value = false;
    Object.assign(form, blankForm());
    await loadRecords();
    $q.notify({ type: 'positive', message: 'Property record saved.' });
  } catch {
    $q.notify({ type: 'negative', message: 'Unable to save property. Check required fields and unique PIN/TD number.' });
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
    const updated = isEditing
      ? await updateTaxDeclaration(selected.value.id, editingDeclarationId.value, payload)
      : await addTaxDeclaration(selected.value.id, payload);

    if (!updated) {
      throw new Error('Empty response');
    }

    await replaceSelected(updated);
    declarationDialog.value = false;
    editingDeclarationId.value = null;
    profileTab.value = 'history';
    await loadRecords();
    $q.notify({ type: 'positive', message: isEditing ? 'Tax declaration updated.' : 'Tax declaration saved.' });
  } catch {
    $q.notify({ type: 'negative', message: 'Unable to save tax declaration. Check required fields and TD number uniqueness.' });
  } finally {
    saving.value = false;
  }
}

async function approveDeclaration(td) {
  if (!selected.value || !td?.id) return;

  saving.value = true;

  try {
    const updated = await approveTaxDeclaration(selected.value.id, td.id);
    await replaceSelected(updated);
    profileTab.value = 'history';
    await loadRecords();
    $q.notify({ type: 'positive', message: 'Tax declaration approved.' });
  } catch {
    $q.notify({ type: 'negative', message: 'Unable to approve tax declaration.' });
  } finally {
    saving.value = false;
  }
}

async function archiveDeclaration(td) {
  if (!selected.value || !td?.id) return;

  saving.value = true;

  try {
    const updated = await archiveTaxDeclaration(selected.value.id, td.id);
    await replaceSelected(updated);
    profileTab.value = 'history';
    await loadRecords();
    $q.notify({ type: 'positive', message: 'Tax declaration cancelled.' });
  } catch {
    $q.notify({ type: 'negative', message: 'Unable to cancel tax declaration.' });
  } finally {
    saving.value = false;
  }
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

function openDigitizeDialog(document) {
  selectedDocument.value = document;
  digitizeForm.file = null;
  digitizeForm.notes = '';
  digitizeForm.ocr_text = '';
  digitizeDialog.value = true;
}

async function saveDigitization() {
  if (!selectedDocument.value?.id || !digitizeForm.file) {
    $q.notify({ type: 'warning', message: 'Select a scanned file to upload.' });
    return;
  }

  saving.value = true;

  try {
    const updated = await digitizeDocument(selectedDocument.value.id, {
      file: digitizeForm.file,
      notes: digitizeForm.notes,
      ocr_text: digitizeForm.ocr_text || undefined
    });
    await replaceSelected(updated);
    digitizeDialog.value = false;
    profileTab.value = 'documents';
    await loadRecords();
    $q.notify({ type: 'positive', message: 'Physical record digitized successfully.' });
  } catch {
    $q.notify({ type: 'negative', message: 'Unable to upload scan for this record.' });
  } finally {
    saving.value = false;
  }
}

function openAssessmentEditDialog(td, record) {
  editingAssessmentId.value = record.id;
  Object.assign(assessmentForm, {
    ...blankAssessmentForm(),
    ...record,
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
  } catch {
    $q.notify({ type: 'negative', message: 'Unable to load movement history.' });
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

  saving.value = false;
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
  } catch {
    $q.notify({ type: 'negative', message: 'Unable to update document record.' });
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
  } catch {
    $q.notify({ type: 'negative', message: 'Unable to archive document record.' });
  } finally {
    saving.value = false;
  }
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
  } catch {
    $q.notify({ type: 'negative', message: 'Unable to record physical movement.' });
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
      profileTab.value = 'assessments';
      await loadRecords();
      $q.notify({ type: 'positive', message: editing ? 'Assessment updated.' : 'Assessment detail saved.' });
    } else {
      $q.notify({ type: 'negative', message: 'Unable to save assessment detail.' });
    }
  } catch {
    $q.notify({ type: 'negative', message: 'Unable to save assessment detail.' });
  } finally {
    saving.value = false;
  }
}

async function removeAssessmentRecord(td, record) {
  if (!selected.value || !td?.id || !record?.id) return;

  saving.value = true;

  try {
    const updated = await removeAssessment(selected.value.id, td.id, record.id);
    await replaceSelected(updated);
    profileTab.value = 'assessments';
    await loadRecords();
    $q.notify({ type: 'positive', message: 'Assessment record removed.' });
  } catch {
    $q.notify({ type: 'negative', message: 'Unable to remove assessment record.' });
  } finally {
    saving.value = false;
  }
}

async function downloadDocument(document) {
  try {
    await downloadDocumentFile(document);
  } catch {
    $q.notify({ type: 'negative', message: 'The scanned file is not available yet.' });
  }
}

async function downloadCsv() {
  try {
    await downloadPropertiesCsv();
  } catch {
    $q.notify({ type: 'negative', message: 'Unable to download CSV export.' });
  }
}

async function downloadBackup() {
  try {
    await downloadBackupJson();
  } catch {
    $q.notify({ type: 'negative', message: 'Unable to download backup export.' });
  }
}

async function exportRecord() {
  if (!selected.value?.id) return;

  try {
    await downloadPropertyDossierExport(selected.value.id);
    $q.notify({ type: 'positive', message: 'Property dossier downloaded.' });
  } catch {
    $q.notify({ type: 'negative', message: 'Unable to export property dossier.' });
  }
}

async function exportActivityCsv() {
  if (!selected.value?.id) return;

  try {
    await downloadPropertyActivityCsv(selected.value.id);
    $q.notify({ type: 'positive', message: 'Activity log exported.' });
  } catch {
    $q.notify({ type: 'negative', message: 'Unable to export activity log.' });
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
    Active: 'green-7',
    Draft: 'blue-grey-6',
    'For Review': 'amber-8',
    Superseded: 'indigo-6',
    Cancelled: 'red-7',
    Archived: 'grey-7'
  }[status] || 'primary';
}

function physicalStatusColor(status) {
  return {
    'On File': 'green-7',
    'For Scanning': 'amber-8',
    Released: 'deep-orange-7',
    Returned: 'teal-7',
    Missing: 'red-7',
    Archived: 'blue-grey-7'
  }[status] || 'primary';
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
  await loadRecords();

  const propertyId = Number(route.query.propertyId);
  if (propertyId) {
    await openPropertyById(propertyId);
  }
}

onMounted(bootstrapRecords);

watch(() => ({
  keyword: keyword.value,
  municipality: municipality.value,
  ...filters
}), () => {
  if (sessionUser.value) {
    loadRecords();
  }
}, { deep: true });

</script>



<style scoped>
/* SearchPage — unified with --c-* design tokens */

.workspace-page {
  background: var(--c-bg, #f4f6f9);
  color: var(--c-ink, #1e293b);
  min-height: 100vh;
}

.workspace-shell {
  max-width: 1440px;
  margin: 0 auto;
  padding: 16px 20px;
}

/* Toolbar / search / filter bands */
.toolbar-band,
.search-band,
.filter-band {
  background: var(--c-surface, #fff);
  border: 1px solid var(--c-border, #e2e6ed);
  border-radius: var(--radius, 8px);
  box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,0.06));
}

.toolbar-band {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 16px;
}

.brand-block,
.toolbar-actions {
  align-items: center;
  display: flex;
  gap: 8px;
  min-width: 0;
}

.brand-mark {
  align-items: center;
  background: var(--c-primary-light, #ebf1f9);
  border: 1px solid var(--c-border, #e2e6ed);
  border-radius: 8px;
  color: var(--c-primary, #2c5282);
  display: flex;
  height: 40px;
  justify-content: center;
  width: 40px;
  flex: 0 0 auto;
}

.search-band {
  margin-top: 10px;
  display: grid;
  grid-template-columns: minmax(200px, 1fr) minmax(160px, 240px) minmax(160px, 240px);
  gap: 10px;
  padding: 10px 14px;
}

.filter-band {
  display: grid;
  grid-template-columns: repeat(5, minmax(120px, 1fr)) repeat(3, minmax(140px, 1fr)) repeat(2, minmax(90px, 0.7fr));
  gap: 8px;
  margin-top: 8px;
  align-items: center;
  padding: 10px 14px;
}


/* Summary metrics */
.summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
  margin-top: 10px;
}

.metric-tile {
  align-items: center;
  background: var(--c-surface, #fff);
  border: 1px solid var(--c-border, #e2e6ed);
  border-radius: var(--radius, 8px);
  box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,0.06));
  display: flex;
  gap: 10px;
  min-width: 0;
  padding: 10px 12px;
}

.metric-icon {
  align-items: center;
  background: var(--c-primary-light, #ebf1f9);
  border-radius: 6px;
  color: var(--c-primary, #2c5282);
  display: flex;
  flex: 0 0 auto;
  font-size: 20px;
  height: 34px;
  justify-content: center;
  width: 34px;
}

.metric-icon.success { background: #ecfdf5; color: var(--c-success, #16a34a); }
.metric-icon.violet { background: #f3f0ff; color: var(--c-violet, #6d5dbc); }
.metric-icon.amber { background: #fffbeb; color: var(--c-warning, #d97706); }

.metric-tile span,
.detail-grid span,
.record-jacket span {
  color: var(--c-muted, #64748b);
  font-size: 0.68rem;
  font-weight: 600;
}

.metric-tile strong {
  color: var(--c-ink, #1e293b);
  font-size: 1.1rem;
  font-weight: 700;
  line-height: 1;
}

/* Content grid */
.content-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 10px;
  margin-top: 10px;
  align-items: start;
}


/* Records table */
.records-table {
  border-radius: var(--radius, 8px);
  overflow: hidden;
  border-color: var(--c-border, #e2e6ed);
  box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,0.06));
}

.records-table :deep(.q-table__top) {
  background: var(--c-primary-dark, #1a3a5c);
  color: white;
  min-height: 40px;
  padding: 8px 14px;
}

.records-table :deep(.q-table__title) {
  font-size: 0.78rem;
  font-weight: 700;
}

.records-table :deep(thead tr) {
  background: var(--c-surface-alt, #f8f9fb);
}

.records-table :deep(th) {
  color: var(--c-primary-dark, #1a3a5c);
  font-size: 0.68rem;
  font-weight: 700;
  height: 36px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.records-table :deep(td) {
  border-color: var(--c-border, #e2e6ed);
  color: var(--c-ink, #1e293b);
  height: 44px;
  font-size: 0.78rem;
}

.records-table :deep(tbody tr) {
  cursor: pointer;
  transition: background 0.15s ease;
}

.records-table :deep(tbody tr:hover) {
  background: var(--c-primary-light, #ebf1f9);
}

.records-table :deep(.q-table__bottom) {
  border-top: 1px solid var(--c-border, #e2e6ed);
  color: var(--c-muted, #64748b);
  min-height: 40px;
  font-size: 0.72rem;
}

.table-tools,
.document-side {
  display: flex;
  align-items: center;
  flex-wrap: nowrap;
  gap: 6px;
}

.document-side :deep(.q-btn) { flex: 0 0 auto; width: auto; }


/* Profile / record panel */
.profile-panel {
  background: var(--c-surface, #fff);
  border: 1px solid var(--c-border, #e2e6ed);
  border-radius: var(--radius, 8px);
  box-shadow: var(--shadow, 0 2px 8px rgba(0,0,0,0.08));
  overflow: visible;
}

.profile-panel :deep(.q-item__section--side) { flex: 0 0 auto; min-width: 0; }

.profile-header {
  background: var(--c-surface, #fff);
  border-bottom: 1px solid var(--c-border, #e2e6ed);
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 10px;
  padding: 12px 16px;
}

.jacket-toolbar {
  align-items: center;
  background: var(--c-surface-alt, #f8f9fb);
  border-bottom: 1px solid var(--c-border, #e2e6ed);
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: space-between;
  padding: 8px 16px;
}

.jacket-toolbar-group {
  align-items: center;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
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

.jacket-toolbar :deep(.q-btn) { min-width: 0; }

.profile-panel :deep(.q-tab-panels) {
  background: var(--c-surface-alt, #f8f9fb);
  padding: 16px;
}

.profile-panel :deep(.q-panel) { background: transparent; padding: 0; }
.profile-panel :deep(.q-tab) { font-weight: 600; font-size: 0.72rem; }
.profile-panel :deep(.q-tab--active) { background: var(--c-primary-light, #ebf1f9); border-radius: 6px 6px 0 0; }


/* Jacket sections */
.jacket-section {
  border: none;
  border-radius: var(--radius, 8px);
  background: var(--c-surface, #fff);
  margin: 12px;
  padding: 16px;
  box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,0.06));
}

.section-head {
  align-items: center;
  display: flex;
  gap: 10px;
  justify-content: space-between;
  margin-bottom: 12px;
  padding-bottom: 10px;
  border-bottom: 1px solid var(--c-border, #e2e6ed);
}

.section-head-content {
  display: flex;
  align-items: center;
  gap: 8px;
}

.section-title {
  font-size: 0.82rem;
  font-weight: 700;
  line-height: 1.3;
  margin: 0;
  color: var(--c-ink, #1e293b);
}

.section-kicker {
  color: var(--c-primary, #2c5282);
  font-size: 0.6rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

/* TD layout */
.td-layout {
  display: grid;
  gap: 10px;
  grid-template-columns: minmax(200px, 260px) minmax(0, 1fr);
}

.td-list {
  border: 1px solid var(--c-border, #e2e6ed);
  border-radius: var(--radius, 8px);
  max-height: 420px;
  overflow: auto;
  background: var(--c-surface, #fff);
}

.td-list-item--active {
  background: var(--c-primary-light, #ebf1f9) !important;
  border-left: 3px solid var(--c-primary, #2c5282);
}

.td-list-badges {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.td-detail-panel {
  background: var(--c-surface, #fff);
  border: 1px solid var(--c-border, #e2e6ed);
  border-radius: var(--radius, 8px);
  display: flex;
  flex-direction: column;
  gap: 12px;
  max-height: 420px;
  overflow: auto;
  padding: 14px;
}

.td-detail-panel--empty {
  align-items: center;
  color: var(--c-muted, #64748b);
  justify-content: center;
  text-align: center;
}

.td-detail-panel--empty span { font-size: 0.75rem; margin-top: 3px; }


.td-detail-header {
  align-items: flex-start;
  border-bottom: 1px solid var(--c-border, #e2e6ed);
  display: flex;
  gap: 10px;
  justify-content: space-between;
  padding-bottom: 10px;
  margin-bottom: 4px;
}

.td-detail-title {
  font-size: 0.85rem;
  font-weight: 700;
  line-height: 1.2;
  margin: 2px 0 0;
}

.td-detail-meta {
  color: var(--c-muted, #64748b);
  font-size: 0.68rem;
  margin: 3px 0 0;
}

/* Info grids */
.info-grid {
  display: grid;
  gap: 8px;
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.info-cell {
  background: var(--c-surface-alt, #f8f9fb);
  border: 1px solid var(--c-border, #e2e6ed);
  border-left: 3px solid var(--c-primary, #2c5282);
  border-radius: 6px;
  display: grid;
  gap: 4px;
  min-width: 0;
  padding: 8px 10px;
}

.info-cell span {
  color: var(--c-muted, #64748b);
  font-size: 0.6rem;
  text-transform: uppercase;
}

.info-cell strong {
  font-size: 0.75rem;
  overflow-wrap: anywhere;
}

/* Record jacket & detail grids */
.record-jacket,
.detail-grid {
  padding: 12px;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;
}

.record-jacket {
  grid-template-columns: repeat(6, minmax(0, 1fr));
  border-top: none;
  border-bottom: none;
  background: var(--c-surface-alt, #f8f9fb);
  padding: 14px;
}

.jacket-metric {
  background: var(--c-surface, #fff);
  border: 1px solid var(--c-border, #e2e6ed);
  border-radius: var(--radius, 8px);
  padding: 10px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.jacket-metric .metric-icon {
  font-size: 20px;
  color: var(--c-primary, #2c5282);
  background: var(--c-primary-light, #ebf1f9);
  padding: 6px;
  border-radius: 6px;
  width: fit-content;
}

.jacket-metric span {
  color: var(--c-muted, #64748b);
  font-size: 0.6rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.jacket-metric strong {
  color: var(--c-ink, #1e293b);
  font-size: 0.82rem;
  font-weight: 700;
}


.detail-grid {
  border: 1px solid var(--c-border, #e2e6ed);
  border-radius: var(--radius, 8px);
  background: var(--c-surface, #fff);
  margin-bottom: 12px;
}

.detail-grid div,
.record-jacket div {
  background: var(--c-surface, #fff);
  border: 1px solid var(--c-border, #e2e6ed);
  border-radius: 6px;
  display: grid;
  gap: 4px;
  min-width: 0;
  padding: 8px 10px;
}

.detail-grid strong,
.record-jacket strong {
  color: var(--c-ink, #1e293b);
  min-width: 0;
  overflow-wrap: anywhere;
  font-size: 0.78rem;
}

.mini-counts {
  display: flex;
  gap: 4px;
  flex-wrap: wrap;
}

/* UI blocks */
.ui-block {
  background: var(--c-surface, #fff);
  border: 1px solid var(--c-border, #e2e6ed);
  border-radius: var(--radius, 8px);
  padding: 14px;
}

.ui-block-head {
  align-items: center;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: space-between;
  margin-bottom: 10px;
  padding-bottom: 8px;
  border-bottom: 1px solid var(--c-border, #e2e6ed);
}

.ui-block-title {
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--c-primary, #2c5282);
}

.ui-block-body {
  color: var(--c-muted, #64748b);
  font-size: 0.78rem;
  margin: 0;
}

.ui-stack { display: grid; gap: 6px; }


/* Row cards (history, documents, assessments) */
.ui-row-card {
  background: var(--c-surface, #fff);
  border: 1px solid var(--c-border, #e2e6ed);
  border-left: 3px solid var(--c-primary, #2c5282);
  border-radius: 6px;
  padding: 10px 12px;
}

.ui-row-card-head {
  align-items: center;
  display: flex;
  gap: 6px;
  justify-content: space-between;
}

.ui-row-card-meta {
  color: var(--c-muted, #64748b);
  display: flex;
  flex-wrap: wrap;
  font-size: 0.68rem;
  gap: 6px;
  margin-top: 4px;
}

.ui-row-card p {
  color: var(--c-muted, #64748b);
  font-size: 0.72rem;
  margin: 4px 0 0;
}

.ui-row-actions { margin-top: 6px; }

.ui-list {
  border-radius: 6px;
  border: 1px solid var(--c-border, #e2e6ed);
  overflow: hidden;
}

/* History / documents / assessments */
.history-list,
.document-groups,
.assessment-groups,
.assessment-lines {
  display: grid;
  gap: 6px;
}

.timeline-expansion,
.timeline-body,
.search-pagination,
.history-item,
.remarks-box,
.document-group,
.assessment-group,
.assessment-line {
  background: var(--c-surface, #fff);
  border: 1px solid var(--c-border, #e2e6ed);
  border-left: 3px solid var(--c-primary, #2c5282);
  border-radius: 6px;
  padding: 10px 12px;
}

.assessment-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.history-heading,
.history-meta,
.document-group-title {
  display: flex;
  justify-content: space-between;
  gap: 8px;
  align-items: flex-start;
}

.history-heading div { display: grid; gap: 2px; }
.history-heading span,
.history-meta,
.remarks-box { color: var(--c-muted, #64748b); }

.history-meta {
  flex-wrap: wrap;
  margin-top: 4px;
  font-size: 0.68rem;
  row-gap: 4px;
}


.panel-lead {
  color: var(--c-muted, #64748b);
  font-size: 0.75rem;
  margin: 0 0 8px;
}

.timeline-block-title {
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  margin-bottom: 4px;
  text-transform: uppercase;
}

.timeline-mini-row {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  font-size: 0.68rem;
  margin-bottom: 3px;
}

.timeline-list {
  border-radius: 6px;
  overflow: hidden;
}

.history-item p {
  margin: 4px 0 0;
  color: var(--c-muted, #64748b);
  font-size: 0.72rem;
}

.inline-actions {
  border-top: 1px solid var(--c-border, #e2e6ed);
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  justify-content: flex-end;
  margin-top: 6px;
  padding-top: 6px;
}

.document-group-title {
  align-items: center;
  border-bottom: 1px solid var(--c-border, #e2e6ed);
  margin-bottom: 6px;
  padding-bottom: 6px;
}

/* Empty states */
.empty-state {
  background: var(--c-surface-alt, #f8f9fb);
  border: 1px dashed var(--c-border-strong, #cdd3de);
  border-radius: var(--radius, 8px);
  color: var(--c-muted, #64748b);
  padding: 20px;
  text-align: center;
  font-size: 0.78rem;
}

.empty-state::before { display: none; }
.empty-state.compact { padding: 12px; font-size: 0.72rem; }

.empty-record-panel {
  align-items: center;
  background: var(--c-surface, #fff);
  border: 1px dashed var(--c-border-strong, #cdd3de);
  border-radius: var(--radius, 8px);
  color: var(--c-muted, #64748b);
  display: flex;
  gap: 10px;
  padding: 16px;
  font-size: 0.78rem;
}

.empty-record-panel div { display: grid; gap: 2px; }

/* Tabs */
.jacket-tabs {
  background: var(--c-surface-alt, #f8f9fb);
  padding: 0 14px;
  border-top: 1px solid var(--c-border, #e2e6ed);
}

.td-detail-actions {
  background: transparent;
  border-bottom: none;
  border-top: 1px solid var(--c-border, #e2e6ed);
  margin-top: auto;
  padding: 8px 0 0;
}


/* Recent chips */
.recent-band { display: flex; flex-direction: column; gap: 6px; }
.recent-chips { display: flex; flex-wrap: wrap; gap: 6px; }

/* Dialogs */
.entry-card { width: min(900px, 96vw); border-radius: var(--radius, 8px); }
.compact-card { width: min(680px, 96vw); }
.entry-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
.span-2 { grid-column: span 2; }

/* Quasar overrides scoped to workspace-page */
.workspace-page :deep(.bg-primary) { background: var(--c-primary, #2c5282) !important; }
.workspace-page :deep(.text-primary) { color: var(--c-primary, #2c5282) !important; }

.workspace-page :deep(.q-btn) {
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.72rem;
  min-height: 32px;
}

.workspace-page :deep(.q-btn--round) { border-radius: 50%; }

.workspace-page :deep(.q-field--outlined .q-field__control) {
  background: var(--c-surface, #fff);
  border-radius: 6px;
}

.workspace-page :deep(.q-field--outlined .q-field__control::before) {
  border-color: var(--c-border, #e2e6ed);
}

.workspace-page :deep(.q-field--outlined.q-field--focused .q-field__control::after) {
  border-color: var(--c-primary, #2c5282);
  border-width: 1px;
}

.workspace-page :deep(.q-field__label),
.workspace-page :deep(.text-blue-grey-7),
.workspace-page :deep(.text-blue-grey-6) {
  color: var(--c-muted, #64748b) !important;
  font-size: 0.68rem;
}

.workspace-page :deep(.q-badge) {
  border-radius: 999px;
  font-size: 0.62rem;
  font-weight: 600;
  min-height: 20px;
  padding: 3px 8px;
}

.workspace-page :deep(.q-tabs) {
  background: var(--c-surface, #fff);
  color: var(--c-muted, #64748b);
  padding: 0 10px;
}

.workspace-page :deep(.q-tab) { min-height: 38px; }
.workspace-page :deep(.q-tab__label) { font-size: 0.68rem; font-weight: 700; }
.workspace-page :deep(.q-tab-panels) { background: var(--c-surface-alt, #f8f9fb); }
.workspace-page :deep(.q-tab-panel) { padding: 12px; }

.workspace-page :deep(.q-list--bordered) {
  border-color: var(--c-border, #e2e6ed);
  border-radius: 6px;
  overflow: hidden;
}

.workspace-page :deep(.q-item) {
  background: var(--c-surface, #fff);
  min-height: 48px;
}

.workspace-page :deep(.q-item:not(:last-child)) {
  border-bottom-color: var(--c-border, #e2e6ed);
}

.workspace-page :deep(.q-dialog .q-card__section:first-child) {
  background: var(--c-primary-dark, #1a3a5c);
  color: white;
}

.workspace-page :deep(.q-dialog .q-card__section:first-child .text-blue-grey-7) {
  color: rgba(255, 255, 255, 0.7) !important;
}


/* Responsive */
@media (max-width: 1180px) {
  .filter-band { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .record-jacket { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}

@media (max-width: 980px) {
  .summary-grid,
  .search-band,
  .entry-grid,
  .record-jacket,
  .detail-grid { grid-template-columns: 1fr; }
  .filter-band { grid-template-columns: 1fr; }
  .span-2 { grid-column: span 1; }
  .toolbar-band { align-items: flex-start; flex-direction: column; }
  .brand-block, .toolbar-actions { align-items: center; width: 100%; }
  .toolbar-actions { flex-wrap: wrap; }
  .jacket-metric { flex-direction: row; align-items: center; }
}

@media (max-width: 640px) {
  .workspace-shell { padding: 10px; }
  .summary-grid { gap: 8px; }
  .metric-tile,
  .search-band,
  .filter-band,
  .profile-header,
  .jacket-toolbar,
  .jacket-section { padding: 8px; }
  .td-layout { grid-template-columns: 1fr; }
  .td-list, .td-detail-panel { max-height: none; }
  .brand-mark { height: 34px; width: 34px; }
}

/* Legacy compat: map old --ui-* usage to new --c-* vars */
.workspace-page {
  --ui-bg: var(--c-bg);
  --ui-surface: var(--c-surface);
  --ui-surface-soft: var(--c-surface-alt);
  --ui-border: var(--c-border);
  --ui-border-strong: var(--c-border-strong);
  --ui-primary: var(--c-primary);
  --ui-primary-strong: var(--c-primary-dark);
  --ui-primary-soft: var(--c-primary-light);
  --ui-ink: var(--c-ink);
  --ui-muted: var(--c-muted);
  --ui-success: var(--c-success);
  --ui-violet: var(--c-violet);
  --ui-amber: var(--c-warning);
  --ui-danger: var(--c-danger);
}
</style>

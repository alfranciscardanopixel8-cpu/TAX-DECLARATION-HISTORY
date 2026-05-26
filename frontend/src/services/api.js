import axios from 'axios';
import { mockProperties } from '../data/mockRecords';

const client = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://127.0.0.1:8002/api',
  timeout: 10000
});

const TOKEN_KEY = 'assessor_api_token';
const ALLOW_OFFLINE_DEMO = import.meta.env.VITE_ALLOW_OFFLINE_DEMO !== 'false';
// Mock fallback only enabled with explicit opt-in via VITE_USE_MOCK_FALLBACK=true
const USE_MOCK_FALLBACK = import.meta.env.VITE_USE_MOCK_FALLBACK === 'true';

function useMockFallback(error) {
  if (!USE_MOCK_FALLBACK) {
    return false;
  }

  // Only fall back on actual network failures, not validation errors or business errors
  return !error?.response || error.response.status >= 500;
}

function setAuthHeader(token) {
  if (token) {
    client.defaults.headers.common.Authorization = `Bearer ${token}`;
  } else {
    delete client.defaults.headers.common.Authorization;
  }
}

setAuthHeader(localStorage.getItem(TOKEN_KEY));

export function getStoredToken() {
  return localStorage.getItem(TOKEN_KEY);
}

export function clearStoredToken() {
  localStorage.removeItem(TOKEN_KEY);
  setAuthHeader(null);
}

const DEMO_USER = {
  id: 0,
  name: 'System Admin',
  email: 'admin@assessor.local',
  role: 'admin',
  status: 'Active',
  can_manage_records: true,
  can_approve_records: true,
  can_administer: true
};

function isNetworkError(error) {
  return !error.response && (error.code === 'ERR_NETWORK' || error.message?.includes('Network Error'));
}

function offlineDemoLogin(email, password) {
  if (!ALLOW_OFFLINE_DEMO) {
    return null;
  }

  const normalizedEmail = (email || '').trim().toLowerCase();
  const isDemoAdmin = normalizedEmail === 'admin@assessor.local' && password === 'password';

  if (!isDemoAdmin) {
    return null;
  }

  const offlineToken = 'offline-demo-token';
  localStorage.setItem(TOKEN_KEY, offlineToken);
  setAuthHeader(offlineToken);
  return { ...DEMO_USER };
}

export async function login(email, password) {
  try {
    const { data } = await client.post('/auth/login', { email, password });
    localStorage.setItem(TOKEN_KEY, data.token);
    setAuthHeader(data.token);
    return data.user;
  } catch (error) {
    if (isNetworkError(error)) {
      const offlineUser = offlineDemoLogin(email, password);

      if (offlineUser) {
        return offlineUser;
      }

      const connectionError = new Error('API_UNAVAILABLE');
      connectionError.cause = error;
      throw connectionError;
    }

    throw error;
  }
}

export async function logout() {
  try {
    await client.post('/auth/logout');
  } finally {
    clearStoredToken();
  }
}

export async function fetchMe() {
  const token = getStoredToken();

  if (!token) return null;

  if (token === 'offline-demo-token') {
    return { ...DEMO_USER };
  }

  try {
    const { data } = await client.get('/auth/me');
    return data.user;
  } catch (error) {
    if (ALLOW_OFFLINE_DEMO && isNetworkError(error) && token === 'offline-demo-token') {
      return { ...DEMO_USER };
    }

    // Only clear token on actual auth failures (401/403), not on transient errors
    if (error?.response?.status === 401 || error?.response?.status === 403) {
      clearStoredToken();
    }
    return null;
  }
}

export async function fetchApiHealth() {
  try {
    const { data } = await client.get('/health', { timeout: 4000 });
    return { online: true, ...data };
  } catch {
    return { online: false, status: 'offline' };
  }
}

export function documentDownloadUrl(documentId) {
  return `${client.defaults.baseURL}/documents/${documentId}/download`;
}

function downloadBlob(data, filename, type = 'application/octet-stream') {
  const blob = new Blob([data], { type });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  link.click();
  URL.revokeObjectURL(url);
}

export async function fetchReferences() {
  try {
    const { data } = await client.get('/references');
    return data;
  } catch {
    return null;
  }
}

export async function fetchProperties(params = {}) {
  try {
    const { data } = await client.get('/properties', { params });

    if (Array.isArray(data?.data)) {
      return {
        items: data.data,
        meta: data.meta || null,
        links: data.links || null
      };
    }

    return {
      items: Array.isArray(data) ? data : [],
      meta: null,
      links: null
    };
  } catch (error) {
    if (!useMockFallback(error)) {
      throw error;
    }

    console.warn('Properties API unavailable, using offline mock data.', error);

    const keyword = (params.search || '').toLowerCase();
    const municipality = params.municipality;

    const items = mockProperties.filter((property) => {
      const current = property.tax_declarations[0] || {};
      const owner = current.owner?.name || '';
      const searchable = [
        property.pin,
        property.property_index_number,
        property.lot_number,
        property.survey_number,
        property.title_number,
        property.barangay,
        property.municipality,
        current.td_number,
        current.arp_number,
        owner
      ].join(' ').toLowerCase();

      const lotFilter = (params.lot_number || '').toLowerCase();

      return (!keyword || searchable.includes(keyword))
        && (!municipality || property.municipality === municipality)
        && (!lotFilter || (property.lot_number || '').toLowerCase().includes(lotFilter));
    }).map((property) => {
      if (!keyword) {
        return property;
      }

      const lotMatch = (property.lot_number || '').toLowerCase().includes(keyword);
      const tdMatch = (property.tax_declarations || []).some((td) => (td.td_number || '').toLowerCase().includes(keyword));

      return {
        ...property,
        search_match: property.search_match || {
          matched_on: lotMatch ? 'lot_number' : tdMatch ? 'td_number' : 'owner',
          matched_value: lotMatch ? property.lot_number : tdMatch ? property.tax_declarations.find((td) => (td.td_number || '').toLowerCase().includes(keyword))?.td_number : property.tax_declarations?.[0]?.owner?.name
        }
      };
    });

    return { items, meta: null, links: null };
  }
}

export async function fetchPropertyDossier(propertyId) {
  try {
    const { data } = await client.get(`/properties/${propertyId}/dossier`);
    return data;
  } catch (error) {
    if (!useMockFallback(error)) {
      throw error;
    }

    const property = mockProperties.find((item) => item.id === propertyId);

    if (!property) {
      return null;
    }

    const timeline = (property.tax_declarations || []).map((td) => ({
      tax_declaration: td,
      assessment_records: td.assessment_records || property.assessment_records?.filter((record) => record.tax_declaration_id === td.id) || [],
      documents: (property.documents || []).filter((document) => document.tax_declaration_id === td.id),
      document_count: (property.documents || []).filter((document) => document.tax_declaration_id === td.id).length,
      assessment_count: (td.assessment_records || []).length,
      data_entry_events: (property.activity_logs || []).filter((log) => log.tax_declaration_id === td.id)
    }));

    return {
      property,
      current_tax_declaration: property.tax_declarations?.find((td) => td.status === 'Active') || property.tax_declarations?.[0],
      tax_declaration_history: property.tax_declarations || [],
      tax_declaration_timeline: timeline,
      property_documents: (property.documents || []).filter((document) => !document.tax_declaration_id),
      owner_history: (property.tax_declarations || []).map((td) => ({
        owner_name: td.owner?.name,
        owner_address: td.owner?.address,
        td_number: td.td_number,
        effectivity_year: td.effectivity_year,
        transaction_type: td.transaction_type,
        status: td.status
      })),
      documents_by_type: {},
      data_entry_timeline: property.activity_logs || [],
      pending_digitization: (property.documents || []).filter((document) => document.needs_digitization || document.physical_copy_status === 'For Scanning'),
      valuation_summary: {},
      counts: {
        tax_declarations: property.tax_declarations?.length || 0,
        assessment_records: property.assessment_records?.length || 0,
        documents: property.documents?.length || 0,
        digitized_documents: (property.documents || []).filter((document) => !document.needs_digitization).length,
        pending_digitization: (property.documents || []).filter((document) => document.needs_digitization || document.physical_copy_status === 'For Scanning').length,
        owners: new Set((property.tax_declarations || []).map((td) => td.owner?.name)).size,
        audit_events: property.activity_logs?.length || 0
      }
    };
  }
}

export async function fetchDashboard() {
  try {
    const { data } = await client.get('/dashboard');
    return data;
  } catch (error) {
    if (!useMockFallback(error)) {
      throw error;
    }

    console.warn('Dashboard API unavailable, using offline totals.', error);

    return {
      properties: mockProperties.length,
      active_properties: mockProperties.filter((property) => property.status === 'Active').length,
      for_review: mockProperties.filter((property) => property.status === 'For Review').length,
      tax_declarations: mockProperties.reduce((sum, property) => sum + (property.tax_declarations?.length || 0), 0),
      active_tax_declarations: mockProperties.flatMap((property) => property.tax_declarations || []).filter((td) => td.status === 'Active').length,
      documents: mockProperties.reduce((sum, property) => sum + (property.documents?.length || 0), 0),
      recent_activity: []
    };
  }
}

export async function fetchActivityLogs(params = {}) {
  const { data } = await client.get('/activity-logs', { params });
  return data;
}

export async function fetchActivityLogSummary() {
  const { data } = await client.get('/activity-logs/summary');
  return data;
}

export async function downloadActivityLogsCsv(params = {}) {
  const { data } = await client.get('/activity-logs/export.csv', { params, responseType: 'blob' });
  downloadBlob(data, `audit-logs-${new Date().toISOString().slice(0, 10)}.csv`, 'text/csv');
}

export async function createProperty(payload) {
  const { data } = await client.post('/properties', payload);
  return data;
}

export async function updateProperty(propertyId, payload) {
  const { data } = await client.put(`/properties/${propertyId}`, payload);
  return data;
}

export async function approveProperty(propertyId) {
  const { data } = await client.post(`/properties/${propertyId}/approve`);
  return data;
}

export async function archiveProperty(propertyId) {
  const { data } = await client.delete(`/properties/${propertyId}`);
  return data;
}

export async function addTaxDeclaration(propertyId, payload) {
  const { data } = await client.post(`/properties/${propertyId}/tax-declarations`, payload);
  return data;
}

export async function updateTaxDeclaration(propertyId, taxDeclarationId, payload) {
  const { data } = await client.put(`/properties/${propertyId}/tax-declarations/${taxDeclarationId}`, payload);
  return data;
}

export async function approveTaxDeclaration(propertyId, taxDeclarationId) {
  const { data } = await client.post(`/properties/${propertyId}/tax-declarations/${taxDeclarationId}/approve`);
  return data;
}

export async function archiveTaxDeclaration(propertyId, taxDeclarationId) {
  const { data } = await client.delete(`/properties/${propertyId}/tax-declarations/${taxDeclarationId}`);
  return data;
}

export async function addAssessment(propertyId, taxDeclarationId, payload) {
  const { data } = await client.post(`/properties/${propertyId}/tax-declarations/${taxDeclarationId}/assessments`, payload);
  return data;
}

export async function updateAssessment(propertyId, taxDeclarationId, assessmentRecordId, payload) {
  const { data } = await client.put(`/properties/${propertyId}/tax-declarations/${taxDeclarationId}/assessments/${assessmentRecordId}`, payload);
  return data;
}

export async function removeAssessment(propertyId, taxDeclarationId, assessmentRecordId) {
  const { data } = await client.delete(`/properties/${propertyId}/tax-declarations/${taxDeclarationId}/assessments/${assessmentRecordId}`);
  return data;
}

export async function addDocument(propertyId, payload) {
  const formData = new FormData();

  Object.entries(payload).forEach(([key, value]) => {
    if (value !== null && value !== undefined && value !== '') {
      formData.append(key, value);
    }
  });

  const { data } = await client.post(`/properties/${propertyId}/documents`, formData);
  return data;
}

export async function updateDocument(documentId, payload) {
  const body = { ...payload };
  delete body.file;

  const { data } = await client.put(`/documents/${documentId}`, body);
  return data;
}

export async function archiveDocument(documentId) {
  const { data } = await client.delete(`/documents/${documentId}`);
  return data;
}

export async function movePhysicalRecord(propertyId, documentId, payload) {
  const { data } = await client.post(`/properties/${propertyId}/documents/${documentId}/movements`, payload);
  return data;
}

export async function downloadDocumentFile(document) {
  const { data } = await client.get(`/documents/${document.id}/download`, { responseType: 'blob' });
  downloadBlob(data, document.file_name || `document-${document.id}`, document.mime_type || 'application/octet-stream');
}

export async function viewDocumentFile(document) {
  const { data } = await client.get(`/documents/${document.id}/download`, { responseType: 'blob' });
  const blob = new Blob([data], { type: document.mime_type || 'application/pdf' });
  const url = URL.createObjectURL(blob);
  return url;
}

export async function saveDocumentOcr(documentId, ocrText) {
  const { data } = await client.post(`/documents/${documentId}/ocr`, { ocr_text: ocrText });
  return data;
}

export async function downloadPropertiesCsv() {
  const { data } = await client.get('/properties/export.csv', { responseType: 'blob' });
  downloadBlob(data, 'property-records.csv', 'text/csv');
}

export async function downloadBackupJson() {
  const { data } = await client.get('/backup/export', { responseType: 'blob' });
  downloadBlob(data, `assessor-system-backup-${new Date().toISOString().slice(0, 10)}.json`, 'application/json');
}

export async function fetchDocumentMovements(documentId) {
  const { data } = await client.get(`/documents/${documentId}/movements`);
  return data;
}

export async function downloadPropertyDossierExport(propertyId) {
  const { data } = await client.get(`/properties/${propertyId}/export/dossier`, { responseType: 'blob' });
  // Open the dossier HTML in a new window for printing/saving as PDF
  const blob = new Blob([data], { type: 'text/html' });
  const url = URL.createObjectURL(blob);
  const win = window.open(url, '_blank');
  if (!win) {
    // Fallback to download if popup blocked
    downloadBlob(data, `property-${propertyId}-dossier.html`, 'text/html');
  }
  // Clean up after a delay
  setTimeout(() => URL.revokeObjectURL(url), 60000);
}

export async function downloadPropertyActivityCsv(propertyId) {
  const { data } = await client.get(`/properties/${propertyId}/export/activity.csv`, { responseType: 'blob' });
  downloadBlob(data, `property-${propertyId}-activity.csv`, 'text/csv');
}

export async function fetchUsers() {
  const { data } = await client.get('/users');
  return data;
}

export async function fetchOwners(params = {}) {
  const { data } = await client.get('/owners', { params });
  return data;
}

export async function fetchOwnerProperties(ownerId) {
  const { data } = await client.get(`/owners/${ownerId}`);
  return data;
}

export async function fetchOwnerDetail(ownerId) {
  const { data } = await client.get(`/owners/${ownerId}`);
  return data;
}

export async function fetchLoginActivity(params = {}) {
  const { data } = await client.get('/auth/login-activity', { params });
  return data;
}

export async function fetchSecurityMatrix() {
  const { data } = await client.get('/security/matrix');
  return data;
}

export async function fetchLoginStats() {
  const { data } = await client.get('/security/login-stats');
  return data;
}

export async function createUser(payload) {
  const { data } = await client.post('/users', payload);
  return data;
}

export async function updateUser(userId, payload) {
  const { data } = await client.put(`/users/${userId}`, payload);
  return data;
}

export async function updateUserPermissions(userId, { grants, denies }) {
  const { data } = await client.put(`/users/${userId}/permissions`, { grants, denies });
  return data;
}

export async function resetUserPermissions(userId) {
  const { data } = await client.delete(`/users/${userId}/permissions`);
  return data;
}

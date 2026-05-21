# Enhancement Phases

Phased delivery for the Provincial Assessor Tax Declaration Retrieval System.

## Phase 0 — Stabilize

- API health endpoint with service metadata
- Header **API online/offline** badge
- Offline demo login gated by `VITE_ALLOW_OFFLINE_DEMO` (default on in dev)
- Mock data fallback gated by `VITE_USE_MOCK_FALLBACK` or `import.meta.env.DEV`
- Clear login errors when API is unreachable

## Phase 1 — Retrieval workflow

- **Movement history** dialog per document (`GET /api/documents/{id}/movements`)
- **Server dossier export** (`GET /api/properties/{id}/export/dossier`)
- **Print jacket** includes TD table and document list
- **Recently opened** properties in `localStorage`

## Phase 2 — Digitization

- Global queue: `GET /api/digitization-queue`
- Workspace tab **Digitization queue**
- Seeder sample: physical TD marked **For Scanning**

## Phase 3 — Workspace UX

- Workspace tabs: Records, Digitization, System activity
- Dashboard **recent activity** panel
- **Assessment edit** dialog (update existing line items)

## Phase 4 — Administration

- Property activity CSV export (`GET /api/properties/{id}/export/activity.csv`)
- Staff user API: list/create/update (`/api/users`, admin only)
- **Staff accounts** workspace tab

## Phase 5 — Performance & quality

- Search indexes migration on properties, tax declarations, owners
- Feature tests: auth login, property dossier shape

## Phase 6 — Scale & structure

- **Sidebar modules** (`config/workspaceModules.js`, `AppSidebar.vue`): Records, Digitization, Operations (Dashboard + Activity), Data (Import), Administration (Staff)
- **Routed workspace**: `/login`, `/workspace/dashboard`, `/workspace/records`, `/workspace/digitize`, `/workspace/activity`, `/workspace/staff`, `/workspace/import`
- **Bulk CSV import**: template download + `POST /api/properties/import` (admin/assessor)
- **OCR text** on documents: searchable via property search; optional on digitize; `POST /api/documents/{id}/ocr`

## Phase 7 — Future

- Automated OCR (Tesseract/cloud), deeper component split of record jacket dialogs

## Environment variables (frontend)

| Variable | Purpose |
|----------|---------|
| `VITE_API_URL` | API base URL (default `http://127.0.0.1:8002/api`) |
| `VITE_ALLOW_OFFLINE_DEMO` | Allow `admin@assessor.local` offline login when API is down |
| `VITE_USE_MOCK_FALLBACK` | Use mock records when API errors (also enabled in dev) |

# Data Entry Guide — Encoding Existing Records

## Overview

This guide walks through how to enter existing property records (from physical files) into the system. This is for the initial encoding phase where the office digitizes their paper-based records.

---

## BEFORE YOU START

### What You Need
- Physical TD folders from the records room
- Access to the system (login as `records@assessor.local` or your assigned account)
- The backend server running (`php artisan serve --port=8002`)

### Encoding Strategy

**Recommended approach:** Encode by municipality, one barangay at a time.

| Phase | What to Encode | Priority |
|-------|---------------|----------|
| Phase 1 | Current ACTIVE TDs only (latest per property) | High |
| Phase 2 | Previous/Superseded TDs (ownership history) | Medium |
| Phase 3 | Scan physical documents | Low (can do later) |

---

## SAMPLE: Encoding a Property with Transfer History

### The Physical File Contains:

```
Property: Lot 1247-B, Brgy. San Isidro, Municipality of Bayombong
PIN: 037-01-004-012-000
Title: TCT-102938
Survey: PSD-03-001248
Area: 480 sqm, Residential

TD History:
  1. TD-2018-000441 (Maria Santos, Transfer, 2018, ₱420,000 MV, ₱84,000 AV) — SUPERSEDED
  2. TD-2026-000128 (Juan Dela Cruz, Transfer, 2026, ₱690,000 MV, ₱138,000 AV) — ACTIVE

Documents on file:
  - TD-2026-000128 (printed copy)
  - Deed of Absolute Sale dated 2026-03-15
  - TCT-102938 (photocopy)
```

---

## STEP-BY-STEP ENCODING

### Step 1: Create the Property (with current active TD)

1. Click **"New Property"** button
2. Fill in the form:

**Property Identification:**
| Field | Value |
|-------|-------|
| PIN | 037-01-004-012-000 |
| Lot Number | Lot 1247-B |
| Survey Number | PSD-03-001248 |
| Title Number | TCT-102938 |
| Barangay | San Isidro |
| Municipality | Bayombong |

**Property Details:**
| Field | Value |
|-------|-------|
| Classification | Residential |
| Land Area | 480 |
| Unit | sqm |

**Owner / Claimant:**
| Field | Value |
|-------|-------|
| Owner Name | Juan Dela Cruz |
| Owner Address | Brgy. San Isidro, Bayombong |

**Initial Tax Declaration (the CURRENT ACTIVE one):**
| Field | Value |
|-------|-------|
| TD Number | TD-2026-000128 |
| ARP Number | ARP-2026-0128 |
| Effectivity Year | 2026 |
| Transaction Type | Transfer |
| TD Status | Active |
| Actual Use | Residential Lot |
| Market Value | 690000 |
| Assessed Value | 138000 |
| Assessment Level | 20 |

3. Click **"Save Property"**

✅ Property is now in the system with its current active TD.

---

### Step 2: Add the Previous TD (history)

1. Find the property in search (type "1247-B" or "037-01-004")
2. Click the property row to open it
3. In the **Tax Declarations** section, click **"Add TD"**
4. Fill in the PREVIOUS TD:

| Field | Value |
|-------|-------|
| Owner Name | Maria Santos |
| Owner Address | Capital City |
| TD Number | TD-2018-000441 |
| ARP Number | ARP-2018-0441 |
| Effectivity Year | 2018 |
| Transaction Type | Transfer |
| TD Status | Superseded |
| Classification | Residential |
| Actual Use | Residential Lot |
| Market Value | 420000 |
| Assessed Value | 84000 |
| Assessment Level | 20 |
| Memoranda | Superseded by TD-2026-000128 after deed of sale. |

5. Click **"Add To History"**

✅ The TD history now shows both declarations with the ownership chain.

---

### Step 3: Register Physical Documents

1. Still on the same property, click **"Add File"**
2. Register each physical document:

**Document 1: Current TD (printed copy)**
| Field | Value |
|-------|-------|
| Related TD | TD-2026-000128 |
| Document Type | Tax Declaration |
| Reference Number | TD-2026-000128 |
| Issued Date | 2026-03-20 |
| Physical Status | On File |
| Storage Location | Records Room A |
| Shelf Number | Shelf 01 |
| Box Number | Box 2026-03 |
| Folder Number | Folder 128 |

**Document 2: Deed of Sale**
| Field | Value |
|-------|-------|
| Related TD | TD-2026-000128 |
| Document Type | Deed of Sale |
| Reference Number | DOS-2026-0331 |
| Issued Date | 2026-03-15 |
| Physical Status | On File |
| Storage Location | Records Room A |
| Box Number | Box 2026-03 |
| Folder Number | Folder 128 |

3. Click **"Save Document"** for each

✅ Physical file locations are now tracked in the system.

---

### Step 4: Scan Documents (Optional — can do later)

1. Open the property record
2. Go to **"All Files"** tab
3. Click the **eye icon** to view or the **scanner icon** on documents marked "For Scanning"
4. Upload the scanned PDF/image
5. Optionally paste OCR text for searchability

✅ Document is now digitized and viewable in the system.

---

## ENCODING TIPS

### Speed Tips
- **Encode the ACTIVE TD first** — that's the most important one
- **Skip previous TDs initially** — add history later when you have time
- **Don't scan yet** — just register the physical location first, scan in batches later
- **Use consistent naming** — TD-YYYY-NNNNNN format for TD numbers

### Common Scenarios

**Property with only 1 TD (never transferred):**
- Just create the property with that TD as "Active" and transaction type "Original"

**Property with General Revision history:**
- Enter the latest TD as Active
- Add older TDs with status "Superseded" and transaction type "General Revision"

**Property that was subdivided:**
- Create each subdivided lot as a separate property
- In remarks, note "Subdivided from Lot XXX"
- Set transaction type to "Subdivision"

**Property with building + land:**
- Create one property (the land)
- In the TD's assessment details, add TWO assessment lines:
  - Line 1: Type = "Land", Area = 480 sqm, values...
  - Line 2: Type = "Building", Area = 120 sqm, values...

---

## DAILY ENCODING TARGET

| Staff Level | Target | Time per Property |
|-------------|--------|-------------------|
| New encoder | 50-60 properties/day | ~8 minutes each |
| Experienced | 80-100 properties/day | ~5 minutes each |
| With helper reading files | 120+ properties/day | ~3-4 minutes each |

**Tip:** Work in pairs — one person reads the physical file, the other types. This doubles speed.

---

## ENCODING CHECKLIST PER PROPERTY

- [ ] PIN entered correctly
- [ ] Lot number matches physical file
- [ ] Owner name spelled correctly
- [ ] TD number is unique (no duplicates)
- [ ] Market value and assessed value entered
- [ ] Assessment level is correct (20% residential, 40% agricultural, 50% commercial)
- [ ] Municipality and barangay are correct
- [ ] Physical file location recorded (room, shelf, box, folder)

---

## AFTER ENCODING

Once all properties are entered:
1. **Provincial Assessor reviews** — spot-checks random entries
2. **Approve TDs** — change status from Draft to Active
3. **Generate reports** — verify totals match physical records
4. **Begin scanning** — digitize physical files in batches

---

## SAMPLE DATA FOR TESTING

Use these to test the system before real encoding:

| PIN | Lot | Owner | TD | Municipality | MV | AV |
|-----|-----|-------|----|--------------|----|-----|
| 037-01-001-001-000 | Lot 101 | Pedro Reyes | TD-2024-000001 | Bayombong | 250,000 | 50,000 |
| 037-01-001-002-000 | Lot 102 | Ana Garcia | TD-2024-000002 | Bayombong | 180,000 | 36,000 |
| 037-01-002-001-000 | Lot 201-A | Roberto Cruz | TD-2025-000010 | Solano | 1,200,000 | 600,000 |
| 037-01-003-005-000 | Lot 305 | Maria Lim | TD-2023-000055 | Bambang | 95,000 | 38,000 |
| 037-01-004-012-000 | Lot 1247-B | Juan Dela Cruz | TD-2026-000128 | Bayombong | 690,000 | 138,000 |

---

*This guide is for the Provincial Assessor Records System initial data encoding phase.*

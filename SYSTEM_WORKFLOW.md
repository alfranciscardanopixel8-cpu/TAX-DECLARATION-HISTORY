# Provincial Assessor System — Real-World Workflow

## Overview

This system digitizes the workflow of the Provincial Assessor's Office for managing real property tax declarations, assessments, and physical records. Below is the complete flow from property entry to tax declaration lifecycle.

---

## ROLES

| Role | Responsibilities |
|------|-----------------|
| **Encoder / Data Entry** | Enters new properties, TDs, scans documents |
| **Municipal Assessor** | Reviews and prepares TDs for approval |
| **Provincial Assessor** | Approves TDs, signs off on assessments |
| **Records Custodian** | Manages physical files, tracks movements |
| **Admin** | System configuration, user management |

---

## FLOW 1: New Property Registration (Original TD)

**Scenario:** A landowner declares a newly titled property for the first time.

```
Step 1: Owner submits documents to Municipal Assessor
        └── Deed of Sale / Title (OCT/TCT)
        └── Survey Plan
        └── Tax Clearance
        └── ID / Authorization

Step 2: Encoder creates NEW PROPERTY in the system
        └── Enters: PIN, Lot No., Survey No., Title No.
        └── Enters: Barangay, Municipality, Classification
        └── Enters: Land Area, Unit of Measure
        └── Transaction Type: "Original"

Step 3: Encoder creates INITIAL TAX DECLARATION
        └── TD Number (assigned by office)
        └── ARP Number
        └── Owner Name & Address
        └── Classification & Actual Use
        └── Market Value, Assessment Level, Assessed Value
        └── Status: "Draft"

Step 4: Encoder uploads SUPPORTING DOCUMENTS
        └── Scans Deed of Sale → attaches to TD
        └── Scans Title → attaches to TD
        └── Scans Survey Plan → attaches to TD
        └── Records physical file location (Cabinet/Box/Folder)

Step 5: Municipal Assessor REVIEWS
        └── Checks field data vs. documents
        └── Verifies computation (Market Value × Level = Assessed Value)
        └── Changes status to "For Review"

Step 6: Provincial Assessor APPROVES
        └── Reviews TD and supporting documents
        └── Clicks "Approve TD" → Status becomes "Active"
        └── System records approval date
        └── Previous TD (if any) becomes "Superseded"

Step 7: Records Custodian FILES physical records
        └── Assigns storage location
        └── Records box number, folder number
        └── Physical status: "On File"
```

---

## FLOW 2: Transfer of Ownership

**Scenario:** Property is sold. New owner needs a new TD.

```
Step 1: New owner submits transfer documents
        └── Deed of Absolute Sale
        └── Transfer Tax Receipt
        └── Capital Gains Tax Receipt
        └── Updated Title (TCT)
        └── Tax Clearance from Treasurer

Step 2: Encoder SEARCHES existing property
        └── Searches by PIN, Lot No., or previous owner
        └── Opens the property record

Step 3: Encoder adds NEW TAX DECLARATION
        └── Transaction Type: "Transfer"
        └── New Owner Name & Address
        └── Same property details (classification, area)
        └── New TD Number assigned
        └── Market Value may be updated
        └── Status: "Draft"

Step 4: Encoder uploads transfer documents
        └── Scans Deed of Sale
        └── Scans new Title
        └── Scans tax receipts

Step 5: Review & Approval (same as Flow 1, Steps 5-7)
        └── Upon approval:
            └── New TD → "Active"
            └── Old TD → "Superseded"
            └── Owner history is preserved
```

---

## FLOW 3: General Revision

**Scenario:** Province-wide revaluation every 3 years per RA 7160.

```
Step 1: Provincial Assessor issues General Revision order
        └── New Schedule of Market Values approved by Sanggunian

Step 2: Encoder processes properties BY MUNICIPALITY
        └── Searches all Active TDs in a municipality
        └── For each property:

Step 3: Add new TD per property
        └── Transaction Type: "General Revision"
        └── Same owner (carried over)
        └── Updated Market Value (per new schedule)
        └── Recalculated: Market Value × Assessment Level = Assessed Value
        └── New TD Number
        └── Status: "Draft"

Step 4: Batch review and approval
        └── Municipal Assessor reviews batch
        └── Provincial Assessor approves batch
        └── All old TDs → "Superseded"
        └── All new TDs → "Active"

Note: This is the most labor-intensive process.
      Future enhancement: Bulk revision tool.
```

---

## FLOW 4: Subdivision

**Scenario:** A large lot is subdivided into smaller lots.

```
Step 1: Owner submits subdivision plan
        └── Approved Subdivision Plan
        └── New individual titles per lot
        └── Technical descriptions

Step 2: Encoder opens PARENT PROPERTY
        └── Cancels the parent TD
        └── Transaction Type on parent: marks as subdivided

Step 3: Encoder creates NEW PROPERTIES for each subdivided lot
        └── Each lot gets its own:
            └── New PIN
            └── New Lot Number
            └── New TD Number
            └── Transaction Type: "Subdivision"
            └── Proportional area and values
            └── References parent property in remarks

Step 4: Upload subdivision plan to each new property

Step 5: Review & Approval
        └── Parent TD → "Cancelled"
        └── Child TDs → "Active" upon approval
```

---

## FLOW 5: Reclassification

**Scenario:** Agricultural land converted to Commercial use.

```
Step 1: Owner submits conversion documents
        └── DAR Conversion Order (for agricultural)
        └── Zoning Certificate
        └── Municipal/Provincial Resolution

Step 2: Encoder opens existing property
        └── Adds new TD
        └── Transaction Type: "Reclassification"
        └── Updates Classification (Agricultural → Commercial)
        └── Updates Actual Use
        └── Recalculates values per new classification schedule
        └── New Market Value (commercial rates are higher)

Step 3: Upload conversion documents

Step 4: Review & Approval
        └── Old TD (Agricultural) → "Superseded"
        └── New TD (Commercial) → "Active"
```

---

## FLOW 6: Physical Record Management

**Scenario:** Someone requests to borrow a physical file.

```
Step 1: Requestor asks Records Custodian for a file

Step 2: Custodian searches property in system
        └── Locates physical file location
        └── Cabinet 3, Box 12, Folder 45

Step 3: Custodian records PHYSICAL MOVEMENT
        └── Status: "On File" → "Checked Out"
        └── Records: Who borrowed, date, purpose
        └── System logs the movement

Step 4: When file is returned
        └── Custodian records return
        └── Status: "Checked Out" → "On File"
        └── Movement history preserved

Step 5: If file needs digitization
        └── Custodian scans the document
        └── Uploads digital copy
        └── Links to correct TD
        └── Physical status: "Digitized"
```

---

## FLOW 7: Correction / Amendment

**Scenario:** Error found in existing TD (wrong area, misspelled name).

```
Step 1: Error identified (by owner complaint or internal audit)

Step 2: Encoder opens property record
        └── Adds new TD
        └── Transaction Type: "Correction"
        └── Corrects the erroneous field(s)
        └── Notes the correction in Memoranda

Step 3: Upload supporting documents
        └── Request letter
        └── Proof of correct information

Step 4: Review & Approval
        └── Erroneous TD → "Cancelled" (not superseded)
        └── Corrected TD → "Active"
        └── Audit trail shows what was corrected
```

---

## SYSTEM STATUS LIFECYCLE

```
                    ┌─────────────┐
                    │   DRAFT     │  ← Created by Encoder
                    └──────┬──────┘
                           │
                           ▼
                    ┌─────────────┐
                    │ FOR REVIEW  │  ← Submitted for review
                    └──────┬──────┘
                           │
                    ┌──────┴──────┐
                    │             │
                    ▼             ▼
             ┌───────────┐  ┌───────────┐
             │  ACTIVE   │  │ CANCELLED │  ← Rejected/Error
             └─────┬─────┘  └───────────┘
                   │
                   │ (when new TD replaces it)
                   ▼
             ┌───────────┐
             │SUPERSEDED │  ← Replaced by newer TD
             └───────────┘
```

---

## DAILY OPERATIONS SUMMARY

| Time | Activity | Who |
|------|----------|-----|
| 8:00 AM | Check pending approvals on dashboard | Provincial Assessor |
| 8:30 AM | Process walk-in requests (transfers, copies) | Encoder |
| 9:00 AM | Encode new properties from field inspections | Encoder |
| 10:00 AM | Scan and upload documents from yesterday | Records Custodian |
| 11:00 AM | Review drafted TDs from encoders | Municipal Assessor |
| 1:00 PM | Approve reviewed TDs | Provincial Assessor |
| 2:00 PM | Process physical file requests | Records Custodian |
| 3:00 PM | Continue encoding / data entry | Encoder |
| 4:00 PM | Generate daily reports | Admin / Assessor |

---

## KEY BUSINESS RULES

1. **Only ONE Active TD per property at a time** — when a new TD is approved, the old one becomes Superseded.
2. **TD Numbers must be unique** — no duplicates allowed system-wide.
3. **Assessed Value = Market Value × Assessment Level** — this formula must always hold.
4. **Physical files must be tracked** — every movement in/out is logged.
5. **All changes are audited** — who did what, when, with full history.
6. **Only Provincial Assessor can approve** — encoders create, assessors approve.
7. **Cancelled TDs cannot be reactivated** — create a new one instead.
8. **General Revision affects ALL active TDs** — province-wide, every 3 years.

---

## ASSESSMENT LEVEL SCHEDULE (Sample)

| Classification | Assessment Level |
|---------------|-----------------|
| Residential | 20% |
| Agricultural | 40% |
| Commercial | 50% |
| Industrial | 50% |
| Special | 15% |

*Actual rates are set by the Provincial Sanggunian via ordinance.*

---

## DOCUMENT TYPES TRACKED

| Document | Purpose |
|----------|---------|
| Tax Declaration | The TD itself (printed copy) |
| Deed of Sale | Proof of transfer |
| Transfer Certificate of Title (TCT) | Land title |
| Original Certificate of Title (OCT) | First-time title |
| Survey Plan | Lot boundaries |
| FAAS | Field Appraisal and Assessment Sheet |
| Certification | Various certifications |
| Tax Clearance | No unpaid taxes |
| Subdivision Plan | For subdivided lots |
| DAR Conversion Order | For reclassification |

---

*This document serves as the operational guide for the Provincial Assessor Records System.*

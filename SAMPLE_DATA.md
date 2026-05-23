# Sample Data for Encoding Practice

Use this data to practice encoding properties, TDs, and assessments in the system.

---

## SAMPLE 1: Simple Residential Land (Original Declaration)

**Scenario:** Maria Cruz inherited a small residential lot. First-time declaration.

### Property
| Field | Value |
|-------|-------|
| Property Type | Land |
| PIN | 037-01-001-005-001 |
| Lot Number | Lot 5, Blk 1 |
| Survey Number | PSD-04-001234 |
| Title Number | TCT-202401 |
| Barangay | Don Mariano Marcos |
| Municipality | Bayombong |
| Classification | Residential |
| Sub-Classification | Residential — Subdivision |
| Actual Use | Residential Lot |
| Land Area | 240 |
| Unit | sqm |
| Boundaries — North | Lot 4, Blk 1 |
| Boundaries — East | Maharlika Highway |
| Boundaries — South | Lot 6, Blk 1 |
| Boundaries — West | Brgy. Road |

### Owner
- **Name:** Maria Santos Cruz
- **Address:** 123 Don Mariano Marcos St., Bayombong, Nueva Vizcaya

### Initial TD
| Field | Value |
|-------|-------|
| TD Number | TD-2024-005001 |
| ARP Number | ARP-2024-005001 |
| Effectivity Year | 2024 |
| Transaction Type | Original |
| Status | Active |
| Market Value | 480,000 |
| Assessment Level | 20 |
| Assessed Value | 96,000 |

---

## SAMPLE 2: Agricultural Land with Multiple TDs (Transfer History)

**Scenario:** Ricefield originally owned by Juan Reyes, sold to Pedro Garcia in 2020, then to current owner Ana Lim in 2025.

### Property
| Field | Value |
|-------|-------|
| Property Type | Land |
| PIN | 037-02-003-012-008 |
| Lot Number | Lot 3045 |
| Survey Number | CSD-04-005678 |
| Title Number | OCT-P-12345 |
| Barangay | Salinas |
| Municipality | Bambang |
| Classification | Agricultural |
| Sub-Classification | Agricultural — Riceland |
| Actual Use | Riceland (Irrigated) |
| Land Area | 12,500 |
| Unit | sqm |
| Boundaries — North | Sandoval Creek |
| Boundaries — East | Lot 3046 (Garcia) |
| Boundaries — South | Provincial Road |
| Boundaries — West | Lot 3044 (Tan) |

### TD History (encode in any order, system sorts)

**TD #1 (Latest — Active):**
| Field | Value |
|-------|-------|
| TD Number | TD-2025-003012 |
| ARP Number | ARP-2025-003012 |
| Effectivity Year | 2025 |
| Transaction Type | Transfer |
| Status | Active |
| Owner | Ana Reyes Lim |
| Owner Address | Brgy. Salinas, Bambang, Nueva Vizcaya |
| Market Value | 1,250,000 |
| Assessment Level | 40 |
| Assessed Value | 500,000 |
| Memoranda | Transferred via Deed of Absolute Sale dated 2025-03-15. |

**TD #2 (Superseded):**
| Field | Value |
|-------|-------|
| TD Number | TD-2020-003012 |
| ARP Number | ARP-2020-003012 |
| Effectivity Year | 2020 |
| Transaction Type | Transfer |
| Status | Superseded |
| Owner | Pedro Garcia |
| Owner Address | Brgy. Buag, Bambang, Nueva Vizcaya |
| Market Value | 875,000 |
| Assessment Level | 40 |
| Assessed Value | 350,000 |
| Memoranda | Superseded by TD-2025-003012 due to sale to Ana Lim. |

**TD #3 (Original, Superseded):**
| Field | Value |
|-------|-------|
| TD Number | TD-2010-003012 |
| ARP Number | ARP-2010-003012 |
| Effectivity Year | 2010 |
| Transaction Type | Original |
| Status | Superseded |
| Owner | Juan Reyes |
| Owner Address | Brgy. Salinas, Bambang, Nueva Vizcaya |
| Market Value | 625,000 |
| Assessment Level | 40 |
| Assessed Value | 250,000 |
| Memoranda | Original declaration. Superseded after sale to Pedro Garcia in 2020. |

---

## SAMPLE 3: Commercial Building

**Scenario:** Two-storey commercial building owned by Velasco Trading Inc.

### Property
| Field | Value |
|-------|-------|
| Property Type | Building |
| PIN | 037-01-001-100-005 |
| Land PIN (where it sits) | 037-01-001-100-001 |
| Barangay | Poblacion |
| Municipality | Solano |
| Classification | Commercial |
| Actual Use | Commercial Building (Retail) |

### Building Details
| Field | Value |
|-------|-------|
| Kind of Building | Commercial |
| Structural Type | Concrete |
| Condition | Good |
| Building Permit No. | BP-2018-00345 |
| CCT Number | (blank) |
| Building Age | 6 |
| Date Constructed | 2018-04-15 |
| Date Completed | 2018-09-10 |
| Date Occupied | 2018-10-01 |
| No. of Storeys | 2 |
| Total Floor Area | 360 |
| Area 1st Floor | 200 |
| Area 2nd Floor | 160 |
| Roofing | Concrete Slab |
| Flooring | Tiles |
| Wall Partition | Concrete Hollow Block |

### Owner
- **Name:** Velasco Trading Inc.
- **Address:** Maharlika Highway, Solano, Nueva Vizcaya

### Initial TD
| Field | Value |
|-------|-------|
| TD Number | TD-2018-100B05 |
| ARP Number | ARP-2018-100B05 |
| Effectivity Year | 2018 |
| Transaction Type | New Construction |
| Status | Active |
| Market Value | 4,500,000 |
| Assessment Level | 35 |
| Assessed Value | 1,575,000 |
| Memoranda | New 2-storey commercial building, completed and occupied 2018. |

---

## SAMPLE 4: Industrial Machinery

**Scenario:** Rice mill machinery installed at Bambang Rice Mill.

### Property
| Field | Value |
|-------|-------|
| Property Type | Machinery |
| PIN | 037-02-005-201-M01 |
| Land PIN (where it sits) | 037-02-005-201-001 |
| Barangay | Buag |
| Municipality | Bambang |
| Classification | Industrial |
| Actual Use | Rice Milling Equipment |

### Machinery Details
| Field | Value |
|-------|-------|
| Kind of Machinery | Rice Mill |
| Brand | Yanmar |
| Model | YM-3000 |
| Capacity | 3,000 kg/hr |
| Date Acquired | 2022-05-20 |
| Economic Life | 15 |
| Acquisition Cost | 1,800,000 |
| Replacement Cost | 2,200,000 |
| Years Used | 3 |
| Remaining Life | 12 |
| Depreciation Percent | 15 |

### Owner
- **Name:** Bambang Rice Mill Corporation
- **Address:** Maharlika Highway, Brgy. Buag, Bambang

### Initial TD
| Field | Value |
|-------|-------|
| TD Number | TD-2022-201M01 |
| ARP Number | ARP-2022-201M01 |
| Effectivity Year | 2022 |
| Transaction Type | New Installation |
| Status | Active |
| Market Value | 1,870,000 |
| Assessment Level | 80 |
| Assessed Value | 1,496,000 |
| Memoranda | Newly installed rice milling machinery. |

---

## SAMPLE 5: Subdivided Property

**Scenario:** A 2,000 sqm parent lot was subdivided into 4 smaller lots in 2024. Encode the parent and one of the child lots.

### Parent Property (Cancelled)
| Field | Value |
|-------|-------|
| Property Type | Land |
| PIN | 037-01-002-050-000 |
| Lot Number | Lot 50 (Parent) |
| Survey Number | PSU-04-002345 |
| Title Number | TCT-150000 |
| Barangay | San Antonio |
| Municipality | Bayombong |
| Classification | Residential |
| Land Area | 2,000 |
| Owner | Roberto Aquino |
| Initial TD | TD-2010-002050 (Status: Cancelled, Transaction: Original) |
| Market Value | 800,000 |
| Memoranda | Subdivided into Lots 50-A, 50-B, 50-C, 50-D in 2024. See subdivision plan PSD-04-2024-789. |

### Child Property (One of 4)
| Field | Value |
|-------|-------|
| Property Type | Land |
| PIN | 037-01-002-050-A01 |
| Lot Number | Lot 50-A |
| Survey Number | PSD-04-2024-789 |
| Title Number | TCT-2024-501A |
| Barangay | San Antonio |
| Municipality | Bayombong |
| Classification | Residential |
| Sub-Classification | Residential — Subdivision |
| Actual Use | Residential Lot |
| Land Area | 500 |
| Unit | sqm |

### Initial TD for Lot 50-A
| Field | Value |
|-------|-------|
| TD Number | TD-2024-050A01 |
| ARP Number | ARP-2024-050A01 |
| Effectivity Year | 2024 |
| Transaction Type | Subdivision |
| Status | Active |
| Owner | Roberto Aquino |
| Market Value | 250,000 |
| Assessment Level | 20 |
| Assessed Value | 50,000 |
| Memoranda | Subdivided from Lot 50 (TD-2010-002050). 1 of 4 resulting lots. |

---

## SAMPLE 6: Reclassified Land (Agricultural → Commercial)

**Scenario:** Riceland reclassified to commercial after Sangguniang Bayan approval.

### Property (already exists)
| Field | Value |
|-------|-------|
| PIN | 037-01-001-080-003 |
| Lot Number | Lot 80-C |
| Barangay | Quezon |
| Municipality | Bayombong |
| Land Area | 1,500 sqm |

### Old TD (Superseded)
| Field | Value |
|-------|-------|
| TD Number | TD-2018-080C03 |
| Year | 2018 |
| Classification | Agricultural |
| Sub-Classification | Agricultural — Riceland |
| Market Value | 525,000 |
| Assessment Level | 40 |
| Assessed Value | 210,000 |
| Status | Superseded |

### New TD (Active)
| Field | Value |
|-------|-------|
| TD Number | TD-2025-080C03R |
| Year | 2025 |
| Transaction Type | Reclassification |
| Classification | Commercial |
| Sub-Classification | Commercial — Secondary |
| Market Value | 4,500,000 |
| Assessment Level | 50 |
| Assessed Value | 2,250,000 |
| Status | Active |
| Memoranda | Reclassified from Agricultural to Commercial per SB Resolution No. 2025-018 dated Jan 15, 2025. |

---

## SAMPLE OWNERS (Common Filipino Names)

For practice, here are 10 sample owners you can use:

| Name | Address |
|------|---------|
| Juan Dela Cruz | Brgy. Don Mariano Marcos, Bayombong |
| Maria Santos | Brgy. Magsaysay, Solano |
| Pedro Garcia | Brgy. Salinas, Bambang |
| Ana Reyes | Brgy. Buag, Bambang |
| Roberto Aquino | Brgy. San Antonio, Bayombong |
| Carmela Lim | Brgy. Quezon, Bayombong |
| Eduardo Tan | Brgy. La Torre, Bayombong |
| Sofia Mendoza | Brgy. Bonfal, Bayombong |
| Antonio Villanueva | Brgy. Aurora, Bayombong |
| Velasco Trading Inc. | Maharlika Hwy, Solano |

---

## SAMPLE DOCUMENTS TO UPLOAD

For each property, you can attach these document types:

1. **Tax Declaration** — TD-XXXX-XXXXXX.pdf
2. **Deed of Sale** — DOS-YYYY-NNNN.pdf
3. **Title (TCT/OCT)** — TCT-NNNNNN.pdf
4. **Survey Plan** — PSD-NN-NNNNNN.pdf
5. **FAAS** — FAAS-YYYY-NNNNNN.pdf
6. **Building Permit** — BP-YYYY-NNNNN.pdf (for buildings)
7. **DAR Conversion Order** — DAR-YYYY-NNNN.pdf (for reclassifications)
8. **Subdivision Plan** — PSD-NN-YYYY-NNN.pdf (for subdivisions)

---

## SAMPLE MUNICIPALITIES (Nueva Vizcaya)

If your dropdown is empty, here are valid options:
- Bayombong
- Solano
- Bambang
- Bagabag
- Aritao
- Dupax del Norte
- Dupax del Sur
- Kasibu
- Kayapa
- Quezon
- Santa Fe
- Villaverde

---

## ENCODING ORDER (Recommended)

1. Start with **Sample 1** (simple residential land) — easiest, single TD
2. Move to **Sample 2** (with TD history) — practice multiple TDs, ownership chain
3. Try **Sample 3** (Building) — practice building-specific fields
4. Try **Sample 4** (Machinery) — practice machinery valuation
5. Try **Sample 5** (Subdivision) — practice parent-child relationship
6. Finish with **Sample 6** (Reclassification) — practice classification changes

After completing all 6, you'll have practiced every scenario the system supports.

---

## ASSESSMENT LEVELS REFERENCE

Use these standard rates per BLGF:

| Classification | Land | Building |
|----------------|------|----------|
| Residential | 20% | 0-20% (varies by FMV bracket) |
| Agricultural | 40% | 25% |
| Commercial | 50% | 30-35% |
| Industrial | 50% | 30-35% |
| Mineral | 50% | 50% |
| Special | 15% | 15% |
| Timberland | 20% | — |

Machinery: 80% (industrial), 50% (other)

---

*This sample data is fictional and for testing purposes only. Use it to practice encoding, then start with real records when ready for production use.*

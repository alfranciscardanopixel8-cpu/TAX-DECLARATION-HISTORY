export const mockProperties = [
  {
    id: 1,
    pin: '037-01-004-012-000',
    property_index_number: 'PIN-037-01-004-012-000',
    lot_number: 'Lot 1247-B',
    survey_number: 'PSD-03-001248',
    title_number: 'TCT-102938',
    barangay: 'San Isidro',
    municipality: 'Sample Municipality',
    province: 'Sample Province',
    classification: 'Residential',
    actual_use: 'Residential Lot',
    land_area: 480,
    unit_of_measure: 'sqm',
    status: 'Active',
    remarks: 'Transfer completed and current TD approved.',
    search_match: { matched_on: 'lot_number', matched_value: 'Lot 1247-B' },
    assessment_records: [
      { id: 1, tax_declaration_id: 2, assessment_type: 'Land', assessed_value: 138000, area: 480, unit_of_measure: 'sqm' },
      { id: 2, tax_declaration_id: 1, assessment_type: 'Land', assessed_value: 84000, area: 480, unit_of_measure: 'sqm' }
    ],
    tax_declarations: [
      {
        id: 2,
        td_number: 'TD-2026-000128',
        arp_number: 'ARP-2026-0128',
        effectivity_year: 2026,
        classification: 'Residential',
        actual_use: 'Residential Lot',
        market_value: 690000,
        assessed_value: 138000,
        assessment_level: 20,
        status: 'Active',
        transaction_type: 'Transfer',
        memoranda: 'Current declaration after ownership transfer.',
        owner: { name: 'Juan Dela Cruz', address: 'Brgy. San Isidro, Sample Municipality' }
      },
      {
        id: 1,
        td_number: 'TD-2018-000441',
        arp_number: 'ARP-2018-0441',
        effectivity_year: 2018,
        classification: 'Residential',
        actual_use: 'Residential Lot',
        market_value: 420000,
        assessed_value: 84000,
        assessment_level: 20,
        status: 'Superseded',
        transaction_type: 'Transfer',
        memoranda: 'Previous declaration under Maria Santos.',
        owner: { name: 'Maria Santos', address: 'Capital City' }
      }
    ],
    documents: [
      {
        id: 1,
        tax_declaration_id: 2,
        document_type: 'Tax Declaration',
        reference_number: 'TD-2026-000128',
        file_name: 'TD-2026-000128.pdf',
        file_path: 'assessor-documents/1/td-2026.pdf',
        issued_at: '2026-03-15',
        physical_copy_status: 'On File',
        storage_location: 'Records Room A',
        shelf_number: 'Shelf 01',
        box_number: 'Box 2026-03',
        folder_number: 'Folder 128',
        custodian: 'Assessment Records Section',
        received_at: '2026-03-15'
      },
      {
        id: 2,
        tax_declaration_id: 2,
        document_type: 'Deed of Sale',
        reference_number: 'DOS-2026-0331',
        file_name: 'deed-of-sale-2026-0331.pdf',
        file_path: 'assessor-documents/1/deed.pdf',
        issued_at: '2026-02-02',
        physical_copy_status: 'On File',
        storage_location: 'Records Room A',
        shelf_number: 'Shelf 01',
        box_number: 'Box 2026-03',
        folder_number: 'Folder 128',
        custodian: 'Assessment Records Section',
        received_at: '2026-02-02'
      },
      {
        id: 4,
        tax_declaration_id: 1,
        document_type: 'Tax Declaration',
        reference_number: 'TD-2018-000441',
        file_name: 'TD-2018-000441-physical.pdf',
        file_path: 'pending-upload/2018-td.pdf',
        issued_at: '2018-05-20',
        physical_copy_status: 'For Scanning',
        needs_digitization: true,
        storage_location: 'Records Room A',
        shelf_number: 'Shelf 02',
        box_number: 'Box 2018-05',
        folder_number: 'Folder 441',
        custodian: 'Assessment Records Section',
        received_at: '2018-05-20'
      },
      {
        id: 3,
        tax_declaration_id: 1,
        document_type: 'Survey Plan',
        reference_number: 'PSD-03-001248',
        file_name: 'survey-plan-1247-b.pdf',
        file_path: 'assessor-documents/1/survey.pdf',
        issued_at: '2018-05-20',
        physical_copy_status: 'Archived',
        storage_location: 'Archive Room B',
        shelf_number: 'Shelf 08',
        box_number: 'Box 2018-05',
        folder_number: 'Folder 441',
        custodian: 'Assessment Records Section',
        received_at: '2018-05-20'
      }
    ],
    activity_logs: [
      { id: 1, action: 'created', description: 'Initial property master record encoded.', created_at: '2026-03-14', user: { name: 'System Admin' } },
      { id: 2, action: 'approved', description: 'TD-2026-000128 approved as active declaration.', created_at: '2026-03-15', tax_declaration_id: 2, tax_declaration: { td_number: 'TD-2026-000128' }, user: { name: 'Provincial Assessor' } },
      { id: 3, action: 'document_added', description: 'TD-2018-000441 physical copy indexed — awaiting scan.', created_at: '2026-03-16', tax_declaration_id: 1, tax_declaration: { td_number: 'TD-2018-000441' }, user: { name: 'Records Staff' } }
    ]
  },
  {
    id: 2,
    pin: '037-02-011-008-000',
    property_index_number: 'PIN-037-02-011-008-000',
    lot_number: 'Lot 88-A',
    survey_number: 'CAD-461-D',
    title_number: 'OCT-5401',
    barangay: 'Poblacion',
    municipality: 'Capital City',
    province: 'Sample Province',
    classification: 'Commercial',
    actual_use: 'Commercial Building',
    land_area: 210,
    unit_of_measure: 'sqm',
    status: 'For Review',
    remarks: 'Awaiting review of revised assessment.',
    tax_declarations: [
      {
        id: 3,
        td_number: 'TD-2025-000877',
        arp_number: 'ARP-2025-0877',
        effectivity_year: 2025,
        classification: 'Commercial',
        actual_use: 'Commercial Building',
        market_value: 2400000,
        assessed_value: 960000,
        assessment_level: 40,
        status: 'For Review',
        transaction_type: 'Revision',
        memoranda: 'Revision due to building improvement.',
        owner: { name: 'Santos Trading Corporation', address: 'Poblacion, Capital City' }
      }
    ],
    documents: [
      {
        id: 4,
        document_type: 'FAAS',
        reference_number: 'FAAS-2025-0877',
        file_name: 'faas-2025-0877.pdf',
        issued_at: '2025-11-08',
        physical_copy_status: 'For Scanning',
        storage_location: 'Encoding Desk',
        shelf_number: 'Intake Tray',
        box_number: 'Box 2025-11',
        folder_number: 'Folder 877',
        custodian: 'Assessment Records Section',
        received_at: '2025-11-08'
      }
    ],
    activity_logs: [
      { id: 3, action: 'review', description: 'Revision submitted for assessor review.', created_at: '2025-11-09' }
    ]
  }
];

export const referenceOptions = {
  municipalities: ['Sample Municipality', 'Capital City'],
  classifications: ['Residential', 'Agricultural', 'Commercial', 'Industrial', 'Special'],
  statuses: ['Active', 'Draft', 'For Review', 'Cancelled', 'Superseded', 'Archived'],
  transactionTypes: ['Original', 'Transfer', 'General Revision', 'Revision', 'Subdivision', 'Consolidation', 'Reclassification', 'Correction', 'Physical Change', 'Dispute/Conflict', 'Reassessment'],
  documentTypes: ['Tax Declaration', 'Deed of Sale', 'Transfer Certificate of Title', 'Survey Plan', 'FAAS', 'Certification', 'Owner Request'],
  assessmentTypes: ['Land', 'Building', 'Machinery', 'Improvement', 'Special'],
  physicalStatuses: ['On File', 'For Scanning', 'Released', 'Returned', 'Missing', 'Archived']
};

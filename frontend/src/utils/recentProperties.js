const STORAGE_KEY = 'assessor_recent_properties';
const MAX_ITEMS = 8;

export function loadRecentProperties() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch {
    return [];
  }
}

export function rememberProperty(property) {
  if (!property?.id) return loadRecentProperties();

  const entry = {
    id: property.id,
    pin: property.pin,
    lot_number: property.lot_number,
    barangay: property.barangay,
    municipality: property.municipality,
    opened_at: new Date().toISOString()
  };

  const next = [entry, ...loadRecentProperties().filter((item) => item.id !== property.id)].slice(0, MAX_ITEMS);
  localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
  return next;
}

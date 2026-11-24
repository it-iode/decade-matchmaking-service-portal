// Ocean Decade Conference (ODC) travel support feature (issue #218)
// Exports: home page section, list, form config and hook.
// The upload form lives on its own page: pages/opportunity/OdcTravelSupportCreate.tsx

export { default as ODCTravelSupportSection } from './components/odc-travel-support-section';
export { default as ODCTravelSupportList } from './components/odc-travel-support-list';
export { useODCTravelSupportForm } from './hooks/use-odc-travel-support-form';
export type { ODCTravelSupportFormData } from './hooks/use-odc-travel-support-form';
export {
    ODC_TRAVEL_SUPPORT_TYPE,
    ODC_TRAVEL_SUPPORT_EXCLUDED_FIELDS,
    odcTravelSupportFormFields,
} from './config/odc-travel-support-form-fields';

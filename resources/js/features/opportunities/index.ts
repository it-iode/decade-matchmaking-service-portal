// Partner opportunities feature barrel export
// Exports: opportunity pages, opportunity cards, opportunity forms

// Components
export { OpportunityActions, ExtendOpportunityDialog, OpportunitiesDialog } from './components';

// Hooks
export { useOpportunityForm, useOpportunityActions } from './hooks';

// Services
export { OpportunityActionService } from './services';

// Types
export { OpportunityStatus } from './types';
export type {
    Opportunity,
    OpportunityList,
    OpportunityPermissions,
    OpportunityFormOptions
} from './types';
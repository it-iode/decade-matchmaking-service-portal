/**
 * Offer status values that mirror the PHP RequestOfferStatus enum
 */
export enum OfferStatusValue {
  ACTIVE = 1,
  INACTIVE = 2
}

/**
 * Human-readable labels for offer statuses
 */
export const OFFER_STATUS_LABELS = {
  [OfferStatusValue.ACTIVE]: 'Active',
  [OfferStatusValue.INACTIVE]: 'Inactive'
} as const;

/**
 * Badge color type matching the Badge component
 */
type BadgeColor = "teal" | "cyan" | "amber" | "green" | "blue" | "red" | 
                  "orange" | "yellow" | "lime" | "emerald" | "sky" | 
                  "indigo" | "violet" | "purple" | "fuchsia" | "pink" | 
                  "rose" | "zinc";

/**
 * Color mapping for offer status badges
 * Maps status values to Badge component color props
 */
export const OFFER_STATUS_COLORS: Record<string, BadgeColor> = {
  "1": 'green',    // ACTIVE
  "2": 'red'       // INACTIVE
};

/**
 * Default color for unknown/undefined statuses
 */
export const DEFAULT_OFFER_STATUS_COLOR: BadgeColor = 'zinc';
# React Components & JavaScript Organization

This document outlines the recommended folder structure and organization for React components and JavaScript files in the Ocean Decade Portal.

## 📁 Folder Structure

```
resources/js/
├── 📁 components/           # Reusable UI components
│   ├── 📁 ui/              # Basic UI components (buttons, inputs, etc.)
│   ├── 📁 forms/           # Form-specific components
│   ├── 📁 layout/          # Layout components (header, footer, etc.)
│   ├── 📁 dialogs/         # Dialog/Modal components
│   ├── 📁 common/          # Other reusable components
│   └── index.ts            # Barrel exports for all components
│
├── 📁 pages/               # Page components (Inertia.js pages)
│   ├── 📁 auth/           # Authentication pages
│   ├── 📁 dashboard/      # Dashboard pages
│   ├── 📁 request/        # Request-related pages
│   ├── 📁 opportunity/    # Opportunity-related pages
│   └── 📁 admin/          # Admin pages
│
├── 📁 layouts/            # Layout wrappers
│   ├── FrontendLayout.tsx
│   ├── BackendLayout.tsx
│   └── index.ts
│
├── 📁 hooks/              # Custom React hooks
│   ├── useForm.ts
│   ├── useApi.ts
│   └── index.ts
│
├── 📁 services/           # API and business logic services
│   ├── api/              # API client and endpoints
│   ├── storage/          # Local storage, session storage
│   └── utils/            # Utility services
│
├── 📁 forms/             # Form configurations and schemas
│   ├── schemas/          # Form validation schemas
│   ├── UIRequestForm.tsx
│   ├── UIOpportunityForm.tsx
│   └── index.ts
│
├── 📁 types/             # TypeScript type definitions
│   ├── api.ts           # API response types
│   ├── forms.ts         # Form data types
│   ├── components.ts    # Component prop types
│   └── global.d.ts      # Global type declarations
│
├── 📁 utils/             # Utility functions
│   ├── helpers.ts       # General helper functions
│   ├── validation.ts    # Validation utilities
│   └── formatting.ts    # Data formatting utilities
│
├── 📁 constants/         # Application constants
│   ├── routes.ts        # Route definitions
│   ├── config.ts        # App configuration
│   └── messages.ts      # Error/success messages
│
├── 📁 data/             # Static data and mock data
│   ├── locations.ts
│   └── countries.ts
│
├── 📁 styles/           # Global styles and themes
│   ├── globals.css
│   └── components.css
│
├── app.tsx              # Main app component
├── bootstrap.ts         # App initialization
└── README.md           # This file
```

## 🎯 Component Organization Principles

### **1. Component Categories**

#### **UI Components** (`components/ui/`)
- Basic, reusable UI elements
- Examples: Button, Input, Select, TagsInput, InputError
- Should be framework-agnostic and highly reusable

#### **Layout Components** (`components/layout/`)
- Components that define page structure
- Examples: Header, Footer, Sidebar, Navigation
- Often contain other components

#### **Dialog Components** (`components/dialogs/`)
- Modal dialogs and overlays
- Examples: XHRAlertDialog, ConfirmDialog, Modal
- Handle user interactions and feedback

#### **Form Components** (`components/forms/`)
- Form-specific components
- Examples: SignInForm, FormField, FormSection
- Handle form logic and validation

#### **Common Components** (`components/common/`)
- Miscellaneous reusable components
- Examples: Logo, Banner, Breadcrumb, UserGuide
- Shared across multiple pages

### **2. Naming Conventions**

#### **Files**
- Use PascalCase for component files: `UserProfile.tsx`
- Use camelCase for utility files: `formatDate.ts`
- Use kebab-case for CSS files: `user-profile.css`

#### **Components**
- Use PascalCase for component names: `UserProfile`
- Use descriptive, semantic names
- Avoid abbreviations unless widely understood

#### **Folders**
- Use lowercase for folder names: `components/`, `pages/`
- Use descriptive names that indicate purpose

### **3. Import/Export Patterns**

#### **Barrel Exports**
Use index.ts files for clean imports:

```typescript
// components/index.ts
export { default as Button } from './ui/Button';
export { default as Header } from './layout/Header';

// Usage
import { Button, Header } from '@/components';
```

#### **Named vs Default Exports**
- Use default exports for components: `export default UserProfile`
- Use named exports for utilities: `export const formatDate`
- Use named exports for types: `export interface User`

## 🔧 Best Practices

### **1. Component Structure**
```typescript
// 1. Imports
import React from 'react';
import { ComponentProps } from '@/types';

// 2. Types/Interfaces
interface ButtonProps {
  variant?: 'primary' | 'secondary';
  children: React.ReactNode;
}

// 3. Component
export default function Button({ variant = 'primary', children }: ButtonProps) {
  return (
    <button className={`btn btn-${variant}`}>
      {children}
    </button>
  );
}
```

### **2. File Organization**
- One component per file
- Related components in the same folder
- Keep files under 300 lines when possible
- Split large components into smaller ones

### **3. Import Order**
```typescript
// 1. React and framework imports
import React from 'react';
import { useForm } from '@inertiajs/react';

// 2. Third-party libraries
import { Dialog } from '@headlessui/react';
import { CheckCircle } from 'lucide-react';

// 3. Internal components
import { Button } from '@/components';
import { useApi } from '@/hooks';

// 4. Types and utilities
import { User } from '@/types';
import { formatDate } from '@/utils';
```

### **4. TypeScript Best Practices**
- Define interfaces for all component props
- Use strict typing for API responses
- Create shared types in `types/` folder
- Use generic types for reusable components

## 📝 Migration Guide

### **From Old Structure to New Structure**

1. **Move Components**
   ```bash
   # Old location
   Components/Dialog/XHRAlertDialog.tsx
   
   # New location
   components/dialogs/Dialog/XHRAlertDialog.tsx
   ```

2. **Update Imports**
   ```typescript
   // Old import
   import XHRAlertDialog from '@/Components/Dialog/XHRAlertDialog';
   
   // New import
   import { XHRAlertDialog } from '@/components';
   ```

3. **Create Barrel Exports**
   - Add index.ts files to each folder
   - Export all components from the folder
   - Use clean import paths

## 🚀 Benefits

### **1. Scalability**
- Easy to add new features
- Clear separation of concerns
- Modular architecture

### **2. Maintainability**
- Related files grouped together
- Easy to find and update components
- Consistent naming conventions

### **3. Developer Experience**
- Intuitive folder structure
- Clean import paths
- Easy onboarding for new developers

### **4. Performance**
- Better tree-shaking
- Lazy loading opportunities
- Optimized bundle splitting

## 🔍 Tools and Utilities

### **VS Code Extensions**
- TypeScript Importer
- Auto Import
- Path Intellisense
- ESLint
- Prettier

### **Linting Rules**
- Enforce consistent import order
- Prevent relative imports beyond 2 levels
- Enforce naming conventions
- Require TypeScript types

This structure provides a solid foundation for a scalable, maintainable React application while following industry best practices and modern development patterns. 
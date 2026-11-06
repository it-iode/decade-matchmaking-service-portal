# Ocean Decade Portal - Technical Documentation

## Project Overview

The **Ocean Decade Portal** is a comprehensive web application designed for the UNESCO Ocean Decade Programme. It serves as a digital platform connecting researchers, organizations, and stakeholders in sustainable ocean science through capacity development requests, partner opportunities, and intelligent matchmaking.

The portal facilitates collaboration by matching organizations seeking capacity development support with partners offering expertise, creating a transparent marketplace for ocean science collaboration and innovation.

## Technical Stack

### Backend Framework
- **Laravel 12** - Modern PHP framework with advanced features
- **PHP 8.2+** - Latest PHP version with performance optimizations
- **MySQL** - Primary database with advanced indexing and relationships
- **Inertia.js** - Full-stack framework providing SPA experience without API complexity

### Frontend Framework
- **React 18** - Modern component-based UI library with concurrent features
- **TypeScript** - Type-safe development with full IDE support
- **Tailwind CSS 4** - Utility-first CSS framework with CSS variables
- **Catalyst UI** - Professional component library built on HeadlessUI
- **HeadlessUI** - Accessible, unstyled UI components

### Development Tools
- **Vite 6** - Fast build tool with HMR and advanced bundling
- **Laravel Sail** - Docker development environment
- **Laravel Pint** - Code style fixer
- **PHPUnit** - Testing framework for backend
- **TypeScript Compiler** - Frontend type checking

### External Integrations
- **Laravel Socialite** - OAuth authentication
- **Spatie Laravel Permission** - Role-based access control
- **DomPDF** - PDF generation for reports
- **Ziggy** - Laravel route generation for frontend

## Architecture Patterns

### Backend Architecture
- **Service Layer Pattern** - Business logic encapsulation
- **Repository Pattern** - Data access abstraction
- **Query Builder Pattern** - Complex query construction
- **Observer Pattern** - Model event handling
- **Dependency Injection** - Loose coupling and testability

### Frontend Architecture
- **Component Composition** - Reusable UI components
- **Context Pattern** - Global state management (dialogs, notifications)
- **Custom Hooks** - Logic reuse and state management
- **Server-Side Routing** - Laravel handles routing, Inertia handles navigation

## Core Entities and Relationships

### Primary Models

#### **User**
- Core authentication and authorization
- **Relationships**: 
  - `hasMany(Request)` - Users can create multiple requests
  - `hasMany(Opportunity)` - Partners can publish opportunities
  - `hasMany(Notification)` - User notifications
  - `hasMany(UserNotificationPreference)` - Notification preferences
- **Roles**: User, Partner, Administrator (via Spatie)

#### **Request (OCD Request)**
- Capacity development requests from organizations
- **Dual Storage**: JSON (legacy) + normalized tables (performance)
- **Relationships**:
  - `belongsTo(User)` - Request creator
  - `belongsTo(User, 'matched_partner_id')` - Matched partner
  - `hasMany(RequestOffer)` - Partner offers
  - `hasOne(RequestDetail)` - Normalized data
  - `belongsTo(RequestStatus)` - Current status

#### **Opportunity**
- Partner-published capacity offerings
- **Relationships**:
  - `belongsTo(User)` - Publishing partner
  - Independent of requests (standalone offerings)

#### **RequestOffer**
- Partner offers to fulfill specific requests
- **Relationships**:
  - `belongsTo(Request)` - Target request
  - `belongsTo(User, 'matched_partner_id')` - Offering partner
  - `morphMany(Document)` - Supporting documents

#### **Notification**
- In-app notification system
- **Relationships**:
  - `belongsTo(User)` - Notification recipient

#### **UserNotificationPreference**
- User notification preferences for request matching
- **Attributes**: `attribute_type`, `attribute_value`, notification settings
- **Supports**: Subthemes, locations, funding ranges, etc.

#### **Document**
- File management with polymorphic relationships
- **Relationships**:
  - `morphTo('parent')` - Can belong to requests, offers, etc.
  - `belongsTo(User, 'uploader_id')` - File uploader

#### **Setting**
- System configuration management
- **Features**: File upload detection, public URL generation

## Project Features

### 🔐 **Authentication & Authorization**
- **OAuth Integration**: External provider authentication
- **Role-Based Access**: User, Partner, Administrator roles
- **Spatie Permissions**: Granular permission system
- **Secure Sessions**: Laravel Sanctum integration

### 📋 **Request Management**
- **Dual Storage System**: JSON + normalized database storage
- **Request Lifecycle**: Draft → Under Review → Validated → Matched
- **Rich Form Builder**: Dynamic form rendering with validation
- **Document Attachments**: File upload and management
- **Status Tracking**: Comprehensive request status system

### 🤝 **Partner Opportunities**
- **Opportunity Publishing**: Partners can publish capacity offerings
- **Opportunity Browse**: Searchable partner opportunities
- **Opportunity Management**: Status updates and lifecycle management

### 💼 **Offer Management**
- **Partner Offers**: Partners can offer services for specific requests
- **Offer Evaluation**: Request creators can evaluate incoming offers
- **Document Support**: Attach supporting documents to offers
- **Status Tracking**: Active, pending, accepted, rejected offers

### 🔔 **Smart Notification System**
- **Attribute-Based Matching**: Users subscribe to specific request attributes
- **Multi-Attribute Support**: Subthemes, locations, funding ranges, etc.
- **Dual Notifications**: In-app + email notifications
- **Bulk Management**: Enable/disable multiple preferences
- **Real-Time Processing**: Notifications sent on request submission

### 🎛️ **Admin Dashboard**
- **System Analytics**: Request statistics and metrics
- **User Management**: Role assignments and permissions
- **Request Oversight**: Admin review and status management
- **System Settings**: Portal configuration and file management

### 📊 **Analytics & Reporting**
- **Request Analytics**: Success rates, completion metrics
- **User Statistics**: Engagement and usage patterns
- **Export Capabilities**: CSV exports and PDF reports
- **Performance Metrics**: System usage and matching effectiveness

### 🌐 **Advanced Search & Filtering**
- **Multi-Criteria Search**: Filter by multiple attributes simultaneously
- **Faceted Search**: Dynamic filter options based on data
- **Sorting Options**: Multiple sort criteria with performance optimization
- **Pagination**: Efficient large dataset handling

### 📁 **Document Management**
- **Polymorphic Attachments**: Documents can belong to any entity
- **Secure Upload**: File validation and secure storage
- **Download Tracking**: Access logging and permission checks
- **File Type Support**: PDFs, images, and other document types

### ⚙️ **System Configuration**
- **Settings Management**: Configurable system parameters
- **File Upload Settings**: Logos, guides, and system files
- **Dynamic Configuration**: Runtime configuration updates

## Main Application Sections

### 🏠 **Public Landing**
- **Homepage**: Project overview with embedded video content
- **Public Metrics**: Success stories and platform statistics
- **User Guides**: Downloadable documentation and tutorials

### 👤 **User Dashboard**
- **Request Management**: Create, edit, and track capacity development requests
- **My Requests**: Personal request history and status tracking
- **Matched Requests**: View accepted partnerships and collaborations
- **Notification Preferences**: Configure intelligent notification settings

### 🤝 **Partner Portal**
- **Opportunity Management**: Create and manage capacity offerings
- **Request Browse**: Discover and respond to capacity development needs
- **Offer Management**: Track offers and partnership opportunities
- **Partner Analytics**: Performance metrics and engagement statistics

### 👨‍💼 **Admin Control Panel**
- **System Dashboard**: Platform-wide analytics and health metrics
- **Request Administration**: Review, approve, and manage all requests
- **User Management**: Role assignments and permission management
- **System Settings**: Portal configuration and file management
- **Notification Management**: System notifications and announcements

## Core Functionalities

### 🔄 **Intelligent Matching System**
- **Attribute-Based Matching**: Match requests with interested partners
- **Multi-Criteria Evaluation**: Consider multiple factors for matching
- **Notification Triggers**: Real-time alerts for relevant opportunities
- **Preference Management**: Granular control over notification criteria

### 📈 **Workflow Management**
- **Request Lifecycle**: Structured progression from creation to completion
- **Status Transitions**: Controlled state changes with validation
- **Approval Workflows**: Admin oversight and quality control
- **Partnership Formation**: Facilitate connections between parties

### 🛡️ **Security & Compliance**
- **Data Protection**: Secure handling of sensitive information
- **Access Control**: Role-based permissions and authorization
- **Audit Logging**: Comprehensive activity tracking
- **File Security**: Secure upload and access control

### 🔧 **System Administration**
- **Configuration Management**: Dynamic system settings
- **User Role Management**: Flexible permission assignments
- **Data Export**: Comprehensive reporting and data extraction
- **System Monitoring**: Performance tracking and health checks

## Database Design

### **Storage Strategy**
- **Dual Storage**: JSON for flexibility + normalized tables for performance
- **Proper Indexing**: Optimized queries with strategic indexes
- **Foreign Key Constraints**: Data integrity and cascading operations
- **Polymorphic Relations**: Flexible document attachments

### **Performance Optimizations**
- **Query Optimization**: Efficient database queries with proper joins
- **Eager Loading**: Prevent N+1 query issues
- **Caching Strategy**: Strategic caching for frequently accessed data
- **Index Strategy**: Composite indexes for complex queries

## Deployment & DevOps

### **Development Environment**
- **Laravel Sail**: Docker-based development environment
- **Hot Module Replacement**: Instant development feedback
- **Database Seeding**: Consistent development data

### **Production Considerations**
- **Asset Optimization**: Minified and compressed frontend assets
- **Database Migrations**: Structured schema evolution
- **Queue Management**: Background job processing
- **Error Monitoring**: Comprehensive error tracking

---

*This technical documentation provides a comprehensive overview of the Ocean Decade Portal's architecture, features, and technical implementation. For specific implementation details, refer to the codebase and individual component documentation.*
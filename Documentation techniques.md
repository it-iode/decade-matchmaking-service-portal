# Portail de la Décennie de l'Océan - Documentation Technique

## Aperçu du Projet

Le **Portail de la Décennie de l'Océan** est une application web conçue pour le Programme de la Décennie de l'Océan de l'UNESCO. Il sert de plateforme numérique connectant les chercheurs, organisations et parties prenantes en sciences océaniques durables à travers des demandes de développement de capacités, des opportunités de partenariat et un système de mise en relation intelligent.

Le portail facilite la collaboration en mettant en relation des organisations cherchant un soutien au développement de capacités avec des partenaires offrant leur expertise, créant un marché transparent pour la collaboration et l'innovation en sciences océaniques.

## Stack Technique

### Framework Backend
- **Laravel 12** - Framework PHP moderne avec fonctionnalités avancées
- **PHP 8.2+** - Dernière version PHP avec optimisations de performance
- **MySQL** - Base de données principale avec indexation avancée et relations
- **Inertia.js** - Framework full-stack offrant une expérience SPA sans complexité d'API

### Framework Frontend
- **React 18** - Bibliothèque UI moderne basée sur les composants avec fonctionnalités concurrentes
- **TypeScript** - Développement type-safe avec support IDE complet
- **Tailwind CSS 4** - Framework CSS utility-first avec variables CSS
- **HeadlessUI** - Composants UI accessibles et non stylés

### Outils de Développement
- **Vite 6** - Outil de build rapide avec HMR et bundling avancé
- **Laravel Sail** - Environnement de développement Docker
- **Laravel Pint** - Correcteur de style de code
- **PHPUnit** - Framework de tests pour le backend
- **TypeScript Compiler** - Vérification de types frontend

### Intégrations Externes
- **Laravel Socialite** - Authentification OAuth
- **Spatie Laravel Permission** - Contrôle d'accès basé sur les rôles
- **DomPDF** - Génération PDF pour les rapports
- **Ziggy** - Génération de routes Laravel pour le frontend

## Patterns d'Architecture

### Architecture Backend
- **Pattern Couche Service** - Encapsulation de la logique métier
- **Pattern Repository** - Abstraction d'accès aux données
- **Pattern Query Builder** - Construction de requêtes complexes
- **Pattern Observer** - Gestion d'événements de modèles
- **Injection de Dépendances** - Couplage faible et testabilité

### Architecture Frontend
- **Composition de Composants** - Composants UI réutilisables
- **Pattern Context** - Gestion d'état global (dialogues, notifications)
- **Hooks Personnalisés** - Réutilisation de logique et gestion d'état
- **Routage Côté Serveur** - Laravel gère le routage, Inertia gère la navigation

## Entités Principales et Relations

### Modèles Principaux

#### **User (Utilisateur)**
- Authentification et autorisation principales
- **Relations** : 
  - `hasMany(Request)` - Les utilisateurs peuvent créer plusieurs demandes
  - `hasMany(Opportunity)` - Les partenaires peuvent publier des opportunités
  - `hasMany(Notification)` - Notifications utilisateur
  - `hasMany(UserNotificationPreference)` - Préférences de notification
- **Rôles** : Utilisateur, Partenaire, Administrateur (via Spatie)

#### **Request (Demande OCD)**
- Demandes de développement de capacités des organisations
- **Stockage Dual** : JSON (héritage) + tables normalisées (performance)
- **Relations** :
  - `belongsTo(User)` - Créateur de la demande
  - `belongsTo(User, 'matched_partner_id')` - Partenaire associé
  - `hasMany(RequestOffer)` - Offres de partenaires
  - `hasOne(RequestDetail)` - Données normalisées
  - `belongsTo(RequestStatus)` - Statut actuel

#### **Opportunity (Opportunité)**
- Offres de capacités publiées par les partenaires
- **Relations** :
  - `belongsTo(User)` - Partenaire éditeur
  - Indépendant des demandes (offres autonomes)

#### **RequestOffer (Offre de Demande)**
- Offres de partenaires pour répondre à des demandes spécifiques
- **Relations** :
  - `belongsTo(Request)` - Demande cible
  - `belongsTo(User, 'matched_partner_id')` - Partenaire offrant
  - `morphMany(Document)` - Documents de support

#### **Notification**
- Système de notifications in-app
- **Relations** :
  - `belongsTo(User)` - Destinataire de la notification

#### **UserNotificationPreference (Préférence de Notification Utilisateur)**
- Préférences de notification utilisateur pour la mise en relation de demandes
- **Attributs** : `attribute_type`, `attribute_value`, paramètres de notification
- **Supporte** : Sous-thèmes, localisations, gammes de financement, etc.

#### **Document**
- Gestion de fichiers avec relations polymorphes
- **Relations** :
  - `morphTo('parent')` - Peut appartenir aux demandes, offres, etc.
  - `belongsTo(User, 'uploader_id')` - Téléchargeur du fichier

#### **Setting (Paramètre)**
- Gestion de configuration système
- **Fonctionnalités** : Détection de téléchargement de fichiers, génération d'URL publique

## Fonctionnalités du Projet

### 🔐 **Authentification et Autorisation**
- **Intégration OAuth** : Authentification par fournisseur externe
- **Accès Basé sur les Rôles** : Rôles Utilisateur, Partenaire, Administrateur
- **Permissions Spatie** : Système de permissions granulaire
- **Sessions Sécurisées** : Intégration Laravel Sanctum

### 📋 **Gestion des Demandes**
- **Système de Stockage Dual** : Stockage JSON + base de données normalisée
- **Cycle de Vie des Demandes** : Brouillon → En Révision → Validé → Associé
- **Constructeur de Formulaires Riche** : Rendu de formulaires dynamique avec validation
- **Pièces Jointes** : Téléchargement et gestion de fichiers
- **Suivi de Statut** : Système de statut de demande complet

### 🤝 **Opportunités Partenaires**
- **Publication d'Opportunités** : Les partenaires peuvent publier des offres de capacités
- **Navigation d'Opportunités** : Opportunités partenaires recherchables
- **Gestion d'Opportunités** : Mises à jour de statut et gestion du cycle de vie

### 💼 **Gestion des Offres**
- **Offres Partenaires** : Les partenaires peuvent offrir des services pour des demandes spécifiques
- **Évaluation d'Offres** : Les créateurs de demandes peuvent évaluer les offres reçues
- **Support Documentaire** : Joindre des documents de support aux offres
- **Suivi de Statut** : Offres actives, en attente, acceptées, rejetées

### 🔔 **Système de Notification Intelligent**
- **Correspondance Basée sur les Attributs** : Les utilisateurs s'abonnent à des attributs de demande spécifiques
- **Support Multi-Attributs** : Sous-thèmes, localisations, gammes de financement, etc.
- **Notifications Duales** : Notifications in-app + email
- **Gestion en Masse** : Activer/désactiver plusieurs préférences
- **Traitement Temps Réel** : Notifications envoyées lors de la soumission de demandes

### 🎛️ **Tableau de Bord Admin**
- **Analytiques Système** : Statistiques et métriques des demandes
- **Gestion Utilisateurs** : Attributions de rôles et permissions
- **Supervision des Demandes** : Révision admin et gestion de statut
- **Paramètres Système** : Configuration du portail et gestion de fichiers

### 📊 **Analytiques et Rapports**
- **Analytiques des Demandes** : Taux de succès, métriques de completion
- **Statistiques Utilisateurs** : Patterns d'engagement et d'utilisation
- **Capacités d'Export** : Exports CSV et rapports PDF
- **Métriques de Performance** : Utilisation système et efficacité de mise en relation

### 🌐 **Recherche Avancée et Filtrage**
- **Recherche Multi-Critères** : Filtrer par plusieurs attributs simultanément
- **Recherche à Facettes** : Options de filtre dynamiques basées sur les données
- **Options de Tri** : Critères de tri multiples avec optimisation de performance
- **Pagination** : Gestion efficace de grands ensembles de données

### 📁 **Gestion Documentaire**
- **Pièces Jointes Polymorphes** : Les documents peuvent appartenir à toute entité
- **Téléchargement Sécurisé** : Validation de fichiers et stockage sécurisé
- **Suivi de Téléchargement** : Journalisation d'accès et vérifications de permissions
- **Support Types de Fichiers** : PDFs, images et autres types de documents

### ⚙️ **Configuration Système**
- **Gestion des Paramètres** : Paramètres système configurables
- **Paramètres de Téléchargement** : Logos, guides et fichiers système
- **Configuration Dynamique** : Mises à jour de configuration en temps d'exécution

## Sections Principales de l'Application

### 🏠 **Page d'Accueil Publique**
- **Page d'Accueil** : Aperçu du projet avec contenu vidéo intégré
- **Métriques Publiques** : Histoires de succès et statistiques de plateforme
- **Guides Utilisateurs** : Documentation téléchargeable et tutoriels

### 👤 **Tableau de Bord Utilisateur**
- **Gestion des Demandes** : Créer, éditer et suivre les demandes de développement de capacités
- **Mes Demandes** : Historique personnel des demandes et suivi de statut
- **Demandes Associées** : Voir les partenariats acceptés et collaborations
- **Préférences de Notification** : Configurer les paramètres de notification intelligents

### 🤝 **Portail Partenaire**
- **Gestion d'Opportunités** : Créer et gérer les offres de capacités
- **Navigation des Demandes** : Découvrir et répondre aux besoins de développement de capacités
- **Gestion d'Offres** : Suivre les offres et opportunités de partenariat
- **Analytiques Partenaire** : Métriques de performance et statistiques d'engagement

### 👨‍💼 **Panneau de Contrôle Admin**
- **Tableau de Bord Système** : Analytiques et métriques de santé à l'échelle de la plateforme
- **Administration des Demandes** : Réviser, approuver et gérer toutes les demandes
- **Gestion Utilisateurs** : Attributions de rôles et gestion des permissions
- **Paramètres Système** : Configuration du portail et gestion de fichiers
- **Gestion des Notifications** : Notifications système et annonces

## Fonctionnalités Principales

### 🔄 **Système de Correspondance Intelligent**
- **Correspondance Basée sur les Attributs** : Associer les demandes avec les partenaires intéressés
- **Évaluation Multi-Critères** : Considérer plusieurs facteurs pour la correspondance
- **Déclencheurs de Notification** : Alertes temps réel pour les opportunités pertinentes
- **Gestion des Préférences** : Contrôle granulaire des critères de notification

### 📈 **Gestion de Workflow**
- **Cycle de Vie des Demandes** : Progression structurée de la création à la completion
- **Transitions de Statut** : Changements d'état contrôlés avec validation
- **Workflows d'Approbation** : Supervision admin et contrôle qualité
- **Formation de Partenariats** : Faciliter les connexions entre parties

### 🛡️ **Sécurité et Conformité**
- **Protection des Données** : Gestion sécurisée des informations sensibles
- **Contrôle d'Accès** : Permissions et autorisation basées sur les rôles
- **Journalisation d'Audit** : Suivi d'activité complet
- **Sécurité des Fichiers** : Téléchargement sécurisé et contrôle d'accès

### 🔧 **Administration Système**
- **Gestion de Configuration** : Paramètres système dynamiques
- **Gestion des Rôles Utilisateurs** : Attributions de permissions flexibles
- **Export de Données** : Rapports complets et extraction de données
- **Surveillance Système** : Suivi de performance et vérifications de santé

## Conception de Base de Données

### **Stratégie de Stockage**
- **Stockage Dual** : JSON pour la flexibilité + tables normalisées pour la performance
- **Indexation Appropriée** : Requêtes optimisées avec index stratégiques
- **Contraintes de Clés Étrangères** : Intégrité des données et opérations en cascade
- **Relations Polymorphes** : Pièces jointes de documents flexibles

### **Optimisations de Performance**
- **Optimisation de Requêtes** : Requêtes de base de données efficaces avec jointures appropriées
- **Chargement Eager** : Prévenir les problèmes de requêtes N+1
- **Stratégie de Cache** : Cache stratégique pour les données fréquemment accédées
- **Stratégie d'Index** : Index composites pour requêtes complexes

## Déploiement et DevOps

### **Environnement de Développement**
- **Laravel Sail** : Environnement de développement basé sur Docker
- **Remplacement de Module à Chaud** : Retour de développement instantané
- **Ensemencement de Base de Données** : Données de développement cohérentes

### **Considérations de Production**
- **Optimisation d'Assets** : Assets frontend minifiés et compressés
- **Migrations de Base de Données** : Évolution de schéma structurée
- **Gestion de Files d'Attente** : Traitement de tâches en arrière-plan
- **Surveillance d'Erreurs** : Suivi d'erreurs complet

---

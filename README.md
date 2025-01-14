# Documentation de l'Application Stubborn-ecommerce

## Introduction
Cette application e-commerce a été développée pour la marque Stubborn afin de vendre des sweat-shirts en ligne. Elle inclut des fonctionnalités telles que la gestion des utilisateurs, un système de panier, et l'intégration du paiement avec Stripe.

---

## Fonctionnalités principales
- Authentification sécurisée avec des rôles (administrateur et client).
- Gestion complète des produits via un back-office.
- Pages de consultation des produits avec filtres par prix.
- Panier interactif permettant la validation et le paiement d'une commande.
- Intégration de Stripe pour les paiements (en mode sandbox).
- Envoi d'emails pour l'activation des comptes via Gmail.
- Tests unitaires pour le panier et le processus d'achat.

---

## Structure du projet
### Routes principales
- **Page d'accueil (`/`)** : Présentation de la marque et produits mis en avant.
- **Connexion (`/login`)** : Formulaire de connexion utilisateur.
- **Inscription (`/register`)** : Création d'un compte utilisateur avec email de confirmation.
- **Produits (`/products`)** : Liste filtrable des produits.
- **Détail produit (`/product/{id}`)** : Consultation d'un produit spécifique.
- **Panier (`/cart`)** : Gestion du panier et validation de commande.
- **Back-office (`/admin`)** : Gestion des produits pour les administrateurs.

### Structure des dossiers
- `src/Controller` : Contrôleurs Symfony.
- `templates` : Templates Twig pour les pages.
- `public` : Assets publics (CSS, JS, images).
- `tests` : Tests unitaires.

---

## Pré-requis
- PHP 8.1 ou supérieur.
- Composer installé.
- Serveur MySQL.
- Symfony CLI (optionnel).
- Compte Stripe (mode sandbox activé).
- Compte Gmail pour l'envoi d'emails.

---

## Installation

1. **Cloner le dépôt**
   ```bash
   git clone https://github.com/votre-repository/stubborn-ecommerce.git
   cd stubborn-ecommerce
   ```

2. **Installer les dépendances**
   ```bash
   composer install
   ```

3. **Configurer la base de données**
   - Copier le fichier `.env` :
     ```bash
     cp .env .env.local
     ```
   - Modifier les paramètres de connexion MySQL :
     ```env
     DATABASE_URL="mysql://username:password@127.0.0.1:3306/stubborn_db"
     ```

4. **Configurer l'envoi d'emails**
   - Ajouter les paramètres Gmail dans votre fichier `.env.local` :
     ```env
     MAILER_DSN="smtp://username:password@smtp.gmail.com:587"
     ```
   - Remplacez `username` et `password` par vos identifiants Gmail. Assurez-vous d'activer "Accès pour les applications moins sécurisées" dans votre compte Gmail ou configurez un mot de passe d'application si vous utilisez une validation à deux facteurs.

5. **Créer la base de données**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

6. **Alimenter la base de données**
   ```bash
   php bin/console doctrine:fixtures:load
   ```

7. **Configurer Stripe**
   - Ajoutez vos clés Stripe dans le fichier `.env.local` :
     ```env
     STRIPE_PUBLIC_KEY="pk_test_your_public_key"
     STRIPE_SECRET_KEY="sk_test_your_secret_key"
     ```
   - Remplacez `your_public_key` et `your_secret_key` par les clés fournies par votre tableau de bord Stripe en mode test.

8. **Démarrer le serveur**
   ```bash
   symfony serve
   ```

9. **Lancer les tests**
   ```bash
   php bin/phpunit
   ```

---

## Commandes utiles
- **Nettoyer le cache** : 
  ```bash
  php bin/console cache:clear
  ```
- **Créer une migration** :
  ```bash
  php bin/console make:migration
  ```
- **Exécuter une migration** :
  ```bash
  php bin/console doctrine:migrations:migrate
  ```

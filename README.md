# GKAS Shop — plateforme e-commerce multi-boutiques de GKAS-GROUP

Une seule application Laravel qui héberge plusieurs boutiques indépendantes
(`gkasshop.com/bella-fashion`, `gkasshop.com/kente-store`…), avec essai gratuit
de 30 jours, abonnements à paiement manuel vérifié, espace vendeur et
administration GKAS-GROUP.

| Élément | Version utilisée |
|---|---|
| PHP | 8.2 (XAMPP) — extensions requises : `pdo_mysql`, `mbstring`, `fileinfo`, `bcmath`, `openssl`, `zip` |
| Laravel | 12.x |
| Base de données | MariaDB 10.4 / MySQL (XAMPP) |
| Frontend | Blade + Tailwind CSS 4 + Vite 7 (aucun framework JS) |
| Tests | PHPUnit 11 sur SQLite en mémoire |

> Les extensions `gd` et `intl` **ne sont pas nécessaires**.

---

## 1. Prérequis

- **XAMPP** (Apache + MySQL/MariaDB, PHP 8.2+) — <https://www.apachefriends.org>
- **Composer** — <https://getcomposer.org>
- **Node.js 20+ et npm** — <https://nodejs.org>

Vérifiez dans un terminal (PowerShell ou Git Bash) :

```bash
php -v
composer --version
node -v
npm -v
```

Si `php` n'est pas reconnu, ajoutez `C:\xampp\php` à la variable d'environnement `PATH`.

## 2. Démarrer Apache et MySQL

Ouvrez le **XAMPP Control Panel** et cliquez sur **Start** pour *Apache* et *MySQL*.

## 3. Créer la base de données

Dans phpMyAdmin (<http://localhost/phpmyadmin>) → *Nouvelle base de données* :
nom `gkas-shop`, interclassement `utf8mb4_unicode_ci`.

Ou en ligne de commande :

```bash
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS `gkas-shop` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

## 4. Configurer le fichier `.env`

```bash
copy .env.example .env      # PowerShell / cmd   (cp .env.example .env sous Git Bash)
```

Ne copiez pas si un `.env` existe déjà : modifiez-le. Paramètres principaux :

```dotenv
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Africa/Lome
DB_CONNECTION=mysql
DB_DATABASE=gkas-shop
DB_USERNAME=root
DB_PASSWORD=
MAIL_MAILER=log                 # e-mails écrits dans storage/logs/laravel.log
GKAS_TRIAL_DAYS=30
GKAS_MAIL_NOTIFICATIONS=false   # true seulement après configuration SMTP
GKAS_DEMO_PASSWORD=             # mot de passe des vendeurs de démo (local) ; aléatoire si vide
```

## 5. Dépendances PHP

```bash
composer install
```

## 6. Dépendances frontend

```bash
npm install
```

## 7. Clé d'application

```bash
php artisan key:generate
```

## 8. Migrations et lien de stockage

```bash
php artisan migrate
php artisan storage:link
```

`storage:link` rend accessibles les images (logos, produits) via `/storage`.

## 9. Données de démonstration

```bash
php artisan db:seed
```

Crée :
- 3 formules d'abonnement **d'exemple** (Mensuel 5 000, Trimestriel 13 500, Annuel 48 000 FCFA), modifiables dans *Administration › Formules* ;
- 2 boutiques de démonstration (`/bella-fashion` — vêtements, `/kente-store` — accessoires), marquées « démo », avec catégories, produits illustrés (images SVG locales) et 4 commandes de démonstration chacune ;
- 2 comptes vendeurs de démonstration : `demo.mode@gkasshop.local` et `demo.accessoires@gkasshop.local`.
  Le mot de passe est **affiché dans le terminal** à la fin du seed (ou celui de `GKAS_DEMO_PASSWORD`).

Le seeder de démonstration refuse de s'exécuter en production et ne recrée pas une boutique déjà existante.

## 10. Créer le premier administrateur (procédure sécurisée)

```bash
php artisan gkas:create-admin
```

Le nom et l'e-mail sont demandés, puis le mot de passe est saisi **de façon masquée**
(10 caractères minimum, majuscules, minuscules et chiffres). Aucun compte administrateur
ni mot de passe n'existe dans le code ou les seeders.

> À lancer dans un vrai terminal interactif (PowerShell, cmd, Windows Terminal).

## 11. Lancer l'application

```bash
php artisan serve
```

Ouvrez <http://127.0.0.1:8000>. Connexion : <http://127.0.0.1:8000/connexion>.

## 12. Compiler les assets

- Développement (rechargement automatique) : `npm run dev` dans un second terminal.
- Sans serveur Vite : `npm run build` (une fois, puis après chaque modification CSS/JS).

## 13. Planificateur de tâches (rappels d'essai / d'abonnement)

La commande `gkas:check-access` (toutes les heures) envoie les rappels J-7, J-3, J-1 et
les notifications d'expiration, **sans doublon**.

- **En local** — dans un troisième terminal, à laisser ouvert :
  ```bash
  php artisan schedule:work
  ```
- **Lancement manuel** : `php artisan gkas:check-access`
- **Serveur Windows** — Planificateur de tâches Windows, une tâche toutes les minutes :
  - Programme : `C:\xampp\php\php.exe`
  - Arguments : `artisan schedule:run`
  - Démarrer dans : `C:\xampp\htdocs\GKAS-SHOP`

Même sans planificateur, **l'accès est toujours correct** : l'expiration est calculée en direct
à partir des dates à chaque requête, et les rappels sont vérifiés à l'ouverture du tableau de bord vendeur.

## 14. Commandes utiles

```bash
php artisan test                 # tests automatisés (SQLite en mémoire, n'affecte pas MySQL)
php artisan route:list --except-vendor
php artisan gkas:check-access    # contrôle des essais / abonnements
php artisan optimize:clear       # vide les caches (config, routes, vues)
php artisan migrate:status
php artisan down / php artisan up   # mode maintenance
```

> ⚠️ `php artisan migrate:fresh` **efface toute la base**. À ne jamais lancer sur une base contenant des données réelles.

## 15. Sauvegardes

**Base de données** :

```bash
C:\xampp\mysql\bin\mysqldump.exe -u root --single-transaction --routines gkas-shop > sauvegarde-gkas-shop-AAAA-MM-JJ.sql
```

Restauration : `C:\xampp\mysql\bin\mysql.exe -u root gkas-shop < sauvegarde.sql`

**Fichiers** — copier régulièrement :
- `storage/app/public/` (logos, bannières, images produits) ;
- `storage/app/private/` (justificatifs de paiement — **confidentiels**) ;
- le fichier `.env` (à conserver hors du dépôt Git, en lieu sûr).

---

## Architecture

```
app/
  Enums/            Statuts métier (commande, paiement, boutique, accès, prospect, rôles, thèmes)
  Models/           Store, Product, Order, Payment, Subscription, Plan, DemoRequest, Setting…
  Services/         Logique métier : StoreService, OrderService, CartService, SubscriptionService,
                    SubscriptionMonitor, ProductService, UserService, ActivityLogger, Notifier
  Policies/         Store, Product, Category, Order, Payment
  Http/Middleware/  EnsureStoreMember, EnsureStoreOperational, EnsureShopAvailable, EnsureAccountUsable
  Http/Controllers/ Site (public), Auth, Admin, Vendor, Shop (boutique publique)
  Http/Requests/    Validation (Form Requests)
  Notifications/    Nouvelle commande, statut, rappels, expiration, paiements, statut boutique
  Console/Commands/ gkas:create-admin, gkas:check-access
config/gkas.php     Durée d'essai, seuils de rappel, limites d'upload, identifiants réservés
resources/views/    site/ (public), admin/, seller/ (espace vendeur), shop/ (boutiques), layouts/, components/
routes/web.php      Routes (boutiques publiques déclarées en dernier : /{boutique})
tests/              97 tests (Feature + Unit)
```

### Rôles

| Rôle | Droits |
|---|---|
| `admin` (GKAS) | Tout : boutiques, statuts, prolongations d'essai, confirmation des paiements, formules, utilisateurs, paramètres, journal |
| `sales` (commercial GKAS) | Tableau de bord, prospects, création/modification de boutiques et vendeurs associés. Ne peut **ni** suspendre, **ni** prolonger un essai, **ni** confirmer un paiement |
| `vendor` | Uniquement ses boutiques (via la table `store_user`), avec un rôle par boutique (ci-dessous) |

Rôles **dans une boutique** (`store_user.role`, voir `App\Enums\StoreRole`) :

| Rôle | Droits |
|---|---|
| Propriétaire | Tout : commandes, catalogue, livraison, infos et apparence, statistiques, export, **abonnement**, **équipe** |
| Gérant | Commandes, catalogue, livraison, infos et apparence, statistiques, export. Ni abonnement ni équipe |
| Employé | Commandes uniquement (consultation, statut, notes, paiement reçu) |

Le propriétaire ajoute ses gérants et employés dans *Espace vendeur › Équipe* : un compte existant
est rattaché, sinon un compte est créé avec un mot de passe temporaire affiché une seule fois. Le
propriétaire lui-même ne peut être changé que par GKAS-GROUP. Les notifications sont ciblées :
les employés reçoivent les commandes, pas les rappels d'abonnement.

Les autorisations sont vérifiées côté serveur (Gates, Policies, middleware, liaisons de routes
« scopées » : un identifiant d'une autre boutique dans l'URL renvoie 404).

### Règles métier principales

- **Statut administratif** (`active / suspended / archived`) **séparé** de l'**accès** (essai, abonnement, expiré).
  Un paiement confirmé ne réactive jamais une boutique suspendue.
- **Essai** : démarre à la création, durée configurable (30 jours calendaires par défaut), prolongation réservée
  aux administrateurs avec motif obligatoire ; tout est historisé (`trial_histories`, `activity_logs`).
- **Expiration** : la boutique publique affiche une page d'indisponibilité et refuse les commandes ;
  le vendeur garde un accès en lecture, l'abonnement et l'export CSV. Aucune donnée n'est supprimée.
- **Paiements** : déclarés par le vendeur (référence + justificatif facultatif, stocké en privé) ou enregistrés par GKAS,
  puis confirmés/rejetés par un administrateur. Confirmation **idempotente** (verrou + statut + contrainte unique
  `subscriptions.payment_id`). Le renouvellement démarre à la fin de l'abonnement en cours.
- **Commandes** : panier en session (identifiants + quantités seulement), prix et total **recalculés en base**,
  transaction avec verrouillage du stock, référence aléatoire `CMD-XXXXXXXXXX`, instantané des produits,
  transitions de statut contrôlées, stock restitué en cas d'annulation.
  La commande est enregistrée **avant** toute redirection WhatsApp ; le message est généré côté serveur.
- **Chiffre d'affaires vendeur** : commandes **livrées** ou **marquées payées** par le vendeur (hors annulées).
- **Livraison** : le vendeur définit ses zones et leurs frais (*Espace vendeur › Livraison*), et peut offrir la
  livraison au-delà d'un montant. Le client choisit sa zone ; les frais sont **recalculés côté serveur** et ajoutés
  au total. La commande conserve le nom de la zone et les frais payés, même si la zone change ensuite.
  Sans zone définie, la boutique fonctionne sans frais automatiques.

### Noms de domaine

**Adresse sur la plateforme** : `APP_URL/{identifiant}`. En production, `APP_URL=https://gkasshop.com`
donne `https://gkasshop.com/bella-fashion`. Les identifiants réservés sont listés dans `config/gkas.php`.

**Domaine personnalisé d'un vendeur** (ex. `bellafashion.com`) — attribué par un administrateur dans
*Administration › Boutiques › (boutique) › Nom de domaine personnalisé* :

1. Chez le registraire du domaine : enregistrement **A** vers l'IP du serveur (et `www` en CNAME si besoin).
2. Sur le serveur Apache : ajouter le domaine en `ServerAlias` de l'hôte virtuel GKAS Shop, puis installer
   un certificat HTTPS (ex. Let's Encrypt / win-acme sous Windows).
3. Dans l'administration : saisir exactement l'hôte utilisé par les clients (`bellafashion.com` ou `www.bellafashion.com`).

La boutique est alors servie à la racine du domaine (`https://bellafashion.com/`, `/produit/…`, `/panier`…) ;
les liens restent sur ce domaine. L'adresse `gkasshop.com/bella-fashion` continue de fonctionner.
Les hôtes de la plateforme ne sont jamais traités comme des domaines de boutique : par défaut l'hôte d'`APP_URL`
et sa variante `www.` ; pour en déclarer d'autres : `GKAS_PLATFORM_DOMAINS=gkasshop.com,www.gkasshop.com`.
`GKAS_CUSTOM_DOMAIN_SCHEME` (défaut `https`) sert à construire les liens affichés.

## Limites connues / à développer

- Pas encore de paiement en ligne : intégration **FedaPay** prévue (gestion manuelle sécurisée en attendant).
- E-mails : désactivés par défaut ; activer `GKAS_MAIL_NOTIFICATIONS` après configuration SMTP.
- Domaines personnalisés : la configuration DNS / Apache / HTTPS reste manuelle (voir ci-dessus).

<?php

return [

    /*
    | Durée d'essai par défaut (jours calendaires). Peut être surchargée
    | depuis Administration > Paramètres (clé « trial_days »).
    */
    'trial_days' => (int) env('GKAS_TRIAL_DAYS', 30),

    // Jours avant expiration auxquels un rappel est envoyé (essai et abonnement).
    'reminder_days' => [7, 3, 1],

    'currency' => env('GKAS_CURRENCY', 'FCFA'),

    /*
    | Domaines personnalisés des vendeurs (ex. bellafashion.com).
    | Les hôtes de la plateforme ne sont jamais traités comme des domaines de
    | boutique. Par défaut : l'hôte d'APP_URL et sa variante « www. ».
    | Liste séparée par des virgules dans GKAS_PLATFORM_DOMAINS.
    */
    'platform_domains' => array_values(array_filter(array_map('trim', explode(',', (string) env(
        'GKAS_PLATFORM_DOMAINS',
        ($host = parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost').',www.'.$host
    ))))),
    'custom_domain_scheme' => env('GKAS_CUSTOM_DOMAIN_SCHEME', 'https'),

    /*
    | Envoi des notifications par e-mail. Désactivé par défaut : les
    | notifications restent toujours enregistrées en base de données.
    | Activez-le seulement après avoir configuré MAIL_* dans le .env.
    */
    'mail_notifications' => (bool) env('GKAS_MAIL_NOTIFICATIONS', false),

    // Mot de passe utilisé par le seeder de démonstration (jamais en production).
    'demo_password' => env('GKAS_DEMO_PASSWORD'),

    'uploads' => [
        'max_kb' => 2048,
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'max_product_images' => 6,
    ],

    /*
    | Identifiants publics interdits pour une boutique : ils entreraient en
    | conflit avec les routes de la plateforme (gkasshop.com/{boutique}).
    */
    'reserved_slugs' => [
        'admin', 'vendeur', 'compte', 'connexion', 'deconnexion', 'login', 'logout',
        'mot-de-passe-oublie', 'reinitialiser-mot-de-passe', 'fonctionnalites',
        'comment-ca-marche', 'demonstration', 'tarifs', 'faq', 'contact',
        'demande-essai', 'conditions', 'confidentialite', 'notifications',
        'storage', 'build', 'api', 'up', 'gkas', 'gkas-shop', 'www', 'mail',
        'aide', 'support', 'boutique', 'boutiques', 'paiement', 'abonnement',
    ],
];

<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Shop;
use App\Http\Controllers\Site\LeadController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Vendor;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Pages d'une boutique publique (partagées par les deux modes d'accès)
|--------------------------------------------------------------------------
*/
$shopRoutes = function () {
    Route::get('/', [Shop\CatalogController::class, 'index'])->name('home');
    Route::get('produit/{product:slug}', [Shop\CatalogController::class, 'show'])->name('product');

    Route::get('panier', [Shop\CartController::class, 'show'])->name('cart');
    Route::post('panier', [Shop\CartController::class, 'add'])->name('cart.add');
    Route::patch('panier', [Shop\CartController::class, 'update'])->name('cart.update');

    Route::get('commander', [Shop\CheckoutController::class, 'create'])->name('checkout');
    Route::post('commander', [Shop\CheckoutController::class, 'review'])->name('checkout.review');
    Route::get('commander/recapitulatif', [Shop\CheckoutController::class, 'summary'])->name('checkout.summary');
    Route::post('commander/valider', [Shop\CheckoutController::class, 'place'])->middleware('throttle:orders')->name('checkout.place');
    Route::get('commande/{order:reference}', [Shop\CheckoutController::class, 'confirmation'])->name('order.confirmation');
};

/*
|--------------------------------------------------------------------------
| Boutiques sur domaine personnalisé — https://bellafashion.com/
| Déclarées en premier : sur le domaine d'un vendeur, « / » est sa boutique.
| Les hôtes de la plateforme (config gkas.platform_domains) sont exclus, ainsi
| que les adresses IP et « localhost » (un domaine doit se terminer par une
| extension alphabétique). Un domaine non attribué renvoie 404.
|--------------------------------------------------------------------------
*/
$platformHosts = implode('|', array_map(fn ($h) => preg_quote(strtolower($h), '#'), config('gkas.platform_domains')));

Route::domain('{store}')
    ->where(['store' => '(?!(?:'.$platformHosts.')$)(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}'])
    ->name('domain.shop.')->middleware('shop.available')->scopeBindings()
    ->group($shopRoutes);

/*
|--------------------------------------------------------------------------
| Site public GKAS Shop
|--------------------------------------------------------------------------
*/
Route::controller(PageController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/fonctionnalites', 'features')->name('site.features');
    Route::get('/comment-ca-marche', 'how')->name('site.how');
    Route::get('/demonstration', 'demo')->name('site.demo');
    Route::get('/tarifs', 'pricing')->name('site.pricing');
    Route::get('/faq', 'faq')->name('site.faq');
    Route::get('/conditions', 'terms')->name('site.terms');
    Route::get('/confidentialite', 'privacy')->name('site.privacy');
});

Route::controller(LeadController::class)->group(function () {
    Route::get('/contact', 'contact')->name('site.contact');
    Route::post('/contact', 'storeContact')->middleware('throttle:public-forms')->name('site.contact.send');
    Route::get('/demande-essai', 'trial')->name('site.trial');
    Route::post('/demande-essai', 'storeTrial')->middleware('throttle:public-forms')->name('site.trial.send');
});

/*
|--------------------------------------------------------------------------
| Authentification
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/connexion', [LoginController::class, 'create'])->name('login');
    Route::post('/connexion', [LoginController::class, 'store'])->name('login.attempt');
    Route::get('/mot-de-passe-oublie', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/mot-de-passe-oublie', [PasswordResetController::class, 'email'])->middleware('throttle:public-forms')->name('password.email');
    Route::get('/reinitialiser-mot-de-passe/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reinitialiser-mot-de-passe', [PasswordResetController::class, 'update'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/deconnexion', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/compte', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('/compte', [AccountController::class, 'update'])->name('account.update');
    Route::put('/compte/mot-de-passe', [AccountController::class, 'password'])->name('account.password');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/tout-lire', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/notifications/{id}', [NotificationController::class, 'open'])->whereUuid('id')->name('notifications.open');
});

/*
|--------------------------------------------------------------------------
| Administration GKAS-GROUP
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:access-admin'])->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    // Boutiques
    Route::resource('boutiques', Admin\StoreController::class)->except('destroy')
        ->parameters(['boutiques' => 'store'])->names('stores');
    Route::post('boutiques/{store}/statut', [Admin\StoreController::class, 'status'])->name('stores.status');
    Route::post('boutiques/{store}/proprietaire', [Admin\StoreController::class, 'owner'])->name('stores.owner');
    Route::put('boutiques/{store}/domaine', [Admin\StoreController::class, 'domain'])->name('stores.domain');
    Route::post('boutiques/{store}/essai', [Admin\TrialController::class, 'extend'])->name('stores.trial.extend');
    Route::get('boutiques/{store}/paiement', [Admin\PaymentController::class, 'create'])->name('stores.payments.create');
    Route::post('boutiques/{store}/paiement', [Admin\PaymentController::class, 'store'])->name('stores.payments.store');

    Route::get('essais', [Admin\TrialController::class, 'index'])->name('trials.index');
    Route::get('abonnements', [Admin\SubscriptionController::class, 'index'])->name('subscriptions.index');

    // Paiements
    Route::get('paiements', [Admin\PaymentController::class, 'index'])->name('payments.index');
    Route::get('paiements/{payment}', [Admin\PaymentController::class, 'show'])->name('payments.show');
    Route::get('paiements/{payment}/justificatif', [Admin\PaymentController::class, 'proof'])->name('payments.proof');
    Route::post('paiements/{payment}/confirmer', [Admin\PaymentController::class, 'confirm'])->name('payments.confirm');
    Route::post('paiements/{payment}/rejeter', [Admin\PaymentController::class, 'reject'])->name('payments.reject');

    // Prospects / demandes commerciales
    Route::middleware('can:manage-prospects')->group(function () {
        Route::resource('prospects', Admin\ProspectController::class)->except(['edit', 'destroy'])
            ->parameters(['prospects' => 'prospect']);
        Route::post('prospects/{prospect}/notes', [Admin\ProspectController::class, 'note'])->name('prospects.notes.store');
    });

    // Réservé aux administrateurs
    Route::middleware('can:manage-platform')->group(function () {
        Route::resource('utilisateurs', Admin\UserController::class)->except('destroy')
            ->parameters(['utilisateurs' => 'user'])->names('users');
        Route::post('utilisateurs/{user}/statut', [Admin\UserController::class, 'toggle'])->name('users.toggle');
        Route::post('utilisateurs/{user}/acces', [Admin\UserController::class, 'resetAccess'])->name('users.reset-access');

        Route::resource('formules', Admin\PlanController::class)->except('show')
            ->parameters(['formules' => 'plan'])->names('plans');

        Route::get('parametres', [Admin\SettingController::class, 'edit'])->name('settings.edit');
        Route::put('parametres', [Admin\SettingController::class, 'update'])->name('settings.update');

        Route::get('journal', [Admin\ActivityLogController::class, 'index'])->name('activity.index');
    });
});

/*
|--------------------------------------------------------------------------
| Espace vendeur — /vendeur/{boutique}/…
| Chaque groupe exige un droit précis selon le rôle dans la boutique
| (propriétaire, gérant, employé) : voir App\Enums\StoreRole.
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->get('/vendeur', [Vendor\DashboardController::class, 'home'])->name('vendor.home');

Route::prefix('vendeur/{store}')->name('vendor.')->middleware(['auth', 'store.member'])->scopeBindings()->group(function () {
    Route::get('/', [Vendor\DashboardController::class, 'show'])->name('dashboard');

    // Abonnement (propriétaire) et export : accessibles même en accès limité
    Route::middleware('store.can:billing')->group(function () {
        Route::get('abonnement', [Vendor\SubscriptionController::class, 'show'])->name('subscription.show');
        Route::post('abonnement/paiement', [Vendor\SubscriptionController::class, 'pay'])->name('subscription.pay');
        Route::post('abonnement/paiement/{payment}/annuler', [Vendor\SubscriptionController::class, 'cancel'])->name('subscription.cancel');
    });
    Route::get('export/{type}', [Vendor\ExportController::class, 'export'])->middleware('store.can:export')
        ->whereIn('type', ['produits', 'commandes'])->name('export');

    // Gestion courante : lecture toujours possible, écriture seulement si la boutique est opérationnelle
    Route::middleware('store.operational')->group(function () {
        Route::middleware('store.can:catalog')->group(function () {
            Route::resource('produits', Vendor\ProductController::class)->except('show')
                ->parameters(['produits' => 'product'])->names('products');
            Route::post('produits/{product}/activation', [Vendor\ProductController::class, 'toggle'])->name('products.toggle');
            Route::delete('produits/{product}/images/{image}', [Vendor\ProductController::class, 'destroyImage'])->name('products.images.destroy');

            Route::resource('categories', Vendor\CategoryController::class)->only(['index', 'store', 'update', 'destroy'])
                ->parameters(['categories' => 'category']);
        });

        Route::middleware('store.can:orders')->group(function () {
            Route::get('commandes', [Vendor\OrderController::class, 'index'])->name('orders.index');
            Route::get('commandes/{order}', [Vendor\OrderController::class, 'show'])->name('orders.show');
            Route::post('commandes/{order}/statut', [Vendor\OrderController::class, 'status'])->name('orders.status');
            Route::put('commandes/{order}/notes', [Vendor\OrderController::class, 'notes'])->name('orders.notes');
            Route::post('commandes/{order}/paiement', [Vendor\OrderController::class, 'markPaid'])->name('orders.paid');
        });

        Route::middleware('store.can:settings')->group(function () {
            Route::get('boutique', [Vendor\StoreSettingsController::class, 'edit'])->name('settings.edit');
            Route::put('boutique', [Vendor\StoreSettingsController::class, 'update'])->name('settings.update');
            Route::get('apparence', [Vendor\StoreSettingsController::class, 'appearance'])->name('appearance.edit');
            Route::put('apparence', [Vendor\StoreSettingsController::class, 'updateAppearance'])->name('appearance.update');

            Route::get('livraison', [Vendor\DeliveryController::class, 'index'])->name('delivery.index');
            Route::post('livraison', [Vendor\DeliveryController::class, 'store'])->name('delivery.store');
            Route::put('livraison/options', [Vendor\DeliveryController::class, 'options'])->name('delivery.options');
            Route::put('livraison/{deliveryZone}', [Vendor\DeliveryController::class, 'update'])->name('delivery.update');
            Route::delete('livraison/{deliveryZone}', [Vendor\DeliveryController::class, 'destroy'])->name('delivery.destroy');
        });

        Route::middleware('store.can:team')->group(function () {
            Route::get('equipe', [Vendor\TeamController::class, 'index'])->name('team.index');
            Route::post('equipe', [Vendor\TeamController::class, 'store'])->name('team.store');
            Route::put('equipe/{member}', [Vendor\TeamController::class, 'update'])->name('team.update');
            Route::delete('equipe/{member}', [Vendor\TeamController::class, 'destroy'])->name('team.destroy');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Boutiques publiques — gkasshop.com/{boutique}
| Déclarées en dernier pour ne pas masquer les routes de la plateforme.
|--------------------------------------------------------------------------
*/
Route::prefix('{store}')->name('shop.')->where(['store' => '[a-z0-9]+(?:-[a-z0-9]+)*'])
    ->middleware('shop.available')->scopeBindings()->group($shopRoutes);

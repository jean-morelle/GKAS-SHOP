{{-- Champs d'informations d'une boutique (partagés vendeur / administration). --}}
<div class="card card-body space-y-4">
    <h2 class="font-semibold text-slate-900">Présentation</h2>
    <x-form.input name="name" label="Nom commercial" :value="$store->name" required maxlength="120" />
    <x-form.textarea name="description" label="Description" :value="$store->description" rows="3" help="Quelques phrases pour présenter votre boutique à vos clients." />
</div>

<div class="card card-body grid gap-4 sm:grid-cols-2">
    <h2 class="font-semibold text-slate-900 sm:col-span-2">Contact</h2>
    <x-form.input name="whatsapp" type="tel" label="Numéro WhatsApp" :value="$store->whatsapp" placeholder="ex. +228 90 00 00 00" help="Avec l'indicatif pays. Reçoit les commandes WhatsApp." />
    <x-form.input name="phone" type="tel" label="Téléphone" :value="$store->phone" />
    <x-form.input name="email" type="email" label="E-mail" :value="$store->email" class="sm:col-span-2" />
</div>

<div class="card card-body grid gap-4 sm:grid-cols-2">
    <h2 class="font-semibold text-slate-900 sm:col-span-2">Livraison et commandes</h2>
    <x-form.input name="address" label="Adresse" :value="$store->address" />
    <x-form.input name="delivery_zone" label="Zone de livraison" :value="$store->delivery_zone" placeholder="ex. Tout Lomé, 24 à 48 h" />
    <x-form.input name="opening_hours" label="Horaires" :value="$store->opening_hours" placeholder="ex. Lun–Sam, 8h–19h" class="sm:col-span-2" />
    <x-form.textarea name="order_conditions" label="Conditions de commande" :value="$store->order_conditions" rows="3" class="sm:col-span-2" help="ex. Paiement à la livraison. Frais de livraison : 1 000 FCFA dans Lomé." />
</div>

<div class="card card-body grid gap-4 sm:grid-cols-3">
    <h2 class="font-semibold text-slate-900 sm:col-span-3">Réseaux sociaux</h2>
    <x-form.input name="tiktok_url" type="url" label="TikTok" :value="$store->tiktok_url" placeholder="https://www.tiktok.com/@…" />
    <x-form.input name="instagram_url" type="url" label="Instagram" :value="$store->instagram_url" placeholder="https://instagram.com/…" />
    <x-form.input name="facebook_url" type="url" label="Facebook" :value="$store->facebook_url" placeholder="https://facebook.com/…" />
</div>

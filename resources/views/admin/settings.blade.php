@extends('admin._layout', ['title' => 'Paramètres'])

@section('content')
    <x-page-header title="Paramètres de la plateforme" />

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf @method('PUT')
        <div class="card card-body grid gap-4 sm:grid-cols-2">
            <h2 class="font-semibold text-slate-900 sm:col-span-2">Identité</h2>
            <x-form.input name="platform_name" label="Nom de la plateforme" :value="$settings['platform_name']" required />
            <x-form.input name="company_name" label="Société" :value="$settings['company_name']" required />
            <div class="sm:col-span-2">
                <label class="form-label">Logo</label>
                @if ($settings['platform_logo'])<img src="{{ Storage::disk('public')->url($settings['platform_logo']) }}" alt="" class="mb-2 h-12">@endif
                <input type="file" name="logo" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-slate-600">
                @error('logo')<p class="form-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="card card-body grid gap-4 sm:grid-cols-2">
            <h2 class="font-semibold text-slate-900 sm:col-span-2">Coordonnées de GKAS-GROUP</h2>
            <x-form.input name="contact_phone" label="Téléphone" :value="$settings['contact_phone']" />
            <x-form.input name="contact_whatsapp" label="WhatsApp" :value="$settings['contact_whatsapp']" />
            <x-form.input name="contact_email" type="email" label="E-mail" :value="$settings['contact_email']" />
            <x-form.input name="contact_address" label="Adresse" :value="$settings['contact_address']" />
        </div>

        <div class="card card-body grid gap-4 sm:grid-cols-2">
            <h2 class="font-semibold text-slate-900 sm:col-span-2">Essais et paiements</h2>
            <x-form.input name="trial_days" type="number" label="Durée d'essai par défaut (jours)" :value="$settings['trial_days']" min="1" max="90" required help="S'applique aux boutiques créées après la modification." />
            <x-form.input name="currency" label="Devise" :value="$settings['currency']" required />
            <x-form.textarea name="payment_instructions" label="Instructions de paiement (affichées aux vendeurs)" :value="$settings['payment_instructions']" rows="5" class="sm:col-span-2"
                help="ex. Flooz : +228 … au nom de GKAS-GROUP. Indiquez ensuite la référence de la transaction." />
        </div>

        <div class="card card-body space-y-4">
            <h2 class="font-semibold text-slate-900">Documents légaux</h2>
            <x-form.textarea name="terms" label="Conditions d'utilisation" :value="$settings['terms']" rows="8" />
            <x-form.textarea name="privacy" label="Politique de confidentialité" :value="$settings['privacy']" rows="8" />
        </div>

        <div class="flex justify-end"><button class="btn btn-primary">Enregistrer les paramètres</button></div>
    </form>
@endsection

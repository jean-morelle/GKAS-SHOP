<?php

namespace App\Exceptions;

use App\Models\Store;
use RuntimeException;

/**
 * Levée lorsqu'une boutique expirée, suspendue, archivée ou non publiée
 * reçoit une tentative de commande.
 */
class StoreUnavailableException extends RuntimeException
{
    public function __construct(public readonly Store $store)
    {
        parent::__construct('Cette boutique n\'accepte pas de nouvelles commandes pour le moment.');
    }

    public function render()
    {
        return response()->view('shop.unavailable', ['store' => $this->store], 403);
    }
}

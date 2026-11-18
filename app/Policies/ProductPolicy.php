<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function update(User $user, Product $product): bool
    {
        return $user->canInStore($product->store_id, 'catalog');
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->update($user, $product);
    }
}

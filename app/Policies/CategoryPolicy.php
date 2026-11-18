<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function update(User $user, Category $category): bool
    {
        return $user->canInStore($category->store_id, 'catalog');
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->update($user, $category);
    }
}

<?php

namespace App\Policies;

use App\Models\Trade;
use App\Models\User;

class TradePolicy
{
    public function view(User $user, Trade $trade): bool
    {
        return $this->owns($user, $trade);
    }

    public function update(User $user, Trade $trade): bool
    {
        return $this->owns($user, $trade);
    }

    public function delete(User $user, Trade $trade): bool
    {
        return $this->owns($user, $trade);
    }

    private function owns(User $user, Trade $trade): bool
    {
        return $trade->account()->where('user_id', $user->id)->exists();
    }
}

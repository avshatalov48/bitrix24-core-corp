<?php

namespace Bitrix\Intranet\Contract\Strategy;

use Bitrix\Intranet\Entity\User;

interface RegistrationStrategy
{
	public function register(User $user): User;
}
<?php
declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Strategy\Registration;

use Bitrix\Intranet\Contract\Strategy\RegistrationStrategy;
use Bitrix\Intranet\Entity\User;

class ReInviteRegistrationStrategy implements RegistrationStrategy
{

	public function register(User $user): User
	{
		throw new \Exception("User {$user->getLogin()} not found or already activated");
	}
}
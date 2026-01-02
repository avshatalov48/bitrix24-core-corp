<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Public\Type\Collection;

use Bitrix\Intranet\Entity\Collection\BaseCollection;
use Bitrix\Intranet\Entity\Type;
use Bitrix\Intranet\Public\Type\EmailInvitation;
use Bitrix\Main\ArgumentException;

/**
 * @extends BaseCollection<\Bitrix\Intranet\Public\Type\BaseInvitation>
 */
class InvitationCollection extends BaseCollection
{
	/**
	 * @inheritDoc
	 */
	protected static function getItemClassName(): string
	{
		return \Bitrix\Intranet\Public\Type\BaseInvitation::class;
	}

	/**
	 * @throws ArgumentException
	 */
	public function countEmailInvitation(): int
	{
		return $this->filter(fn ($invitation) => $invitation instanceof EmailInvitation)->count();
	}

	/**
	 * @throws ArgumentException
	 */
	public function countPhoneInvitation(): int
	{
		return $this->filter(fn ($invitation) => $invitation instanceof \Bitrix\Intranet\Public\Type\PhoneInvitation)->count();
	}
}
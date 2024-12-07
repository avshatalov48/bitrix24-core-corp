<?php

namespace Bitrix\Intranet\Entity\Collection;

use Bitrix\Intranet\Entity\Department;
use Bitrix\Intranet\Entity\Invitation;

/**
 * @extends BaseCollection<Invitation>
 */
class InvitationCollection extends BaseCollection
{
	protected static function getItemClassName(): string
	{
		return Invitation::class;
	}

	public function getUserIds(): array
	{
		return $this->map(function ($invitation) {
			return $invitation->getUserId();
		});
	}
}
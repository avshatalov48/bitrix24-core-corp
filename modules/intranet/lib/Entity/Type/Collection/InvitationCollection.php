<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Entity\Type\Collection;

use Bitrix\Intranet\Entity\Collection\BaseCollection;
use Bitrix\Intranet\Entity\Type;

/**
 * @extends BaseCollection<Type\BaseInvitation>
 */
class InvitationCollection extends BaseCollection
{
	/**
	 * @inheritDoc
	 */
	protected static function getItemClassName(): string
	{
		return Type\BaseInvitation::class;
	}
}
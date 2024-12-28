<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Dto\Invitation;

use Bitrix\Intranet\Entity\Type\Email;
use Bitrix\Intranet\Entity\Type\Phone;
use Bitrix\Intranet\Enum\InvitationStatus;

class UserInvitationDto
{
	public function __construct(
		public readonly ?string $name = null,
		public readonly ?string $lastName = null,
		public readonly ?Phone $phone = null,
		public readonly ?Email $email = null,
		public ?InvitationStatus $invitationStatus = null,
	)
	{
	}
}

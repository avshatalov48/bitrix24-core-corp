<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Dto\Invitation;

use Bitrix\Intranet\Entity\Collection\BaseCollection;
use Bitrix\Intranet\Entity\Type\Email;
use Bitrix\Intranet\Entity\Type\Phone;

/**
 * @extends BaseCollection<UserInvitationDto>
 */
class UserInvitationDtoCollection extends BaseCollection
{
	/**
	 * @inheritDoc
	 */
	protected static function getItemClassName(): string
	{
		return UserInvitationDto::class;
	}

	public function getEmails(): array
	{
		$emails = [];

		$this->forEach(function (UserInvitationDto $dto) use (&$emails) {
			if ($dto->email instanceof Email)
			{
				$emails[] = $dto->email->toLogin();
			}
		});

		return $emails;
	}

	public function getPhones(): array
	{
		$phones = [];

		$this->forEach(function (UserInvitationDto $dto) use (&$phones) {
			if ($dto->phone instanceof Phone)
			{
				$phones[] = $dto->phone->defaultFormat();
			}
		});

		return $phones;
	}
}

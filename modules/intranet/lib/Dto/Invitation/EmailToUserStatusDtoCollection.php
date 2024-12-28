<?php

namespace Bitrix\Intranet\Dto\Invitation;

use Bitrix\Intranet\Entity\Collection\BaseCollection;

/**
 * @extends BaseCollection<EmailToUserStatusDto>
 */
class EmailToUserStatusDtoCollection extends BaseCollection implements \JsonSerializable
{
	/**
	 * @inheritDoc
	 */
	protected static function getItemClassName(): string
	{
		return EmailToUserStatusDto::class;
	}

	public function jsonSerialize(): array
	{
		return $this->map(fn(EmailToUserStatusDto $dto) => $dto->jsonSerialize());
	}
}

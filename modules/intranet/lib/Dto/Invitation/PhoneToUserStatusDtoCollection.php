<?php

namespace Bitrix\Intranet\Dto\Invitation;

use Bitrix\Intranet\Entity\Collection\BaseCollection;

/**
 * @extends BaseCollection<PhoneToUserStatusDto>
 */
class PhoneToUserStatusDtoCollection extends BaseCollection implements \JsonSerializable
{
	/**
	 * @inheritDoc
	 */
	protected static function getItemClassName(): string
	{
		return PhoneToUserStatusDto::class;
	}

	public function jsonSerialize(): array
	{
		return $this->map(fn(PhoneToUserStatusDto $dto) => $dto->jsonSerialize());
	}
}

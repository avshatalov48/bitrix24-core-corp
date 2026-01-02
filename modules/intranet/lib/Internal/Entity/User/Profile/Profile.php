<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Entity\User\Profile;

use Bitrix\Main\Entity\EntityInterface;

class Profile implements EntityInterface
{
	public function __construct(
		public readonly BaseInfo $baseInfo,
		public readonly FieldSectionCollection $fieldSectionCollection,
	)
	{}

	public function getId(): int
	{
		return $this->baseInfo->getId();
	}
}

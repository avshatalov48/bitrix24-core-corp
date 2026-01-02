<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Provider;

use Bitrix\Tasks\V2\Internal\Repository\UserOptionRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Service\OptionDictionary;

class AhaMomentProvider
{
	public function __construct(private UserOptionRepositoryInterface $userOptionRepository)
	{

	}

	public function get(int $userId): array
	{
		$ahaAuditorsCompactShow = !$this->isShown($userId, OptionDictionary::AhaAuditorsInCompactForm);

		return [
			OptionDictionary::AhaAuditorsInCompactForm->value => $ahaAuditorsCompactShow,
		];
	}

	private function isShown(int $userId, OptionDictionary $optionDictionary): bool
	{
		return $this->userOptionRepository->isSet(
			optionDictionary: $optionDictionary,
			userId: $userId,
		);
	}
}

<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller;

use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\CloseSession;
use Bitrix\Tasks\V2\Internal\Repository\UserOptionRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Service\OptionDictionary;

class Option extends BaseController
{
	/**
	 * @ajaxAction tasks.V2.Option.setBool
	 */
	#[CloseSession]
	public function setBoolAction(
		string $optionName,
		bool $value,
		UserOptionRepositoryInterface $userOptionRepository,
	): array|null
	{
		$option = OptionDictionary::tryFrom($optionName);

		if (!$option)
		{
			return null;
		}

		$userOptionRepository->add(
			optionDictionary: $option,
			userId: $this->context->getUserId(),
			value: $value ? 'true' : 'false',
		);

		return [];
	}
}

<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1;

use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\OptionDictionary;
use Bitrix\Booking\Internals\Repository\OptionRepositoryInterface;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Request;

class Option extends BaseController
{
	private int $userId;
	private OptionRepositoryInterface $optionRepository;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);

		$this->userId = (int)CurrentUser::get()->getId();
		$this->optionRepository = Container::getOptionRepository();
	}

	public function setBoolAction(string $optionName, bool $value): void
	{
		$this->handleRequest(function() use ($optionName, $value)
		{
			$option = OptionDictionary::tryFrom($optionName);
			if (!$option)
			{
				return;
			}

			$this->optionRepository->set(
				userId: $this->userId,
				option: $option,
				value: $value ? 'true' : 'false',
			);
		});
	}
}

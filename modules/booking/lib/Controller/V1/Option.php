<?php

declare(strict_types=1);

namespace Bitrix\Booking\Controller\V1;

use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Exception\ErrorBuilder;
use Bitrix\Booking\Internals\Exception\Exception;
use Bitrix\Booking\Internals\Service\OptionDictionary;
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

	public function setBoolAction(string $optionName, bool $value): array|null
	{
		try
		{
			$option = OptionDictionary::tryFrom($optionName);
			if (!$option)
			{
				$this->addError(ErrorBuilder::build('Unknown option'));

				return null;
			}

			$this->optionRepository->set(
				userId: $this->userId,
				option: $option,
				value: $value ? 'true' : 'false',
			);

			return [];
		}
		catch (Exception $e)
		{
			$this->addError(ErrorBuilder::buildFromException($e));

			return null;
		}
	}
}

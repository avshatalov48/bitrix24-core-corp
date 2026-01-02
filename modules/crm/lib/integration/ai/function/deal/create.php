<?php

namespace Bitrix\Crm\Integration\AI\Function\Deal;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Integration\AI\Contract\AIFunction;
use Bitrix\Crm\Integration\AI\Function\Deal\Dto\CreateParameters;
use Bitrix\Crm\Item\Deal;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\Operation;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Crm\Result;
use CCrmOwnerType;

final class Create implements AIFunction
{
	private readonly Factory $factory;

	public function __construct(
		private readonly int $currentUserId,
	)
	{
		$this->factory = Container::getInstance()->getFactory(CCrmOwnerType::Deal);
	}

	public function isAvailable(): bool
	{
		return true;
	}

	public function invoke(...$args): \Bitrix\Main\Result
	{
		$parameters = new CreateParameters($args);
		if ($parameters->hasValidationErrors())
		{
			return Result::fail($parameters->getValidationErrors());
		}

		$data = [
			Deal::FIELD_NAME_TITLE => $parameters->title,
			Deal::FIELD_NAME_CATEGORY_ID => $parameters->categoryId,
		];

		/** @var Deal $deal */
		$deal = $this->factory->createItem($data);

		return $this->getAddOperation($deal)->launch();
	}

	private function getAddOperation(Deal $deal): Operation\Add
	{
		$operation = $this->factory->getAddOperation($deal);
		$operation
			->getContext()
			->setUserId($this->currentUserId);

		$operation
			->disableCheckFields()
			->disableCheckRequiredUserFields();

		return $operation;
	}
}

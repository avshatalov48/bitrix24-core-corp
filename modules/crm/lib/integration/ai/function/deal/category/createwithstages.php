<?php

namespace Bitrix\Crm\Integration\AI\Function\Deal\Category;

use Bitrix\Crm\Integration\AI\Contract\AIFunction;
use Bitrix\Crm\Integration\AI\Function\Deal\Dto\Category\CreateWithStagesParameters;
use Bitrix\Crm\Integration\AI\Function\Deal\Dto\Category\Stage;
use Bitrix\Crm\Result;

final class CreateWithStages implements AIFunction
{
	private readonly Create $createCategory;
	private readonly UpdateStageList $updateStageList;

	public function __construct(
		private readonly int $currentUserId,
	)
	{
		$this->createCategory = new Create($this->currentUserId);
		$this->updateStageList = new UpdateStageList($this->currentUserId);
	}

	public function isAvailable(): bool
	{
		return true;
	}

	public function invoke(...$args): \Bitrix\Main\Result
	{
		$parameters = new CreateWithStagesParameters($args);
		if ($parameters->hasValidationErrors())
		{
			return Result::fail($parameters->getValidationErrors());
		}

		$createResult = $this->createCategory->invoke(name: $parameters->name);
		if (!$createResult->isSuccess())
		{
			return $createResult;
		}

		$categoryId = $createResult->getData()['id'];
		$stages = array_map(static fn (Stage $stage) => $stage->toArray(), $parameters->stages);

		$updateResult = $this->updateStageList->invoke(
			categoryId: $categoryId,
			stages: $stages,
		);

		if (!$updateResult->isSuccess())
		{
			return $updateResult;
		}

		return Result::success(id: $categoryId);
	}
}

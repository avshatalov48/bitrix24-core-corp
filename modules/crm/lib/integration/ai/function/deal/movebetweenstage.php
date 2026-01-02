<?php

namespace Bitrix\Crm\Integration\AI\Function\Deal;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Integration\AI\Contract\AIFunction;
use Bitrix\Crm\Integration\AI\Function\Deal\Dto\MoveBetweenStageParameters;
use Bitrix\Crm\Item\Deal;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\Operation\Update;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Crm\Result;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Localization\Loc;
use CCrmOwnerType;

final class MoveBetweenStage implements AIFunction
{
	private readonly Factory $factory;

	private const LIMIT = 100;

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

	public function invoke(...$args): Result
	{
		$parameters = new MoveBetweenStageParameters($args);
		if ($parameters->hasValidationErrors())
		{
			return Result::fail($parameters->getValidationErrors());
		}

		$fromStage = $this->factory->getStageFromCategory($parameters->categoryId, $parameters->from);
		if ($fromStage === null)
		{
			return Result::fail(Loc::getMessage('CRM_INTEGRATION_AI_FUNCTION_DEAL_MOVE_BETWEEN_STAGE_FROM_STAGE_NOT_FOUND_ERROR'), 'STAGE_NOT_FOUND');
		}

		$toStage = $this->factory->getStageFromCategory($parameters->categoryId, $parameters->to);
		if ($toStage === null)
		{
			return Result::fail(Loc::getMessage('CRM_INTEGRATION_AI_FUNCTION_DEAL_MOVE_BETWEEN_STAGE_TO_STAGE_NOT_FOUND_ERROR'), 'STAGE_NOT_FOUND');
		}

		$errors = new ErrorCollection();
		foreach ($this->getDeals($fromStage->getStatusId()) as $deal)
		{
			$deal
				->setStageId($toStage->getStatusId())
				->setStageSemanticId($toStage->getSemantics() ?? PhaseSemantics::PROCESS);

			$result = $this->getUpdateOperation($deal)->launch();
			if (!$result->isSuccess())
			{
				$errors->add($result->getErrors());
			}
		}

		if (!$errors->isEmpty())
		{
			return Result::fail(ErrorCode::groupErrorsByMessage($errors));
		}

		return Result::success();
	}

	/**
	 * @return Deal[]
	 */
	private function getDeals(string $stageId): array
	{
		$params = [
			'select' => [
				'*',
			],
			'filter' => [
				'=' . Deal::FIELD_NAME_STAGE_ID => $stageId,
			],
			'limit' => self::LIMIT,
			'order' => [
				'ID' => 'DESC',
			],
		];

		return $this->factory->getItems($params);
	}

	private function getUpdateOperation(Deal $deal): Update
	{
		$operation = $this->factory->getUpdateOperation($deal);
		$operation
			->getContext()
			->setUserId($this->currentUserId);

		$operation
			->disableCheckFields()
			->disableCheckRequiredUserFields();

		return $operation;
	}
}

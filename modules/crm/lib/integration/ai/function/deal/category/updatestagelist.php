<?php

namespace Bitrix\Crm\Integration\AI\Function\Deal\Category;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\EO_Status;
use Bitrix\Crm\EO_Status_Collection;
use Bitrix\Crm\Integration\AI\Contract\AIFunction;
use Bitrix\Crm\Integration\AI\Function\Deal\Dto\Category\UpdateStageListParameters;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Result;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Crm\Stage\DefaultProcessColorGenerator;
use Bitrix\Main\Application;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\ORM\Objectify\State;
use CCrmOwnerType;
use CCrmStatus;
use Exception;
use RuntimeException;
use Throwable;

final class UpdateStageList implements AIFunction
{
	private readonly Factory $factory;
	private readonly UserPermissions $permissions;
	private readonly Connection $connection;
	private readonly DefaultProcessColorGenerator $stageColorGenerator;

	private const SORT_STEP = 10;

	public function __construct(
		private readonly int $currentUserId,
	)
	{
		$this->factory = Container::getInstance()->getFactory(CCrmOwnerType::Deal);
		$this->permissions = Container::getInstance()->getUserPermissions($this->currentUserId);
		$this->stageColorGenerator = new DefaultProcessColorGenerator();
		$this->connection = Application::getConnection();
	}

	public function isAvailable(): bool
	{
		return true;
	}

	public function invoke(...$args): Result
	{
		$parameters = new UpdateStageListParameters($args);
		if ($parameters->hasValidationErrors())
		{
			return Result::fail($parameters->getValidationErrors());
		}

		if (!$this->permissions->isAdminForEntity(CCrmOwnerType::Deal))
		{
			return Result::failAccessDenied();
		}

		try
		{
			$this->connection->startTransaction();

			$existsStageCollection = $this->factory->getStages($parameters->categoryId);
			$newStageCollection = $this->applyStageChanges($existsStageCollection, $parameters->stages);

			$this->saveStages($newStageCollection, $parameters->categoryId);
		}
		catch (Throwable $e)
		{
			$this->connection->rollbackTransaction();

			return Result::fail($e->getMessage(), $e->getCode());
		}

		$this->connection->commitTransaction();

		return Result::success();
	}

	private function applyStageChanges(EO_Status_Collection $existsStages, array $stagesToUpdate): EO_Status_Collection
	{
		$appliedStageCollection = new EO_Status_Collection();
		$lastUpdatedStage = null;

		foreach ($existsStages->getAll() as $existsStage)
		{
			$isCurrentExistsStageFinal = PhaseSemantics::isFinal($existsStage->getSemantics());
			$isStagesToUpdateEmpty = empty($stagesToUpdate);

			$shouldUpdateCurrentExistsStage = !$isStagesToUpdateEmpty && !$isCurrentExistsStageFinal;
			if ($shouldUpdateCurrentExistsStage)
			{
				$currentStage = array_shift($stagesToUpdate);
				$existsColor = $existsStage->getColor() ?: null;
				$defaultColor = $this->stageColorGenerator->generate();

				$existsStage
					->setName($currentStage->name)
					->setColor($currentStage->color ?? $existsColor ?? $defaultColor);

				$appliedStageCollection->add($existsStage);
				$lastUpdatedStage = $existsStage;

				continue;
			}

			$shouldAddRemainingNewStages = !$isStagesToUpdateEmpty && $isCurrentExistsStageFinal;
			if ($shouldAddRemainingNewStages)
			{
				foreach ($stagesToUpdate as $stage)
				{
					$defaultColor = $this->stageColorGenerator->generate();

					$appliedStageCollection->add(
						(new EO_Status())
							->setName($stage->name)
							->setColor($stage->color ?? $defaultColor),
					);
				}

				$stagesToUpdate = [];

				$appliedStageCollection->add($existsStage);

				continue;
			}

			$shouldDeleteCurrentExistsStage = $isStagesToUpdateEmpty && !$isCurrentExistsStageFinal;
			if ($shouldDeleteCurrentExistsStage)
			{
				// changes to the last applied stage are transferred to the system process stage
				if ($existsStage->getSystem())
				{
					$appliedStageCollection->add(
						$existsStage
							->setName($lastUpdatedStage->getName())
							->setColor($lastUpdatedStage->getColor()),
					);

					$deleteResult = $lastUpdatedStage->delete();
					if (!$deleteResult->isSuccess())
					{
						$error = $deleteResult->getError() ?? ErrorCode::getGeneralError();

						throw new RuntimeException($error->getMessage());
					}

					continue;
				}

				$deleteResult = $existsStage->delete();
				if (!$deleteResult->isSuccess())
				{
					$error = $deleteResult->getError() ?? ErrorCode::getGeneralError();

					throw new RuntimeException($error->getMessage());
				}

				continue;
			}

			$appliedStageCollection->add($existsStage);
		}

		$currentStageSort = self::SORT_STEP;
		foreach ($appliedStageCollection->getAll() as $stage)
		{
			$stage->setSort($currentStageSort);
			$currentStageSort += self::SORT_STEP;
		}

		return $appliedStageCollection;
	}

	/**
	 * @throws NotSupportedException
	 * @throws Exception
	 */
	private function saveStages(EO_Status_Collection $stages, int $categoryId): void
	{
		$stageService = $this->getStageService($categoryId);

		foreach (array_reverse($stages->getAll()) as $stage)
		{
			$fields = [
				'NAME' => $stage->getName(),
				'COLOR' => $stage->getColor(),
				'SORT' => $stage->getSort(),
			];

			if ($stage->state === State::RAW)
			{
				$addResult = $stageService->Add($fields);
				if ($addResult === false)
				{
					$errorMessage = $stageService->GetLastError() ?? ErrorCode::getGeneralError()->getMessage();

					throw new RuntimeException($errorMessage);
				}

				continue;
			}

			$updateResult = $stageService->Update($stage->getId(), $fields);
			if ($updateResult === false)
			{
				$errorMessage = $stageService->GetLastError() ?? ErrorCode::getGeneralError()->getMessage();

				throw new RuntimeException($errorMessage);
			}
		}
	}

	private function getStageService(int $categoryId): CCrmStatus
	{
		$entityId = $this->factory->getStagesEntityId($categoryId);

		return new CCrmStatus($entityId);
	}
}

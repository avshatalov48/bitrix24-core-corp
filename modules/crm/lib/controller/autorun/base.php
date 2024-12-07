<?php

namespace Bitrix\Crm\Controller\Autorun;

use Bitrix\Crm\Controller\Autorun\Dto\PreparedData;
use Bitrix\Crm\Controller\Autorun\Dto\Progress;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Item;
use Bitrix\Crm\ListEntity;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\Router;
use Bitrix\Main\Application;
use Bitrix\Main\Data\LocalStorage\SessionLocalStorage;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Error;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Result;
use Bitrix\Main\Text\StringHelper;
use Bitrix\Main\Type\ArrayHelper;

abstract class Base extends \Bitrix\Crm\Controller\Base
{
	private const STEP_LIMIT = 10;

	private Connection $connection;
	private Router $router;

	private SessionLocalStorage $dataStorage;
	private SessionLocalStorage $progressStorage;
	protected Progress $progress;
	private PreparedData $data;

	final protected function getDefaultPreFilters(): array
	{
		$filters = parent::getDefaultPreFilters();
		$filters[] = new ActionFilter\Scope(ActionFilter\Scope::AJAX);

		return $filters;
	}

	final protected function init(): void
	{
		parent::init();

		$prefix = 'crm_batch_' . $this->getSessionKeyPrefix();

		$this->dataStorage = Application::getInstance()->getLocalSession("{$prefix}_data");
		$this->progressStorage = Application::getInstance()->getLocalSession("{$prefix}_progress");
		$this->connection = Application::getConnection();
		$this->router = Container::getInstance()->getRouter();
	}

	private function getSessionKeyPrefix(): string
	{
		$reflection = new \ReflectionClass($this);

		// \Bitrix\Crm\Controller\Autorun\SetStage -> SetStage -> set_stage
		return StringHelper::camel2snake($reflection->getShortName());
	}

	final public function prepareAction(array $params): ?array
	{
		$entityTypeId = (int)($params['entityTypeId'] ?? null);
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory || !\CCrmOwnerType::isUseFactoryBasedApproach($entityTypeId) || !$this->isEntityTypeSupported($factory))
		{
			$this->addError(ErrorCode::getEntityTypeNotSupportedError($entityTypeId));

			return null;
		}

		$gridId = (string)($params['gridId'] ?? null);
		if (empty($gridId))
		{
			$this->addError(ErrorCode::getRequiredArgumentMissingError('gridId'));

			return null;
		}

		$filter = $this->prepareFilter($factory->getEntityTypeId(), $gridId, $params);

		$hash = $this->calculateHash($entityTypeId, $gridId, $filter);

		$data = $this->prepareData($hash, $gridId, $entityTypeId, $filter, $params, $factory);

		if ($data->hasValidationErrors())
		{
			$this->addErrors($data->getValidationErrors()->toArray());

			return null;
		}

		$this->dataStorage->set($hash, $data->toArray());

		if ($this->progressStorage->get($hash))
		{
			unset($this->progressStorage[$hash]);
		}

		return [ 'hash' => $hash ];
	}

	protected function isEntityTypeSupported(Factory $factory): bool
	{
		return true;
	}

	private function prepareFilter(int $entityTypeId, string $gridId, array $params): ?array
	{
		if (!empty($params['entityIds']) && is_array($params['entityIds']))
		{
			$entityIds = $params['entityIds'];
			ArrayHelper::normalizeArrayValuesByInt($entityIds);

			if (empty($entityIds))
			{
				$this->addError(new Error('entityIds should be a int[]', ErrorCode::INVALID_ARG_VALUE));

				return null;
			}

			return ['@ID' => $entityIds];
		}

		$filterFactory = Container::getInstance()->getFilterFactory();
		$filter = $filterFactory->getFilter($filterFactory::getSettingsByGridId($entityTypeId, $gridId));

		$rawUIFilter = (!empty($params['filter']) && is_array($params['filter'])) ? $params['filter'] : null;
		if (is_array($rawUIFilter))
		{
			return $filter->getValue($rawUIFilter);
		}

		return $filterFactory->getFilterValue($filter);
	}

	private function calculateHash(int $entityTypeId, string $gridId, array $filter): string
	{
		// normalize filter for hash computation
		ksort($filter, SORT_STRING);

		return md5(
			\CCrmOwnerType::ResolveName($entityTypeId)
			. ':'
			. mb_strtoupper($gridId)
			. ':'
			. implode(',', array_map(fn($k, $v) => "{$k}:{$v}", array_keys($filter), $filter))
		);
	}

	protected function prepareData(
		string $hash,
		string $gridId,
		int $entityTypeId,
		array $filter,
		array $params,
		Factory $factory
	): Dto\PreparedData
	{
		$class = $this->getPreparedDataDtoClass();

		return new $class([
			'hash' => $hash,
			'gridId' => $gridId,
			'entityTypeId' => $entityTypeId,
			'filter' => $filter,
		]);
	}

	/**
	 * @return class-string<Dto\PreparedData>
	 */
	protected function getPreparedDataDtoClass(): string
	{
		return Dto\PreparedData::class;
	}

	final public function processAction(array $params): ?array
	{
		$hash = (string)($params['hash'] ?? '');
		if (empty($hash))
		{
			$this->addError(ErrorCode::getRequiredArgumentMissingError('hash'));

			return null;
		}

		if (!$this->initProcessDataByHash($hash))
		{
			return null;
		}

		$factory = Container::getInstance()->getFactory($this->data->entityTypeId);
		if (!$factory || !\CCrmOwnerType::isUseFactoryBasedApproach($this->data->entityTypeId))
		{
			throw new InvalidOperationException(
				"Factory not found for type {$this->data->entityTypeId}. It seems that 'prepare' action added invalid data",
			);
		}

		$this->initProgressByHash($hash);

		if ($this->progress->totalCount === null)
		{
			$this->progress->totalCount = $this->getItemsCount($factory);
		}

		$itemsToProcess = $this->getItemsToProcess($factory);

		$this->processItems($factory, $itemsToProcess);

		$isCompleted = count($itemsToProcess) < self::STEP_LIMIT;
		if ($isCompleted)
		{
			unset($this->dataStorage[$hash]);
			unset($this->progressStorage[$hash]);
		}
		else
		{
			$this->progressStorage->set($hash, $this->progress->toArray());
		}

		$response = [
			'status' => $isCompleted ? 'COMPLETED' : 'PROGRESS',
			'processedItems' => $this->progress->processedCount,
			'totalItems' => $this->progress->totalCount,
		];

		if ($this->progress->hasErrors())
		{
			$response['errors'] = $this->progress->getErrors();
		}

		$this->sendAnalyticsData($this->data, $response);

		return $response;
	}

	/**
	 * @param string $hash
	 *
	 * @return bool
	 * @throws InvalidOperationException
	 */
	private function initProcessDataByHash(string $hash): bool
	{
		$dataArray = $this->dataStorage->get($hash);
		if (!is_array($dataArray))
		{
			$this->addError(ErrorCode::getNotFoundError());

			return false;
		}

		$class = $this->getPreparedDataDtoClass();

		$this->data = new $class($dataArray);
		if ($this->data->hasValidationErrors())
		{
			$errorMessages = array_map(
				fn(Error $error) => $error->getMessage(),
				$this->data->getValidationErrors()->toArray(),
			);

			throw new InvalidOperationException(
				'Invalid prepared data in session: ' . implode('|', $errorMessages),
			);
		}

		return true;
	}

	private function initProgressByHash(string $hash): void
	{
		$this->progress = new Dto\Progress($this->progressStorage->get($hash));
		if ($this->progress->hasValidationErrors())
		{
			$errorMessages = array_map(
				fn(Error $error) => $error->getMessage(),
				$this->progress->getValidationErrors()->toArray(),
			);

			throw new InvalidOperationException(
				'Invalid progress data in session: ' . implode('|', $errorMessages),
			);
		}
	}

	private function getItemsCount(Factory $factory): int
	{
		$filter = $this->data->filter->filter;

		if ($this->isUseOrmApproach($factory))
		{
			return $factory->getItemsCount($filter);
		}

		return ListEntity\Entity::getInstance($factory->getEntityName())->getCount($filter);
	}

	private function isUseOrmApproach(Factory $factory): bool
	{
		return \CCrmOwnerType::isUseDynamicTypeBasedApproach($factory->getEntityTypeId());
	}

	private function getItemsToProcess(Factory $factory): array
	{
		$filter = $this->data->filter->filter;
		if ($this->progress->lastId > 0)
		{
			$filter['>ID'] = $this->progress->lastId;
		}

		if ($this->isUseOrmApproach($factory))
		{
			return $this->getItemsToProcessViaOrm($factory, $filter);
		}

		return $this->getItemsToProcessViaListEntity($factory, $filter);
	}

	private function getItemsToProcessViaOrm(Factory $factory, array $filter): array
	{
		return $factory->getItems([
			'select' => ['*'],
			'filter' => $filter,
			'order' => [
				'ID' => 'ASC',
			],
			'limit' => self::STEP_LIMIT,
		]);
	}

	private function getItemsToProcessViaListEntity(Factory $factory, array $filter): array
	{
		$dbResult = ListEntity\Entity::getInstance($factory->getEntityName())->getItems([
			'select' => ['ID'],
			'filter' => $filter,
			'order' => [
				'ID' => 'ASC',
			],
			'limit' => self::STEP_LIMIT,
			'offset' => 0,
		]);

		$ids = [];
		while ($row = $dbResult->Fetch())
		{
			$ids[] = $row['ID'];
		}

		if (empty($ids))
		{
			return [];
		}

		return $factory->getItems([
			'select' => ['*'],
			'filter' => [
				'@ID' => $ids,
			],
			'order' => [
				'ID' => 'ASC',
			],
		]);
	}

	private function processItems(Factory $factory, array $itemsToProcess): void
	{
		$itemsThatShouldBeProcessed = $this->filterOutSkippableItems($factory, $itemsToProcess, $this->data);

		foreach ($itemsToProcess as $item)
		{
			$this->progress->processedCount++;
			$this->progress->lastId = $item->getId();

			if (!in_array($item, $itemsThatShouldBeProcessed, true))
			{
				continue;
			}

			if ($this->isWrapItemProcessingInTransaction())
			{
				$this->connection->startTransaction();
			}

			$result = $this->processItem($factory, $item, $this->data);

			if ($result->isSuccess())
			{
				$this->progress->addSuccessId($item->getId());
			}

			if ($result->isSuccess() && $this->isWrapItemProcessingInTransaction())
			{
				$this->connection->commitTransaction();
			}
			elseif (!$result->isSuccess())
			{
				if ($this->isWrapItemProcessingInTransaction())
				{
					$this->connection->rollbackTransaction();
				}

				$this->progress->addErrorId($item->getId());

				foreach ($result->getErrors() as $error)
				{
					$this->progress->addError(
						new Error(
							$error->getMessage(),
							$error->getCode(),
							[
								'info' => [
									'title' => $item->getHeading(),
									'showUrl' => $this->router->getItemDetailUrl($item->getEntityTypeId(), $item->getId()),
								],
							],
						)
					);
				}
			}
		}
	}

	protected function filterOutSkippableItems(Factory $factory, array $itemsToProcess, PreparedData $data): array
	{
		return array_filter($itemsToProcess, fn(Item $item) => !$this->isItemShouldBeSkipped($factory, $item, $data));
	}

	/**
	 * Is item should be skipped since there is no sense in processing it.
	 * For example, if this action changes item stage, and item is already at that stage, there is no sense processing
	 * it.
	 *
	 * Note that this method should not check permissions or data correctness.
	 * Its designed only for performance optimization to skip unneeded work
	 *
	 */
	protected function isItemShouldBeSkipped(Factory $factory, Item $item, PreparedData $data): bool
	{
		return false;
	}

	/**
	 * Returns true if `$this->processItem` should be wrapped with transaction. You can return `false` from this method
	 * if you want to manage transaction yourself.
	 *
	 * But it's highly recommended that you use some form of transaction in `$this->processItem` anyway to maintain
	 * consistency, even if you decide to return `false` from this method.
	 *
	 * @return bool
	 */
	protected function isWrapItemProcessingInTransaction(): bool
	{
		return true;
	}

	/**
	 * Do the work here. Should check all permissions necessary
	 *
	 * @param Factory $factory
	 * @param Item $item
	 * @param PreparedData $data
	 *
	 * @return Result
	 */
	abstract protected function processItem(Factory $factory, Item $item, PreparedData $data): Result;

	final public function cancelAction(array $params): ?array
	{
		$hash = $params['hash'] ?? '';
		if (empty($hash))
		{
			$this->addError(ErrorCode::getRequiredArgumentMissingError('hash'));
			return null;
		}

		unset($this->dataStorage[$hash], $this->progressStorage[$hash]);

		return [ 'hash' => $hash ];
	}

	protected function sendAnalyticsData(PreparedData $data, array $response): void
	{
	}
}

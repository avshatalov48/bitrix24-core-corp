<?php

namespace Bitrix\BIConnector\ExternalSource;

use Bitrix\Main;
use Bitrix\BIConnector;
use Bitrix\BIConnector\Integration\Superset\Integrator\Integrator;
use Bitrix\BIConnector\Integration\Superset\Integrator\Request\IntegratorResponse;

final class SupersetIntegration
{
	private Integrator $integrator;

	public function __construct()
	{
		$this->integrator = Integrator::getInstance();
	}

	/**
	 * Adds new dataset in superset
	 *
	 * @param Internal\ExternalDataset $dataset
	 * @return Main\Result
	 */
	public function createDataset(Internal\ExternalDataset $dataset): Main\Result
	{
		$result = new Main\Result();

		$response = $this->integrator->createDataset(['name' => $dataset->getName()]);
		if ($response->hasErrors())
		{
			$result->addError(new Main\Error(
				Main\Localization\Loc::getMessage('SUPERSET_INTEGRATION_CREATE_DATASET_ERROR'))
			);

			return $result;
		}

		$responseData = $response->getData();
		$updateResult = Internal\ExternalDatasetTable::update($dataset->getId(), ['EXTERNAL_ID' => $responseData['id']]);
		if (!$updateResult->isSuccess())
		{
			$result->addErrors($updateResult->getErrors());
		}

		return $result;
	}

	/**
	 * Deletes dataset in superset
	 *
	 * @param Internal\ExternalDataset $dataset
	 * @return Main\Result
	 */
	public function deleteDataset(Internal\ExternalDataset $dataset): Main\Result
	{
		$result = new Main\Result();

		$id = $dataset->getExternalId();
		if (!$id)
		{
			return $result;
		}

		$response = $this->integrator->deleteDataset($id);
		if ($response->hasErrors())
		{
			if ($response->getStatus() === IntegratorResponse::STATUS_NO_ACCESS)
			{
				$result->addError(new Main\Error(
						Main\Localization\Loc::getMessage('SUPERSET_INTEGRATION_DELETE_DATASET_PERMISSION_ERROR'),
						IntegratorResponse::STATUS_NO_ACCESS
					),
				);
			}
			else
			{
				$result->addError(new Main\Error(
						Main\Localization\Loc::getMessage('SUPERSET_INTEGRATION_DELETE_DATASET_ERROR'),
						$response->getStatus()
					)
				);
			}
		}

		return $result;
	}

	/**
	 * Gets dataset url for creating chart
	 *
	 * @param Internal\ExternalDataset $dataset
	 * @return Main\Result
	 */
	public function getDatasetUrl(Internal\ExternalDataset $dataset): Main\Result
	{
		$result = new Main\Result();

		if (!$dataset->getExternalId())
		{
			$result->addError(new Main\Error(
				Main\Localization\Loc::getMessage('SUPERSET_INTEGRATION_DATASET_NOT_FOUND_ERROR')
			));

			return $result;
		}

		$response = $this->integrator->getDatasetUrl($dataset->getExternalId());
		if ($response->hasErrors())
		{
			$result->addError(new Main\Error(
				Main\Localization\Loc::getMessage('SUPERSET_INTEGRATION_DATASET_GET_URL_ERROR'))
			);

			return $result;
		}

		$responseData = $response->getData();
		$result->setData([
			'url' => $responseData['url'],
		]);

		return $result;
	}

	/**
	 * Gets dataset from superset
	 *
	 * @param Internal\ExternalDataset $dataset
	 * @return Main\Result
	 */
	public function loadDataset(Internal\ExternalDataset $dataset): Main\Result
	{
		$result = new Main\Result();

		$id = $dataset->getExternalId();
		if (!$id)
		{
			$result->addError(new Main\Error(
				Main\Localization\Loc::getMessage('SUPERSET_INTEGRATION_DATASET_NOT_FOUND_ERROR')
			));

			return $result;
		}

		$response = $this->integrator->getDatasetById($id);
		if ($response->hasErrors())
		{
			$result->addErrors($response->getErrors());

			return $result;
		}

		$result->setData($response->getData());

		return $result;
	}

	/**
	 * Gets datasets from superset
	 *
	 * @param Internal\ExternalDatasetCollection $datasetCollection
	 * @return Main\Result
	 */
	public function loadDatasetList(Internal\ExternalDatasetCollection $datasetCollection): Main\Result
	{
		$result = new Main\Result();

		$ids = $datasetCollection->getExternalIdList();
		$ids = array_filter($ids, static fn ($id) => $id > 0);

		$response = $this->integrator->getDatasetList($ids);
		if ($response->hasErrors())
		{
			$result->addErrors($response->getErrors());

			return $result;
		}

		$result->setData($response->getData());

		return $result;
	}

	public function updateDataset(Internal\ExternalDataset $dataset): Main\Result
	{
		$result = new Main\Result();

		$id = $dataset->getExternalId();
		if (!$id)
		{
			$result->addError(new Main\Error(
				Main\Localization\Loc::getMessage('SUPERSET_INTEGRATION_DATASET_NOT_FOUND_ERROR')
			));

			return $result;
		}

		$manager = BIConnector\Manager::getInstance();
		$service = new BIConnector\Services\ApacheSuperset($manager);
		$tableFields = $service->getTableFields($dataset->getName());

		$columns = [];
		foreach ($tableFields as $tableField)
		{
			$columns[] = [
				'name' => $tableField['ID'],
				'type' => $tableField['TYPE'],
			];
		}

		$response = $this->integrator->updateDataset($id, ['columns' => $columns]);
		if ($response->hasErrors())
		{
			$result->addErrors($response->getErrors());

			return $result;
		}

		$result->setData($response->getData());

		return $result;
	}

	/**
	 * @see DatasetManager::EVENT_ON_AFTER_ADD_DATASET
	 *
	 * @param Main\Event $event
	 * @return Main\EventResult
	 */
	public static function onAfterAddDataset(Main\Event $event): Main\EventResult
	{
		/** @var Internal\ExternalDataset $dataset */
		$dataset = $event->getParameter('dataset');

		$supersetIntegration = new SupersetIntegration();
		$createDatasetResult = $supersetIntegration->createDataset($dataset);
		if (!$createDatasetResult->isSuccess())
		{
			return new Main\EventResult(Main\EventResult::ERROR, new Main\Error($createDatasetResult->getError()));
		}

		return new Main\EventResult(Main\EventResult::SUCCESS);
	}

	/**
	 * @see DatasetManager::EVENT_ON_AFTER_UPDATE_DATASET
	 *
	 * @param Main\Event $event
	 * @return Main\EventResult
	 */
	public static function onAfterUpdateDataset(Main\Event $event): Main\EventResult
	{
		/** @var Internal\ExternalDataset $dataset */
		$dataset = $event->getParameter('dataset');

		$supersetIntegration = new SupersetIntegration();
		$updateDatasetResult = $supersetIntegration->updateDataset($dataset);
		if (!$updateDatasetResult->isSuccess())
		{
			return new Main\EventResult(Main\EventResult::ERROR, new Main\Error($updateDatasetResult->getError()));
		}

		return new Main\EventResult(Main\EventResult::SUCCESS);
	}

	/**
	 * @see DatasetManager::EVENT_ON_AFTER_DELETE_DATASET
	 *
	 * @param Main\Event $event
	 * @return Main\EventResult
	 */
	public static function onAfterDeleteDataset(Main\Event $event): Main\EventResult
	{
		foreach ($event->getResults() as $result)
		{
			if ($result->getType() === Main\EventResult::ERROR)
			{
				return new Main\EventResult(Main\EventResult::ERROR);
			}
		}

		/** @var Internal\ExternalDataset $dataset */
		$dataset = $event->getParameter('dataset');

		$supersetIntegration = new SupersetIntegration();
		$deleteDatasetResult = $supersetIntegration->deleteDataset($dataset);
		if (!$deleteDatasetResult->isSuccess())
		{
			$error = $deleteDatasetResult->getError();
			if ($error?->getCode() !== IntegratorResponse::STATUS_NOT_FOUND)
			{
				return new Main\EventResult(Main\EventResult::ERROR, new Main\Error($error));
			}
		}

		return new Main\EventResult(Main\EventResult::SUCCESS);
	}
}

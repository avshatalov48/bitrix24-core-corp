<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action;

use Bitrix\Main\Grid\Row\Action\BaseAction;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class DeleteDatasetAction extends BaseAction
{

	public static function getId(): ?string
	{
		return 'delete';
	}

	public function processRequest(HttpRequest $request): ?Result
	{
		return null;
	}

	protected function getText(): string
	{
		return Loc::getMessage('BICONNECTOR_DATASET_GRID_ACTION_DELETE') ?? '';
	}

	public function getControl(array $rawFields): ?array
	{
		$datasetId = $rawFields['ID'];
		if (!$datasetId)
		{
			return null;
		}

		$this->onclick = "BX.BIConnector.ExternalDatasetManager.Instance.deleteDataset({$datasetId})";

		return parent::getControl($rawFields);
	}
}

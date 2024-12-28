<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Action;

use Bitrix\Main\Grid\Row\Action\BaseAction;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class OpenDatasetAction extends BaseAction
{

	public static function getId(): ?string
	{
		return 'open';
	}

	public function processRequest(HttpRequest $request): ?Result
	{
		return null;
	}

	protected function getText(): string
	{
		return Loc::getMessage('BICONNECTOR_DATASET_GRID_ACTION_OPEN') ?? '';
	}

	public function getControl(array $rawFields): ?array
	{
		$datasetId = $rawFields['ID'];
		$datasetType = $rawFields['TYPE'];
		if (!$datasetId && !$datasetType)
		{
			return null;
		}

		$this->onclick = "BX.BIConnector.DatasetImport.Slider.open(\"{$datasetType}\", $datasetId)";

		return parent::getControl($rawFields);
	}
}

<?php

namespace Bitrix\Crm\Timeline\HistoryDataModel\Presenter;

use Bitrix\Crm\Item;
use Bitrix\Crm\Timeline\HistoryDataModel\Presenter;
use Bitrix\Main\Localization\Loc;

class Modification extends Presenter
{
	protected function getHistoryTitle(string $fieldName = null): string
	{
		if ($fieldName === Item::FIELD_NAME_STAGE_ID)
		{
			return (string)Loc::getMessage('CRM_TIMELINE_PRESENTER_MODIFICATION_STAGE_ID');
		}
		if ($fieldName === Item::FIELD_NAME_CATEGORY_ID)
		{
			return (string)Loc::getMessage('CRM_TIMELINE_PRESENTER_MODIFICATION_CATEGORY_ID_2');
		}

		$fieldTitle = $this->entityImplementation->getFieldTitle((string)$fieldName) ?? $fieldName;

		return (string)Loc::getMessage(
			'CRM_TIMELINE_PRESENTER_MODIFICATION_BASE_TITLE',
			['#FIELD_NAME#' => $fieldTitle]
		);
	}

	protected function prepareDataBySettingsForSpecificEvent(array $data, array $settings): array
	{
		$fieldName = $settings['FIELD'] ?? '';
		$data['MODIFIED_FIELD'] = $fieldName;

		if ($fieldName === Item::FIELD_NAME_IS_MANUAL_OPPORTUNITY)
		{
			$castToString = fn(bool $val) => $val ? 'Y' : 'N';

			$data['START'] = is_bool($settings['START']) ? $castToString($settings['START']) : $settings['START'];
			$data['FINISH'] = is_bool($settings['FINISH']) ? $castToString($settings['FINISH']) : $settings['START'];
		}
		if ($fieldName === Item::FIELD_NAME_CATEGORY_ID)
		{
			$proxyFields = [
				'START_CATEGORY_NAME',
				'FINISH_CATEGORY_NAME',
				'START_STAGE_NAME',
				'FINISH_STAGE_NAME'
			];
			foreach ($proxyFields as $field)
			{
				$data[$field] = $settings[$field];
			}
		}

		return $data;
	}
}

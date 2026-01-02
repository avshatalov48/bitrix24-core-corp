<?php

namespace Bitrix\Crm\Timeline\HistoryDataModel\Presenter;

use Bitrix\Crm\Item;
use Bitrix\Crm\Timeline\HistoryDataModel\Presenter;
use Bitrix\Main\Localization\Loc;

class Modification extends Presenter
{
	protected function getHistoryTitle(?string $fieldName = null, array $settings = []): string
	{
		if ($fieldName === Item::FIELD_NAME_STAGE_ID)
		{
			return (string)Loc::getMessage('CRM_TIMELINE_PRESENTER_MODIFICATION_STAGE_ID');
		}

		if ($fieldName === Item::FIELD_NAME_CATEGORY_ID)
		{
			return (string)Loc::getMessage('CRM_TIMELINE_PRESENTER_MODIFICATION_CATEGORY_ID_2');
		}

		if ($fieldName === 'NEXT_EXECUTION')
		{
			$code = (
				empty($settings['START'])
					? 'CRM_TIMELINE_PRESENTER_MODIFICATION_RECURRING_NEXT_EXECUTION'
					: 'CRM_TIMELINE_PRESENTER_MODIFICATION_RECURRING_NEXT_EXECUTION_CHANGED'
			);

			return (string)Loc::getMessage($code);
		}

		// @todo maybe need change, now by analogy with deals
		if ($fieldName === 'ACTIVE')
		{
			$messageCode = (
				$settings['FINISH'] !== 'Y'
					? 'CRM_TIMELINE_PRESENTER_MODIFICATION_RECURRING_NOT_ACTIVE'
					: 'CRM_TIMELINE_PRESENTER_MODIFICATION_RECURRING_ACTIVE'
			);

			// @todo support other dynamic entity types
			if ($this->entityImplementation->getEntityTypeId() === \CCrmOwnerType::SmartInvoice)
			{
				$messageCode .= '_SMART_INVOICE';
			}

			return (string)Loc::getMessage($messageCode);
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

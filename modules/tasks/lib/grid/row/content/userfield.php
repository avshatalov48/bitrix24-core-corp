<?php
namespace Bitrix\Tasks\Grid\Row\Content;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Grid\Row\Content;

/**
 * Class UserField
 *
 * @package Bitrix\Tasks\Grid\Row\Content
 */
class UserField extends Content
{
	public static function prepare(array $row, array $parameters): string
	{
		$fieldName = $parameters['USER_FIELD_NAME'];

		if ($fieldName === 'UF_CRM_TASK')
		{
			return Content\UserField\Crm::prepare($row, $parameters);
		}

		$fieldValue = $row[$fieldName];
		$userFieldData = $parameters['UF'][$fieldName];

		if ($userFieldData['USER_TYPE_ID'] !== 'boolean' && empty($fieldValue) && $fieldValue !== '0')
		{
			return Loc::getMessage('TASKS_GRID_ROW_CONTENT_USER_FIELD_NOT_PRESENT');
		}

		if ($userFieldData['USER_TYPE_ID'] === 'boolean')
		{
			$messagePostfix = (empty($fieldValue) ? 'NO' : 'YES');
			$fieldValue = Loc::getMessage("TASKS_GRID_ROW_CONTENT_USER_FIELD_BOOLEAN_{$messagePostfix}");
		}

		if (is_array($fieldValue))
		{
			return implode(', ', array_map(
				static function($item) {
					return htmlspecialcharsbx($item);
				},
				$fieldValue
			));
		}

		return htmlspecialcharsbx($fieldValue);
	}
}
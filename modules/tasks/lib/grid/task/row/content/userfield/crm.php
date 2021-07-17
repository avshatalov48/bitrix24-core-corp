<?php
namespace Bitrix\Tasks\Grid\Task\Row\Content\UserField;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Grid\Task\Row\Content\UserField;
use CCrmOwnerType;
use CCrmOwnerTypeAbbr;

/**
 * Class Crm
 *
 * @package Bitrix\Tasks\Grid\Task\Row\Content\UserField
 */
class Crm extends UserField
{
	public function prepare(): string
	{
		$row = $this->getRowData();

		if (empty($row['UF_CRM_TASK']))
		{
			return Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_USER_FIELD_CRM_NOT_PRESENT');
		}

		sort($row['UF_CRM_TASK']);

		$collection = [];
		foreach ($row['UF_CRM_TASK'] as $value)
		{
			[$type, $id] = explode('_', $value);
			$typeId = CCrmOwnerType::ResolveID(CCrmOwnerTypeAbbr::ResolveName($type));
			$title = CCrmOwnerType::GetCaption($typeId, $id);
			$url = CCrmOwnerType::GetEntityShowPath($typeId, $id);

			if (!isset($collection[$type]))
			{
				$collection[$type] = [];
			}

			if ($title)
			{
				$safeTitle = htmlspecialcharsbx($title);
				$collection[$type][] = "<a href=\"{$url}\">{$safeTitle}</a>";
			}
		}

		$html = [];
		if ($collection)
		{
			$html[] = '<div class="tasks-list-crm-div">';
			$previousType = null;

			foreach ($collection as $type => $items)
			{
				if (empty($items))
				{
					continue;
				}

				$html[] = '<div class="tasks-list-crm-div-wrapper">';
				if ($type !== $previousType)
				{
					$html[] = '<span class="tasks-list-crm-div-type">'
						.Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_USER_FIELD_CRM_CRM_TYPE_'.$type)
						.'</span>';
				}
				$html[] = implode(', ', $items);
				$html[] = '</div>';

				$previousType = $type;
			}

			$html[] = '</div>';
		}

		return implode('', $html);
	}
}
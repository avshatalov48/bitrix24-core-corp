<?php
namespace Bitrix\Tasks\Grid\Task\Row\Content;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Grid\Task\Row\Content;
use CCrmOwnerType;
use CCrmOwnerTypeAbbr;

/**
 * Class CrmField
 *
 * @package Bitrix\Tasks\Grid\Task\Row\Content\
 */
class CrmField extends Content
{
	/**
	 * @return string
	 */
	public function prepare(): string
	{
		$row = $this->getRowData();
		$parameters = $this->getParameters();

		if (empty($row['UF_CRM_TASK']))
		{
			return Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_CRM_FIELD_NOT_PRESENT') ?? '';
		}

		$collection = [];
		foreach ($row['UF_CRM_TASK'] as $value)
		{
			[$type, $id] = explode('_', $value);

			if ($type !== $parameters['CRM_FIELD_ID'])
			{
				continue;
			}

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
			$html[]= '<div class="tasks-list-crm-div">';

			foreach ($collection as $type => $items)
			{
				if (empty($items))
				{
					continue;
				}

				$html[] = implode(', ', $items);
				$html[] = '</div>';
			}

			$html[]= '</div>';
		}

		return implode('', $html);
	}
}
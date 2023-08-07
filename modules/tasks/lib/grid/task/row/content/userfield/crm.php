<?php
namespace Bitrix\Tasks\Grid\Task\Row\Content\UserField;

use Bitrix\Crm\Service\Container;
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
			return Loc::getMessage('TASKS_GRID_TASK_ROW_CONTENT_USER_FIELD_CRM_NOT_PRESENT') ?? '';
		}

		sort($row['UF_CRM_TASK']);

		$collection = [];
		foreach ($row['UF_CRM_TASK'] as $value)
		{
			[$type, $id] = explode('_', $value);
			$typeId = CCrmOwnerType::ResolveID(CCrmOwnerTypeAbbr::ResolveName($type));
			$title = CCrmOwnerType::GetCaption($typeId, $id);
			$url = CCrmOwnerType::GetEntityShowPath($typeId, $id);

			if (!isset($collection[$typeId]))
			{
				$collection[$typeId] = [];
			}

			if ($title)
			{
				$safeTitle = htmlspecialcharsbx($title);
				$collection[$typeId][] = "<a href=\"{$url}\">{$safeTitle}</a>";
			}
		}

		$html = [];
		if ($collection)
		{
			$html[] = '<div class="tasks-list-crm-div">';
			$previousTypeId = null;

			foreach ($collection as $typeId => $items)
			{
				if (empty($items))
				{
					continue;
				}

				$html[] = '<div class="tasks-list-crm-div-wrapper">';
				if ($typeId !== $previousTypeId)
				{
					$factory = Container::getInstance()->getFactory($typeId);
					$typeTitle = htmlspecialcharsbx($factory ? $factory->getEntityDescription() : '');
					$html[] = "<span class='tasks-list-crm-div-type'>{$typeTitle}:</span>";
				}
				$html[] = implode(', ', $items);
				$html[] = '</div>';

				$previousTypeId = $typeId;
			}

			$html[] = '</div>';
		}

		return implode('', $html);
	}
}
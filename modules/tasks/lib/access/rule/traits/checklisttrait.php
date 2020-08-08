<?php


namespace Bitrix\Tasks\Access\Rule\Traits;

use Bitrix\Tasks\Access\Model\ChecklistModel;
use Bitrix\Tasks\CheckList\Internals\CheckList;

trait ChecklistTrait
{
	private function isList($params): bool
	{
		if (
			is_array($params)
			&& array_keys($params)[0] === 0
		)
		{
			return true;
		}

		return false;
	}

	private function getModelFromParams($params): ChecklistModel
	{
		if (is_array($params) && !$this->isList($params))
		{
			return ChecklistModel::createFromArray($params);
		}

		if ($params instanceof CheckList)
		{
			return ChecklistModel::createFromChecklist($params);
		}

		if (is_numeric($params))
		{
			return ChecklistModel::createFromId((int) $params);
		}

		return new ChecklistModel();
	}

}
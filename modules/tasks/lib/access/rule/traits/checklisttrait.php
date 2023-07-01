<?php


namespace Bitrix\Tasks\Access\Rule\Traits;

use Bitrix\Tasks\Access\Model\ChecklistModel;
use Bitrix\Tasks\CheckList\Internals\CheckList;
use Bitrix\Tasks\Internals\Task\MemberTable;

trait ChecklistTrait
{
	/**
	 * @param $params
	 * @return bool
	 */
	private function isList($params): bool
	{
		return
			is_array($params)
			&& (array_keys($params)[0] ?? null) === 0
		;
	}

	/**
	 * @param $params
	 * @return ChecklistModel
	 */
	private function getModelFromParams($params): ChecklistModel
	{
		if ($params instanceof ChecklistModel)
		{
			return $params;
		}

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

	/**
	 * @param array $params
	 * @return array
	 */
	private function prepareParams(array $params): array
	{
		$res = $params;

		foreach ($params as $k => $row)
		{
			if (empty($row['MEMBERS']) || !is_array($row['MEMBERS']))
			{
				continue;
			}

			$members = [];
			foreach ($row['MEMBERS'] as $v => $member)
			{
				if (!is_array($member))
				{
					continue;
				}

				$memberKeys = array_keys($member);
				if (!is_array($member[$memberKeys[0]]))
				{
					$members[$v] = $member;
					continue;
				}

				foreach ($member as $id => $data)
				{
					$members[$id] = [
						'TYPE' => ($data['TYPE'] === 'accomplice') ? MemberTable::MEMBER_TYPE_ACCOMPLICE : MemberTable::MEMBER_TYPE_AUDITOR,
						'NAME' => $data['NAME']
					];
				}
			}
			$res[$k]['MEMBERS'] = $members;
		}

		return $res;
	}
}
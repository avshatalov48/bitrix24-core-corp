<?php
namespace Bitrix\Timeman\Controller;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\Controller;

use Bitrix\Main\Engine\ActionFilter\Scope;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordCollection;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordTable;
use Bitrix\Timeman\Util\Form\Filter\Validator\NumberValidator;

class WorktimeRecord extends Controller
{
	protected function getDefaultPreFilters()
	{
		return array_merge(
			parent::getDefaultPreFilters(),
			[
				new Scope(Scope::REST),
			]
		);
	}

	public function getAction($id)
	{
		$readableUserIds = [];
		$checkAccess = false;
		if (!$this->hasCurrentUserAccess())
		{
			$checkAccess = true;
			$readableUserIds = $this->getUserIdsWhoseRecordCurrentUserHaveAccess();
		}

		$validator = (new NumberValidator())->configureIntegerOnly(true)->configureMin(1);
		if (!$validator->validate($id)->isSuccess())
		{
			throw new ArgumentException('id must be integer, greater than 0');
		}

		$filter = ['ID' => $id];
		if ($checkAccess)
		{
			$filter['USER_ID'] = $readableUserIds;
		}

		$record = WorktimeRecordTable::query()
			->addSelect('*')
			->setFilter($filter)
			->fetchObject();

		return $record ? $this->convertRecordFields($record) : [];
	}

	public function listAction(PageNavigation $pageNavigation, $select = [], $filter = [], $order = [])
	{
		$readableUserIds = [];
		$checkAccess = false;
		if (!$this->hasCurrentUserAccess())
		{
			$checkAccess = true;
			$readableUserIds = $this->getUserIdsWhoseRecordCurrentUserHaveAccess();
		}

		foreach ($select as $field)
		{
			if (!WorktimeRecordTable::getEntity()->hasField($field))
			{
				throw new ArgumentException('WorktimeRecord does not have field ' . htmlspecialcharsbx($field));
			}
		}
		$select = empty($select) ? ['*'] : $select;
		$order = empty($order) ? ['ID' => 'DESC'] : $order;
		if (!in_array('ID', $select, true))
		{
			$select[] = 'ID';
		}

		if ($checkAccess)
		{
			if (empty($readableUserIds))
			{
				return [];
			}

			if (isset($filter['USER_ID']))
			{
				$filter['USER_ID'] = array_intersect((array)$filter['USER_ID'], $readableUserIds);
				if (empty($filter['USER_ID']))
				{
					return [];
				}
			}
			else
			{
				$filter['USER_ID'] = $readableUserIds;
			}
		}

		/** @var WorktimeRecordCollection $records */
		$records = WorktimeRecordTable::query()
			->setSelect($select)
			->setFilter($filter)
			->setOffset($pageNavigation->getOffset())
			->setLimit($pageNavigation->getLimit())
			->setOrder($order)
			->exec()
			->fetchCollection();

		$result = [];
		foreach ($records->getAll() as $record)
		{
			$result[] = $this->convertRecordFields($record);
		}

		return new Page('WORKTIME_RECORDS', $result, function () use ($filter) {
			return WorktimeRecordTable::getCount($filter);
		});
	}

	private function hasCurrentUserAccess(): bool
	{
		if ($this->getCurrentUser()->isAdmin())
		{
			return true;
		}

		$accessUsers = \CTimeMan::getAccess();
		if (count($accessUsers['READ']) <= 0)
		{
			return false;
		}

		$canEditAll = in_array('*', $accessUsers['WRITE']);
		$canReadAll = in_array('*', $accessUsers['READ']);

		if ($canEditAll || $canReadAll)
		{
			return true;
		}

		return false;
	}

	private function getUserIdsWhoseRecordCurrentUserHaveAccess(): array
	{
		$accessUsers = \CTimeMan::getAccess();

		$directUsers = \CTimeMan::getDirectAccess($this->getCurrentUser()->getId());
		if (empty($directUsers))
		{
			return $accessUsers['READ'];
		}

		return array_intersect($accessUsers['READ'], $directUsers);
	}

	private function convertRecordFields(\Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord $record)
	{
		return $this->convertKeysToCamelCase($record->collectValues());
	}
}
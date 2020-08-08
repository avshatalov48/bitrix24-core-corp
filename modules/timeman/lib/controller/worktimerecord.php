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
		$validator = (new NumberValidator())->configureIntegerOnly(true)->configureMin(1);
		if (!$validator->validate($id)->isSuccess())
		{
			throw new ArgumentException('id must be integer, greater than 0');
		}
		$record = WorktimeRecordTable::query()
			->addSelect('*')
			->where('ID', $id)
			->fetchObject();
		return $record ? $this->convertRecordFields($record) : [];
	}

	public function listAction(PageNavigation $pageNavigation, $select = [], $filter = [], $order = [])
	{
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

	private function convertRecordFields(\Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord $record)
	{
		return $this->convertKeysToCamelCase($record->collectValues());
	}
}
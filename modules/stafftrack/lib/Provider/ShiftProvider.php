<?php

namespace Bitrix\StaffTrack\Provider;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Main\Text\Emoji;
use Bitrix\Main\Type\Date;
use Bitrix\StaffTrack\Access\Model\ShiftModel;
use Bitrix\StaffTrack\Access\ShiftAccessController;
use Bitrix\StaffTrack\Access\ShiftAction;
use Bitrix\StaffTrack\Dictionary\Status;
use Bitrix\StaffTrack\Helper\DateHelper;
use Bitrix\StaffTrack\Model\ShiftTable;
use Bitrix\StaffTrack\Model\Shift;
use Bitrix\StaffTrack\Model\ShiftCollection;
use Bitrix\StaffTrack\Shift\ShiftDto;
use Bitrix\StaffTrack\Shift\ShiftRegistry;

class ShiftProvider
{
	/** @var array $instances */
	private static array $instances = [];
	/** @var int $userId */
	private int $userId;

	/**
	 * @param int $userId
	 * @return self
	 */
	public static function getInstance(int $userId): self
	{
		if (!isset(self::$instances[$userId]))
		{
			self::$instances[$userId] = new self($userId);
		}

		return self::$instances[$userId];
	}

	/**
	 * @param int $userId
	 */
	private function __construct(int $userId)
	{
		$this->userId = $userId;
	}

	/**
	 * @param int $shiftId
	 * @return Shift|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function get(int $shiftId): ?Shift
	{
		return ShiftTable::getById($shiftId)->fetchObject();
	}

	/**
	 * @param array $filter
	 * @param array $select
	 * @param array $order
	 * @param int $limit
	 * @return ShiftCollection
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function list(
		array $filter = [],
		array $select = [],
		array $order = [],
		int $limit = 0
	): ShiftCollection
	{
		$query = ShiftTable::query();

		$this->prepareSelect($select, $query);
		$this->prepareFilter($filter, $query);
		$this->prepareOrder($order, $query);
		$this->prepareLimit($limit, $query);

		$query->getQuery();

		if (empty($query->getWhereChains()))
		{
			return new ShiftCollection();
		}

		return $query->exec()->fetchCollection();
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function hasActiveShift(): bool
	{
		$dateTime = DateHelper::getInstance()->getOffsetDate($this->userId);

		return $this->findByDate($dateTime->format(DateHelper::DATE_FORMAT)) !== null;
	}

	/**
	 * @param string|null $date
	 * @return Shift|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function findByDate(?string $date): ?Shift
	{
		$timeBeforeShiftStart = DateHelper::getInstance()->getServerDate()->add('-8 hours');
		$selectedDate = DateHelper::getInstance()->getServerDate($date);
		$yesterdayDate = (clone $selectedDate)->add('-1 day');

		$res = ShiftTable::query()
			->setSelect(ShiftRegistry::DEFAULT_SELECT)
			->where('USER_ID', $this->userId)
			->where(
				Query::filter()
					->logic(ConditionTree::LOGIC_OR)
					->where('SHIFT_DATE', $selectedDate)
					->where(
						Query::filter()
							->logic(ConditionTree::LOGIC_AND)
							->where('SHIFT_DATE', $yesterdayDate)
							->where('DATE_CREATE', '>=', $timeBeforeShiftStart)
					)
			)
			->setLimit(1)
			->exec()
		;

		return $res->fetchObject();
	}

	/**
	 * @param array $userIds
	 * @param string $monthCode
	 * @return array
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getUsersStatisticsByMonthCode(array $userIds, string $monthCode): array
	{
		[$month, $year] = explode('.', $monthCode);
		$nextMonth = (int)$month + 1;

		$dateFrom = new Date("01.$month.$year", 'd.m.Y');
		$dateTo = new Date("00.$nextMonth.$year", 'd.m.Y');

		$result = ShiftTable::query()
			->setSelect([
				new ExpressionField('CHECKIN_COUNT', 'COUNT(USER_ID)'),
				'USER_ID',
			])
			->whereIn('USER_ID', $userIds)
			->where('SHIFT_DATE', '>=', $dateFrom)
			->where('SHIFT_DATE', '<=', $dateTo)
			->where('STATUS', Status::WORKING->value)
			->setGroup('USER_ID')
			->fetchAll()
		;

		$statistics = array_column($result, 'CHECKIN_COUNT', 'USER_ID');

		return array_map(static fn (int $userId) => [
			'id' => $userId,
			'checkinCount' => (int)($statistics[$userId] ?? 0),
		], $userIds);
	}

	/**
	 * @param array $select
	 * @param Query $query
	 * @return void
	 */
	private function prepareSelect(array $select, Query $query): void
	{
		empty($select)
			? $query->setSelect(ShiftRegistry::DEFAULT_SELECT)
			: $query->setSelect($select)
		;
	}

	/**
	 * @param array $filter
	 * @param Query $query
	 * @return void
	 */
	private function prepareFilter(array $filter, Query $query): void
	{
		foreach ($filter as $key => $value)
		{
			switch ($key)
			{
				case 'DATE_FROM':
					$query->where('SHIFT_DATE', '>=', DateHelper::getInstance()->getServerDate($value));
					break;
				case 'DATE_TO':
					$query->where('SHIFT_DATE', '<=', DateHelper::getInstance()->getServerDate($value));
					break;
				case 'SHIFT_DATE':
					$query->where($key, DateHelper::getInstance()->getServerDate($value));
					break;
				case 'ID':
				case 'USER_ID':
				case 'STATUS':
					if (is_array($value))
					{
						$value = array_map(static function($item) {
							return (int)$item;
						}, $value);

						if (empty($value))
						{
							$value = [''];
						}

						$query->whereIn($key, $value);
					}
					else if ((int)$value)
					{
						$query->where($key, (int)$value);
					}
					break;
			}
		}
	}

	/**
	 * @param array $order
	 * @param Query $query
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function prepareOrder(array $order, Query $query): void
	{
		$orderList = [];
		$shiftFields = ShiftTable::getFields();

		foreach ($order as $key => $value)
		{
			if (in_array($key, $shiftFields, true))
			{
				$orderList[$key] = (mb_strtoupper($value) === 'DESC')
					? 'DESC'
					: 'ASC'
				;
			}
		}

		!empty($orderList) && $query->setOrder($orderList);
	}

	/**
	 * @param int $limit
	 * @param Query $query
	 * @return void
	 */
	private function prepareLimit(int $limit, Query $query): void
	{
		$limit > 0 && $query->setLimit($limit);
	}

	/**
	 * @param ShiftCollection $collection
	 * @return array
	 * @throws \Bitrix\Main\Access\Exception\UnknownActionException
	 */
	public function prepareClientData(ShiftCollection $collection): array
	{
		$result = [];
		$accessController = ShiftAccessController::getInstance($this->userId);

		foreach ($collection as $value)
		{
			$accessModel = ShiftModel::createFromObject($value);

			if ($accessController->check(ShiftAction::VIEW, $accessModel))
			{
				$result[] = $value->toArray();
			}
		}

		return $result;
	}

	/**
	 * @param ShiftDto $shiftDto
	 * @return ShiftDto
	 */
	public function prepareToAdd(ShiftDto $shiftDto): ShiftDto
	{
		if (!empty($shiftDto->cancelReason))
		{
			$shiftDto->setDateCancel(DateHelper::getInstance()->getServerDate());
		}

		if (empty($shiftDto->shiftDate))
		{
			$shiftDto->setShiftDate(DateHelper::getInstance()->getServerDate());
		}

		return $shiftDto
			->setUserId($this->userId)
			->setDateCreate(DateHelper::getInstance()->getServerDate())
			->setMessage($this->prepareStringParam($shiftDto->message))
			->setLocation($this->prepareStringParam($shiftDto->location))
			->setCancelReason($this->prepareStringParam($shiftDto->cancelReason))
		;
	}

	/**
	 * @param Shift $shift
	 * @param ShiftDto $shiftDto
	 * @return ShiftDto
	 */
	public function prepareToUpdate(Shift $shift, ShiftDto $shiftDto): ShiftDto
	{
		if (!empty($shiftDto->cancelReason))
		{
			$shiftDto->setDateCancel(DateHelper::getInstance()->getServerDate());
		}

		return $shiftDto
			->setId($shift->getId())
			->setUserId($shift->getUserId())
			->setShiftDate($shift->getShiftDate())
			->setDateCreate($shift->getDateCreate())
			->setMessage($this->prepareStringParam($shiftDto->message))
			->setLocation($this->prepareStringParam($shiftDto->location))
			->setCancelReason($this->prepareStringParam($shiftDto->cancelReason))
		;
	}

	/**
	 * @param string|null $value
	 * @return string|null
	 */
	private function prepareStringParam(?string $value): ?string
	{
		return is_string($value) ? Emoji::encode(trim($value)) : $value;
	}
}
<?php

namespace Bitrix\ImOpenLines\V2\Session;

use Bitrix\Im\V2\Collection;
use Bitrix\Im\V2\Common\ContextCustomer;
use Bitrix\Im\V2\Entity\User\UserPopupItem;
use Bitrix\Im\V2\Rest\PopupData;
use Bitrix\Im\V2\Rest\PopupDataAggregatable;
use Bitrix\Im\V2\Rest\RestConvertible;
use Bitrix\Im\V2\Service\Context;
use Bitrix\ImOpenLines\Model\SessionTable;
use Bitrix\Main\ORM\Query\Query;

/**
 * @extends Collection<Session>
 */
class SessionCollection extends Collection implements RestConvertible, PopupDataAggregatable
{
	use ContextCustomer;

	protected const ALLOWED_FILTERS = ['CHAT_ID', 'STATUS', 'START_ID', 'END_ID'];
	protected const ALLOWED_ORDERS = ['START_ID', 'END_ID', 'ID'];

	public static function getCollectionElementClass(): string
	{
		return Session::class;
	}

	public static function find(array $filter, array $order, ?int $limit = null, ?Context $context = null, array $select = []): self
	{
		$query = SessionTable::query();
		$query->setSelect(['ID']);

		if (isset($limit))
		{
			$query->setLimit($limit);
		}

		$sessionOrder = ['START_ID' => $order['ID'] ?? 'DESC', 'ID' => $order['ID'] ?? 'DESC'];
		$query->setOrder($sessionOrder);
		static::processFilters($query, $filter, $sessionOrder);
		$sessionIds = $query->fetchCollection()->getIdList();

		if (empty($sessionIds))
		{
			return new static();
		}

		if (empty($select))
		{
			$select = ['*'];
		}

		return new static(SessionTable::query()->whereIn('ID', $sessionIds)->setOrder($sessionOrder)->setSelect($select)->fetchCollection());
	}

	private static function processFilters(Query $query, array $filters, array $orders): void
	{
		foreach ($filters as $filterKey => $filterValue)
		{
			if (in_array($filterKey, self::ALLOWED_FILTERS, true))
			{
				$query->where($filterKey, $filterValue);
			}
		}

		foreach ($orders as $orderKey => $orderValue)
		{
			if (
				in_array($orderKey, self::ALLOWED_ORDERS, true)
				&& in_array($orderValue, ['ASC', 'DESC'], true)
			)
			{
				$query->setOrder([$orderKey => $orderValue]);
			}
		}
	}

	public  function getIds(): array
	{
		return $this->getPrimaryIds();
	}

	public function getPrimaryIds(): array
	{
		$ids = [];

		foreach ($this as $item)
		{
			if ($id = $item->getPrimaryId())
			{
				$ids[] = $id;
			}
		}

		return $ids;
	}

	public function getOperatorsIds(): array
	{
		$operatorsIds = [];

		foreach ($this as $item)
		{
			if ($id = $item->getOperatorId())
			{
				$operatorsIds[] = $id;
			}
		}

		return $operatorsIds;
	}

	public function getPopupData(array $excludedList = []): PopupData
	{
		$popup = [
			new UserPopupItem($this->getOperatorsIds()),
		];

		return new PopupData($popup);
	}

	public static function getRestEntityName(): string
	{
		return 'sessions';
	}

	public function toRestFormat(array $option = []): ?array
	{
		$sessionsForRest = [];

		foreach ($this as $item)
		{
			$sessionsForRest[] = $item->toRestFormat($option);
		}

		return $sessionsForRest;
	}
}
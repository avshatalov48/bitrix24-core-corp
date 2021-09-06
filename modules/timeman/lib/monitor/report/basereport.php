<?php
namespace Bitrix\Timeman\Monitor\Report;

use Bitrix\Main\ORM\Entity;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Timeman\Monitor\Contract\IMonitorReportData;

abstract class BaseReport implements IMonitorReportData
{
	private $filter;
	private $order;
	protected $query;
	private $limit;
	private $offset;

	public function __construct($filter = null, $order = null, $limit = null, $offset = null)
	{
		$this->filter = $filter;
		$this->order = $order;
		$this->limit = $limit;
		$this->offset = $offset;

		$this->query = $this->createQuery();
	}

	public function getQuery(): Query
	{
		return $this->query;
	}

	abstract protected function createQuery(): Query;

	abstract public function getTotalCount(): int;

	public function getData(): array
	{
		return $this->query->exec()->fetchAll();
	}

	public function getFilter()
	{
		return $this->filter;
	}

	public function setFilter($filter): void
	{
		$this->filter = $filter;
	}

	public function getOrder()
	{
		return $this->order;
	}

	public function setOrder($order): void
	{
		$this->order = $order;
	}

	public function getLimit(): ?int
	{
		return $this->limit;
	}

	public function setLimit(int $limit): void
	{
		$this->limit = $limit;
	}

	public function getOffset(): ?int
	{
		return $this->offset;
	}

	public function setOffset(int $offset): void
	{
		$this->offset = $offset;
	}

	public function getTotalCountByColumnName(string $columnName): int
	{
		$totalDataQuery =
			$this
				->getQuery()
				->setLimit(null)
				->setOffset(null)
		;

		$entity = Entity::getInstanceByQuery($totalDataQuery);
		$totalCountQuery = new Query($entity);

		return
			$totalCountQuery
				->setSelect([
					'CNT' => Query::expr()->count($columnName)
				])
				->exec()
				->fetch()['CNT']
			;
	}
}

<?php

namespace Bitrix\Tasks\Flow\Internal\Event\Template;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Internal\FlowTable;

class FlowTemplateEventHandler
{
	private Connection $connection;
	private int $templateId = 0;

	public function __construct()
	{
		$this->init();
	}

	public function withTemplateId(int $templateId): static
	{
		$this->templateId = $templateId;
		return $this;
	}

	/**
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public function onTemplateDelete(): void
	{
		if ($this->templateId <= 0)
		{
			return;
		}

		$query = FlowTable::query()
			->addSelect(new ExpressionField('1', '1'))
			->where('TEMPLATE_ID', $this->templateId)
			->getQuery();

		if ($this->connection->query($query)->fetch() === false)
		{
			return;
		}

		$table = FlowTable::getTableName();
		$field = $this->connection->getSqlHelper()->quote('TEMPLATE_ID');

		$query = "update {$table} set {$field} = 0 where {$field} = {$this->templateId}";

		$this->connection->query($query);
	}

	private function init(): void
	{
		$this->connection = Application::getConnection();
	}
}
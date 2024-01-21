<?php

namespace Bitrix\Crm\FieldContext;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ORM\Data\DeleteResult;

class Repository
{
	private ItemIdentifier $itemIdentifier;
	private ?int $contextId = null;
	private EntityFieldContextTable $entity;
	private ?array $data = null;
	private ContextManager $contextManager;
	private bool $hasFieldsContextTables;

	public const TABLES_CREATED_OPTION_NAME = 'crm_fields_context_dynamic_tables_created';

	public static function createFromId(int $entityTypeId, int $entityId): ?self
	{
		if (!EntityFactory::getInstance()->hasEntity($entityTypeId))
		{
			return null;
		}

		$itemIdentifier = new ItemIdentifier($entityTypeId, $entityId);

		return new self($itemIdentifier);
	}

	public function __construct(ItemIdentifier $itemIdentifier)
	{
		$this->itemIdentifier = $itemIdentifier;
		$this->entity = $this->getEntity();
		$this->hasFieldsContextTables = self::hasFieldsContextTables();
		$this->contextManager = new ContextManager();
	}

	private function getEntity(): EntityFieldContextTable
	{
		$entity = (EntityFactory::getInstance())->getEntity($this->itemIdentifier->getEntityTypeId());

		if ($entity)
		{
			return $entity;
		}

		throw new InvalidEntityTypeIdException();
	}

	public static function hasFieldsContextTables(): bool
	{
		return (Option::get('crm', self::TABLES_CREATED_OPTION_NAME, 'Y') === 'Y');
	}

	public function add(string $fieldName, string $valueId): void
	{
		if (!$this->hasFieldsContextTables || !$this->contextId)
		{
			return;
		}

		if ($this->hasFieldValueInData($fieldName, $valueId))
		{
			return;
		}

		$primaryFields = [
			$this->entity->getIdColumnName(),
			'FIELD_NAME',
			'VALUE_ID',
		];

		$fieldValues = [
			$this->entity->getIdColumnName() => $this->itemIdentifier->getEntityId(),
			'FIELD_NAME' => $fieldName,
			'VALUE_ID' => $valueId,
			'CONTEXT' => $this->contextId,
		];

		$this->addQueryExecute($primaryFields, $fieldValues);

		$this->appendToData($fieldName, $valueId);
	}

	protected function addQueryExecute(array $primaryFields, array $fieldValues): void
	{
		$connection = Application::getConnection();

		$sqlHelper = $connection->getSqlHelper();

		$sql = $sqlHelper->prepareMerge(
			$this->entity::getTableName(),
			$primaryFields,
			$fieldValues,
			$fieldValues,
		);

		$connection->queryExecute($sql[0]);
	}

	public function delete(string $fieldName, string $valueId): DeleteResult
	{
		if (!$this->hasFieldsContextTables)
		{
			return new DeleteResult();
		}

		return $this->entity::delete([
			$this->entity->getIdColumnName() => $this->itemIdentifier->getEntityId(),
			'FIELD_NAME' => $fieldName,
			'VALUE_ID' => $valueId,
		]);
	}

	public function setContextId(?int $context): self
	{
		$availableContextIds = Container::getInstance()->getFieldsContextManager()->getAvailableContextIds();

		if ($context && !in_array($context, $availableContextIds, true))
		{
			throw new InvalidContextException('Context: ' . $context . ' is not supported');
		}

		$this->contextId = $context;

		return $this;
	}

	public function getContextId(): ?int
	{
		return $this->contextId;
	}

	protected function hasFieldValueInData(string $fieldName, string $valueId): bool
	{
		$data = $this->getFieldsData();

		return (isset($data[$fieldName][$valueId]) && $data[$fieldName][$valueId] === $this->contextId);
	}

	public function getFieldsData(): array
	{
		if (!$this->hasFieldsContextTables)
		{
			return [];
		}

		if ($this->data)
		{
			return $this->data;
		}

		$data = $this->entity::getList([
			'select' => [
				'FIELD_NAME',
				'VALUE_ID',
				'CONTEXT',
			],
			'filter' => [
				'=' . $this->entity->getIdColumnName() => $this->itemIdentifier->getEntityId(),
			],
		]);

		$result = [];
		foreach ($data as $item)
		{
			$fieldName = $item['FIELD_NAME'];
			if (!isset($result[$fieldName]))
			{
				$result[$fieldName] = [];
			}

			$result[$fieldName][$item['VALUE_ID']] = $item['CONTEXT'];
		}

		$this->data = $result;

		return $result;
	}

	protected function appendToData(string $fieldName, string $valueId): void
	{
		if (!isset($this->data[$fieldName]))
		{
			$this->data[$fieldName] = [];
		}

		$this->data[$fieldName][$valueId] = $this->contextId;
	}
}
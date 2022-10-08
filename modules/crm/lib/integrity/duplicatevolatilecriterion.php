<?php

namespace Bitrix\Crm\Integrity;

use Bitrix\Crm\EntityBankDetail;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Integrity\Entity\DuplicateIndexTable;
use Bitrix\Crm\Integrity\Volatile\Type\BaseField;
use Bitrix\Main;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotSupportedException;
use CCrmOwnerType;

class DuplicateVolatileCriterion extends DuplicateCriterion
{
	/** @var $volatileType BaseField */
	private $volatileType;

	/** @var int */
	protected $entityTypeId = CCrmOwnerType::Undefined;

	/** @var int */
	protected $volatileTypeId = DuplicateIndexType::UNDEFINED;

	/** @var string */
	protected $value = '';

	protected static function setQueryFilter(Entity\Query $query, array $matches)
	{
		$type = (int)($matches['TYPE_ID'] ?? DuplicateIndexType::UNDEFINED);
		if ($type === DuplicateIndexType::UNDEFINED)
		{
			throw new ArgumentException("Parameter 'TYPE_ID' is required.", 'matches');
		}

		$value = $matches['VALUE'] ?? '';
		if ($value === '')
		{
			throw new ArgumentException("Parameter 'VALUE' is required.", 'matches');
		}

		$query->addFilter('=TYPE_ID', $type);
		$query->addFilter('=VALUE', $value);
	}

	protected static function filterValidTypes(array $volatileTypes)
	{
		$volatileTypeMap = [];

		foreach ($volatileTypes as $volatileTypeId)
		{
			$volatileTypeId = (int)$volatileTypeId;
			if (static::isSupportedType($volatileTypeId))
			{
				$volatileTypeMap[$volatileTypeId] = true;
			}
		}

		return array_keys($volatileTypeMap);
	}

	public static function register(
		int $entityTypeId,
		int $entityId,
		array $fieldCategoryIds = [],
		array $volatileTypes = []
	)
	{
		if ($entityTypeId === CCrmOwnerType::BankDetail)
		{
			[$entityTypeId, $entityId] = array_values(
				EntityBankDetail::getSingleInstance()->getOwnerEntityById($entityId)
			);
		}
		if ($entityTypeId === CCrmOwnerType::Requisite)
		{
			[$entityTypeId, $entityId] = array_values(
				EntityRequisite::getSingleInstance()->getOwnerEntityById($entityId)
			);
		}

		if (
			(
				$entityTypeId === CCrmOwnerType::Lead
				|| $entityTypeId === CCrmOwnerType::Company
				|| $entityTypeId === CCrmOwnerType::Contact
			)
			&& $entityId > 0
		)
		{
			$volatileTypeIds =
				empty($volatileTypes)
					? static::getAllSupportedDedupeTypes()
					: static::filterValidTypes($volatileTypes)
			;
			foreach ($volatileTypeIds as $volatileTypeId)
			{
				$criterion = new static($volatileTypeId);
				if (
					!$criterion->isNull()
					&& $entityTypeId === $criterion->getEntityTypeId()
					&& (
						empty($fieldCategoryIds)
						|| in_array($criterion->getFieldCategoryId(), $fieldCategoryIds, true)
					)
				)
				{
					$criterion->registerByEntityId($entityId);
				}
			}
		}
	}

	protected static function createQuery(): Entity\Query
	{
		return (new Entity\Query(DuplicateVolatileMatchCodeTable::getEntity()));
	}

	public static function prepareMatchHash(array $matches)
	{
		$value = $matches['VALUE'] ?? '';
		$typeId = (int)($matches['TYPE_ID'] ?? DuplicateIndexType::UNDEFINED);
		return ($value !== '' ? md5("$typeId;$value") : '');
	}

	public static function getRegisteredEntityMatches(
		int $entityTypeID,
		int $entityID,
		int $volatileTypeId = DuplicateIndexType::UNDEFINED
	): array
	{
		$params = [
			'select' => ['ID', 'TYPE_ID', 'VALUE'],
			'order' => ['ID' => 'ASC'],
			'filter' => [
				'ENTITY_TYPE_ID' => $entityTypeID,
				'ENTITY_ID' => $entityID,
			],
		];

		if (DuplicateIndexType::isDefined($volatileTypeId))
		{
			$params['filter']['TYPE_ID'] = $volatileTypeId;
		}

		$res = DuplicateVolatileMatchCodeTable::getList($params);
		$results = [];
		while ($row = $res->fetch())
		{
			$matches = [
				'TYPE_ID' => (int)($row['TYPE_ID'] ?? DuplicateIndexType::UNDEFINED),
				'VALUE' => $row['VALUE'] ?? '',
			];
			$results[static::prepareMatchHash($matches)] = $matches;
		}

		return $results;
	}

	public static function getSupportedEntityTypes(): array
	{
		return [CCrmOwnerType::Lead, CCrmOwnerType::Company, CCrmOwnerType::Contact];
	}

	public static function getSupportedDedupeTypes(): array
	{
		return [
			DuplicateIndexType::VOLATILE_1,
			DuplicateIndexType::VOLATILE_2,
			DuplicateIndexType::VOLATILE_3,
			DuplicateIndexType::VOLATILE_4,
			DuplicateIndexType::VOLATILE_5,
			DuplicateIndexType::VOLATILE_6,
			DuplicateIndexType::VOLATILE_7,
		];
	}

	public static function isSupportedType(int $volatileTypeId): bool
	{
		return in_array($volatileTypeId, static::getSupportedDedupeTypes(), true);
	}

	public static function prepareSortParams(
		int $entityTypeID,
		array $entityIDs,
		int $volatileTypeId = DuplicateIndexType::UNDEFINED
	): array
	{
		if (empty($entityIDs))
		{
			return [];
		}

		if(!static::isSupportedType($volatileTypeId))
		{
			$volatileTypeId = DuplicateIndexType::UNDEFINED;
		}

		$query = new Entity\Query(DuplicateVolatileMatchCodeTable::getEntity());

		$query->addSelect('ENTITY_ID');
		$query->addSelect('TYPE_ID');
		$query->addSelect('VALUE');

		$subQuery = new Entity\Query(DuplicateVolatileMatchCodeTable::getEntity());
		$subQuery->registerRuntimeField('', new Entity\ExpressionField('MIN_ID', 'MIN(ID)'));
		$subQuery->addSelect('MIN_ID');

		$subQuery->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
		$subQuery->addFilter('@ENTITY_ID', $entityIDs);

		if ($volatileTypeId !== DuplicateIndexType::UNDEFINED)
		{
			$subQuery->addFilter('=TYPE_ID', $volatileTypeId);
		}

		$subQuery->addGroup('ENTITY_ID');
		$subQuery->addGroup('TYPE_ID');

		$query->registerRuntimeField('',
			new Entity\ReferenceField('M',
				Entity\Base::getInstanceByQuery($subQuery),
				['=this.ID' => 'ref.MIN_ID'],
				['join_type' => 'INNER']
			)
		);

		$result = [];

		$res = $query->exec();
		while ($row = $res->fetch())
		{
			$entityId = intval($row['ENTITY_ID']);
			if (!isset($result[$entityId]))
			{
				$result[$entityId] = [];
			}

			$volatileTypeId = (int)($row['TYPE_ID'] ?? DuplicateIndexType::UNDEFINED);
			$value = $row['VALUE'] ?? '';
			$result[$entityId][$volatileTypeId] = $value;
		}
		return $result;
	}

	public static function unregister(
		int $entityTypeId,
		int $entityId,
		int $volatileTypeId = DuplicateIndexType::UNDEFINED
	)
	{
		$filter = [
			'=ENTITY_TYPE_ID' => $entityTypeId,
			'=ENTITY_ID' => $entityId,
		];

		if ($volatileTypeId !== DuplicateIndexType::UNDEFINED)
		{
			$filter['=TYPE_ID'] = $volatileTypeId;
		}

		$res = DuplicateVolatileMatchCodeTable::getList(
			[
				'select' => ['ID'],
				'order' => ['ID' => 'ASC'],
				'filter' => $filter,
			]
		);
		while ($row = $res->fetch())
		{
			DuplicateVolatileMatchCodeTable::delete($row['ID']);
		}

		$volatileTypeIds =
			$volatileTypeId === DuplicateIndexType::UNDEFINED
				? static::getAllSupportedDedupeTypes()
				: [$volatileTypeId]
		;

		foreach ($volatileTypeIds as $volatileTypeId)
		{
			DuplicateEntityMatchHash::unregisterEntity(
				$entityTypeId,
				$entityId,
				$volatileTypeId
			);
		}
	}

	protected function setEntityTypeId(int $entityTypeId)
	{
		$this->entityTypeId = $entityTypeId;
	}

	protected function setVolatileTypeId(int $volatileTypeId)
	{
		$this->volatileTypeId = $volatileTypeId;
	}

	protected function getFieldCategoryId(): int
	{
		return $this->volatileType->getFieldCategoryId();
	}

	protected function getIdFromEntityFields(array $entityFields): int
	{
		return (int)($entityFields['ID'] ?? 0);
	}

	protected function registerByEntityId(int $entityId)
	{
		$entityTypeId = $this->getEntityTypeId();
		$volatileTypeId = $this->getVolatileTypeId();
		$values = $this->volatileType->getValues($entityId);
		$values = $this->volatileType->prepareCodes($values);

		DuplicateVolatileMatchCodeTable::replaceValues(
			$entityTypeId,
			$entityId,
			$volatileTypeId,
			$values
		);

		if (static::isSupportedType($volatileTypeId))
		{
			DuplicateEntityMatchHash::unregisterEntity($entityTypeId, $entityId, $volatileTypeId);
			foreach ($values as $value)
			{
				$matches = ['TYPE_ID' => $volatileTypeId, 'VALUE' => $value];
				DuplicateEntityMatchHash::register(
					$entityTypeId,
					$entityId,
					$volatileTypeId,
					self::prepareMatchHash($matches)
				);
			}
		}
	}

	public function __construct(int $volatileTypeId, string $value = '')
	{
		parent::__construct();

		$this->volatileType = BaseField::getInstance($volatileTypeId);
		$this->useStrictComparison = false;
		$this->setVolatileTypeId($volatileTypeId);
		$this->setEntityTypeId($this->volatileType->getEntityTypeId());
		$this->setValue($value);
	}

	public function isNull(): bool
	{
		return $this->volatileType->isNull();
	}

	public function getEntityTypeId(): int
	{
		return $this->entityTypeId;
	}

	public function getVolatileTypeId(): int
	{
		return $this->volatileTypeId;
	}

	public function getValue(): string
	{
		return $this->value;
	}

	public function setValue(string $value)
	{
		$this->value = $value;
	}

	public static function checkIndex(array $params): bool
	{
		$entityTypeId =
			isset($params['ENTITY_TYPE_ID'])
				? intval($params['ENTITY_TYPE_ID'])
				: CCrmOwnerType::Undefined
		;
		if ($entityTypeId !== CCrmOwnerType::Undefined
			&& $entityTypeId !== CCrmOwnerType::Lead
			&& $entityTypeId !== CCrmOwnerType::Contact
			&& $entityTypeId !== CCrmOwnerType::Company
		)
		{
			throw new NotSupportedException(
				"Entity type: '"
				. CCrmOwnerType::ResolveName($entityTypeId)
				. "' is not supported in current context"
			);
		}

		$volatileTypeId = isset($params['TYPE_ID']) ? intval($params['TYPE_ID']) : DuplicateIndexType::UNDEFINED;
		if(!static::isSupportedType($volatileTypeId))
		{
			throw new NotSupportedException(
				"Criterion type(s): '"
				. DuplicateIndexType::resolveName($volatileTypeId)
				. "' is not supported in current context"
			);
		}

		$userId = isset($params['USER_ID']) ? intval($params['USER_ID']) : 0;

		$scope = null;
		if (isset($params['SCOPE']))
		{
			$scope = $params['SCOPE'];
			if (!DuplicateIndexType::checkScopeValue($scope))
			{
				throw new ArgumentException("Parameter has invalid value", 'SCOPE');
			}
		}

		$filter = array(
			'=USER_ID' => $userId,
			'=ENTITY_TYPE_ID' => $entityTypeId,
			'=TYPE_ID' => $volatileTypeId
		);

		if ($scope !== null)
		{
			$filter['=SCOPE'] = $scope;
		}

		$listParams = array(
			'select' => array('USER_ID', 'TYPE_ID', 'ENTITY_TYPE_ID'),
			'order' => array('USER_ID'=>'ASC', 'TYPE_ID'=>'ASC', 'ENTITY_TYPE_ID'=>'ASC'),
			'filter' => $filter,
			'limit' => 1
		);

		$dbResult = DuplicateIndexTable::getList($listParams);

		return is_array($dbResult->fetch());
	}

	public function equals(DuplicateCriterion $item): bool
	{
		if (!($item instanceof DuplicateVolatileCriterion))
		{
			return false;
		}
		/** @var DuplicateVolatileCriterion $item */
		if ($this->getVolatileTypeId() !== $item->getVolatileTypeId())
		{
			return false;
		}

		return $this->getValue() === $item->getValue();
	}

	public function find($entityTypeID = CCrmOwnerType::Undefined, $limit = 50): ?Duplicate
	{
		if ($this->getVolatileTypeId() === DuplicateIndexType::UNDEFINED)
		{
			return null;
		}

		if ($this->getValue() === '')
		{
			return null;
		}

		if (!is_int($entityTypeID))
		{
			throw new ArgumentTypeException('entityTypeId', 'integer');
		}

		if (!is_int($limit))
		{
			throw new ArgumentTypeException('limit', 'integer');
		}

		if ($limit <= 0)
		{
			$limit = 50;
		}

		$filter = [
			'=TYPE_ID' => $this->getVolatileTypeId(),
			'=VALUE' => $this->getValue(),
		];

		if (CCrmOwnerType::IsDefined($entityTypeID))
		{
			$filter['ENTITY_TYPE_ID'] = $entityTypeID;
		}

		$listParams = $this->applyEntityCategoryFilter($entityTypeID, [
			'select' => ['ENTITY_TYPE_ID', 'ENTITY_ID'],
			'order' => [
				'ENTITY_TYPE_ID' => $this->sortDescendingByEntityTypeId ? 'DESC' : 'ASC',
				'ENTITY_ID' => 'ASC',
			],
			'filter' => $filter,
			'limit' => $limit,
		]);

		$res = DuplicateVolatileMatchCodeTable::getList($listParams);
		$entities = [];
		while ($row = $res->fetch())
		{
			$entityTypeID = (int)($row['ENTITY_TYPE_ID'] ?? 0);
			$entityId = (int)($row['ENTITY_ID'] ?? 0);

			if (CCrmOwnerType::IsDefined($entityTypeID) && $entityId > 0)
			{
				$entities[] = new DuplicateEntity($entityTypeID, $entityId);
			}
		}

		return !empty($entities) ? new Duplicate($this, $entities) : null;
	}

	public function getIndexTypeID(): int
	{
		return $this->getVolatileTypeId();
	}

	public function getMatchName(): string
	{
		return $this->volatileType->getMatchName();
	}

	protected function getShortenValue(string $value): string
	{
		$length = mb_strlen($value);

		if ($length > 50)
		{
			$partLength = (int)floor((40) / 3);
			if ($partLength > 0)
			{
				$triplePartLength = $partLength * 3;
				$extLength = ($triplePartLength < 40) ? 40 - $triplePartLength : 0;
				$centerPartLength = $partLength + $extLength;
				$halfCenterPartLength = (int)floor($centerPartLength / 2);
				$centerParthPosition = (int)floor($length / 2) - $halfCenterPartLength - 1;
				$leftPart = mb_substr($value, 0, $partLength);
				$rightPart = mb_substr($value, -$partLength);
				$centerPart = mb_substr($value, $centerParthPosition, $centerPartLength);
				$value = $leftPart . ' ... ' . $centerPart . ' ... ' . $rightPart;
			}
		}

		return $value;
	}

	public function getMatchDescription(): string
	{
		return $this->getShortenValue($this->getValue());
	}

	public function getMatches(): array
	{
		return [
			'TYPE_ID' => $this->getVolatileTypeId(),
			'VALUE' => $this->getValue(),
		];
	}

	public function getMatchHash(): string
	{
		$value = $this->getValue();
		$typeId = $this->getVolatileTypeId();
		return $value !== '' ? md5("$typeId;$value") : '';
	}

	public static function createFromMatches(array $matches): DuplicateVolatileCriterion
	{
		$volatileTypeId = $matches['TYPE_ID'] ?? DuplicateIndexType::UNDEFINED;
		$value = $matches['VALUE'] ?? '';
		return new DuplicateVolatileCriterion($volatileTypeId, $value);
	}

	public static function loadEntityMatches($entityTypeId, $entityId, $volatileTypeId): array
	{
		$query = new Main\Entity\Query(DuplicateVolatileMatchCodeTable::getEntity());
		$query->addFilter('=ENTITY_TYPE_ID', $entityTypeId);
		$query->addFilter('=ENTITY_ID', $entityId);
		$query->addFilter('=TYPE_ID', $volatileTypeId);

		$query->addSelect('VALUE');
		$query->addSelect('TYPE_ID');

		$dbResult = $query->exec();
		$results = [];
		while($fields = $dbResult->fetch())
		{
			$results[] = [
				'TYPE_ID' => $volatileTypeId,
				'VALUE' => $fields['VALUE'] ?? '',
			];
		}
		return $results;
	}

	public function getSummary(): ?string
	{
		return Loc::getMessage(
			"CRM_DUP_CRITERION_VOL_SUMMARY",
			[
				'#FIELD#'=> $this->getMatchName(),
				'#VALUE#'=> $this->getMatchDescription(),
			]
		);
	}

	public function getTextTotals($count, $limit = 0): ?string
	{
		if (!is_int($count))
		{
			$count = (int)$count;
		}

		if (!is_int($limit))
		{
			$limit = (int)$limit;
		}

		$exceeded = $limit > 0 && $count > $limit;
		if ($exceeded)
		{
			$count = $limit;
		}

		return Loc::getMessage(
			$exceeded
				? 'CRM_DUP_CRITERION_VOL_ENTITY_TOTAL_EXCEEDED'
				: 'CRM_DUP_CRITERION_VOL_ENTITY_TOTAL'
			,
			[
				'#FIELD#'=> $this->getMatchName(),
				'#VALUE#'=> $this->getMatchDescription(),
				'#QTY#'=> Duplicate::entityCountToText($count)
			]
		);
	}

	public function getTypeName(): string
	{
		return DuplicateIndexType::resolveName($this->getVolatileTypeId());
	}

	public static function prepareCodes(int $volatileTypeId, array $values): array
	{
		$criterion = new static($volatileTypeId);
		if (!$criterion->isNull())
		{
			return $criterion->volatileType->prepareCodes($values);
		}

		return [];
	}
	public static function prepareCode(int $volatileTypeId, string $value): string
	{
		$result = static::prepareCodes($volatileTypeId, [$value]);

		return !empty($result) ? $result[0] : $value;
	}

	public function prepareSearchQuery(
		$entityTypeID = CCrmOwnerType::Undefined,
		array $select = null,
		array $order = null,
		$limit = 0
	): Entity\Query
	{
		if (!static::isSupportedType($this->getVolatileTypeId()))
		{
			throw new InvalidOperationException('The field "volatileTypeId" is not assigned.');
		}

		if ($this->getValue() === '')
		{
			throw new InvalidOperationException('The field "value" is not assigned.');
		}

		if (!is_int($entityTypeID))
		{
			throw new ArgumentTypeException('entityTypeID', 'integer');
		}

		$query = new Entity\Query(DuplicateVolatileMatchCodeTable::getEntity());
		if (!is_array($select))
		{
			$select = [];
		}
		if (empty($select))
		{
			$select = ['ENTITY_TYPE_ID', 'ENTITY_ID'];
		}
		$query->setSelect($select);

		if (is_array($order) && !empty($order))
		{
			$query->setOrder($order);
		}

		$filter = ['=TYPE_ID' => $this->getVolatileTypeId()];
		$value = static::prepareCode($this->getVolatileTypeId(), $this->getValue());
		if ($this->useStrictComparison)
		{
			$filter['=VALUE'] = $value;
		}
		else
		{
			$filter['%VALUE'] = new SqlExpression('?s', $value.'%');
		}

		if (CCrmOwnerType::IsDefined($entityTypeID))
		{
			$filter['ENTITY_TYPE_ID'] = $entityTypeID;
		}

		$query->setFilter($filter);

		if ($limit > 0)
		{
			$query->setLimit($limit);
		}

		return $query;
	}
}
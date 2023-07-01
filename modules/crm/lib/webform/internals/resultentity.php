<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm\Internals;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\WebForm\Helper;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\Collection;

Loc::loadMessages(__FILE__);

/**
 * Class ResultEntityTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ResultEntity_Query query()
 * @method static EO_ResultEntity_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ResultEntity_Result getById($id)
 * @method static EO_ResultEntity_Result getList(array $parameters = [])
 * @method static EO_ResultEntity_Entity getEntity()
 * @method static \Bitrix\Crm\WebForm\Internals\EO_ResultEntity createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\WebForm\Internals\EO_ResultEntity_Collection createCollection()
 * @method static \Bitrix\Crm\WebForm\Internals\EO_ResultEntity wakeUpObject($row)
 * @method static \Bitrix\Crm\WebForm\Internals\EO_ResultEntity_Collection wakeUpCollection($rows)
 */
class ResultEntityTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_webform_result_entity';
	}

	public static function getMap()
	{
		return array(
			'FORM_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'RESULT_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'ENTITY_NAME' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'ITEM_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			new Reference(
				'RESULT',
				ResultTable::class,
				Join::on("this.RESULT_ID", "ref.ID")
			),
		);
	}

	public static function addBatch($formId, array $list)
	{
		$counterEntities = array();
		foreach($list as $item)
		{
			$result = static::add(array(
				'FORM_ID' => $formId,
				'RESULT_ID' => $item['RESULT_ID'],
				'ENTITY_NAME' => $item['ENTITY_NAME'],
				'ITEM_ID' => $item['ITEM_ID'],
			));
			if($result->isSuccess() && !$item['IS_DUPLICATE'])
			{
				$counterEntities[] = $item['ENTITY_NAME'];
			}
		}

		if(count($counterEntities) > 0)
		{
			FormCounterTable::incEntityCounters($formId, $counterEntities);
		}
	}

	public static function getCountOfUniqueResultIds(): int
	{
		return (int)static::query()
			->addSelect(
				Query::expr()->countDistinct('RESULT_ID'),
				'CNT'
			)
			->exec()
			->fetch()['CNT'];
	}

	public static function getMinUniqueResultIdHigherThan(int $limit): int
	{
		$row = static::query()
			->addSelect('RESULT_ID')
			->addGroup('RESULT_ID')
			->setOffset($limit)
			->setLimit(1)
			->addOrder('RESULT_ID')
			->exec()
			->fetch()
		;

		return $row ? (int)$row['RESULT_ID'] : 0;
	}

	public static function isResultExistsForItemHigherThan(ItemIdentifier $identifier, int $resultId): bool
	{
		$entityName = \CCrmOwnerType::ResolveName($identifier->getEntityTypeId());

		return static::query()
			->addSelect('ITEM_ID')
			->addFilter('>RESULT_ID', $resultId)
			->addFilter('=ENTITY_NAME', $entityName)
			->addFilter('=ITEM_ID', $identifier->getEntityId())
			->exec()
			->fetch() !== false
		;
	}

	public static function getEntityIdsHigherThan(int $entityTypeId, int $resultId): array
	{
		$entityName = \CCrmOwnerType::ResolveName($entityTypeId);

		return static::query()
			->addSelect('ITEM_ID')
			->addOrder('ITEM_ID')
			->addFilter('>RESULT_ID', $resultId)
			->addFilter('=ENTITY_NAME', $entityName)
			->exec()
			->fetchCollection()
			->getItemIdList()
		;
	}

	public static function getItemIdsThatHasResultsHigherThan(int $entityTypeId, int $resultId, array $itemIds): array
	{
		Collection::normalizeArrayValuesByInt($itemIds);
		if (empty($itemIds))
		{
			return [];
		}
		$entityName = \CCrmOwnerType::ResolveName($entityTypeId);

		return static::query()
			->addSelect('ITEM_ID')
			->addOrder('ITEM_ID')
			->addFilter('>RESULT_ID', $resultId)
			->addFilter('=ENTITY_NAME', $entityName)
			->addFilter('@ITEM_ID', $itemIds)
			->exec()
			->fetchCollection()
			->getItemIdList()
		;
	}
}

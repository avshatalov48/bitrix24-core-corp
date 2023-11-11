<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm;

use Bitrix\Crm;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

Loc::loadMessages(__FILE__);

/**
 * Class QuoteElementTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_QuoteElement_Query query()
 * @method static EO_QuoteElement_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_QuoteElement_Result getById($id)
 * @method static EO_QuoteElement_Result getList(array $parameters = [])
 * @method static EO_QuoteElement_Entity getEntity()
 * @method static \Bitrix\Crm\EO_QuoteElement createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\EO_QuoteElement_Collection createCollection()
 * @method static \Bitrix\Crm\EO_QuoteElement wakeUpObject($row)
 * @method static \Bitrix\Crm\EO_QuoteElement_Collection wakeUpCollection($rows)
 */
class QuoteElementTable extends Entity\DataManager
{
	/**
	 * @inheritdoc
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_quote_elem';
	}

	/**
	 * @inheritdoc
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'QUOTE_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'QUOTE' => array(
				'data_type' => '\Bitrix\Crm\QuoteTable',
				'reference' => array('=this.QUOTE_ID' => 'ref.ID')
			),
			'STORAGE_TYPE_ID' => array(
				'data_type' => 'enum',
				'primary' => true,
				'values' => array(
					Crm\Integration\StorageType::Disk,
					Crm\Integration\StorageType::File,
					Crm\Integration\StorageType::WebDav,
					Crm\Integration\StorageType::Undefined,
				)
			),
			'ELEMENT_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			/*(new Entity\ReferenceField(
				'FILE',
				\Bitrix\Main\FileTable::class,
				Entity\Query\Join::on('this.ELEMENT_ID', 'ref.ID')->where('this.STORAGE_TYPE_ID', Crm\Integration\StorageType::File),
				array('join_type' => 'INNER')
			)),*/
		);
	}

	public static function deleteByQuoteId(int $quoteId): Result
	{
		$result = new Result();

		$list = static::getList([
			'filter' => [
				'=QUOTE_ID' => $quoteId,
			],
		]);
		while($item = $list->fetchObject())
		{
			Crm\Integration\StorageManager::deleteFile($item->getElementId(), (int)$item->getStorageTypeId());
			$deleteResult = $item->delete();
			if(!$deleteResult->isSuccess())
			{
				$result->addErrors($deleteResult->getErrors());
			}
		}

		return $result;
	}
}

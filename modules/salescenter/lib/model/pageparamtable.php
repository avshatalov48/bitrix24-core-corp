<?php

namespace Bitrix\SalesCenter\Model;

use Bitrix\Main\ORM;

/**
 * Class PageParamTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_PageParam_Query query()
 * @method static EO_PageParam_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_PageParam_Result getById($id)
 * @method static EO_PageParam_Result getList(array $parameters = array())
 * @method static EO_PageParam_Entity getEntity()
 * @method static \Bitrix\SalesCenter\Model\EO_PageParam createObject($setDefaultValues = true)
 * @method static \Bitrix\SalesCenter\Model\EO_PageParam_Collection createCollection()
 * @method static \Bitrix\SalesCenter\Model\EO_PageParam wakeUpObject($row)
 * @method static \Bitrix\SalesCenter\Model\EO_PageParam_Collection wakeUpCollection($rows)
 */
class PageParamTable extends ORM\Data\DataManager
{
	public static function getTableName(): string
	{
		return 'b_salescenter_page_param';
	}

	public static function getMap(): array
	{
		return [
			(new ORM\Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new ORM\Fields\IntegerField('PAGE_ID'))
				->configureRequired(),
			new ORM\Fields\Relations\Reference('PAGE', '\Bitrix\SalesCenter\Model\Page', ['=this.PAGE_ID' => 'ref.ID']),
			(new ORM\Fields\StringField('FIELD'))
		];
	}

	public static function deleteByPageId(int $pageId)
	{
		$list = static::getList([
			'select' => [
				'ID',
			],
			'filter' => [
				'=PAGE_ID' => $pageId,
			],
		]);
		while($item = $list->fetch())
		{
			static::delete($item['ID']);
		}
	}
}
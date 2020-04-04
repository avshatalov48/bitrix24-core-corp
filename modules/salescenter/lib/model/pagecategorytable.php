<?php

namespace Bitrix\SalesCenter\Model;

use Bitrix\Main;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

class PageCategoryTable extends Main\ORM\Data\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_salescenter_page_category';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new StringField('NAME', [
				'required' => true,
			]),
			new StringField('CODE'),
			new IntegerField('SORT', [
				'required' => true,
				'default_value' => 500
			]),
		];
	}

	/**
	 * @return Main\ORM\Objectify\EntityObject|string
	 */
	public static function getObjectClass()
	{
		return PageCategory::class;
	}

	/**
	 * @return string
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function installDefaultCategories()
	{
		global $DB;
		if(!$DB->TableExists(static::getTableName()))
		{
			return '\\Bitrix\\SalesCenter\\Model\\PageCategoryTable::installDefaultCategories();';
		}
		$categoriesCount = static::getCount();
		if($categoriesCount > 0)
		{
			return '';
		}

		$categories = [
			['NAME' => 'HOWTO', 'CODE' => 'HOWTO', 'SORT' => 100],
			['NAME' => 'WHERE', 'CODE' => 'WHERE', 'SORT' => 200],
			['NAME' => 'FEEDBACK', 'CODE' => 'FEEDBACK', 'SORT' => 300],
			['NAME' => 'LOYALTY', 'CODE' => 'LOYALTY', 'SORT' => 400],
		];

		foreach($categories as $data)
		{
			$category = new PageCategory();
			$category->setName($data['NAME'])->setCode($data['CODE'])->setSort($data['SORT'])->save();
		}

		return '';
	}
}
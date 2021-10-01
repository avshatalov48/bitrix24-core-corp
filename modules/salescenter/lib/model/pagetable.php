<?php

namespace Bitrix\SalesCenter\Model;

use Bitrix\Main;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\SalesCenter\Integration\LandingManager;

/**
 * Class PageTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Page_Query query()
 * @method static EO_Page_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Page_Result getById($id)
 * @method static EO_Page_Result getList(array $parameters = array())
 * @method static EO_Page_Entity getEntity()
 * @method static \Bitrix\SalesCenter\Model\Page createObject($setDefaultValues = true)
 * @method static \Bitrix\SalesCenter\Model\EO_Page_Collection createCollection()
 * @method static \Bitrix\SalesCenter\Model\Page wakeUpObject($row)
 * @method static \Bitrix\SalesCenter\Model\EO_Page_Collection wakeUpCollection($rows)
 */
class PageTable extends Main\ORM\Data\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_salescenter_page';
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
			new StringField('NAME'),
			new StringField('URL'),
			new IntegerField('LANDING_ID'),
			new Main\ORM\Fields\BooleanField('HIDDEN', [
				'values' => ['N', 'Y'],
				'default_value' => 'N',
			]),
			new Main\ORM\Fields\BooleanField('IS_WEBFORM', [
				'values' => ['N', 'Y'],
				'default_value' => 'N',
			]),
			new Main\ORM\Fields\BooleanField('IS_FRAME_DENIED', [
				'values' => ['N', 'Y'],
				'default_value' => 'N',
			]),
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
		return Page::class;
	}

	/**
	 * @param array $fields
	 * @param array $oldFields
	 * @return Main\ORM\EventResult
	 */
	protected static function onChange(array $fields, array $oldFields = [])
	{
		$result = new Main\ORM\EventResult();

		$landingId = null;
		if(isset($fields['LANDING_ID']))
		{
			$landingId = $fields['LANDING_ID'];
		}
		elseif(isset($oldFields['LANDING_ID']))
		{
			$landingId = $oldFields['LANDING_ID'];
		}

		if($landingId)
		{
			$pageInfo = LandingManager::getInstance()->getLanding($landingId, false);
			if($pageInfo)
			{
				if(isset($fields['NAME']) && $pageInfo['TITLE'] === $fields['NAME'])
				{
					$result->modifyFields(['NAME' => '']);
				}
				$result->unsetField('URL');
			}
			else
			{
				$result->addError(new Main\ORM\EntityError('Landing not found'));
			}
		}
		else
		{
			if(empty($fields['NAME']) && empty($oldFields['NAME']))
			{
				$result->addError(new Main\ORM\EntityError('Name cannot be empty'));
			}
			if(empty($fields['URL']) && empty($oldFields['URL']))
			{
				$result->addError(new Main\ORM\EntityError('Url cannot be empty'));
			}
		}

		return $result;
	}

	/**
	 * @param Event $event
	 * @return Main\ORM\EventResult
	 */
	public static function onBeforeAdd(Event $event)
	{
		$fields = $event->getParameter('fields');
		return static::onChange($fields);
	}

	/**
	 * @param Event $event
	 * @return Main\ORM\EventResult
	 */
	public static function onBeforeUpdate(Event $event)
	{
		$oldFields = static::getById($event->getParameter('primary')['ID'])->fetch();
		$fields = $event->getParameter('fields');

		return static::onChange($fields, $oldFields);
	}

	public static function onAfterDelete(Event $event)
	{
		$pageId = $event->getParameter('primary');
		if(is_array($pageId))
		{
			$pageId = $pageId['ID'];
		}
		$pageId = (int) $pageId;
		if($pageId > 0)
		{
			PageParamTable::deleteByPageId($pageId);
		}
	}
}
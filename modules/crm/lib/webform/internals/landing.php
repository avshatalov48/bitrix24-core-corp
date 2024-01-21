<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm\Internals;

use Bitrix\Main\ORM;
use Bitrix\Crm\Integration;
use Bitrix\Crm\UI\Webpack;

/**
 * Class LandingTable
 * @package Bitrix\Crm\WebForm\Internals
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Landing_Query query()
 * @method static EO_Landing_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Landing_Result getById($id)
 * @method static EO_Landing_Result getList(array $parameters = [])
 * @method static EO_Landing_Entity getEntity()
 * @method static \Bitrix\Crm\WebForm\Internals\EO_Landing createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\WebForm\Internals\EO_Landing_Collection createCollection()
 * @method static \Bitrix\Crm\WebForm\Internals\EO_Landing wakeUpObject($row)
 * @method static \Bitrix\Crm\WebForm\Internals\EO_Landing_Collection wakeUpCollection($rows)
 */
class LandingTable extends ORM\Data\DataManager
{
	/**
	 * Get table name.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_webform_landing';
	}

	/**
	 * Get map.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'autocomplete' => true,
				'primary' => true,
			),
			'FORM_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'LANDING_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'LANDING' => array(
				'data_type' => \Bitrix\Landing\Internals\LandingTable::class,
				'reference' => array('=this.LANDING_ID' => 'ref.ID')
			),
			'FORM' => array(
				'data_type' => FormTable::class,
				'reference' => array('=this.ID' => 'ref.FORM_ID'),
			),
		);
	}

	public static function getLandingPublicUrl($formId)
	{
		static $urls = null;
		if ($urls === null)
		{
			$urls = [];
			$map = static::getLandingMap();
			$landingList = array_values($map);
			$map = array_flip($map);
			foreach (array_chunk($landingList, 50) as $chunk)
			{
				$result = Integration\Landing\FormLanding::getInstance()->getPublicUrl($chunk);
				foreach ($result as $landingId => $url)
				{
					$urls[$map[$landingId]] = $url;
				}
			}
		}

		return $urls[$formId] ?? null;
	}

	public static function getLandingEditUrl($formId)
	{
		$landingId = static::getLandingId($formId);
		if (!$landingId)
		{
			return null;
		}

		return Integration\Landing\FormLanding::getInstance()->getEditUrl($landingId);
	}

	public static function getLandingId($formId)
	{
		$rows = static::getLandingMap();
		return $rows[$formId] ?? null;
	}

	protected static function getLandingMap($cache = true)
	{
		static $rows = null;

		if (!Integration\Landing\FormLanding::getInstance()->canUse())
		{
			$rows = [];
		}

		if ($rows === null || !$cache)
		{
			$rows = FormTable::getDefaultTypeList([
				'select' => ['FORM_ID' => 'ID', 'LANDING_ID' => 'LANDING.LANDING_ID'],
				'order' => ['FORM_ID' => 'ASC'],
				'cache' => ['ttl' => 3600],
			])->fetchAll();

			$rows = array_combine(
				array_column($rows, 'FORM_ID'),
				array_column($rows, 'LANDING_ID')
			);

			if (empty($rows))
			{
				$generate = true;
			}
			else
			{
				$generate = count(array_filter(
					$rows,
					function ($landingId)
					{
						return !$landingId;
					}
				)) > 0;
			}

			$rows = array_filter(
				$rows,
				function ($landingId)
				{
					return !!$landingId;
				}
			);

			if ($cache && $generate)
			{
				if (static::generateLandings())
				{
					$rows = static::getLandingMap(false);
				}
			}
		}

		return $rows;
	}

	public static function createLanding($formId, $name = null)
	{
		if (!$formId)
		{
			return null;
		}

		if ($row = static::query()->addSelect('LANDING_ID')->where('FORM_ID', $formId)->fetch())
		{
			return $row['LANDING_ID'];
		}

		$webpack = Webpack\Form::instance($formId);
		if (!$webpack->isBuilt() && !$webpack->build())
		{
			return null;
		}

		$landingId = Integration\Landing\FormLanding::getInstance()->createLanding($formId, $name);
		if (!$landingId)
		{
			return null;
		}

		$result = static::add(['FORM_ID' => $formId, 'LANDING_ID' => $landingId]);
		if ($result->isSuccess())
		{
			return $landingId;
		}

		return null;
	}

	protected static function generateLandings()
	{
		static $isStarted = false;
		if ($isStarted)
		{
			return false;
		}
		$isStarted = true;

		$generated = false;
		$rows = FormTable::getDefaultTypeList([
			'select' => ['ID', 'NAME', 'LANDING_ID' => 'LANDING.LANDING_ID'],
			'filter' => ['=LANDING.LANDING_ID' => null],
			'order' => ['ID' => 'ASC'],
		]);
		foreach ($rows as $row)
		{
			if (!static::createLanding($row['ID'], $row['NAME']))
			{
				return $generated;
			}

			$generated = true;
		}

		return $generated;
	}

	/**
	 * @param ORM\Event $event Event.
	 * @return ORM\EventResult
	 */
	public static function onDelete(ORM\Event $event)
	{
		$result = new ORM\EventResult;
		$row = static::getRowById($event->getParameter('id'));
		if ($row)
		{
			Integration\Landing\FormLanding::getInstance()->deleteLanding($row['LANDING_ID']);
		}

		return $result;
	}
}

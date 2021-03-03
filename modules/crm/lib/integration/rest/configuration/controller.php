<?php


namespace Bitrix\Crm\Integration\Rest\Configuration;


use Bitrix\Crm\Integration\Rest\Configuration\Entity;
use Bitrix\Main\Event;
use Bitrix\Main\Localization\Loc;
use Exception;

Loc::loadMessages(__FILE__);

class Controller
{
	private static $entityList = [
		Entity\Lead::ENTITY_CODE => 200,
		Entity\Deal::ENTITY_CODE => 200,
		Entity\Status::ENTITY_CODE => 300,
		Entity\Field::ENTITY_CODE => 400,
		Entity\DetailConfiguration::ENTITY_CODE => 500,
		Entity\Setting::ENTITY_CODE => 600,
		Entity\WebForm::ENTITY_CODE => 800,
	];

	/**
	 * @return array of entity
	 */
	public static function getEntityList()
	{
		return static::$entityList;
	}

	/**
	 * check can work with current step
	 * @param Event $event
	 *
	 * @return bool
	 */
	protected static function check(Event $event)
	{
		$code = $event->getParameter('CODE');
		if(!static::$entityList[$code])
		{
			return false;
		}

		return true;
	}

	/**
	 * @param Event $event
	 *
	 * @return array export result
	 * @return null for skip no access step
	 */
	public static function onExport(Event $event)
	{
		$result = null;
		if(static::check($event))
		{
			$data = $event->getParameters();
			try
			{
				switch ($data['CODE'])
				{
					case Entity\Status::ENTITY_CODE:
						$result = Entity\Status::export($data);
						break;
					case Entity\Field::ENTITY_CODE:
						$result = Entity\Field::export($data);
						break;
					case Entity\DetailConfiguration::ENTITY_CODE:
						$result = Entity\DetailConfiguration::getInstance()->export($data);
						break;
					case Entity\Setting::ENTITY_CODE:
						$result = Entity\Setting::export($data);
						break;
					case Entity\WebForm::ENTITY_CODE:
						$result = Entity\WebForm::getInstance()->export($data);
						break;
				}
			}
			catch (Exception $e)
			{
				$result['NEXT'] = false;
				$result['ERROR_ACTION'] = $e->getMessage();
				$result['ERROR_MESSAGES'] = Loc::getMessage(
					'CRM_ERROR_CONFIGURATION_EXPORT_EXCEPTION',
					[
						'#CODE#' => $data['CODE']
					]
				);
			}
		}

		return $result;
	}

	/**
	 * @param Event $event
	 *
	 * @return array clear result
	 * @return null for skip no access step
	 */
	public static function onClear(Event $event)
	{
		$result = null;
		if (static::check($event))
		{
			$data = $event->getParameters();
			try
			{
				switch ($data['CODE'])
				{
					case Entity\Status::ENTITY_CODE:
						$result = Entity\Status::clear($data);
						break;
					case Entity\Field::ENTITY_CODE:
						$result = Entity\Field::clear($data);
						break;
					case Entity\DetailConfiguration::ENTITY_CODE:
						$result = Entity\DetailConfiguration::getInstance()->clear($data);
						break;
					case Entity\Lead::ENTITY_CODE:
						$result = Entity\Lead::clear($data);
						break;
					case Entity\Deal::ENTITY_CODE:
						$result = Entity\Deal::clear($data);
						break;
					case Entity\WebForm::ENTITY_CODE:
						$result = Entity\WebForm::getInstance()->clear($data);
						break;
				}
			}
			catch (Exception $e)
			{
				$result['NEXT'] = false;
				$result['ERROR_ACTION'] = $e->getMessage();
				$result['ERROR_MESSAGES'] = Loc::getMessage(
					'CRM_ERROR_CONFIGURATION_CLEAR_EXCEPTION',
					[
						'#CODE#' => $data['CODE']
					]
				);
			}
		}

		return $result;
	}

	/**
	 * @param Event $event
	 *
	 * @return array import result
	 * @return null for skip no access step
	 */
	public static function onImport(Event $event)
	{
		$result = null;
		if (static::check($event))
		{
			$data = $event->getParameters();
			try
			{
				switch ($data['CODE'])
				{
					case Entity\Status::ENTITY_CODE:
						$result = Entity\Status::import($data);
						break;
					case Entity\Field::ENTITY_CODE:
						$result = Entity\Field::import($data);
						break;
					case Entity\DetailConfiguration::ENTITY_CODE:
						$result = Entity\DetailConfiguration::getInstance()->import($data);
						break;
					case Entity\Setting::ENTITY_CODE:
						$result = Entity\Setting::import($data);
						break;
					case Entity\WebForm::ENTITY_CODE:
						$result = Entity\WebForm::getInstance()->import($data);
						break;
				}
			}
			catch (Exception $e)
			{
				$result['NEXT'] = false;
				$result['ERROR_ACTION'] = $e->getMessage();
				$result['ERROR_MESSAGES'] = Loc::getMessage(
					'CRM_ERROR_CONFIGURATION_IMPORT_EXCEPTION',
					[
						'#CODE#' => $data['CODE']
					]
				);
			}
		}

		return $result;
	}
}
<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Crm\SiteButton;

use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Event;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\EventManager;
use Bitrix\Crm\Automation\Trigger\GuestReturnTrigger;
use Bitrix\Crm\Tracking;

/**
 * Class Guest
 * @package Bitrix\Crm\SiteButton
 */
class Guest
{
	const EVENT_NAME_PREFIX = 'OnSiteGuest';

	public static function getControllerActionNames()
	{
		return array(
			'Return'
		);
	}

	public static function getCookieName()
	{
		return 'b24_crm_guest_id';
	}

	public static function runController()
	{
		$request = Context::getCurrent()->getRequest();
		$gid = $request->get('gid') ?: self::getCookieGuestId();
		$action = $request->get('a');
		$eventName = $request->get('e');
		$data = $request->get('d');
		if (is_string($data))
		{
			try
			{
				$data = Json::decode($data);
			}
			catch (\Exception $exception)
			{
			}
		}
		if (!is_array($data))
		{
			$data = [];
		}
		$answerData = array();

		switch ($action)
		{
			case 'reg':
				if (!$gid)
				{
					$answerData['gid'] = self::register();
				}
				break;

			case 'link':
				$answerData['gid'] = self::link($gid, $data);
				break;

			case 'storeTrace':
			case 'registerOrder':
				if (!empty($data['trace']))
				{
					Tracking\Trace::create($data['trace'])->useTraceDetecting(false)->save();
					Application::getInstance()->addBackgroundJob(
						function ()
						{
							Tracking\Internals\TraceTable::deleteUnusedTraces();
						}
					);
				}
				break;

			case 'event':
				if (self::checkEventName($eventName) && $gid)
				{
					self::sendEvent($eventName, $data);
					self::runAutomation($eventName, $gid);
				}
		}

		self::giveControllerResponse($answerData);
	}

	protected static function giveControllerResponse(array $data = array(), $isError = false)
	{
		global $APPLICATION;
		$APPLICATION->restartBuffer();

		$origin = Context::getCurrent()->getServer()->get('HTTP_ORIGIN');
		if ($origin)
		{
			header('Access-Control-Allow-Origin: ' . $origin);
			header('Access-Control-Allow-Credentials: true');
		}


		header('Content-Type: application/json; charset=UTF-8');
		echo Json::encode(array('error' => $isError, 'data' => $data));
		\CMain::finalActions();
		exit;
	}

	public static function register(array $data = array())
	{
		$addResult = Internals\GuestTable::add(array());
		if (!$addResult->isSuccess())
		{
			return null;
		}
		$savedData = $addResult->getData();

		if (isset($data['ENTITIES']))
		{
			self::bindEntitiesById($addResult->getId(), $data['ENTITIES']);
		}

		return $savedData['GID'];
	}

	public static function link($gid = null, array $data = array())
	{
		if (!$gid)
		{
			return self::getCookieGuestId();
		}

		$cookieParts = explode('_', self::getCookieName());
		$cookiePrefix = $cookieParts[0];
		unset($cookieParts[0]);
		$cookieName = implode('_', $cookieParts);
		global $APPLICATION;
		$APPLICATION->set_cookie(
			$cookieName,
			$gid,
			time() + 3600 * 24 * 365 * 10,
			"/",
			false,
			false,
			true,
			$cookiePrefix
		);

		return $gid;
	}

	public static function registerByContext(array $data = array())
	{
		$gid = self::getCookieGuestId();
		if (!$gid)
		{
			$gid = self::register($data);
			self::link($gid);
		}

		if ($data && isset($data['ENTITIES']))
		{
			self::bindEntities($gid, $data['ENTITIES']);
		}

		return $gid;
	}

	public static function getByGuestId($gid, $isSelectEntities = false)
	{
		$listDb = Internals\GuestTable::getList(array(
			'filter' => array(
				'=GID' => $gid
			),
			'limit' => 1
		));
		$guest = $listDb->fetch();
		if ($guest && $isSelectEntities)
		{
			$date = new DateTime();
			$date->add('-90 days');
			$guest['ENTITIES'] = Internals\GuestEntityTable::getList(array(
				'select' => array('ENTITY_TYPE_ID', 'ENTITY_ID'),
				'filter' => array(
					'=GUEST_ID' => $guest['ID'],
					'>DATE_CREATE' => $date
				),
				'limit' => 15,
				'order' => array('DATE_CREATE' => 'DESC')
			))->fetchAll();
		}

		return $guest;
	}

	public static function bindEntities($gid, array $entities = array())
	{
		$guest = self::getByGuestId($gid, true);
		if (!$guest)
		{
			return null;
		}

		$existedEntities = array();
		foreach ($guest['ENTITIES'] as $entity)
		{
			if (!isset($existedEntities[$entity['ENTITY_TYPE_ID']]))
			{
				$existedEntities[$entity['ENTITY_TYPE_ID']] = array();
			}

			$existedEntities[$entity['ENTITY_TYPE_ID']][] = $entity['ENTITY_ID'];
		}

		$entitiesForAdd = array();
		foreach ($entities as $entity)
		{
			if (!isset($existedEntities[$entity['ENTITY_TYPE_ID']]))
			{
				$entitiesForAdd[] = $entity;
				continue;
			}

			if (in_array($entity['ENTITY_ID'], $existedEntities[$entity['ENTITY_TYPE_ID']]))
			{
				continue;
			}

			$entitiesForAdd[] = $entity;
		}

		return self::bindEntitiesById($guest['ID'], $entitiesForAdd);
	}

	protected static function bindEntitiesById($id, array $entities = array())
	{
		foreach ($entities as $entity)
		{
			$addResult = Internals\GuestEntityTable::add(array(
				'GUEST_ID' => $id,
				'ENTITY_TYPE_ID' => $entity['ENTITY_TYPE_ID'],
				'ENTITY_ID' => $entity['ENTITY_ID']
			));
			if (!$addResult->isSuccess())
			{
				return false;
			}
		}

		return true;
	}

	public static function getCookieGuestId()
	{
		static $gid = null;
		if (!$gid)
		{
			$cookieParts = explode('_', self::getCookieName());
			$cookiePrefix = $cookieParts[0];
			unset($cookieParts[0]);
			$cookieName = implode('_', $cookieParts);

			global $APPLICATION;
			$gid = $APPLICATION->get_cookie($cookieName, $cookiePrefix);
		}

		return $gid ? $gid : null;
	}

	public static function checkEventName($eventName)
	{
		return is_string($eventName) && in_array($eventName, self::getControllerActionNames());
	}

	public static function sendEvent($eventName, array $data = array())
	{
		if (!self::checkEventName($eventName))
		{
			throw new ArgumentException("Wrong parameter \"$eventName\".");
		}

		$event = new Event('crm', self::EVENT_NAME_PREFIX . $eventName, array($data));
		EventManager::getInstance()->send($event);
	}

	private static function runAutomation($eventName, $gid)
	{
		if ($eventName != 'Return')
		{
			return;
		}

		$data = self::getByGuestId($gid, true);
		$bindings = array();
		foreach ($data['ENTITIES'] as $entity)
		{
			$bindings[] = array(
				'OWNER_TYPE_ID' => $entity['ENTITY_TYPE_ID'],
				'OWNER_ID' => $entity['ENTITY_ID'],
			);
		}

		GuestReturnTrigger::execute($bindings);
	}

	public static function getLoader()
	{
		$serverAddress = htmlspecialcharsbx(ResourceManager::getServerAddress() . '/');
		return '<script data-skip-moving="true">'
			. '(function(w,d,u,b){'
			. '   dat=\'b24CrmGuestData\';w[dat]=w[dat]||{name: b, ref:u};'
			. '   s=d.createElement(\'script\');r=(Date.now()/1000|0);s.async=1;s.src=u+\'?\'+r;'
			. '   h=d.getElementsByTagName(\'script\')[0];h.parentNode.insertBefore(s,h);'
			. '})(window,document,\'' . $serverAddress . '/bitrix/js/crm/guest_tracker.js\',\'b24CrmGuest\');'
			. '</script>';
	}
}

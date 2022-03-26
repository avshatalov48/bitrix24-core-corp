<?php
namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm\Activity;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class ProviderManager
{
	private static $providers = null;
	/**
	 * @return Base[] - List of providers.
	 */
	public static function getProviders()
	{
		if(self::$providers === null)
		{
			self::$providers = array(
				Meeting::getId()         => Meeting::className(),
				Task::getId()			 => Task::className(),
				Call::getId()            => Call::className(),
				CallList::getId()        => CallList::className(),
				Email::getId()           => Email::className(),
				Sms::getId()             => Sms::className(),
				Notification::getId()    => Notification::className(),
				OpenLine::getId()        => OpenLine::className(),
				WebForm::getId()         => WebForm::className(),
				Livefeed::getId()        => Livefeed::className(),
				ExternalChannel::getId() => ExternalChannel::className(),
				Request::getId()         => Request::className(),
				RestApp::getId()         => RestApp::className(),
				Delivery::getId()        => Delivery::className(),
				CallTracker::getId()    => CallTracker::class,
				StoreDocument::getId()   => StoreDocument::className(),
			);

			if(Visit::isAvailable())
			{
				self::$providers[Visit::getId()] = Visit::className();
			}

			if (Zoom::isAvailable())
			{
				self::$providers[Zoom::getId()] = Zoom::className();
			}

			foreach(GetModuleEvents('crm', 'OnGetActivityProviders', true) as $event)
			{
				$result = (array)ExecuteModuleEventEx($event);
				foreach ($result as $provider)
				{
					/** @var \Bitrix\Crm\Activity\Provider\Base  $provider */
					$provider = (string)$provider;
					if ($provider
						&& class_exists($provider)
						&& (is_subclass_of($provider, Base::className()) || in_array(Base::className(), class_implements($provider)))
					)
					{
						self::$providers[$provider::getId()] = $provider;
					}
				}
			}
		}
		return self::$providers;
	}

	/**
	 * Get completable providers
	 * @return array
	 */
	public static function getCompletableProviderList()
	{
		$results = array();
		foreach(self::getProviders() as $providerID => $provider)
		{
			/** @var \Bitrix\Crm\Activity\Provider\Base $provider */
			if($provider::isCompletable())
			{
				$results[] = array('ID' => $providerID, 'NAME' => $provider::getName());
			}
		}
		return $results;
	}

	public static function transferOwnership($oldEntityTypeId, $oldEntityId, $newEntityTypeId, $newEntityId)
	{
		foreach(self::getProviders() as $provider)
		{
			/** @var \Bitrix\Crm\Activity\Provider\Base $provider */
			$provider::transferOwnership($oldEntityTypeId, $oldEntityId, $newEntityTypeId, $newEntityId);
		}
	}

	public static function deleteByOwner($entityTypeId, $entityId)
	{
		foreach(self::getProviders() as $provider)
		{
			/** @var \Bitrix\Crm\Activity\Provider\Base $provider */
			$provider::deleteByOwner($entityTypeId, $entityId);
		}
	}

	/**
	 * @return int
	 */
	public static function prepareToolbarButtons(array &$buttons, array $params = null)
	{
		if(!is_array($params))
		{
			$params = array();
		}

		$ownerTypeID = isset($params['OWNER_TYPE_ID']) ? (int)$params['OWNER_TYPE_ID'] : \CCrmOwnerType::Undefined;
		$ownerID = isset($params['OWNER_ID']) ? (int)$params['OWNER_ID'] : 0;
		$count = 0;
		$providerParams = array('OWNER_TYPE_ID' => $ownerTypeID, 'OWNER_ID' => $ownerID);
		foreach(self::getProviders() as $provider)
		{
			foreach($provider::getPlannerActions($providerParams) as $action)
			{
				$name = isset($action['NAME']) ? $action['NAME'] : '';
				if($name === '')
				{
					continue;
				}

				$action = array_merge($action, array('OWNER_TYPE_ID' => $ownerTypeID, 'OWNER_ID' => $ownerID));
				$actionParams = htmlspecialcharsbx(\CUtil::PhpToJSObject($action));
				$buttons[] = array(
					'TEXT' => $name,
					'TITLE' => $name,
					'ONCLICK' => "(new BX.Crm.Activity.Planner()).showEdit({$actionParams})",
					'ICON' => 'btn-new'
				);
				$count++;
			}

			$count += $provider::prepareToolbarButtons($buttons, $params);
		}

		return $count;
	}

	/**
	 * Process activity creation.
	 * @param array $activityFields
	 * @param array|null $params
	 */
	public static function processCreation(array $activityFields, array $params = null)
	{
		foreach(self::getProviders() as $provider)
		{
			$provider::processCreation($activityFields, $params);
		}
	}
}
<?php
namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm\Activity\Provider\Tasks;
use Bitrix\Crm\Badge\SourceIdentifier;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Monitor;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Query;

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
			self::$providers = [
				Meeting::getId() => Meeting::className(),
				Task::getId() => Task::className(),
				Call::getId() => Call::className(),
				CallList::getId() => CallList::className(),
				Email::getId() => Email::className(),
				Sms::getId() => Sms::className(),
				Notification::getId() => Notification::className(),
				OpenLine::getId() => OpenLine::className(),
				WebForm::getId() => WebForm::className(),
				Livefeed::getId() => Livefeed::className(),
				ExternalChannel::getId() => ExternalChannel::className(),
				Request::getId() => Request::className(),
				RestApp::getId() => RestApp::className(),
				Delivery::getId() => Delivery::className(),
				CallTracker::getId() => CallTracker::class,
				StoreDocument::getId() => StoreDocument::className(),
				Document::getId() => Document::className(),
				SignDocument::getId() => SignDocument::className(),
				ToDo::getId() => ToDo::className(),
				Payment::getId() => Payment::className(),
				ConfigurableRestApp::getId() => ConfigurableRestApp::className(),
				CalendarSharing::getId() => CalendarSharing::className(),
				Tasks\Comment::getId() => Tasks\Comment::class,
				Tasks\Task::getId() => Tasks\Task::class,
			];

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

	/**
	 * @param int $activityId
	 * @param array $activityFields - fields after an update, in other words - current fields
	 * @param array $bindings - bindings that were not changed in this update (there is a separate method for bindings change)
	 * @return void
	 */
	final public static function syncBadgesOnActivityUpdate(int $activityId, array $activityFields, array $bindings = []): void
	{
		$provider = \CCrmActivity::GetActivityProvider($activityFields);
		if (!isset($provider))
		{
			return;
		}

		if (empty($bindings))
		{
			$activityBindings = \CCrmActivity::GetBindings($activityId);
			$bindings = is_array($activityBindings) ? $activityBindings : [];
		}

		$bindingsFiltered = self::filterBindings($bindings);
		if (empty($bindingsFiltered))
		{
			return;
		}

		$provider::syncBadges($activityId, $activityFields, $bindingsFiltered);
		foreach ($bindingsFiltered as $singleBinding)
		{
			Monitor::getInstance()->onBadgesSync(
				new ItemIdentifier((int)$singleBinding['OWNER_TYPE_ID'], (int)$singleBinding['OWNER_ID'])
			);
		}
	}

	final public static function syncBadgesOnBindingsChange(
		int $activityId,
		array $addedBindings,
		array $removedBindings
	): void
	{
		$container = Container::getInstance();
		$source = new SourceIdentifier(
			SourceIdentifier::CRM_OWNER_TYPE_PROVIDER,
			\CCrmOwnerType::Activity,
			$activityId,
		);

		$activity = $container->getActivityBroker()->getById($activityId);
		if ($activity)
		{
			$provider = \CCrmActivity::GetActivityProvider($activity);
			if ($provider)
			{
				$provider::syncBadges(
					$activityId,
					$activity,
					self::filterBindings($addedBindings),
				);
			}
		}

		$removedBindingsFiltered = self::filterBindings($removedBindings);
		if (empty($removedBindingsFiltered))
		{
			return;
		}

		$badgesToRemoveQuery =
			\Bitrix\Crm\Badge\Model\BadgeTable::query()
				->setSelect(['TYPE', 'VALUE', 'ENTITY_TYPE_ID', 'ENTITY_ID'])
				->where('SOURCE_PROVIDER_ID', $source->getProviderId())
				->where('SOURCE_ENTITY_TYPE_ID', $source->getEntityTypeId())
				->where('SOURCE_ENTITY_ID', $source->getEntityId())
		;
		$bindingsFilter = Query\Query::filter()->logic(Query\Filter\ConditionTree::LOGIC_OR);
		foreach ($removedBindingsFiltered as $binding)
		{
			$bindingsFilter->where(
				Query\Query::filter()
					->where('ENTITY_TYPE_ID', $binding['OWNER_TYPE_ID'])
					->where('ENTITY_ID', $binding['OWNER_ID'])
			);
		}

		$badgesToRemoveQuery->where($bindingsFilter);

		foreach ($badgesToRemoveQuery->fetchCollection() as $badgeToRemove)
		{
			$badge = $container->getBadge($badgeToRemove->get('TYPE'), $badgeToRemove->get('VALUE'));
			$badge->unbind(
				new ItemIdentifier($badgeToRemove->get('ENTITY_TYPE_ID'), $badgeToRemove->get('ENTITY_ID')),
				$source,
			);
		}
	}

	private static function filterBindings(array $bindings): array
	{
		return array_filter(
			$bindings,
			static function ($binding): bool {
				return (
					is_array($binding)
					&& isset($binding['OWNER_TYPE_ID'])
					&& isset($binding['OWNER_ID'])
					&& \CCrmOwnerType::IsDefined($binding['OWNER_TYPE_ID'])
					&& (int)$binding['OWNER_ID'] > 0
				);
			}
		);
	}
}
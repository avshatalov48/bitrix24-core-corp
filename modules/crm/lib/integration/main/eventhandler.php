<?php
namespace Bitrix\Crm\Integration\Main;

use Bitrix\Crm\Activity\Provider\Email;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Integrity;
use Bitrix\Crm\Category\ItemCategoryUserField;
use Bitrix\Crm\WebForm;
use Bitrix\Main\Application;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Mail\Callback\Result;

/**
 * Class EventHandler
 * @package Bitrix\Crm\Integration\Main
 */
class EventHandler
{
	/**
	 * Handler of event `main/onAfterSetEnumValues`.
	 *
	 * @param Event $event Event.
	 * @return void
	 */
	public static function onAfterSetEnumValues(Event $event)
	{
		$fieldId = $event->getParameter(0);
		$items = $event->getParameter(1);
		$field = \CUserTypeEntity::GetByID($fieldId);
		if(!is_array($field))
		{
			return;
		}

		if (substr($field['ENTITY_ID'], 0, 4) !== 'CRM_')
		{
			return;
		}

		WebForm\EntityFieldProvider::onUpdateUserFieldItems($field, $items);
	}

	/**
	 * Handler of event `main/OnAfterUserTypeDelete`.
	 *
	 * @param array $field Field.
	 * @param int $id ID.
	 * @return void
	 */
	public static function onAfterUserTypeDelete(array $field, $id)
	{
		$crmEntityPrefix = ServiceLocator::getInstance()->get('crm.type.factory')->getUserFieldEntityPrefix();
		if(strpos($field['ENTITY_ID'], $crmEntityPrefix) === 0)
		{
			$entityTypeId = \CCrmOwnerType::ResolveIDByUFEntityID($field['ENTITY_ID']);
			if(isset($field['FIELD_NAME']))
			{
				(new ItemCategoryUserField($entityTypeId))->deleteByName($field['FIELD_NAME']);
			}
		}

		Integrity\Volatile\EventHandler::onUserFieldDelete($field, (int)$id);
	}

	private const ACTIVITY_QUERY_TTL = 600;

	/**
	 * Handler of event main/onMailEventMailChangeStatus.
	 *
	 * @param Result $result Callback result instance.
	 * @return void
	 */
	public static function onMailEventMailChangeStatus(Result $result) : void
	{
		$isBelongCrm = ($result->isBelongTo("crm","rpa") || $result->isBelongTo("crm","act"));
		if (!$isBelongCrm || !$result->isError() || !$result->isPermanentError())
		{
			return;
		}
		if (0 === $id = \CCrmActivity::ParseUrn($result->getEntityId())["ID"])
		{
			return;
		}

		/**@var <string,string[]>$statusChangeRegistry */
		static $statusChangeRegistry;
		if(!$statusChangeRegistry)
		{
			$statusChangeRegistry = [];
			Application::getInstance()->addBackgroundJob(
				static function() use (&$statusChangeRegistry) :void {
					/**@var string[] $results*/
					foreach ($statusChangeRegistry as $id => $results)
					{
						$activity = ActivityTable::query()
							->setSelect(["ID","SETTINGS","AUTHOR_ID","EDITOR_ID","SUBJECT"])
							->setCacheTtl(self::ACTIVITY_QUERY_TTL)
							->where('ID', $id)
							->exec();

						if (!$activity = $activity->fetch())
						{
							return;
						}

						$activity["SETTINGS"] = $activity["SETTINGS"] ?? [];
						$meta = $activity["SETTINGS"]["EMAIL_META"] ?? [];
						$emails = array_filter(
							array_merge(
								explode(", ",$meta["to"] ?? ""),
								explode(", ",$meta["cc"] ?? ""),
								explode(", ",$meta["bcc"] ?? "")
							)
						);
						$diff = array_diff(
							array_unique($emails),
							array_unique($results)
						);

						$status = !empty($diff)? Email::ERROR_TYPE_PARTIAL : Email::ERROR_TYPE_FULL;
						if ($activity["SETTINGS"]["SENT_ERROR"] !== $status)
						{
							$settings = ["SETTINGS" => ["SENT_ERROR" => $status] + $activity["SETTINGS"]];
							ActivityTable::update($id, $settings);
						}
					}
				}
			);
		}

		$statusChangeRegistry[$id] = $statusChangeRegistry[$id] ?? [];
		$statusChangeRegistry[$id][] = $result->getEmail();
	}

	/**
	 * Handler of event main/onMailEventMailChangeStatus.
	 *
	 * @param Result $result Callback result instance.
	 * @return void
	 */
	public static function onMailEventSendNotification(Result $result) : void
	{
		if (
			!$result->isError()
			|| !$result->isPermanentError()
			|| !$result->isBelongTo("crm","act")
			|| !$result->isBlacklistable()
			|| !Loader::includeModule("im")
		)
		{
			return ;
		}
		if (0 === $id = \CCrmActivity::ParseUrn($result->getEntityId())["ID"])
		{
			return;
		}

		/**@var <string,string[]> $notificationRegistry */
		static $notificationRegistry;
		if (!$notificationRegistry)
		{
			$notificationRegistry = [];
			/*static array-registry and background job needs to deduplicate notification*/
			Application::getInstance()->addBackgroundJob(
				static function() use (&$notificationRegistry) : void {
					/**@var string[] $results*/
					foreach ($notificationRegistry as $id => $results)
					{
						$activity = ActivityTable::query()
							->setSelect(["ID","SETTINGS","AUTHOR_ID","EDITOR_ID","SUBJECT"])
							->setCacheTtl(self::ACTIVITY_QUERY_TTL)
							->where('ID', $id)
							->exec();

						if (!$activity = $activity->fetch())
						{
							return;
						}

						$emails = implode(",", array_unique($results));
						$users = array_unique(
							array_filter([$activity["AUTHOR_ID"],$activity["EDITOR_ID"]])
						);
						$message = 	Loc::getMessage(
							"CRM_EMAIL_ERROR_MESSAGE_NOTIFICATION",
							array(
								"%mail_link_start%" => "<a href=\"/crm/activity/?ID={$activity['ID']}&open_view={$activity['ID']}\">",
								"%mail_link_end%" => "</a>",
								"%blacklist_link_start%" => "<a href=\"/settings/configs/mail_blacklist.php\">",
								"%blacklist_link_end%" => "</a>",
								"%subject%" => $activity["SUBJECT"],
								"%emails%" => $emails
							)
						);
						foreach ($users as $userId)
						{
							\CIMNotify::Add([
								"TO_USER_ID" => $userId,
								"FROM_USER_ID" => 0,
								"NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
								"NOTIFY_MODULE" => "crm",
								"NOTIFY_MESSAGE" => $message
							]);
						}
					}
				}
			);
		}

		$notificationRegistry[$id] = $notificationRegistry[$id] ?? [];
		$notificationRegistry[$id][] = $result->getEmail();
	}
}

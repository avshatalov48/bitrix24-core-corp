<?
/**
 * Class implements all further interactions with "socialnetwork" module considering "task item" entity
 *
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Tasks\Integration\Socialnetwork\UI;

use \Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\Collab\Provider\CollabProvider;
use Bitrix\Tasks\Internals\Task\Status;
use CDBResult;
use CUser;

Loc::loadMessages(__FILE__);

final class Task extends \Bitrix\Tasks\Integration\Socialnetwork
{
	private static array $userStorage = [];
	/**
	 * Returns default action path for the current site and task NOT being added to a group
	 *
	 * Example:
	 *
	 * 		if ($arTask["GROUP_ID"] > 0)
			{
			$path = str_replace("#group_id#", $arTask["GROUP_ID"], COption::GetOptionString("tasks", "paths_task_group_entry", "/workgroups/group/#group_id#/tasks/task/view/#task_id#/", $arTask["SITE_ID"]));
			}
			else
			{
			$path = str_replace("#user_id#", $arTask["RESPONSIBLE_ID"], COption::GetOptionString("tasks", "paths_task_user_entry", "/company/personal/user/#user_id#/tasks/task/view/#task_id#/", $arTask["SITE_ID"]));
			}
	 *
	 * @return string
	 */
	public static function getActionPath($groupId = 0, $userId = 0, $siteId = '')
	{
		// todo: somehow make correlation with CTaskNotification::getNotificationPath(), it has very similar functionality

		if(\Bitrix\Tasks\Integration\Extranet::isExtranetSite())
		{
			$urlPrefix = '/extranet/contacts/personal';
		}
		else
		{
			$optionPath = (string) \COption::getOptionString('intranet', 'path_task_user_entry'); // tasks was previously in intranet
			if($optionPath != '')
			{
				$optionPath = (string) \COption::getOptionString('tasks', 'paths_task_user_action');
			}

			if($optionPath != '')
			{
				return $optionPath;
			}

			// todo: if $siteId is set, use its dir, not SITE_DIR
			$urlPrefix = (defined('SITE_DIR') ? SITE_DIR : '/').'company/personal';
		}

		return $urlPrefix.'/user/#user_id#/tasks/task/#action#/#task_id#/';
	}

	/**
	 * Formats task entity to show in the Live Feed. Eventually, just call the component bitrix:tasks.task.livefeed
	 *
	 * @param $arFields
	 * @param $arParams
	 * @return array
	 */
	public static function formatFeedEntry($arFields, $arParams)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$task_datetime = null;

		if(!static::includeModule())
		{
			return false;
		}

		\Bitrix\Main\UI\Extension::load(['ui.design-tokens', 'ui.fonts.opensans']);
		$APPLICATION->SetAdditionalCSS('/bitrix/js/tasks/css/tasks.css');

		if (isset($arFields['~PARAMS']) && $arFields['~PARAMS'])
			$arFields['PARAMS'] = unserialize($arFields['~PARAMS'], ['allowed_classes' => false]);
		elseif (isset($arFields['PARAMS']) && $arFields['PARAMS'])
			$arFields['PARAMS'] = unserialize($arFields['PARAMS'], ['allowed_classes' => false]);
		else
			$arFields['PARAMS'] = array();

		$arResult = array(
			'EVENT'           => $arFields,
			'CREATED_BY'      => \CSocNetLogTools::FormatEvent_GetCreatedBy($arFields, $arParams, false),
			'ENTITY'          => \CSocNetLogTools::FormatEvent_GetEntity($arFields, $arParams, false),
			'EVENT_FORMATTED' => array(),
			'CACHED_CSS_PATH' => '/bitrix/js/tasks/css/tasks.css'
		);

		$arResult["AVATAR_SRC"] = \CSocNetLogTools::FormatEvent_CreateAvatar($arFields, $arParams);

		// here we must have user-related personalized link. Because of cache usage, the #USER_PERSONAL_TASK_URL#
		// is replaced by socialnetwork just before feed display
		$taskHtmlTitle = '<a href="#USER_PERSONAL_TASK_URL#">'.\Bitrix\Main\Text\Emoji::decode($arFields["TITLE"]).'</a>';

		// Prepare event title (depends on action and gender of actor)
		{
			$actorUserId = null;
			$actorUserName = '';
			$actorMaleSuffix = '';
			$eventTitlePhraseSuffix = '_DEFAULT';

			if (isset($arParams['NAME_TEMPLATE']))
				$nameTemplate = $arParams['NAME_TEMPLATE'];
			else
				$nameTemplate = \CSite::GetNameFormat();

			if (isset($arFields["PARAMS"], $arFields['PARAMS']['TYPE']))
			{
				if ($arFields["PARAMS"]["TYPE"] === "create")
				{
					$eventTitlePhraseSuffix = '_CREATE_24';
					if (isset($arFields["PARAMS"]["CREATED_BY"]))
						$actorUserId = $arFields["PARAMS"]["CREATED_BY"];
				}
				elseif ($arFields["PARAMS"]["TYPE"] === 'modify')
				{
					$eventTitlePhraseSuffix = '_MODIFY_24';
					if (isset($arFields["PARAMS"]["CHANGED_BY"]))
						$actorUserId = $arFields["PARAMS"]["CHANGED_BY"];
				}
				elseif ($arFields["PARAMS"]["TYPE"] === 'status')
				{
					$eventTitlePhraseSuffix = '_STATUS_24';
					if (isset($arFields["PARAMS"]["CHANGED_BY"]))
						$actorUserId = $arFields["PARAMS"]["CHANGED_BY"];
				}
				elseif ($arFields["PARAMS"]["TYPE"] === 'comment')
				{
					$eventTitlePhraseSuffix = '';
				}
			}

			if ($actorUserId)
			{
				$arUser = self::getUser((int)$actorUserId);

				if ($arUser)
				{
					if (isset($arUser['PERSONAL_GENDER']))
					{
						switch ($arUser['PERSONAL_GENDER'])
						{
							case "F":
							case "M":
								$actorMaleSuffix = '_' . $arUser['PERSONAL_GENDER'];
								break;
						}
					}

					$actorUserName = CUser::FormatName($nameTemplate, $arUser, true);
				}
			}

			$eventTitleTemplate = Loc::getMessage('TASKS_SONET_GL_EVENT_TITLE_TASK'
				. $eventTitlePhraseSuffix . $actorMaleSuffix);

			$eventTitle = str_replace(
				array('#USER_NAME#', '#TITLE#'),
				array($actorUserName, $taskHtmlTitle),
				$eventTitleTemplate
			);
			$eventTitleWoTaskName = str_replace(
				array('#USER_NAME#', '#TITLE#'),
				array($actorUserName, ''),
				$eventTitleTemplate
			);
		}

		$title_tmp = str_replace(
			"#TITLE#",
			$taskHtmlTitle,
			Loc::getMessage("TASKS_SONET_GL_EVENT_TITLE_TASK")
		);

		if(isset($arFields["PARAMS"]["CREATED_BY"]))
		{
			$suffix = (
				isset($GLOBALS['arExtranetUserID']) && is_array($GLOBALS['arExtranetUserID']) && in_array($arFields['PARAMS']['CREATED_BY'], $GLOBALS['arExtranetUserID'])
					? Loc::getMessage('TASKS_SONET_LOG_EXTRANET_SUFFIX')
					: ''
			);

			$arUser = self::getUser((int)$arFields['PARAMS']['CREATED_BY']);

			if ($arUser)
			{
				$title_tmp .= " ("
					. str_replace(
						"#USER_NAME#",
						CUser::FormatName(\CSite::GetNameFormat(false), $arUser) . $suffix,
						Loc::getMessage("TASKS_SONET_GL_EVENT_TITLE_TASK_CREATED")
					)
					. ")";
			}
		}

		$title = $title_tmp;

		if (in_array(
			$arFields["PARAMS"]["TYPE"],
			array("create", "status", 'modify', 'comment'),
			true
		))
		{
			if ( ! (
				isset($arFields['PARAMS']['CHANGED_FIELDS'])
				&& is_array($arFields['PARAMS']['CHANGED_FIELDS'])
			))
			{
				$arFields['PARAMS']['CHANGED_FIELDS'] = array();
			}

			$rsTask = \CTasks::GetByID($arFields["SOURCE_ID"], false);
			if ($arTask = $rsTask->Fetch())
			{
				$task_datetime = $arTask["CHANGED_DATE"];
				if ($arFields["PARAMS"]["TYPE"] == "create")
				{
					if (isset($arParams["MOBILE"]) && $arParams["MOBILE"] == "Y")
					{
						$message_24_1 = $taskHtmlTitle;
					}
					else
					{
						$message      = $message_24_1 = $eventTitle;
						$message_24_2 = $changes_24 = "";
					}
				}
				elseif ($arFields["PARAMS"]["TYPE"] == "modify")
				{
					$arChangesFields = $arFields["PARAMS"]["CHANGED_FIELDS"];
					$changes_24 = implode(", ", \CTaskNotifications::__Fields2Names($arChangesFields));

					if (
						isset($arParams["MOBILE"])
						&& $arParams["MOBILE"] == "Y"
					)
					{
						$message_24_1 = $taskHtmlTitle;
					}
					else
					{
						$message = str_replace(
							"#CHANGES#",
							implode(", ", \CTaskNotifications::__Fields2Names($arChangesFields)),
							Loc::getMessage("TASKS_SONET_GL_TASKS2_TASK_CHANGED_MESSAGE")
						);
						$message_24_1 = $eventTitle;
						$message_24_2 = Loc::getMessage("TASKS_SONET_GL_TASKS2_TASK_CHANGED_MESSAGE_24_2");
					}
				}
				elseif ($arFields["PARAMS"]["TYPE"] == "status")
				{
					$message = Loc::getMessage("TASKS_SONET_GL_TASKS2_TASK_STATUS_MESSAGE_".$arTask["STATUS"]);

					$message_24_1 = $eventTitle;

					if ((int)$arTask["STATUS"] === Status::DECLINED)
					{
						$message      = str_replace("#TASK_DECLINE_REASON#", $arTask["DECLINE_REASON"], $message);
						$message_24_2 = Loc::getMessage("TASKS_SONET_GL_TASKS2_TASK_STATUS_MESSAGE_".$arTask["STATUS"]."_24_2");
						$changes_24   = $arTask["DECLINE_REASON"];
					}
					else
						$message_24_2 = $changes_24 = $message;
				}
				elseif ($arFields['PARAMS']['TYPE'] === 'comment')
				{
					$message_24_1 = $eventTitle;
					$message_24_2 = $changes_24 = $message = '';
				}

				$prevRealStatus = false;

				if (isset($arFields['PARAMS']['PREV_REAL_STATUS']))
					$prevRealStatus = $arFields['PARAMS']['PREV_REAL_STATUS'];

				ob_start();
				$template = '';
				$isMobile = 'N';
				if (isset($arParams["MOBILE"]) && $arParams["MOBILE"] === 'Y')
				{
					$template = 'mobile';
					$isMobile = 'Y';
				}
				$GLOBALS['APPLICATION']->IncludeComponent(
					"bitrix:tasks.task.livefeed",
					$template,
					array(
						"MOBILE"        => $isMobile,
						"TASK"          => $arTask,
						"MESSAGE"       => ($message ?? null),
						"MESSAGE_24_1"  => ($message_24_1 ?? null),
						"MESSAGE_24_2"  => ($message_24_2 ?? null),
						"CHANGES_24"    => $changes_24,
						"NAME_TEMPLATE"	=> ($arParams["NAME_TEMPLATE"] ?? null),
						"PATH_TO_USER"	=> ($arParams["PATH_TO_USER"] ?? null),
						'TYPE'          => $arFields["PARAMS"]["TYPE"],
						'task_tmp'      => $taskHtmlTitle,
						'taskHtmlTitle' => $taskHtmlTitle,
						'PREV_REAL_STATUS' => $prevRealStatus
					),
					null,
					array("HIDE_ICONS" => "Y")
				);
				$arFields["MESSAGE"] = ob_get_contents();
				ob_end_clean();
			}
		}

		if (isset($arParams['MOBILE']) && $arParams["MOBILE"] === "Y")
		{
			$arResult["EVENT_FORMATTED"] = array(
				"TITLE"             => '',
				"TITLE_24"          => $eventTitleWoTaskName,
				"MESSAGE"           => htmlspecialcharsbx($arFields['MESSAGE']),
				"DESCRIPTION"       => $arFields['TITLE'],
				"DESCRIPTION_STYLE" => 'task'
			);
		}
		else
		{
			$strMessage      = $arFields['MESSAGE'];
			$strShortMessage = ($arFields['~MESSAGE'] ?? null);
			$url = ($arFields['~URL'] ?? '');

			$arResult["EVENT_FORMATTED"] = array(
				"TITLE"            => $title,
				"MESSAGE"          => $strMessage,
				"SHORT_MESSAGE"    => $strShortMessage,
				"IS_MESSAGE_SHORT" => true,
				"STYLE"            => 'tasks-info',
				"COMMENT_URL"      => $url . (mb_strpos($url, '?') > 0 ? '&' : '?') . 'MID=#ID##com#ID#'
			);
		}

		if ($arFields["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP)
		{
			$isExtranet =
				is_array($GLOBALS["arExtranetGroupID"] ?? null)
				&& in_array($arFields['ENTITY_ID'], $GLOBALS['arExtranetGroupID'])
			;
			$arResult['EVENT_FORMATTED']['DESTINATION'] = [
				[
					'STYLE' => 'sonetgroups',
					'TITLE' => $arResult['ENTITY']['FORMATTED']['NAME'],
					'URL' => $arResult['ENTITY']['FORMATTED']['URL'],
					'IS_EXTRANET' => $isExtranet,
					'IS_COLLAB' => $isExtranet && CollabProvider::getInstance()->isCollab((int)$arFields['ENTITY_ID']),
				],
			];
		}

		if ($task_datetime !== '')
		{
			$arResult["EVENT_FORMATTED"]["LOG_DATE_FORMAT"] = $task_datetime;
		}

		return $arResult;
	}

	private static function getUser(int $userId): bool|array
	{
		if (!isset(self::$userStorage[$userId]))
		{
			self::$userStorage[$userId] = CUser::GetList(
				'id',
				'asc',
				[
					'ID_EQUAL_EXACT' => $userId
				],
				[
					'FIELDS' => [
						'PERSONAL_GENDER',
						'ID',
						'NAME',
						'LAST_NAME',
						'SECOND_NAME',
						'LOGIN',
					],
				]
			)->Fetch();
		}

		return self::$userStorage[$userId];
	}
}
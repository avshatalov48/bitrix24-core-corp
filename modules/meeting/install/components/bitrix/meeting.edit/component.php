<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!CModule::IncludeModule("meeting"))
	return ShowError(GetMessage("ME_MODULE_NOT_INSTALLED"));

$arParams['MEETING_ID'] = intval($arParams['MEETING_ID']);
$arParams['COPY'] = $arParams['COPY'] == 'Y';
$arParams['GROUP_ID'] = intval($arParams['GROUP_ID']);

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

if ($arParams['MEETING_ID'] && !$arParams['COPY'])
	$arParams['ITEM_URL'] .= '?from='.$arParams['MEETING_ID'];

//$arParams['CALENDAR_ID'] = intval($arParams['CALENDAR_ID']);

$arParams['EDIT'] = $arParams['EDIT'] == 'Y' || $arParams['MEETING_ID'] <= 0 || $arParams['COPY'];

$arResult['IS_NEW_CALENDAR'] = CMeeting::IsNewCalendar();

$arResult['START_INDEX'] = 0;
if ($arParams['MEETING_ID'] > 0)
{
	$arResult['ACCESS'] = CMeeting::GetUserRole($arParams['MEETING_ID']);
	if ($arResult['ACCESS'])
	{
		if ($_REQUEST['DELETE'] == 'Y' && $arResult['ACCESS'] == CMeeting::ROLE_OWNER && check_bitrix_sessid())
		{
			CMeeting::Delete($arParams['MEETING_ID']);
			$APPLICATION->RestartBuffer();
			die();
		}

		$dbRes = CMeeting::GetList(array(), array('ID' => $arParams['MEETING_ID']), false, false, array('*'));
		if (!$arResult['MEETING'] = $dbRes->GetNext())
		{
			return ShowError(GetMessage("ME_MEETING_NOT_FOUND"));
		}

		if(CMeeting::CheckPlace($arResult["MEETING"]["PLACE"]))
		{
			$arResult["MEETING"]["PLACE_ID"] = $arResult["MEETING"]["PLACE"];
		}

		$arResult['MEETING']['USERS'] = CMeeting::GetUsers($arParams['MEETING_ID']);
		$arResult['MEETING']['CURRENT_RIGHTS'] = $arResult['ACCESS'];//$arResult['MEETING']['USERS'][$USER->GetID()]; // not arParams[USER_ID]!
		if (!$arResult['MEETING']['CURRENT_RIGHTS'])
			return ShowError(GetMessage("ME_MEETING_ACCESS_DENIED"));

		$arResult['MEETING']['FILES'] = array();
		$dbFiles = CMeeting::GetFiles($arParams['MEETING_ID']);
		while ($arFile = $dbFiles->Fetch())
		{
			$arResult['MEETING']['FILES'][$arFile['FILE_ID']] = $arFile;
		}

		if (!$arParams['COPY'] && $arResult['MEETING']['EVENT_ID'] > 0)
		{
			if ($arResult['IS_NEW_CALENDAR'])
			{
				$arResult['MEETING']['EVENT'] = CMeeting::GetEvent($arResult['MEETING']['EVENT_ID']);
			}

			$arResult['MEETING']['USERS_EVENT'] = array();
			$arGuests = CMeeting::GetEventGuests($arResult['MEETING']['EVENT_ID'], $USER->GetID());
			foreach ($arGuests as $guest)
			{
				$arResult['MEETING']['USERS_EVENT'][$guest['id']] = $guest['status'];
			}
		}

		$arResult['CAN_EDIT'] = $arParams['COPY'] || $arResult['MEETING']['CURRENT_RIGHTS'] == CMeeting::ROLE_OWNER || $arResult['MEETING']['CURRENT_RIGHTS'] == CMeeting::ROLE_KEEPER;

		$arResult['MEETING']['AGENDA'] = array();

		if (!$arParams['COPY'])
		{
			$dbRes = CMeeting::GetItems($arParams['MEETING_ID']);
			while ($arRes = $dbRes->GetNext())
			{
				// if ($arParams['COPY'] && $arRes['INSTANCE_TYPE'] == CMeetingInstance::TYPE_AGENDA)
					// continue;

				$arRes['RESPONSIBLE'] = CMeetingInstance::GetResponsible($arRes['ID']);

				$arRes['REPORTS'] = array();
				$dbReports = CMeetingReports::GetList(array('ID' => 'ASC'), array('INSTANCE_ID' => $arRes['ID']));
				while ($arReport = $dbReports->Fetch())
				{
					$arFiles = array();
					$dbFiles = CMeetingReports::GetFiles($arReport['ID']);
					while ($arFile = $dbFiles->Fetch())
					{
						$arFiles[$arFile['FILE_ID']] = $arFile;
					}

					$arRes['REPORTS'][] = array(
						'ID' => $arReport['ID'],
						'REPORT' => $arReport['REPORT'],
						'USER_ID' => $arReport['USER_ID'],
						'FILES' => CMeeting::GetFilesData($arFiles, array(
							"REPORT" => $arReport['ID']
						)),
					);
				}

				$arRes['FILES'] = array();
				if($arRes['ITEM_ID'] > 0)
				{
					$dbFiles = CMeetingItem::GetFiles($arRes['ITEM_ID']);
					while ($arFile = $dbFiles->Fetch())
					{
						$arRes['FILES'][$arFile['FILE_ID']] = $arFile['FILE_SRC'];
					}

					if(count($arRes['FILES']) > 0)
					{
						$arRes['FILES'] = CMeeting::GetFilesData($arRes['FILES'], array("ITEM" => $arRes['ITEM_ID']));
					}
				}

				$arRes['TASKS_COUNT'] = CMeetingItem::GetTasksCount($arRes['ITEM_ID'], $arRes['ID']);

				if (!$arParams['COPY'])
				{
					$arRes['EDITABLE'] = CMeetingItem::IsEditable($arRes['ITEM_ID']);
					if ($arRes['TASK_ID'] && CModule::IncludeModule('tasks'))
					{
						$dbTask = CTasks::GetByID($arRes['TASK_ID']);
						if ($arTask = $dbTask->Fetch())
							$arRes['TASK_ACCESS'] = true;
					}
				}


				$arResult['MEETING']['AGENDA'][$arRes['ID']] = $arRes;
			}
		}

		if ($arResult['MEETING']['DATE_START'] && MakeTimeStamp($arResult['MEETING']['DATE_START'])>0)
		{
			$arFormats = array(
				'ru' => 'j F',
				'en' => 'F j',
				'de' => 'j. F',
			);

			$dateFormat = isset($arFormats[LANGUAGE_ID])
				? $arFormats[LANGUAGE_ID]
				: $arFormats[LangSubst(LANGUAGE_ID)];

			$APPLICATION->SetTitle(GetMessage('ME_MEETING_EDIT', array(
				'#ID#' => $arResult['MEETING']['ID'],
				'#DATE#' => FormatDate($dateFormat, MakeTimeStamp($arResult['MEETING']['DATE_START'])),
				'#TITLE#' => $arResult['MEETING']['TITLE'],
			)));

//			$arResult['MEETING']['DATE_START'] = FormatDate($DB->DateFormatToPhp(FORMAT_DATE).' H:i', MakeTimeStamp($arResult['MEETING']['DATE_START']));
//			$arResult['MEETING']['DATE_START'] = date($DB->DateFormatToPhp(FORMAT_DATE).((IsAmPmMode()) ? ' g:i a' : ' H:i'), MakeTimeStamp($arResult['MEETING']['DATE_START']));
		}
		else
			$APPLICATION->SetTitle(GetMessage('ME_MEETING_EDIT_NO_DATE', array(
				'#ID#' => $arResult['MEETING']['ID'],
				'#TITLE#' => $arResult['MEETING']['TITLE'],
			)));
	}
	else
	{
		return ShowError(GetMessage("ME_MEETING_ACCESS_DENIED"));
	}
}

if ($arParams['SET_NAVCHAIN'] !== 'N')
	$APPLICATION->AddChainItem(strlen($arResult['MEETING']['TITLE']) > 0 ? $arResult['MEETING']['TITLE'] : GetMessage('ME_MEETING_ADD'), $arParams['MEETING_URL']);

if ($arParams['COPY'])
{
	$APPLICATION->SetTitle(GetMessage('ME_MEETING_COPY'));
	$arResult['ACCESS'] = CMeeting::ROLE_OWNER;

	$arResult['MEETING']['PARENT_ID'] = $arResult['MEETING']['ID'];
	unset($arResult['MEETING']['ID']);
	unset($arResult['MEETING']['EVENT_ID']);
	unset($arResult['MEETING']['DATE_FINISH']);

	if ($arResult['MEETING']['DATE_START']&&MakeTimeStamp($arResult['MEETING']['DATE_START'])>0)
	{
		$t = MakeTimeStamp($arResult['MEETING']['DATE_START']);
		$d = ConvertTimeStamp($t - $t%3600 + CTimeZone::GetOffset() + 7*86400, 'FULL');
	}
	else
	{
		$t = time();
		$d = ConvertTimeStamp($t - $t%3600 + CTimeZone::GetOffset() + 3600, 'FULL');
	}

	$arResult['MEETING']['DATE_START'] = $d;
}
else if ($arParams['MEETING_ID'] <= 0)
{
	$APPLICATION->SetTitle(GetMessage('ME_MEETING_ADD'));
	$arResult['ACCESS'] = CMeeting::ROLE_OWNER;
	$t = time();
	// default date - in 3 days at current time + 1 hour
	$d = ConvertTimeStamp($t - $t%3600 + CTimeZone::GetOffset() + 3600, 'FULL');
	$arResult['MEETING'] = array(
		"DURATION" => 1200,
		"DATE_START" => $d,
		"USERS" => array($USER->GetID() => CMeeting::ROLE_OWNER),
		"AGENDA" => array(),
		"FILES" => array(),
	);
	$arResult['CAN_EDIT'] = true;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_REQUEST['save']) && $arResult['CAN_EDIT'] && check_bitrix_sessid())
{
	$bFromEditForm = $_POST['edit'] == 'Y';
	$arParams['COPY'] = $_POST['COPY'] == 'Y';

	$MEETING_ID = $arParams['MEETING_ID'];

	$res = true;
	$bNew = false;
	$bUpdateEvent = false;
	if ($bFromEditForm)
	{
		$bUpdateEvent = true;

		$res = false;

		$arFields = array(
			'TITLE' => trim($_REQUEST['TITLE']),
			'DESCRIPTION' => trim($_REQUEST['DESCRIPTION']),
			'DATE_START' => CMeeting::MakeDateTime($_REQUEST['DATE_START_DATE'], $_REQUEST['DATE_START_TIME']),
			'DURATION' => $_REQUEST['DURATION'] * $_REQUEST['DURATION_COEF'],
			'PLACE' => $_REQUEST['PLACE'],
			'GROUP_ID' => $_REQUEST['GROUP_ID'],
		);

		if(strlen($_REQUEST['PLACE_ID'])>0)
			$arFields['PLACE'] = $_REQUEST['PLACE_ID'];

		$TextParser = new CBXSanitizer();
		$TextParser->SetLevel(CBXSanitizer::SECURE_LEVEL_LOW);
		$arFields['DESCRIPTION'] = $TextParser->SanitizeHtml($arFields['DESCRIPTION']);

		if (strlen($arFields['TITLE']) <= 0)
			$arFields['TITLE'] = GetMessage('ME_MEETING_TITLE_DEFAULT');

		$arFields['FILES'] = \Bitrix\Main\UI\FileInputUtility::instance()->checkFiles(
			'MEETING_DESCRIPTION',
			$_REQUEST['FILES']
		);

		$deletedFileList = \Bitrix\Main\UI\FileInputUtility::instance()->checkDeletedFiles('MEETING_DESCRIPTION');

		if ($arParams['COPY'])
		{
			$arResult['MEETING']['OWNER_ID'] = $USER->GetID();
			$arFields['PARENT_ID'] = $_REQUEST['PARENT_ID'];
			$MEETING_ID = 0;
		}

		if(isset($_REQUEST['PROTOCOL_TEXT']))
		{
			$arFields['PROTOCOL_TEXT'] = trim($_REQUEST['PROTOCOL_TEXT']);
		}

		if ($MEETING_ID > 0)
		{
			$res = CMeeting::Update($MEETING_ID, $arFields);
		}
		else
		{
			$MEETING_ID = CMeeting::Add($arFields);
			$res = $MEETING_ID > 0;
			$bNew = true;
		}
	}
	elseif (isset($_REQUEST['PROTOCOL_TEXT']))
	{
		$arFields = array(
			'PROTOCOL_TEXT' => trim($_REQUEST['PROTOCOL_TEXT'])
		);
		CMeeting::Update($MEETING_ID, $arFields);
	}

	if ($res)
	{
		$arEventParams = null;

		$arUsers = $_REQUEST['USERS'];
		if (!is_array($arUsers))
			$arUsers = array();
		$arKeepers = $_REQUEST['KEEPERS'];
		if (!is_array($arKeepers))
			$arKeepers = array();

		$USERS = array(
			$USER->GetID() => $arResult['ACCESS']
		);

		foreach ($arKeepers as $USER_ID)
		{
			if (!isset($USERS[$USER_ID]))
				$USERS[$USER_ID] = CMeeting::ROLE_KEEPER;
		}

		foreach ($arUsers as $USER_ID)
		{
			if (!isset($USERS[$USER_ID]))
				$USERS[$USER_ID] = CMeeting::ROLE_MEMBER;
		}

		if ($arResult['MEETING']['OWNER_ID'])
			$USERS[$arResult['MEETING']['OWNER_ID']] = CMeeting::ROLE_OWNER;

		if (count($USERS) > 0)
		{
			$APPLICATION->RestartBuffer();

			if (!$bUpdateEvent)
			{
				if (count($USERS) != count($arResult['MEETING']['USERS']))
				{
					$bUpdateEvent = true;
				}
				else
				{
					foreach ($arResult['MEETING']['USERS'] as $key => $value)
					{
						if (!$USERS[$key] || $USERS[$key] != $value)
						{
							$bUpdateEvent = true;
							break;
						}
					}

				}
			}

			if ($bUpdateEvent)
			{
				CMeeting::SetUsers($MEETING_ID, $USERS, true);
				$arEventParams = array(
					$MEETING_ID,
					array(
						'ID' => $MEETING_ID,
						'USERS' => $USERS,
						'OWNER_ID' => isset($arResult['MEETING']['OWNER_ID'])
							? $arResult['MEETING']['OWNER_ID'] : $USER->GetID(),
						'EVENT_ID' => isset($arFields['EVENT_ID'])
							? $arFields['EVENT_ID'] : $arResult['MEETING']['EVENT_ID'],
						'STATE' => $arResult['MEETING']['CURRENT_STATE'],
						'TITLE' => isset($arFields['TITLE'])
							? $arFields['TITLE'] : $arResult['MEETING']['TITLE'],
						'DESCRIPTION' => isset($arFields['DESCRIPTION'])
							? $arFields['DESCRIPTION'] : $arResult['MEETING']['DESCRIPTION'],
						'DATE_START' => isset($arFields['DATE_START'])
							? $arFields['DATE_START'] : $arResult['MEETING']['DATE_START'],
						'DATE_FINISH' => isset($arFields['DATE_FINISH'])
							? $arFields['DATE_FINISH'] : $arResult['MEETING']['DATE_FINISH'],
						'DURATION' => isset($arFields['DURATION'])
							? $arFields['DURATION'] : $arResult['MEETING']['DURATION'],
						'PLACE' => isset($arFields['PLACE'])
							? $arFields['PLACE'] : $arResult['MEETING']['PLACE'],
					),
					$arParams
				);

				if ($bFromEditForm && isset($_REQUEST['EVENT_NOTIFY']))
				{
					if (isset($_REQUEST['EVENT_REINVITE']))
						$arEventParams[1]['REINVITE'] = $_REQUEST['EVENT_REINVITE'] == 'Y';

					$arEventParams[1]['NOTIFY'] = $_REQUEST['EVENT_NOTIFY'] == 'Y';
				}
				elseif ($arResult['MEETING']['EVENT'])
				{
					$arEventParams[1]['NOTIFY'] = $arResult['MEETING']['EVENT']['MEETING']['NOTIFY'];
				}
			}
		}

		$arAgenda = $_REQUEST['AGENDA'];
		$bDeleted = false;
		if (is_array($arAgenda))
		{
			$arNewAgendaMap = array();
			$arNewAgendaTasks = array();

			if (isset($_REQUEST['AGENDA_TASK']) && CModule::IncludeModule('tasks'))
			{
				$arEmplIDs = null;
			}

			foreach ($arAgenda as $key => $item)
			{
				if ($_REQUEST['AGENDA_PARENT'][$key] && $_REQUEST['AGENDA_PARENT'][$key] == 'outside')
				{
					$_REQUEST['AGENDA_PARENT'][$key] = 0;
				}

				if ($_REQUEST['AGENDA_PARENT'][$key] && intval($_REQUEST['AGENDA_PARENT'][$key]) <= 0)
				{
					if (
					// if parent instance is new and we have inserted it
						array_key_exists($_REQUEST['AGENDA_PARENT'][$key], $arNewAgendaMap)
						&& $arNewAgendaMap[$_REQUEST['AGENDA_PARENT'][$key]][0] > 0
					)
					{
						// then we've its real ID and can use it
						$_REQUEST['AGENDA_PARENT'][$key] = $arNewAgendaMap[$_REQUEST['AGENDA_PARENT'][$key]][0];
					}
					// otherwise if we haven't view this item yet
					else if (!array_key_exists($key, $arNewAgendaMap))
					{
						// we make a note that we have already viewed it
						$arNewAgendaMap[$key] = array(0);
						// and shift it to the end of the list
						unset($arAgenda[$key]);
						$arAgenda[$key] = $item;
						// C_ya_l8r!
						continue;
					}
					else
					{
						// but if we've already view this item and parent still doesn't exists
						// we should set parent to 0 and shut up ;-(
						$_REQUEST['AGENDA_PARENT'][$key] = 0;
					}
				}

				$arFields = array('MEETING_ID' => $MEETING_ID);

				$bNew = (intval($key) == 0);
				if ($bNew)
				{
					$arFields['MEETING_ID'] = $MEETING_ID;

					if ($_REQUEST['AGENDA_ITEM'][$key])
					{
						$arFields['ITEM_ID'] = $_REQUEST['AGENDA_ITEM'][$key];
					}
				}

				$arFields['SORT'] = $_REQUEST['AGENDA_SORT'][$key];

				if ($bNew || isset($arResult['MEETING']['AGENDA'][$key]) && $arResult['MEETING']['AGENDA'][$key]['EDITABLE'])
				{
					$arFields['TITLE'] = trim($_REQUEST['AGENDA_TITLE'][$key]);

					if ($bNew)
						$arFields['ITEM_ID'] = $_REQUEST['AGENDA_ITEM'][$key];
					else
						$arFields['ITEM_ID'] = $arResult['MEETING']['AGENDA'][$key]['ITEM_ID'];

					if (strlen($arFields['TITLE']) <= 0 || $arFields['TITLE'] == GetMessage('ME_MEETING_TITLE_DEFAULT') || $arFields['TITLE'] == GetMessage('ME_MEETING_TITLE_DEFAULT_1'))
					{
						if ($bNew && !$arFields['ITEM_ID'])
							continue;
						else
							unset($arFields['TITLE']);
					}
					//$arFields['DESCRIPTION'] = trim($_REQUEST['AGENDA_DESCRIPTION'][$key]);
				}

				if ($_REQUEST['AGENDA_DELETED'][$key])
				{
					if (!$bNew)
					{
						$bDeleted = true;
						CMeetingInstance::Delete($key, true);
					}

					continue;
				}

				$arFields['RESPONSIBLE'] = $_REQUEST['AGENDA_RESPONSIBLE'][$key];
				$arFields['DEADLINE'] = $_REQUEST['AGENDA_DEADLINE'][$key];

				if (isset($_REQUEST['AGENDA_TYPE'][$key]))
				{
					$arFields['INSTANCE_TYPE'] =
						$_REQUEST['AGENDA_TYPE'][$key] == CMeetingInstance::TYPE_TASK
							? CMeetingInstance::TYPE_TASK : CMeetingInstance::TYPE_AGENDA;
				}

				if ($bNew)
				{
					$arFields['ORIGINAL_TYPE'] =
						$_REQUEST['AGENDA_ORIGINAL'][$key] == CMeetingInstance::TYPE_TASK
						? CMeetingInstance::TYPE_TASK : CMeetingInstance::TYPE_AGENDA;
				}

				if (isset($_REQUEST['AGENDA_PARENT'][$key]))
					$arFields['INSTANCE_PARENT_ID'] = intval($_REQUEST['AGENDA_PARENT'][$key]);

				if ($_REQUEST['AGENDA_TASK'][$key] && !$arResult['MEETING']['AGENDA'][$key]['TASK_ID'])
				{
					$TASK_ID = intval($_REQUEST['AGENDA_TASK'][$key]);
					if ($TASK_ID <= 0)
					{
						if ($arEmplIDs === null)
						{
							$arEmplIDs = array();
							$dbEmpl = CIntranetUtils::GetSubordinateEmployees($arResult['MEETING']['OWNER_ID'], true, 'Y', array('ID'));
							while ($arEmpl = $dbEmpl->Fetch())
							{
								$arEmplIDs[$arEmpl['ID']] = true;
							}
						}

						$taskDeadline = '';
						// Skip invalid deadline
						if (
							isset($arFields['DEADLINE'])
							&& (MakeTimeStamp($arFields['DEADLINE']) > 0)
						)
						{
							$taskDeadline = $arFields['DEADLINE'];
						}

						$responsibleId = $arFields['RESPONSIBLE'][0];
						$arTaskFields = array(
							'RESPONSIBLE_ID' => $responsibleId > 0 ? $responsibleId : $USER->GetID(),
							'TITLE' => strlen($arFields['TITLE']) > 0 ? $arFields['TITLE'] : $arResult['MEETING']['AGENDA'][$key]['TITLE'],
							'DEADLINE' => $taskDeadline,
							'TAGS' => array(),
							'STATUS' => 2,
							'SITE_ID' => SITE_ID
						);

						if ($arResult['MEETING']['OWNER_ID'] && $arResult['MEETING']['OWNER_ID'] != $USER->GetID())
						{
							$arTaskFields['CREATED_BY'] = $arResult['MEETING']['OWNER_ID'];
							$arTaskFields['AUDITORS'] = array($USER->GetID());
						}

						if ($_REQUEST['GROUP_ID'] > 0)
						{
							$arTaskFields['GROUP_ID'] = (int)$_REQUEST['GROUP_ID'];
						}
						elseif ($arParams['MEETING_ID'] > 0)
						{
							$rsTasks_rsMeetingData = CMeeting::GetList(array(), array('ID' => $arParams['MEETING_ID']));
							if ($arTasks_arMeetingData = $rsTasks_rsMeetingData->Fetch())
							{
								if ($arTasks_arMeetingData['GROUP_ID'] > 0)
								{
									$arTaskFields['GROUP_ID'] = (int)$arTasks_arMeetingData['GROUP_ID'];
								}
							}
						}

						$taskItem = CTaskItem::add($arTaskFields, $USER->GetID());
						$TASK_ID = $taskItem->getId();
					}

					if ($TASK_ID > 0)
					{
						$arNewAgendaTasks[$key] = $TASK_ID;
						$arFields['TASK_ID'] = $TASK_ID;
					}
				}

				if ($bNew)
				{
					if (!$arFields['ITEM_ID'])
					{
						$arFields['ITEM_ID'] = CMeetingItem::Add($arFields, true);
						$INSTANCE_ID = CMeetingInstance::Add($arFields);
					}
					else
					{
						$INSTANCE_ID = CMeetingInstance::Add($arFields);
					}
					$arNewAgendaMap[$key] = array($INSTANCE_ID, $arFields['ITEM_ID']);
				}
				else
				{
					if ($arFields['TITLE'])
					{
						CMeetingItem::Update($arFields['ITEM_ID'], $arFields);
					}

					CMeetingInstance::Update($key, $arFields);
				}

				if (isset($arFields['TASK_ID']))
				{
					if (!$arFields['ITEM_ID'])
						$arFields['ITEM_ID'] = $arResult['MEETING']['AGENDA'][$key]['ITEM_ID'];

					CMeetingItem::AddTask($arFields['ITEM_ID'], $arFields['TASK_ID']);
				}
			}
		}

		if ($bDeleted)
		{
			CMeetingItem::DeleteAbandoned();
		}

		if ($bUpdateEvent && is_array($arEventParams))
		{
			CMeeting::AddEvent($arEventParams[0], $arEventParams[1], $arEventParams[2]);
		}

		if ($_REQUEST['save_type'] == 'BGSAVE')
		{
			$APPLICATION->RestartBuffer();
?>
<script type="text/javascript">
if (top.document.forms.meeting_edit)
{
	top.document.forms.meeting_edit.MEETING_ID.value = '<?=$MEETING_ID?>';
<?
			if ($arParams['COPY'])
			{
?>
	top.document.forms.meeting_edit.COPY.parentNode.removeChild(top.document.forms.meeting_edit.COPY);
<?
			}
?>
<?
			if (count($arNewAgendaTasks) > 0)
			{
?>
	top.replaceTasks(<?=CUtil::PhpToJsObject($arNewAgendaTasks)?>);
<?
			}
?>
<?
			if (count($arNewAgendaMap) > 0)
			{
?>
	top.replaceKeys(<?=CUtil::PhpToJsObject($arNewAgendaMap)?>, '<?=CUtil::JSEscape($arParams['ITEM_URL'])?>');
<?
			}
?>
}
</script>
<?
			die();
		}
		else
		{
			LocalRedirect(str_replace('#MEETING_ID#', $MEETING_ID, $arParams["MEETING_URL_TPL"]));
		}
	}
}

if ($_REQUEST['AGENDA_EX'] && check_bitrix_sessid())
{
	$APPLICATION->RestartBuffer();

	$arResult['POPUP'] = $_REQUEST['POPUP'] == 'Y';

	$this->IncludeComponentTemplate('agenda_ex');
	die();
}

$arResult['USERS'] = array();
$dbUsers = CUser::GetList($by='ID', $order='ASC', array('ID' => implode('|', array_keys($arResult['MEETING']['USERS']))));
while ($arUser = $dbUsers->GetNext())
{
	$arResult['USERS'][$arUser['ID']] = $arUser;
}

if (CModule::IncludeModule('forum'))
{
	$obForumConnector = new CMeetingForumHandlers($arParams['FORUM_ID'], $arResult['MEETING']);
	$arParams['FORUM_ID'] = $obForumConnector->GetForumID();

	foreach ($arResult['MEETING']['AGENDA'] as &$arItem)
	{
		$arItem['COMMENTS_COUNT'] = intval(CForumTopic::GetMessageCount($arParams['FORUM_ID'], "MEETING_ITEM_".$arItem['ITEM_ID'], true));
		if ($arItem['COMMENTS_COUNT'] > 0)
			$arItem['COMMENTS_COUNT']--;
	}
}

CJSCore::Init(array('ajax', 'popup', 'date', 'meeting'));

if ($arResult['CAN_EDIT'])
{
	$APPLICATION->AddHeadScript('/bitrix/js/main/utils.js');
	$APPLICATION->AddHeadScript('/bitrix/js/main/dd.js');
}

if ($arParams['EDIT'] && $arResult['CAN_EDIT'] || isset($arResult["MEETING"]["PLACE_ID"]))
{
	$arResult['MEETING_ROOMS_LIST'] = array();
	if ($arParams['RESERVE_MEETING_IBLOCK_ID'] || $arParams['RESERVE_VMEETING_IBLOCK_ID'])
	{
		$dbMeetingsList = CIBlockSection::GetList(
			array('IBLOCK_ID' => 'ASC', 'NAME' => 'ASC', 'ID' => 'DESC'),
			array('IBLOCK_ID' =>
				array(intval($arParams['RESERVE_MEETING_IBLOCK_ID']), intval($arParams['RESERVE_VMEETING_IBLOCK_ID']))
			),
			false,
			array('ID', 'IBLOCK_ID', 'NAME', 'DESCRIPTION')
		);
		while ($arRoom = $dbMeetingsList->Fetch())
		{
			$arRoom["MEETING_ROOM_ID"] = CMeeting::MakePlace($arRoom["IBLOCK_ID"], $arRoom["ID"]);
			$arResult['MEETING_ROOMS_LIST'][] = $arRoom;

			if(isset($arResult["MEETING"]["PLACE_ID"]) && $arResult["MEETING"]["PLACE_ID"] == $arRoom["MEETING_ROOM_ID"])
			{
				$arResult["MEETING"]["PLACE"] = htmlspecialcharsbx($arRoom["NAME"]);
			}
		}
	}
}

if($arParams['EDIT'] && $arResult['CAN_EDIT'])
{
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/tools/clock.php");
	$this->IncludeComponentTemplate('tpl_edit');
}
else
{
	if (is_array($arResult['MEETING']['FILES']) && count($arResult['MEETING']['FILES']) > 0)
	{
		$arResult['MEETING']['FILES'] = CMeeting::GetFilesData($arResult['MEETING']['FILES'], array('MEETING' => $arResult['MEETING']['ID']));
	}

	if ($arResult['MEETING']['GROUP_ID'] > 0 && CModule::IncludeModule('socialnetwork'))
	{
		if ($arGroup = CSocNetGroup::GetByID($arResult['MEETING']['GROUP_ID']))
		{
			$arResult['MEETING']['GROUP_NAME'] = $arGroup['NAME'];
			$arResult['MEETING']['GROUP_URL'] = str_replace(
				"#group_id#", $arGroup['ID'],
				COption::GetOptionString('socialnetwork', 'group_path_template', '/workgroups/group/#group_id#/', SITE_ID)
			);
		}
	}

	$this->IncludeComponentTemplate('tpl_view');
}

?>
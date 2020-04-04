<?
define("STOP_STATISTICS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if(!$USER->IsAuthorized())
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

if (!CModule::IncludeModule('meeting'))
	return;

if (isset($_REQUEST['fileId']))
{
	$fileId = intval($_REQUEST['fileId']);
	$meetingId = intval($_REQUEST['meetingId']);
	$itemId = intval($_REQUEST['itemId']);
	$reportId = intval($_REQUEST['reportId']);

	$checkedFileId = 0;

	if ($fileId > 0)
	{
		if ($reportId > 0)
		{
			$dbRes = CMeetingReports::GetList(array('ID' => 'DESC'), array('ID' => $reportId), false, false, array('MEETING_ID'));
			if($arReport = $dbRes->Fetch())
			{
				if (CMeeting::GetUserRole($arReport['MEETING_ID']))
				{
					$dbRes = CMeetingReports::GetFiles($reportId, $fileId);
					$arRes = $dbRes->Fetch();
					if ($arRes)
					{
						$checkedFileId = $arRes['FILE_ID'];
					}
				}
			}
		}
		elseif ($itemId > 0)
		{
			$bHasAccess = false;

			$dbRes = CMeetingInstance::GetList(array('ID' => 'DESC'), array('ITEM_ID' => $itemId), false, false, array('MEETING_ID'));
			while ($arInstance = $dbRes->Fetch())
			{
				if (CMeeting::GetUserRole($arInstance['MEETING_ID']))
				{
					$bHasAccess = true;
					break;
				}
			}

			if($bHasAccess)
			{
				$dbRes = CMeetingItem::GetFiles($itemId, $fileId);
				$arRes = $dbRes->Fetch();
				if ($arRes)
				{
					$checkedFileId = $arRes['FILE_ID'];
				}
			}
		}
		elseif ($meetingId > 0)
		{
			if(CMeeting::GetUserRole($meetingId))
			{
				$dbRes = CMeeting::GetFiles($meetingId, $fileId);
				$arRes = $dbRes->Fetch();
				if ($arRes)
				{
					$checkedFileId = $arRes['FILE_ID'];
				}
			}
		}
	}

	if ($checkedFileId > 0)
	{
		$arFile = CFile::GetFileArray($checkedFileId);
		if (is_array($arFile))
		{
			CFile::ViewByUser($arFile, array("force_download" => true));
		}
	}

	die();
}

$MEETING_ID = intval($_REQUEST['MEETING_ID']);

if ($MEETING_ID > 0)
{
	$ACCESS = CMeeting::GetUserRole($MEETING_ID);
	if ($ACCESS)
	{
		if ($ACCESS == CMeeting::ROLE_OWNER || $ACCESS == CMeeting::ROLE_KEEPER)
		{
			$new_state = $_REQUEST['STATE'];
			if ($new_state && check_bitrix_sessid())
			{
				$arFields = array(
					'CURRENT_STATE' => $new_state
				);

				switch ($new_state)
				{
					case CMeeting::STATE_ACTION:
						$arFields['DATE_START'] = ConvertTimeStamp(false, 'FULL');
					break;
					case CMeeting::STATE_CLOSED:
						$arFields['DATE_FINISH'] = ConvertTimeStamp(false, 'FULL');
					break;
// TODO we lose original DATE_START here; fix it later during calendar integration
					case CMeeting::STATE_PREPARE:
						$arFields['DATE_FINISH'] = '';
					break;
				}

				CMeeting::Update($MEETING_ID, $arFields);
			}
		}

		$dbRes = CMeeting::GetByID($MEETING_ID);
		if ($arMeeting = $dbRes->Fetch())
		{
			if ($arMeeting['EVENT_ID'] > 0)
			{
				$ownerId = $USER->GetID();
				$arMeeting['USERS'] =  CMeeting::GetUsers($MEETING_ID);
				foreach($arMeeting['USERS'] as $userId=>$userRole)
				{
					if($userRole == CMeeting::ROLE_OWNER)
					{
						$ownerId = $userId;
					}
				}

				$arMeeting['OWNER_ID'] = $ownerId;

				$arMeeting['REINVITE'] = false;

				CMeeting::AddEvent($MEETING_ID, $arMeeting);
			}

			Header('Content-Type: application/json');
			echo "{id: '".$MEETING_ID."', state: '".$arMeeting['CURRENT_STATE']."', date_start: '".MakeTimeStamp($arMeeting['DATE_START'])."000'}";
		}
	}
}
elseif(isset($_REQUEST['PLACE_ID']))
{
	$arPlace = CMeeting::CheckPlace($_REQUEST['PLACE_ID']);
	if(is_array($arPlace) && $arPlace['ROOM_IBLOCK'] > 0 && $arPlace['ROOM_ID'] > 0)
	{
		$eventId = intval($_REQUEST['EVENT_ID']);

		$eventStart = CMeeting::MakeDateTime($_REQUEST['DATE_START_DATE'], $_REQUEST['DATE_START_TIME']);
		$eventFinish = CMeeting::MakeDateTime($_REQUEST['DATE_START_DATE'], $_REQUEST['DATE_START_TIME'], $_REQUEST['DURATION']);

		$arFilter = array(
			"ACTIVE" => "Y",
			"IBLOCK_ID" => $arPlace['ROOM_IBLOCK'],
			"SECTION_ID" => $arPlace['ROOM_ID'],
			"<DATE_ACTIVE_FROM" => $eventFinish,
			">DATE_ACTIVE_TO" => $eventStart,
			"PROPERTY_PERIOD_TYPE" => "NONE",
		);

		$reservationId = 0;
		if($eventId > 0)
		{
			$arEvent = CMeeting::GetEvent($eventId);
			if(is_array($arEvent) && is_array($arEvent['LOCATION']) && $arEvent['LOCATION']['mrevid'] > 0)
			{
				$reservationId = $arEvent['LOCATION']['mrevid'];
				$arFilter["!=ID"] = $reservationId;
			}
		}

		$bReserved = false;

		$dbElements = CIBlockElement::GetList(array("DATE_ACTIVE_FROM" => "ASC"), $arFilter, false, false, array('ID'));
		if ($dbElements->Fetch())
		{
			$bReserved = true;
		}
		else
		{
			// copy-paste sucks!
			include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/components/bitrix/intranet.reserve_meeting/init.php");

			$arPeriodicElements = __IRM_SearchPeriodic(
				$eventStart,
				$eventFinish,
				$arPlace['ROOM_IBLOCK'],
				$arPlace['ROOM_ID']
			);

			for ($i = 0, $l = count($arPeriodicElements); $i < $l; $i++)
			{
				if (!$reservationId || $arPeriodicElements[$i]['ID'] != $reservationId)
				{
					$bReserved = true;
					break;
				}
			}
		}

		Header('Content-Type: application/json');
		echo "{result:'".($bReserved ? 'reserved' : 'ok')."'}";
	}
	else
	{
		Header('Content-Type: application/json');
		echo "{result:'error',error:'wrong_place_id'}";
	}
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>
<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

$arParams['EDIT'] = ($_REQUEST['MEETING_ITEM_EDIT']  ?? null) === 'Y';
$arParams['UPDATE'] = ($_REQUEST['MEETING_ITEM_VIEW'] ?? null) === 'Y';
$arParams['UPDATE_TASKS'] = ($_REQUEST['MEETING_TASKS_RELOAD'] ?? null) === 'Y';
$arParams['COMMENTS'] = (int)($_REQUEST['MEETING_ITEM_COMMENTS'] ?? null);

if ($arParams["NAME_TEMPLATE"] == '')
{
	$arParams["NAME_TEMPLATE"] = CSite::GetNameFormat(false);
}

$arParams["NAME_TEMPLATE"] = str_replace(["#NOBR", "#/NOBR#"], ["",""], $arParams["NAME_TEMPLATE"]);

$arParams['ITEM_ID'] = (int)$arParams['ITEM_ID'];

if ($arParams['ITEM_ID'] <= 0)
{
	return false;
}

if (!CModule::IncludeModule("meeting"))
{
	ShowError(GetMessage("ME_MODULE_NOT_INSTALLED"));
	return;
}

$dbRes = CMeetingItem::GetList([], ['ID' => $arParams['ITEM_ID']]);
if (!$arResult['ITEM'] = $dbRes->Fetch())
{
	ShowError(GetMessage("ME_MEETING_ITEM_NOT_FOUND"));
	return;
}

$arResult['FROM_MEETING'] = (int)($_REQUEST['from'] ?? null);

$bHasAccess = false;
$arResult['ITEM']['INSTANCES'] = [];
$arResult['CAN_EDIT'] = false;
$arUserIDs = [];

$dbRes = CMeetingInstance::GetList(['ID' => 'DESC'], ['ITEM_ID' => $arParams['ITEM_ID']]);
while ($arInstance = $dbRes->Fetch())
{
	$dbMeeting = CMeeting::GetList([], ['ID' => $arInstance['MEETING_ID']], false, false, ['*']);
	if ($arMeeting = $dbMeeting->Fetch())
	{
		$arMeeting['ACCESS'] = CMeeting::GetUserRole($arInstance['MEETING_ID']);
		if ($arMeeting['ACCESS'])
		{
			$bHasAccess = true;
			if ($arMeeting['ACCESS'] == CMeeting::ROLE_OWNER || $arMeeting['ACCESS'] == CMeeting::ROLE_KEEPER)
			{
				$arResult['CAN_EDIT'] = true;
			}
		}

		$arUserIDs[] = $arMeeting['OWNER_ID'];
		$arInstance['MEETING'] = $arMeeting;
	}

	$arInstance['RESPONSIBLE'] = CMeetingInstance::GetResponsible($arInstance['ID']);
	$arUserIDs = array_merge($arUserIDs, $arInstance['RESPONSIBLE']);

	$arInstance['B_RESPONSIBLE'] = in_array($USER->GetID(), $arInstance['RESPONSIBLE']);
	$arInstance['B_EDIT'] = $arInstance['B_RESPONSIBLE'] && $arMeeting['CURRENT_STATE'] != CMeeting::STATE_CLOSED;

	$arInstance['REPORTS'] = [];
	$arReportsMap = [];
	$dbReports = CMeetingReports::GetList([], ['INSTANCE_ID' => $arInstance['ID']]);
	while ($arRep = $dbReports->Fetch())
	{
		$arReportsMap[$arRep['ID']] = true;

		$arRep['FILES'] = [];
		$dbFiles = CMeetingReports::GetFiles($arRep['ID']);
		while ($arFile = $dbFiles->Fetch())
		{
			$arRep['FILES'][$arFile['FILE_ID']] = [
				'FILE_ID' => $arFile['FILE_ID'],
				'FILE_SRC' => $arFile['FILE_SRC'],
			];
		}

		if (!$arInstance['B_EDIT'])
		{
			$arRep['FILES'] = CMeeting::GetFilesData($arRep['FILES'], ['REPORT' => $arRep['ID']]);
		}

		$arInstance['REPORTS'][] = $arRep;
	}

	if (
		$_SERVER['REQUEST_METHOD'] === 'POST'
		&& ($_REQUEST['save'] ?? null)
		&& (int)($_REQUEST['INSTANCE_ID'] ?? null) === (int)$arInstance['ID']
		&& in_array($USER->GetID(), $arInstance['RESPONSIBLE'])
		&& check_bitrix_sessid()
	)
	{
		$APPLICATION->RestartBuffer();

		$REPORT_ID = (int)($_REQUEST['REPORT_ID'] ?? null);

		if($REPORT_ID <= 0)
		{
			foreach($arInstance['REPORTS'] as $arRep)
			{
				if($arRep["USER_ID"] == $USER->GetID())
				{
					$REPORT_ID = (int)$arRep["ID"];
				}
			}
		}

		$arFields = [
			'USER_ID' => $USER->GetID(),
			'REPORT' => ($_REQUEST['REPORT'] ?? null),
		];

		$TextParser = new CBXSanitizer();
		$TextParser->SetLevel(CBXSanitizer::SECURE_LEVEL_LOW);
		$arFields['REPORT'] = $TextParser->SanitizeHtml($arFields['REPORT']);

		$res = false;
		if ($REPORT_ID > 0)
		{
			if (array_key_exists($REPORT_ID, $arReportsMap))
			{
				$res = CMeetingReports::Update($REPORT_ID, $arFields);
			}
		}
		else
		{
			$arFields['INSTANCE_ID'] = $arInstance['ID'];
			$arFields['ITEM_ID'] = $arInstance['ITEM_ID'];
			$arFields['MEETING_ID'] = $arInstance['MEETING_ID'];

			$REPORT_ID = CMeetingReports::Add($arFields);
			$res = $REPORT_ID > 0;
		}

		if($res)
		{
			$fileList = \Bitrix\Main\UI\FileInputUtility::instance()->checkFiles(
				'MEETING_ITEM_REPORT_FILES_'.$arInstance['ID'],
				($_REQUEST['FILES'] ?? null)
			);
			$deletedFileList = \Bitrix\Main\UI\FileInputUtility::instance()->checkDeletedFiles(
				'MEETING_ITEM_REPORT_FILES_'.$arInstance['ID']
			);

			if (is_array($fileList))
			{
				CMeetingReports::SetFiles(
					$REPORT_ID,
					array_values($fileList)
				);
			}
		}

		if ($res)
		{
			echo $REPORT_ID;
		}

		die();
	}

	$arResult['ITEM']['INSTANCES'][] = $arInstance;
}

if (!$bHasAccess)
{
	ShowError(GetMessage("ME_MEETING_ACCESS_DENIED"));
	return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_REQUEST['save_item']) && check_bitrix_sessid())
{
	$arFiles = ($_REQUEST['FILES'] ?? null);

	$arFields = [];

	if ($arResult['CAN_EDIT'])
	{
		if (isset($_REQUEST['ITEM_TITLE']))
		{
			$arResult['ITEM']['TITLE'] = $arFields['TITLE'] = trim($_REQUEST['ITEM_TITLE']);
		}
		if (isset($_REQUEST['ITEM_DESCRIPTION']))
		{
			$textParser = new CBXSanitizer();
			$textParser->SetLevel(CBXSanitizer::SECURE_LEVEL_LOW);
			$arFields['DESCRIPTION'] = $textParser->SanitizeHtml(trim($_REQUEST['ITEM_DESCRIPTION']));
			$arResult['ITEM']['DESCRIPTION'] = $arFields['DESCRIPTION'];
		}
	}

	$arFields['FILES'] = is_array(($_REQUEST['ITEM_FILES'] ?? null))
		? \Bitrix\Main\UI\FileInputUtility::instance()->checkFiles('MEETING_ITEM_FILES_'.$arParams['ITEM_ID'], $_REQUEST['ITEM_FILES'])
		: [];
	$arFields['TASKS'] = is_array(($_REQUEST['ITEM_TASKS'] ?? null)) ? $_REQUEST['ITEM_TASKS'] : [];

	CMeetingItem::Update($arParams['ITEM_ID'], $arFields);
	$arParams['UPDATE'] = true;
}

$arResult['ITEM']['FILES'] = [];
$dbRes = CMeetingItem::GetFiles($arResult['ITEM']['ID']);
while ($arFile = $dbRes->Fetch())
{
	$arResult['ITEM']['FILES'][$arFile['FILE_ID']] = $arFile;
}

$arResult['ITEM']['TASKS'] = CMeetingItem::GetTasks($arResult['ITEM']['ID']);

$arResult['USERS'] = [];
if (count($arUserIDs) > 0)
{
	$dbRes = CUser::GetList(
		'ID',
		'ASC',
		[
			'ID' => implode('|', array_unique($arUserIDs))
		]
	);
	while ($arUser = $dbRes->Fetch())
	{
		$arResult['USERS'][$arUser['ID']] = $arUser;
	}
}

if ($arParams['EDIT'] && $arResult['CAN_EDIT']):
	$APPLICATION->ShowAJaxHead();
	$arResult['INCLUDE_LANG'] = true;
	$this->IncludeComponentTemplate('edit');
	die();
elseif ($arParams['UPDATE_TASKS']):
	$APPLICATION->RestartBuffer();
	$arResult['INCLUDE_LANG'] = true;
	$this->IncludeComponentTemplate('tasks');
	die();
elseif ($arParams['COMMENTS'] && !($_REQUEST['MID'] ?? null)):
	$APPLICATION->RestartBuffer();

	$obForumConnector = new CMeetingItemForumHandlers(($arParams['FORUM_ID'] ?? null), $arResult['ITEM']);
	$arParams['FORUM_ID'] = $obForumConnector->GetForumID();

	$arParams['MINIMAL'] = true;

	$this->IncludeComponentTemplate('comments');
	die();
else:
	$title = GetMessage('ME_ITEM_TITLE', [
		'#ID#' => $arResult['ITEM']['ID'],
		'#TITLE#' => $arResult['ITEM']['TITLE'],
	]);
	$APPLICATION->SetTitle($title);
	if (($arParams['SET_NAVCHAIN'] ?? null) !== 'N')
	{
		$APPLICATION->AddChainItem($title, $arParams['ITEM_URL']);
	}

	if (is_array($arResult['ITEM']['FILES']) && count($arResult['ITEM']['FILES']) > 0)
	{
		$arResult['ITEM']['FILES'] = CMeeting::GetFilesData($arResult['ITEM']['FILES'], ['ITEM' => $arResult['ITEM']['ID']]);
	}

	if ($arParams['UPDATE'])
	{
		$APPLICATION->RestartBuffer();
		$arResult['INCLUDE_LANG'] = true;

		$this->IncludeComponentTemplate('view');
		die();
	}

	$obForumConnector = new CMeetingItemForumHandlers(($arParams['FORUM_ID'] ?? null), $arResult['ITEM']);
	$arParams['FORUM_ID'] = $obForumConnector->GetForumID();

	CJSCore::Init(['meeting', 'ajax']);

	$this->IncludeComponentTemplate();
endif;
?>
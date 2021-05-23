<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CBXFeatures::IsFeatureEnabled('intranet_sharepoint'))
	return false;

/*
$arParams = array(
	'IBLOCK_ID' => information block id
	'OUTPUT' => 'Y' - output links, 'N' => just return array with links

	'MODE' => ''
);

TODO:
1. get something like "reload command" - what to do when it's needed to refresh page (not only BX.reload)
2. CHECK ACCESS! - done but need testing
*/

$arParams['OUTPUT'] = $arParams['OUTPUT'] == 'N' ? 'N' : 'Y';

$arParams['IBLOCK_ID'] = intval($arParams['IBLOCK_ID']);
if ($arParams['IBLOCK_ID'] <= 0 && $arParams['MODE'] != 'test')
{
	ShowError(GetMessage('SL_ERROR_NO_IBLOCK'));
	return false;
}

if (!CModule::IncludeModule('intranet'))
{
	return false;
}

if ($arParams['IBLOCK_ID'] > 0 && !CIntranetSharepoint::CheckAccess($arParams['IBLOCK_ID']))
{
	if ($arParams['OUTPUT'] == 'Y')
	{
		ShowError(GetMessage('SL_ERROR_ACCESS_DENIED'));
	}

	return false;
}

if (!$arParams['MODE'])
{
	$dbRes = CIntranetSharepoint::GetByID($arParams['IBLOCK_ID']);

	$arResult['LINKS'] = array();
	$arResult['SELF'] = $this->GetPath().'/ajax.php';
	if ($arRes = $dbRes->Fetch())
	{
		$arResult['INFO'] = array(
			'SP_URL' => $arRes['SP_URL'],
			'SYNC_DATE' => $arRes['SYNC_DATE'],
			'SYNC_ERRORS' => $arRes['SYNC_ERRORS'],
		);

		$arResult['LINKS'] = array(
			'edit' => array(
				'TYPE' => 'edit',
				'URL' => $arResult['SELF'].'?mode=info&ID='.$arParams['IBLOCK_ID'],
				'TEXT' => GetMessage('SL_LINK_EDIT'),
				'ICON' => 'bx-sharepoint-settings',
			),

			'sync' => array(
				'TYPE' => 'sync',
				'URL' => $arResult['SELF'].'?mode=sync&ID='.$arParams['IBLOCK_ID'],
				'TEXT' => GetMessage('SL_LINK_SYNC'),
				'ICON' => 'bx-sharepoint-sync',
			),
		);

		$arResult['LINKS']['edit']['ONCLICK'] = $APPLICATION->GetPopupLink(array('URL' => $arResult['LINKS']['edit']['URL']));
		$arResult['LINKS']['sync']['ONCLICK'] = 'return BXSPSync(0, this, \''.(CUtil::JSEscape($arResult['LINKS']['sync']['URL'])).'\');';
	}
	else
	{
		$arResult['LINKS'] = array(
			'add' => array(
				'TYPE' => 'add',
				'URL' => $arResult['SELF'].'?mode=edit&ID='.$arParams['IBLOCK_ID'].'&'.bitrix_sessid_get(),
				'TEXT' => GetMessage('SL_LINK_ADD'),
				'ICON' => 'bx-sharepoint-settings',
			),
		);

		$arResult['LINKS']['add']['ONCLICK'] = $APPLICATION->GetPopupLink(array('URL' => $arResult['LINKS']['add']['URL']));

	}

	$this->IncludeComponentTemplate(); // we have to include it always to link js and css

	$template =& $this->GetTemplate();
	$APPLICATION->AddHeadScript($template->GetFolder().'/sync.js');
	CUtil::InitJSCore(array('window', 'ajax'));

	return $arResult;
}
else
{
	$APPLICATION->RestartBuffer();

	$arResult['SELF'] = $this->GetPath().'/ajax.php';

	switch ($arParams['MODE'])
	{
		case 'edit':
			if (!check_bitrix_sessid())
				return;

			$dbRes = CIntranetSharepoint::GetByID($arParams['IBLOCK_ID'], true);
			$arResult['SERVICE'] = $dbRes->Fetch();
			if (!$arResult['SERVICE']) $arResult['SERVICE'] = array();

			$STEP = 0;
			$arResult['ERROR'] = '';
			if ($_REQUEST['server'])
			{
				$STEP = 2;
				CUtil::JSPostUnEscape();
				$arResult['URL'] = parse_url($_REQUEST['server']);

				if (is_array($arResult['URL']) && isset($arResult['URL']['host']))
				{
					$arResult['URL']['user'] = $_REQUEST['user'];
					$arResult['URL']['pass'] = $_REQUEST['pass'];

					CModule::IncludeModule('webservice');

					if (!$_REQUEST['list_id'])
					{
						$CLIENT = new CSPListsClient($arResult['URL']);
						if (!($arResult['LISTS'] = $CLIENT->GetListCollection()))
						{
							if ($ex = $APPLICATION->GetException())
								$arResult['ERROR'] = $ex->GetString();
							else
								$arResult['ERROR'] = 'STRANGE ERROR OCCURED!';
						}

						$arResult['LISTS_CONNECTED'] = array();
						$dbRes = CIntranetSharepoint::GetList();
						while ($arRes = $dbRes->Fetch())
							$arResult['LISTS_CONNECTED'][] = $arRes['SP_LIST_ID'];
					}
					else
					{
						$listID = CIntranetUtils::makeGUID($_REQUEST['list_id']);
						$listID_clear = CIntranetUtils::checkGUID($listID);

						if (!$_REQUEST['FIELDS'])
						{
							$STEP = 3;
							$CLIENT = new CSPListsClient($arResult['URL']);
							if (!($arResult['LIST'] = $CLIENT->GetList($listID_clear)))
							{
								if ($ex = $APPLICATION->GetException())
									$arResult['ERROR'] = $ex->GetString();
								else
									$arResult['ERROR'] = 'STRANGE ERROR OCCURED!';
							}

							$_SESSION['SP_LIST_'.$listID_clear] = $arResult['LIST'];
						}
						else
						{
							$STEP = 4;

							$FIELDS = $_REQUEST['FIELDS'];
							$SESSIONLIST = $_SESSION['SP_LIST_'.$listID_clear];

							$arFields = array(
								'IBLOCK_ID' => $arParams['IBLOCK_ID'],
								'SP_LIST_ID' => $listID_clear,
								'SP_URL' => $_REQUEST['server'],
								'SP_AUTH_USER' => $_REQUEST['user'],
								'SP_AUTH_PASS' => $_REQUEST['pass'],
								'SYNC_PERIOD' => $_REQUEST['period'],
								'FIELDS' => $_REQUEST['FIELDS'],
								'LIST_DATA' => $SESSIONLIST['FIELDS'],
							);

							if ($arResult['SERVICE']['IBLOCK_ID'])
							{
								$res = CIntranetSharepoint::Update(
									$arResult['SERVICE']['IBLOCK_ID'],
									$arFields
								);
							}
							else
							{
								$res = CIntranetSharepoint::Add($arFields);
							}

							if (!$res)
							{
								if ($ex = $APPLICATION->GetException())
									$arResult['ERROR'] = $ex->GetString();
								else
									$arResult['ERROR'] = 'STRANGE ERROR OCCURED!';
							}
							else
							{
								unset($_SESSION['SP_LIST_'.$listID_clear]);
							}
						}
					}
				}
				else
				{
					$arResult['ERROR'] = GetMessage('SL_ERROR_WRONG_URL');
				}
			}

			if ($arResult['ERROR'])
			{
				$STEP = 1;
			}

			if ($STEP < 1)
				$this->IncludeComponentTemplate('form_server');
			elseif ($STEP < 2)
				$this->IncludeComponentTemplate('form_error');
			elseif ($STEP < 3)
				$this->IncludeComponentTemplate('form_lists');
			elseif ($STEP < 4)
				$this->IncludeComponentTemplate('form_list');
			elseif ($STEP >= 4)
				$this->IncludeComponentTemplate('form_finish');

		break;
		case 'info':

			$dbRes = CIntranetSharepoint::GetByID($arParams['IBLOCK_ID'], true);
			if ($arResult['SERVICE'] = $dbRes->Fetch())
			{
				$arResult['TYPES'] = CIntranetSharepoint::GetTypes($arResult['SERVICE']['IBLOCK_ID']);

				$this->IncludeComponentTemplate('settings');
			}

		break;

		case 'sync':

			if (!check_bitrix_sessid())
				return;

			set_time_limit(300);

			if ($_REQUEST['full'])
			{
				CIntranetSharepoint::ClearSyncData($arParams['IBLOCK_ID']);
			}

			if (!$_REQUEST['sync_action'])
			{
				if (!($arResult['RESULT'] = CIntranetSharepoint::RequestItemsNext(
					$arParams['IBLOCK_ID'],
					array('SYNC_NUM_ROWS' => BX_INTRANET_SP_NUM_ROWS_MANUAL)
				)))
				{
					if ($ex = $APPLICATION->GetException())
						$arResult['ERROR'] = $ex->GetString();
					else
						$arResult['ERROR'] = 'STRANGE ERROR OCCURED!';
				}
				else
				{
					if ($arResult['RESULT']['COUNT'] > 0)
					{
						$arQueue = array();
						foreach ($arResult['RESULT']['DATA'] as $arRow)
						{
							if (!CIntranetSharepoint::Sync(
								$arResult['RESULT']['SERVICE'],
								$arRow,
								$arQueue
							))
							{
								if ($ex = $APPLICATION->GetException())
								{
									$arResult['ERROR'] = $ex->GetString();
								}
							}
						}

						$arResult['QUEUE'] = false;
						if (count($arQueue) > 0)
						{
							foreach ($arQueue as $item)
							{
								$item['IBLOCK_ID'] = $arResult['RESULT']['SERVICE']['IBLOCK_ID'];

								if (CIntranetSharepointQueue::Add($item))
								{
									$arResult['QUEUE'] = true;
								}
							}
						}
					}
				}
			}
			else
			{
				$arResult['RESULT'] = array('MORE_ROWS' => false);
			}

			if (!$arResult['RESULT']['MORE_ROWS'])
			{
				if ($_REQUEST['sync_action'] == 'queue')
				{
					CIntranetSharepoint::AgentQueue($arParams['IBLOCK_ID']);
				}
				elseif ($_REQUEST['sync_action'] == 'log')
				{
					CIntranetSharepoint::AgentUpdate($arParams['IBLOCK_ID']);
				}
			}

			if ($arResult['ERROR'])
			{
				$this->IncludeComponentTemplate('sync_error');
			}
			else
			{
				if (!$arResult['RESULT']['MORE_ROWS'])
				{
					if (!$arResult['QUEUE_EXISTS'] = CIntranetSharepoint::IsQueue($arParams['IBLOCK_ID']))
					{
						$arResult['LOG_EXISTS'] = CIntranetSharepoint::IsLog($arParams['IBLOCK_ID']);
					}
				}

				$this->IncludeComponentTemplate('sync');
			}

		break;

		case 'test':
			if (!check_bitrix_sessid())
				return;

			$sp_server = $_REQUEST['sp_server'];
			$sp_user = $_REQUEST['sp_user'];
			$sp_pass = $_REQUEST['sp_pass'];

			$arResult['SERVER'] = 0; $arResult['AUTH'] = 0;
			if ($sp_server && $sp_server != 'http://' && ($URL = CHTTP::ParseURL($sp_server)))
			{
				if ($URL['host'] && $URL['scheme'] == 'http')
				{
					$ob = new CHTTP();
					$ob->setFollowRedirect(false);

					if ($sp_user)
					{
						$ob->SetAuthBasic($sp_user, $sp_pass);
					}

					if ($ob->Get($sp_server) !== false)
					{
						if ($ob->status == 200 || $ob->status == 302 || $ob->status == 401)
						{
							$arResult['SERVER'] = 1;

							if ($ob->status != 401)
								$arResult['AUTH'] = 1;
						}
					}
				}
			}

			$this->IncludeComponentTemplate('form_server_test');

		break;

	}

	return;
}
?>
<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if (!CModule::IncludeModule('iblock'))
	return false;
if (!CModule::IncludeModule("webdav"))
	return false;
if (!empty($arParams['ACTION']) && !empty($_REQUEST["ENTITY_ID"]) && check_bitrix_sessid())
{

	$arParams["IBLOCK_ID"] = isset($arParams['IBLOCK_ID']) ? intval($arParams['IBLOCK_ID']) : 0;
	$arParams["ENTITY_TYPE"] = (isset($arParams['ENTITY_TYPE']) && in_array($arParams['ENTITY_TYPE'], array('IBLOCK', 'ELEMENT', 'SECTION'))) ? $arParams['ENTITY_TYPE'] : '';
	$arParams["ENTITY_ID"] = isset($arParams['ENTITY_ID']) ? intval($arParams['ENTITY_ID']) : 0;
	$arParams['SOCNET_GROUP_ID'] = (isset($arParams['SOCNET_GROUP_ID']) && (intval($arParams['SOCNET_GROUP_ID']) > 0)) ? intval($arParams['SOCNET_GROUP_ID']) : 0;
	$arParams["SOCNET_TYPE"] = (isset($arParams['SOCNET_TYPE']) && in_array($arParams['SOCNET_TYPE'], array('user', 'group'))) ? $arParams['SOCNET_TYPE'] : '';
	$arParams["SOCNET_ID"] = isset($arParams['SOCNET_ID']) ? intval($arParams['SOCNET_ID']) : 0;
	$arParams["ACTION"] = (isset($_REQUEST["ACTION"]) ? strtolower($_REQUEST["ACTION"]) : '');
	$arParams["ACTION"] = (in_array($arParams["ACTION"], array("set_rights")) ? $arParams["ACTION"] : '');

	$arError = array();
	$ID = intval($_REQUEST["ENTITY_ID"]);
	switch ($arParams['ACTION'])
	{
		case "set_rights":
			$allowed = $USER->CanDoOperation('webdav_change_settings');
			if ($arParams['ENTITY_TYPE'] == 'SECTION')
			{
				$ibRights = new CIBlockSectionRights($arParams['IBLOCK_ID'], $ID);
				$op = 'section_rights_edit';
			}
			elseif ($arParams['ENTITY_TYPE'] == 'ELEMENT')
			{
				$ibRights = new CIBlockElementRights($arParams['IBLOCK_ID'], $ID);
				$op = 'element_rights_edit';
			}
			else
			{
				$ibRights = new CIBlockRights($arParams['IBLOCK_ID']);
				$op = 'iblock_rights_edit';
			}

			if (!$allowed)
			{
				if ($arParams['ENTITY_TYPE'] == 'IBLOCK')
					$allowed = $ibRights->UserHasRightTo($arParams['IBLOCK_ID'], $arParams['IBLOCK_ID'], $op);
				else
					$allowed = $ibRights->UserHasRightTo($arParams['IBLOCK_ID'], $ID, $op);
			}

			if (!$allowed)
				$arError[] = array("id" => "no_rights", "text" => GetMessage("WD_NO_RIGHTS"));

			if (empty($arError))
			{
				$arRights = CIBlockRights::Post2Array($_POST["RIGHTS"]);
				$ibRights->SetRights($arRights);

				global $CACHE_MANAGER;
				$CACHE_MANAGER->ClearByTag('iblock_id_' . $arParams['IBLOCK_ID']);


				// get affected elements
				$arElements = array();

				if ($arParams['ENTITY_TYPE'] == 'ELEMENT')
				{
					$arElements[] = array(
						'ID' => $ID,
						'IBLOCK_ID' => $arParams['IBLOCK_ID']
					);
				}
				elseif ($arParams['ENTITY_TYPE'] == 'SECTION')
				{
					$dbFiles = CIBlockElement::GetList(
						array(),
						array(
							'IBLOCK_ID' => $arParams['IBLOCK_ID'],
							'SECTION_ID' => $ID,
							'INCLUDE_SUBSECTIONS' => 'Y',
							'SHOW_HISTORY' => 'Y'
						),
						false,
						false,
						array('ID', 'IBLOCK_ID')
					);
					if ($dbFiles)
					{
						while( $arFile = $dbFiles->Fetch())
						{
							$arElements[] = array(
								'ID' => $arFile['ID'],
								'IBLOCK_ID' => $arFile['IBLOCK_ID']
							);
						}
					}
				}
				elseif ($arParams['ENTITY_TYPE'] == 'IBLOCK')
				{
					$dbFiles = CIBlockElement::GetList(
						array(),
						array('IBLOCK_ID' => $ID, 'INCLUDE_SUBSECTIONS' => 'Y'),
						false,
						false,
						array('ID', 'IBLOCK_ID')
					);
					if ($dbFiles)
					{
						while( $arFile = $dbFiles->Fetch())
						{
							$arElements[] = array(
								'ID' => $arFile['ID'],
								'IBLOCK_ID' => $arFile['IBLOCK_ID']
							);
						}
					}
				}

				if (CModule::IncludeModule('socialnetwork')) // update socnet rights
				{
					foreach ($arElements as $elm)
					{
						CWebDavSocNetEvent::SocnetLogUpdateRights($elm['ID'], $elm['IBLOCK_ID'],
							(empty($arParams['SOCNET_TYPE']) ? ENTITY_FILES_COMMON_EVENT_ID : ENTITY_FILES_SOCNET_EVENT_ID));
					}
				}

				if (CModule::IncludeModule('search')) // update search rights
				{
					foreach ($arElements as $elm)
					{
						CWebDavIblock::UpdateSearchRights($elm['ID'], $elm['IBLOCK_ID']);
					}
				}
			}

			break;
	}
	if (!empty($arError))
	{
		$e = new CAdminException($arError);
		$arResult["ERROR_MESSAGE"] = $e->GetString();
	}
	else
	{
		if (!isset($arParams['DO_NOT_REDIRECT']))
		{
			if (isset($_REQUEST['back_url']))
			{
				$dest = urldecode($_REQUEST['back_url']);
			}
			else
			{
				$dest = $APPLICATION->GetCurPageParam("result=".$arParams["ACTION"], array("action", "ID", "sessid", "result"));
			}
			LocalRedirect($dest);
		}
	}
}
?>

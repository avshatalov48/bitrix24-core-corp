<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CCrmPerms::IsAccessEnabled())
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['CAN_EDIT'] = $arResult['CAN_DELETE'] = true;

global $APPLICATION;
$curPageUrl = $APPLICATION->GetCurPage();
$arParams['PATH_TO_MAIL_TEMPLATE_LIST'] = CrmCheckPath('PATH_TO_MAIL_TEMPLATE_LIST', $arParams['PATH_TO_MAIL_TEMPLATE_LIST'], $curPageUrl);
$arParams['PATH_TO_MAIL_TEMPLATE_EDIT'] = CrmCheckPath('PATH_TO_MAIL_TEMPLATE_EDIT', $arParams['PATH_TO_MAIL_TEMPLATE_EDIT'], $curPageUrl.'?element_id=#element_id#&edit');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$userID = isset($arParams['USER_ID']) ? intval($arParams['USER_ID']) : 0;
if($userID <= 0)
{
	$userID = CCrmPerms::GetCurrentUserID();
}
$arResult['USER_ID'] = $userID;

$userAccessCodes = array();
$res = \CAccess::getUserCodes($userID, array('PROVIDER_ID' => 'intranet'));
while ($item = $res->fetch())
	$userAccessCodes[] = $item['ACCESS_CODE'];

$checkIfCanEdit = function ($ownerId, $scope) use (&$userID, &$userAccessCodes)
{
	if (\CCrmPerms::isAdmin() || $ownerId == $userID)
		return true;

	if (\CCrmMailTemplateScope::Common == $scope && in_array(sprintf('IU%u', $ownerId), $userAccessCodes))
		return true;

	return false;
};

$checkIfCanDelete = function ($ownerId, $scope) use (&$userID, &$userAccessCodes)
{
	if (\CCrmPerms::isAdmin() || $ownerId == $userID)
		return true;

	return false;
};

$arResult['GRID_ID'] = 'CRM_MAIL_TEMPLATE_LIST';
$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
$arResult['TAB_ID'] = isset($arParams['TAB_ID']) ? $arParams['TAB_ID'] : '';

$arResult['HEADERS'] = array(
	array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_MAIL_TEMPLATE_ID'), 'sort' => 'ID', 'default' => false, 'editable' => false),
	array('id' => 'TITLE', 'name' => GetMessage('CRM_COLUMN_MAIL_TEMPLATE_TITLE'), 'sort' => 'TITLE', 'default' => true, 'editable' => true, 'params' => array('size' => 60)),
	array('id' => 'SORT', 'name' => GetMessage('CRM_COLUMN_MAIL_TEMPLATE_SORT'), 'sort' => 'SORT', 'default' => false, 'editable' => true),
	array('id' => 'ENTITY_TYPE_NAME', 'name' => GetMessage('CRM_COLUMN_MAIL_TEMPLATE_ENTITY_TYPE'), 'default' => true, 'editable' => false),
	array('id' => 'SCOPE_NAME', 'name' => GetMessage('CRM_COLUMN_MAIL_TEMPLATE_SCOPE'), 'default' => true, 'editable' => false),
	array('id' => 'IS_ACTIVE', 'name' => GetMessage('CRM_COLUMN_MAIL_TEMPLATE_IS_ACTIVE'), 'sort' => 'IS_ACTIVE', 'default' => true, 'editable' => true, 'type'=>'checkbox'),
	array('id' => 'OWNER_FORMATTED_NAME', 'name' => GetMessage('CRM_COLUMN_MAIL_TEMPLATE_OWNER'), 'default' => false, 'editable' => false),
	array('id' => 'CREATED', 'name' => GetMessage('CRM_COLUMN_MAIL_TEMPLATE_CREATED'), 'sort' => 'CREATED', 'default' => false, 'editable' => false),
	array('id' => 'LAST_UPDATED', 'name' => GetMessage('CRM_COLUMN_MAIL_TEMPLATE_LAST_UPDATED'), 'sort' => 'LAST_UPDATED', 'default' => false, 'editable' => false)
);

if(check_bitrix_sessid())
{
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_button_'.$arResult['GRID_ID']]))
	{
		$action = $_POST['action_button_'.$arResult['GRID_ID']];
		if($arResult['CAN_DELETE'] && $action === 'delete')
		{
			if($_POST['action_all_rows_'.$arResult['GRID_ID']] == 'Y')
			{
				//Delete all user items
				$dbResult = CCrmMailTemplate::GetList(array(), array('=OWNER_ID' => $userID), false, false, array('TITLE'));
				while($fields = $dbResult->Fetch())
				{
					if(!CCrmMailTemplate::Delete($fields['ID']))
					{
						$errors = CCrmMailTemplate::GetErrorMessages();
						if(empty($errors))
						{
							$errors[] = GetMessage('CRM_MAIL_TEMPLATE_DELETION_GENERAL_ERROR', array('#TITLE#' => $fields['TITLE']));
						}
						ShowError(implode("\n", $errors));
					}
				}
			}
			else
			{
				$IDs = isset($_POST['ID']) ? $_POST['ID'] : array();
				foreach($IDs as $ID)
				{
					$dbResult = CCrmMailTemplate::GetList(array(), array('=ID' => $ID), false, false, array('OWNER_ID', 'TITLE', 'SCOPE'));
					$fields = $dbResult->Fetch();
					if (!is_array($fields) || !$checkIfCanDelete($fields['OWNER_ID'], $fields['SCOPE']))
					{
						continue;
					}

					if(!CCrmMailTemplate::Delete($ID))
					{
						$errors = CCrmMailTemplate::GetErrorMessages();
						if(empty($errors))
						{
							$errors[] = GetMessage('CRM_MAIL_TEMPLATE_DELETION_GENERAL_ERROR', array('#TITLE#' => $fields['TITLE']));
						}
						ShowError(implode("\n", $errors));
					}
				}
			}

			unset($_POST['ID'], $_REQUEST['ID']); // otherwise the filter will work
		}
		elseif($arResult['CAN_EDIT'] && $action === 'edit' && isset($_POST['FIELDS']) && is_array($_POST['FIELDS']))
		{
			$errors = array();
			foreach($_POST['FIELDS'] as $ID => &$data)
			{
				$dbResult = CCrmMailTemplate::GetList(array(), array('=ID' => $ID), false, false, array('OWNER_ID', 'SCOPE'));
				$curFields = $dbResult->Fetch();
				if (!is_array($curFields) || !$checkIfCanEdit($curFields['OWNER_ID'], $curFields['SCOPE']))
				{
					continue;
				}

				$fields = array();

				//TITLE, SORT, IS_ACTIVE
				if(isset($data['TITLE']))
				{
					$fields['TITLE'] = $data['TITLE'];
				}

				if(isset($data['SORT']))
				{
					$fields['SORT'] = intval($data['SORT']);
				}

				if(isset($data['IS_ACTIVE']))
				{
					$fields['IS_ACTIVE'] = $data['IS_ACTIVE'];
				}

				if (count($fields) > 0)
				{
					if(!CCrmMailTemplate::Update($ID, $fields))
					{
						$updateErrors = CCrmMailTemplate::GetErrorMessages();
						if(empty($updateErrors))
						{
							$updateErrors[] = GetMessage('CRM_MAIL_TEMPLATE_UPDATE_GENERAL_ERROR');
						}
						$errors = array_merge($errors, $updateErrors);
					}
				}
			}
		}

		if(!isset($_POST['AJAX_CALL']))
		{
			if(!empty($errors))
			{
				ShowError(implode("\n", $errors));
			}
			else
			{
				LocalRedirect($APPLICATION->GetCurPage());
			}
		}
	}
	elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action_'.$arResult['GRID_ID']]))
	{
		if ($arResult['CAN_DELETE'] && $_GET['action_'.$arResult['GRID_ID']] === 'delete')
		{
			$ID = isset($_GET['ID']) ? intval($_GET['ID']) : 0;
			if($ID > 0)
			{
				$dbResult = CCrmMailTemplate::GetList(array(), array('=ID' => $ID), false, false, array('OWNER_ID', 'TITLE', 'SCOPE'));
				$fields = $dbResult->Fetch();
				if(is_array($fields) && $checkIfCanDelete($fields['OWNER_ID'], $fields['SCOPE']) && !CCrmMailTemplate::Delete($ID))
				{
					$errors = CCrmMailTemplate::GetErrorMessages();
					if(empty($errors))
					{
						$errors[] = GetMessage('CRM_MAIL_TEMPLATE_DELETION_GENERAL_ERROR', array('#TITLE#' => $fields['TITLE']));
					}
					ShowError(implode("\n", $errors));
				}
			}
			unset($_GET['ID'], $_REQUEST['ID']); // otherwise the filter will work
		}

		if (!isset($_GET['AJAX_CALL']))
		{
			LocalRedirect($arParams['PATH_TO_MAIL_TEMPLATE_LIST']);
		}
	}
	elseif($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['conv']))
	{
		if(CCrmPerms::IsAdmin())
		{
			$conv = strtoupper($_GET['conv']);
			if($conv === 'EXEC')
			{
				$mailFrom = COption::GetOptionString('crm', 'email_from');
				$mailTemplate = COption::GetOptionString('crm', 'email_template');
				$mailTemplate = CAllCrmMailTemplate::ConvertHtmlToBbCode($mailTemplate);
				if($mailFrom !== '' || $mailTemplate !== '')
				{

					$element = array();
					$element['TITLE'] = GetMessage('CRM_MAIL_TEMPLATE_COMMON_TEMPLATE_NAME');
					$element['IS_ACTIVE'] = 'Y';
					$element['OWNER_ID'] = CCrmSecurityHelper::GetCurrentUserID();
					$element['SORT'] = 100;
					$element['EMAIL_FROM'] = $mailFrom;
					$element['SCOPE'] = CCrmMailTemplateScope::Common;
					$element['SUBJECT'] = '';
					$element['BODY'] = $mailTemplate;

					$entityTypes = array(
						CCrmOwnerType::Lead,
						CCrmOwnerType::Deal,
						CCrmOwnerType::Contact,
						CCrmOwnerType::Company
					);

					foreach($entityTypes as $entityTypeID)
					{
						$element['ENTITY_TYPE_ID'] = $entityTypeID;
						CCrmMailTemplate::Add($element);
					}
				}

				COption::SetOptionString('crm', '~CRM_MAIL_TEMPLATE_LIST_CONVERTING', 'Y');
			}
			elseif($conv === 'SKIP')
			{
				COption::SetOptionString('crm', '~CRM_MAIL_TEMPLATE_LIST_CONVERTING', 'Y');
			}
			elseif($conv === 'RESET')
			{
				COption::RemoveOption('crm', '~CRM_MAIL_TEMPLATE_LIST_CONVERTING');
			}
		}

		LocalRedirect(CHTTP::urlDeleteParams($curPageUrl, array('conv')));
	}
}

$gridOptions = new CCrmGridOptions($arResult['GRID_ID']);
$gridSorting = $gridOptions->GetSorting(
	array(
		'sort' => array('SORT' => 'asc'),
		'vars' => array('by' => 'by', 'order' => 'order')
	)
);

$sort = $arResult['SORT'] = $gridSorting['sort'];
$arResult['SORT_VARS'] = $gridSorting['vars'];

$items = array();
$dbResult = CAllCrmMailTemplate::GetList($sort, array('LOGIC'=>'OR', '=OWNER_ID' => $userID, 'SCOPE'=> CCrmMailTemplateScope::Common));
$count = 0;
while($fields = $dbResult->GetNext())
{
	$ID = intval($fields['~ID']);
	$fields['~OWNER_FORMATTED_NAME'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $fields['~OWNER_LOGIN'],
			'NAME' => $fields['~OWNER_NAME'],
			'SECOND_NAME' => $fields['~OWNER_SECOND_NAME'],
			'LAST_NAME' => $fields['~OWNER_LAST_NAME']
		),
		true, false
	);
	$fields['OWNER_FORMATTED_NAME'] = htmlspecialcharsbx($fields['~OWNER_FORMATTED_NAME']);
	$fields['~ENTITY_TYPE_NAME'] = isset($fields['~ENTITY_TYPE_ID']) ? CCrmOwnerType::GetDescription($fields['~ENTITY_TYPE_ID']) : '';
	$fields['ENTITY_TYPE_NAME'] = htmlspecialcharsbx($fields['~ENTITY_TYPE_NAME']);
	$fields['~SCOPE_NAME'] = isset($fields['~SCOPE']) ? CCrmMailTemplateScope::GetDescription($fields['~SCOPE']) : '';
	$fields['SCOPE_NAME'] = htmlspecialcharsbx($fields['~SCOPE_NAME']);

	$fields['CAN_EDIT'] = true;
	$fields['CAN_DELETE'] = $checkIfCanDelete($fields['~OWNER_ID'], $fields['~SCOPE']);
	$fields['PATH_TO_EDIT'] = $fields['PATH_TO_DELETE'] = '';
	if($fields['CAN_EDIT'])
	{
		$fields['PATH_TO_EDIT'] = CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_MAIL_TEMPLATE_EDIT'],
				array('element_id' => $ID)
			) ;
	}

	if($fields['CAN_DELETE'])
	{
		$fields['PATH_TO_DELETE'] =
			CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_MAIL_TEMPLATE_LIST'],
					array('element_id' => $ID)
				),
				array('action_'.$arResult['GRID_ID'] => 'delete', 'ID' => $ID, 'sessid' => bitrix_sessid())
			);
	}

	$items[] = $fields;
	$count++;
}
$arResult['ROWS_COUNT'] = $count;

$arResult['ITEMS'] = &$items;

$arResult['NEED_FOR_CONVERTING'] = false;
if(CCrmPerms::IsAdmin())
{
	$curPage = $APPLICATION->GetCurPage();
	if(COption::GetOptionString('crm', '~CRM_MAIL_TEMPLATE_LIST_CONVERTING', 'N') !== 'Y'
		&& COption::GetOptionString('crm', 'email_from') !== '')
	{
		$arResult['NEED_FOR_CONVERTING'] = true;
		$sessid = bitrix_sessid();
		$arResult['CONV_EXEC_URL'] = CHTTP::urlAddParams($curPage, array('conv' => 'exec', 'sessid' => $sessid));
		$arResult['CONV_SKIP_URL'] = CHTTP::urlAddParams($curPage, array('conv' => 'skip', 'sessid' => $sessid));
	}
}

$arResult['MESSAGE_VIEW_ID'] = isset($arParams['MESSAGE_VIEW_ID']) ? $arParams['MESSAGE_VIEW_ID'] : '';
$this->IncludeComponentTemplate();

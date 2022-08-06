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

global $APPLICATION, $USER_FIELD_MANAGER;
$curPageUrl = $APPLICATION->GetCurPage();
$arParams['PATH_TO_MAIL_TEMPLATE_LIST'] = CrmCheckPath('PATH_TO_MAIL_TEMPLATE_LIST', $arParams['PATH_TO_MAIL_TEMPLATE_LIST'], $curPageUrl);
$arParams['PATH_TO_MAIL_TEMPLATE_EDIT'] = CrmCheckPath('PATH_TO_MAIL_TEMPLATE_EDIT', $arParams['PATH_TO_MAIL_TEMPLATE_EDIT'], $curPageUrl.'?element_id=#element_id#&edit');
$arResult['EXTERNAL_CONTEXT'] = isset($_REQUEST['external_context']) ? $_REQUEST['external_context'] : '';

$userID = isset($arParams['USER_ID']) ? intval($arParams['USER_ID']) : 0;
if($userID <= 0)
{
	$userID = CCrmPerms::GetCurrentUserID();
}
$arResult['USER_ID'] = $userID;

$elementID = isset($arParams['ELEMENT_ID']) ? intval($arParams['ELEMENT_ID']) : 0;
if($elementID <= 0)
{
	$paramName = isset($arParams['ELEMENT_ID_PARAM_NAME']) ? strval($arParams['ELEMENT_ID_PARAM_NAME']) : '';
	if($paramName === '')
	{
		$paramName = 'element_id';
	}

	$elementID = isset($_REQUEST[$paramName]) ? intval($_REQUEST[$paramName]) : 0;
}

$element = array();
if($elementID > 0)
{
	$element = CCrmMailTemplate::GetByID($elementID);
	if(!$element
		|| (!\CCrmPerms::isAdmin() && $element['OWNER_ID'] != $userID && \CCrmMailTemplateScope::Common != $element['SCOPE']))
	{
		ShowError(GetMessage('CRM_MAIL_TEMPLATE_NOT_FOUND'));
		@define('ERROR_404', 'Y');
		if($arParams['SET_STATUS_404'] === 'Y')
		{
			CHTTP::SetStatus('404 Not Found');
		}
		return;
	}
	$elementID = $element['ID'];

	$arResult['CAN_EDIT'] = \CCrmPerms::isAdmin() || $element['OWNER_ID'] == $userID;
	if (!$arResult['CAN_EDIT'] && \CCrmMailTemplateScope::Common == $element['SCOPE'])
	{
		$arResult['CAN_EDIT'] = (bool) \CAccess::getUserCodes(
			$userID,
			array(
				'PROVIDER_ID' => 'intranet',
				'ACCESS_CODE' => sprintf('IU%u', $element['OWNER_ID']),
			)
		)->fetch();
	}

	$APPLICATION->setTitle($element['TITLE']);
}
else
{
	$element['ENTITY_TYPE_ID'] = isset($_REQUEST['ENTITY_TYPE_ID']) ? (int) $_REQUEST['ENTITY_TYPE_ID'] : 0;
	$element['OWNER_ID'] = $userID;
	$element['IS_ACTIVE'] = 'Y';
}

$errors = array();

if(check_bitrix_sessid())
{
	if($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['save']) || isset($_POST['apply'])))
	{
		$elementID = isset($_POST['element_id']) ? intval($_POST['element_id']) : 0;
		$isNew = $elementID <= 0;
		$element = array();

		$element['TITLE'] = isset($_POST['TITLE']) ? $_POST['TITLE'] : '';
		$element['IS_ACTIVE'] = isset($_POST['IS_ACTIVE']) && $_POST['IS_ACTIVE'] === 'Y' ?  'Y' : 'N';
		$element['EMAIL_FROM'] = isset($_POST['EMAIL_FROM']) ? $_POST['EMAIL_FROM'] : '';
		$element['SCOPE'] = CCrmPerms::IsAdmin() && isset($_POST['SCOPE']) ? $_POST['SCOPE'] : null;
		$element['SUBJECT'] = isset($_POST['SUBJECT']) ? $_POST['SUBJECT'] : '';
		$element['ENTITY_TYPE_ID'] = isset($_POST['ENTITY_TYPE_ID']) && CCrmOwnerType::IsDefined($_POST['ENTITY_TYPE_ID'])
			? intval($_POST['ENTITY_TYPE_ID']) : 0;
		$element['BODY_TYPE'] = isset($_POST['BODY_TYPE']) ? intval($_POST['BODY_TYPE']) : \CCrmContentType::BBCode;
		$element['BODY'] = isset($_POST['BODY']) ? $_POST['BODY'] : '';

		if (\CCrmContentType::Html == $element['BODY_TYPE'])
		{
			$element['BODY'] = \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getPostList()->getRaw('BODY');

			$element['BODY'] = preg_replace('/<!--.*?-->/is', '', $element['BODY']);
			$element['BODY'] = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $element['BODY']);
			$element['BODY'] = preg_replace('/<title[^>]*>.*?<\/title>/is', '', $element['BODY']);

			$sanitizer = new \CBXSanitizer();
			$sanitizer->setLevel(\CBXSanitizer::SECURE_LEVEL_LOW);
			$sanitizer->applyDoubleEncode(false);

			if (\Bitrix\Main\Loader::includeModule('mail'))
			{
				// @TODO: since it is used in several modules, it is worth moving to main
				$sanitizer->addTags(\Bitrix\Mail\Helper\Message::getWhitelistTagAttributes());
			}
			else
			{
				$sanitizer->addTags(array('style' => array()));
			}

			$element['BODY'] = $sanitizer->sanitizeHtml($element['BODY']);
			$element['BODY'] = preg_replace('/https?:\/\/bxacid:(n?\d+)/i', 'bxacid:\1', $element['BODY']);
		}

		$element['UF_ATTACHMENT'] = empty($_REQUEST['FILES']) || !is_array($_REQUEST['FILES']) ? array() : $_REQUEST['FILES'];

		if (!$isNew)
		{
			$curElement = \CCrmMailTemplate::getList(
				array(),
				array('=ID' => $elementID),
				false,
				false,
				array('OWNER_ID', 'SCOPE', 'SORT')
			)->fetch();

			if (!is_array($curElement))
			{
				$errors[] = getMessage('CRM_MAIL_TEMPLATE_NOT_FOUND');
			}
			else if (!\CCrmPerms::isAdmin())
			{
				if (intval($curElement['OWNER_ID']) !== $userID)
				{
					if (\CCrmMailTemplateScope::Common == $curElement['SCOPE'])
					{
						$curCanEdit = (bool) \CAccess::getUserCodes(
							$userID,
							array(
								'PROVIDER_ID' => 'intranet',
								'ACCESS_CODE' => sprintf('IU%u', $curElement['OWNER_ID']),
							)
						)->fetch();
					}

					if (empty($curCanEdit))
						$errors[] = GetMessage('CRM_PERMISSION_DENIED');
				}
			}

			$element['SORT'] = (int) (isset($_POST['SORT']) ? $_POST['SORT'] : $curElement['SORT']);

			if(empty($errors) && !CCrmMailTemplate::Update($elementID, $element))
			{
				$errors = CCrmMailTemplate::GetErrorMessages();
				if(empty($errors))
				{
					$errors[] = GetMessage('CRM_MAIL_TEMPLATE_UPDATE_UNKNOWN_ERROR');
				}
			}
		}
		else
		{
			$element['SORT'] = (int) (isset($_POST['SORT']) ? $_POST['SORT'] : 100);

			$element['OWNER_ID'] = $userID;
			$elementID = CCrmMailTemplate::Add($element);
			if(!is_int($elementID) || $elementID <= 0)
			{
				$errors = CCrmMailTemplate::GetErrorMessages();
				if(empty($errors))
				{
					$errors[] = GetMessage('CRM_MAIL_TEMPLATE_ADD_UNKNOWN_ERROR');
				}
			}
		}

		if(!empty($errors))
		{
			ShowError(implode("\n", $errors));
			$arResult['ERRORS'] = $errors;
		}
		else
		{
			if(isset($_POST['apply']))
			{
				$target = \CComponentEngine::makePathFromTemplate(
					$arParams['PATH_TO_MAIL_TEMPLATE_EDIT'],
					array('element_id' => $elementID)
				);

				if (isset($_REQUEST['IFRAME']))
					$target = \CHTTP::urlAddParams($target, array('IFRAME' => $_REQUEST['IFRAME']));
				if (isset($_REQUEST['IFRAME_TYPE']))
					$target = \CHTTP::urlAddParams($target, array('IFRAME_TYPE' => $_REQUEST['IFRAME_TYPE']));

				LocalRedirect($target);
			}
			else
			{
				if(isset($arResult['EXTERNAL_CONTEXT']) && $arResult['EXTERNAL_CONTEXT'] !== '')
				{
					$arResult['EXTERNAL_EVENT'] = array(
						'NAME' => 'onCrmMailTemplateCreate',
						'PARAMS' => array(
							'context' => $arResult['EXTERNAL_CONTEXT'],
							'templateTitle' => htmlspecialcharsbx($element['TITLE']),
							'entityType' => $element['ENTITY_TYPE_ID'],
							'templateId' => $elementID
						)
					);
					$this->includeComponentTemplate('event');
					return;
				}
				else
				{
					LocalRedirect(
						CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_MAIL_TEMPLATE_LIST'])
					);
				}
			}
		}
	}
	elseif ($_SERVER['REQUEST_METHOD'] == 'GET' &&  isset($_GET['delete']))
	{
		if(CCrmMailTemplate::Exists($elementID)
			&& (\CCrmPerms::isAdmin() || $element['OWNER_ID'] == $userID)
			&& !CCrmMailTemplate::Delete($elementID))
		{
				$errors = CCrmMailTemplate::GetErrorMessages();
				if(empty($errors))
				{
					$errors[] = GetMessage('CRM_MAIL_TEMPLATE_DELETE_UNKNOWN_ERROR');
				}
				ShowError(implode("\n", $errors));
			return;
		}

		LocalRedirect(
			CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_MAIL_TEMPLATE_LIST'])
		);
	}
}

$element['FILES'] = $USER_FIELD_MANAGER->getUserFieldValue('CRM_MAIL_TEMPLATE', 'UF_ATTACHMENT', $element['ID']);
$element['FILES'] = !empty($element['FILES']) && is_array($element['FILES']) ? $element['FILES'] : array();

$arResult['ELEMENT_ID'] = $elementID;
$arResult['ELEMENT'] = $element;
$isEditMode = $elementID > 0;

$arResult['FORM_ID'] = $arResult['GRID_ID'] = 'CRM_MAIL_TEMPLATE_EDIT';
$arResult['BACK_URL'] = CComponentEngine::MakePathFromTemplate(
	$arParams['PATH_TO_MAIL_TEMPLATE_LIST'],
	array()
);
$arResult['FIELDS']['tab_1'][] = array(
	'ID' => 'TITLE',
	'NAME' => GetMessage('CRM_MAIL_TEMPLATE_TITLE'),
	'VALUE' => isset($element['TITLE']) ? htmlspecialcharsbx($element['TITLE']) : '',
	'REQUIRED' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'ID' => 'SORT',
	'NAME' => GetMessage('CRM_MAIL_TEMPLATE_SORT'),
	'VALUE' => isset($element['SORT']) ? intval($element['SORT']) : 100
);

if(CCrmPerms::IsAdmin())
{
	$arResult['FIELDS']['tab_1'][] = array(
		'ID' => 'SCOPE',
		'NAME' => GetMessage('CRM_MAIL_TEMPLATE_SCOPE'),
		'VALUE' => isset($element['SCOPE']) ? $element['SCOPE'] : CCrmMailTemplateScope::Personal,
		'ALL_VALUES' => CCrmMailTemplateScope::GetAllDescriptions()
	);
}

$arResult['FIELDS']['tab_1'][] = array(
	'ID' => 'IS_ACTIVE',
	'NAME' => GetMessage('CRM_MAIL_TEMPLATE_IS_ACTIVE'),
	'VALUE' => isset($element['IS_ACTIVE']) && $element['IS_ACTIVE'] === 'Y' ? 'Y' : 'N'
);

$arResult['FIELDS']['tab_1'][] = array(
	'ID' => 'EMAIL_FROM',
	'NAME' => GetMessage('CRM_MAIL_TEMPLATE_EMAIL_FROM'),
	'VALUE' => isset($element['EMAIL_FROM']) ? htmlspecialcharsbx($element['EMAIL_FROM']) : ''
);

$arResult['FIELDS']['tab_1'][] = array(
	'ID' => 'SUBJECT',
	'NAME' => GetMessage('CRM_MAIL_TEMPLATE_SUBJECT'),
	'VALUE' => isset($element['SUBJECT']) ? htmlspecialcharsbx($element['SUBJECT']) : ''
);

$types = [
	CCrmOwnerType::Lead,
	CCrmOwnerType::Deal,
	CCrmOwnerType::Contact,
	CCrmOwnerType::Company,
	CCrmOwnerType::Quote,
];
if (\Bitrix\Crm\Settings\InvoiceSettings::getCurrent()->isOldInvoicesEnabled())
{
	$types[] = \CCrmOwnerType::Invoice;
}
$types[] = \CCrmOwnerType::SmartInvoice;
$typesMap = \Bitrix\Crm\Service\Container::getInstance()->getDynamicTypesMap()->load([
	'isLoadStages' => false,
	'isLoadCategories' => false,
]);
foreach ($typesMap->getTypes() as $type)
{
	if ($type->getIsClientEnabled())
	{
		$types[] = $type->getEntityTypeId();
	}
}
$arResult['OWNER_TYPES'] = CCrmOwnerType::GetDescriptions($types);

$arResult['FIELDS']['tab_1'][] = array(
	'ID' => 'ENTITY_TYPE_ID',
	'NAME' => GetMessage('CRM_MAIL_ENTITY_TYPE'),
	'VALUE' => isset($element['ENTITY_TYPE_ID']) ? $element['ENTITY_TYPE_ID'] : 0,
	'ALL_VALUES' => $arResult['OWNER_TYPES'],
	'REQUIRED' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'ID' => 'BODY',
	'NAME' => GetMessage('CRM_MAIL_TEMPLATE_BODY'),
	'VALUE' => isset($element['BODY']) ? $element['BODY'] : '',
	'VALUE_TYPE' => isset($element['BODY_TYPE']) ? $element['BODY_TYPE'] : '',
);

$this->IncludeComponentTemplate();

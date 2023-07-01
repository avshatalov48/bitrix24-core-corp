<?php
/** @global CUser $USER */
/** @global CMain $APPLICATION */
/** @global array $FIELDS */
/** @global CDatabase $DB */

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Iblock;
use Bitrix\Catalog;
use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php');

/** @global CAdminPage $adminPage */
global $adminPage;
/** @global CAdminSidePanelHelper $adminSidePanelHelper */
global $adminSidePanelHelper;

Loader::includeModule('catalog');
Loc::loadMessages(__FILE__);

$APPLICATION->setTitle(Loc::getMessage('PSL_PAGE_TITLE'));

$publicMode = $adminPage->publicMode;
$selfFolderUrl = $adminPage->getSelfFolderUrl();

if (
	!AccessController::getCurrent()->check(ActionDictionary::ACTION_CATALOG_READ)
	&& !AccessController::getCurrent()->check(ActionDictionary::ACTION_CATALOG_VIEW)
)
{
	require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php');
	ShowError(Loc::getMessage('PSL_ACCESS_DENIED'));
	require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php');
	die();
}

if(isset($_REQUEST['mode']) && ($_REQUEST['mode'] == 'list' || $_REQUEST['mode'] == 'frame'))
	CFile::disableJSFunction(true);

$request = Main\Context::getCurrent()->getRequest();
$adminSection = $request->isAdminSection();
if ($adminSection)
{
	$urlParams = [
		'find_section_section' => -1,
		'WF' => 'Y',
	];
}
else
{
	$urlParams = [
		'find_section_section' => -1,
		'WF' => 'Y',
		'return_url' => $APPLICATION->GetCurPageParam(
			'',
			[
				'mode',
				'table_id',
				'internal',
				'grid_id',
				'grid_action',
				'bxajaxid',
				'sessid',
			]
		),
	];
}
/** @var \Bitrix\Catalog\Url\AdminPage\CatalogBuilder $urlBuilder */
$urlBuilder = Iblock\Url\AdminPage\BuilderManager::getInstance()->getBuilder();
if ($urlBuilder === null)
{
	$APPLICATION->SetTitle(Loc::getMessage('PSL_PAGE_TITLE'));
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
	ShowError(GetMessage("PSL_ERR_BUILDER_ADSENT"));
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
	die();
}

$tableId = 'tbl_product_subscription_list';
$sortObject = new CAdminUiSorting($tableId, 'DATE_FROM', 'DESC');
$listObject = new CAdminUiList($tableId, $sortObject);

$by = mb_strtoupper($sortObject->getField());
$order = mb_strtoupper($sortObject->getOrder());

$listContactTypes = array();
$contactType = Catalog\SubscribeTable::getContactTypes();
foreach ($contactType as $contactTypeId => $contactTypeData)
{
	$listContactTypes[$contactTypeId] = $contactTypeData["NAME"];
}

$filterFields = array(
	array(
		"id" => "ID",
		"name" => GetMessage('PSL_FILTER_ID'),
		"filterable" => "="
	),
	array(
		"id" => "USER_ID",
		"name" => GetMessage('PSL_FILTER_USER_ID'),
		"type" => "custom_entity",
		"selector" => array("type" => "user"),
		"filterable" => "=",
		"default" => true
	),
	array(
		"id" => "USER_CONTACT",
		"name" => GetMessage('PSL_FILTER_USER_CONTACT'),
		"filterable" => "%",
		"quickSearch" => "%"
	),
	array(
		"id" => "ITEM_ID",
		"name" => GetMessage('PSL_FILTER_ITEM_ID'),
		"type" => "number",
		"filterable" => "=",
		"default" => true
	),
	array(
		"id" => "DATE_FROM",
		"name" => GetMessage("PSL_FILTER_DATE_FROM"),
		"type" => "date",
		"filterable" => "",
		"default" => true
	),
	array(
		"id" => "DATE_TO",
		"name" => GetMessage("PSL_FILTER_DATE_TO"),
		"type" => "date",
		"filterable" => ""
	),
	array(
		"id" => "CONTACT_TYPE",
		"name" => GetMessage("PSL_FILTER_CONTACT_TYPE"),
		"type" => "list",
		"items" => $listContactTypes,
		"filterable" => "="
	),
	array(
		"id" => "ACTIVE",
		"name" => GetMessage("PSL_FILTER_ACTIVE"),
		"type" => "list",
		"items" => array(
			"Y" => GetMessage("PSL_FILTER_YES"),
			"N" => GetMessage("PSL_FILTER_NO")
		),
		"filterable" => ""
	),
);

$filter = array();

$listObject->AddFilter($filterFields, $filter);

if (isset($_REQUEST['ITEM_ID']))
{
	$filter["ITEM_ID"] = $_REQUEST['ITEM_ID'];
}
if (isset($filter["ACTIVE"]))
{
	if ($filter["ACTIVE"] == 'Y')
	{
		$filter[] = array(
			'LOGIC' => 'OR',
			array('=DATE_TO' => false),
			array('>DATE_TO' => date($DB->dateFormatToPHP(CLang::getDateFormat('FULL')), time()))
		);
	}
	else
	{
		$filter[] = array(
			'LOGIC' => 'AND',
			array('!=DATE_TO' => false),
			array('<DATE_TO' => date($DB->dateFormatToPHP(CLang::getDateFormat('FULL')), time()))
		);
	}
	unset($filter["ACTIVE"]);
}

$subscribeManager = new Catalog\Product\SubscribeManager();

if(($listRowId = $listObject->groupAction()))
{
	switch($_REQUEST['action'])
	{
		case 'delete':
			$itemId = 0;
			if (isset($_REQUEST['itemId']))
				$itemId = $_REQUEST['itemId'];
			$subscribeManager->deleteManySubscriptions($listRowId, $itemId);
			break;
		case 'activate':
			$subscribeManager->activateSubscription($listRowId);
			break;
		case 'deactivate':
			$subscribeManager->deactivateSubscription($listRowId);
			break;
	}

	$errorObject = current($subscribeManager->getErrors());
	if($errorObject)
	{
		$listObject->addGroupError($errorObject->getMessage());
	}

	if ($listObject->hasGroupErrors())
	{
		$adminSidePanelHelper->sendJsonErrorResponse($listObject->getGroupErrors());
	}
	else
	{
		$adminSidePanelHelper->sendSuccessResponse();
	}
}

$headers = array();
$headers['ID'] = array('id' => 'ID', 'content' => 'ID', 'sort' => 'ID', 'default' => true, 'align' => 'center');
$headers['DATE_FROM'] = array('id' => 'DATE_FROM','content' => Loc::getMessage('PSL_DATE_FROM'),
	'sort' => 'DATE_FROM', 'default' => true);
$headers['USER_CONTACT'] = array('id' => 'USER_CONTACT','content' => Loc::getMessage('PSL_USER_CONTACT'),
	'sort' => 'USER_CONTACT', 'default' => true);
$headers['USER_ID'] = array('id' => 'USER_ID', 'content' => Loc::getMessage('PSL_USER'),
	'sort' => 'USER_ID', 'default' => true);
$headers['CONTACT_TYPE'] = array('id' => 'CONTACT_TYPE','content' => Loc::getMessage('PSL_CONTACT_TYPE'),
	'sort' => 'CONTACT_TYPE', 'default' => true, 'align' => 'center');
$headers['ACTIVE'] = array('id' => 'ACTIVE', 'content' => Loc::getMessage('PSL_ACTIVE'),
	'default' => true, 'align' => 'center');
$headers['DATE_TO'] = array('id' => 'DATE_TO','content' => Loc::getMessage('PSL_DATE_TO'),
	'sort' => 'DATE_TO', 'default' => true);
$headers['ITEM_ID'] = array('id' => 'ITEM_ID','content' => Loc::getMessage('PSL_ITEM_ID'),
	'sort' => 'ITEM_ID', 'default' => false, 'align' => 'right');
$headers['PRODUCT_NAME'] = array('id' => 'PRODUCT_NAME','content' => Loc::getMessage('PSL_PRODUCT_NAME'),
	'sort' => 'PRODUCT_NAME', 'default' => true);
$headers['SITE_ID'] = array('id' => 'SITE_ID','content' => Loc::getMessage('PSL_SITE_ID'),
	'sort' => 'SITE_ID', 'default' => true, 'align' => 'center');

$listObject->addHeaders($headers);

$select = array();
$ignoreFields = array('ACTIVE');
$selectFields = array_keys($headers);
$selectFields = array_diff($selectFields, $ignoreFields);
foreach($selectFields as $fieldName)
{
	$select[$fieldName] = $fieldName;
}
$select['PRODUCT_NAME'] = 'IBLOCK_ELEMENT.NAME';
$select['IBLOCK_ID'] = 'IBLOCK_ELEMENT.IBLOCK_ID';

$queryObject = Catalog\SubscribeTable::getList(array(
	'select' => $select,
	'filter' => $filter,
	'order' => array($by => $order),
));

$queryObject = new CAdminUiResult($queryObject, $tableId);
$queryObject->NavStart();

$listObject->SetNavigationParams($queryObject, array("BASE_LINK" => $selfFolderUrl."cat_subscription_list.php"));

$actionUrl = '&lang='.LANGUAGE_ID;
$listUserData = array();
$rowList = [];

while($subscribe = $queryObject->fetch())
{
	$subscribe['CONTACT_TYPE'] = $contactType[$subscribe['CONTACT_TYPE']]['NAME'];
	if(!empty($subscribe['USER_ID']))
	{
		$listUserData[$subscribe['USER_ID']][] = $subscribe['ID'];
	}

	$rowList[$subscribe['ID']] = $row = &$listObject->addRow($subscribe['ID'], $subscribe);

	if($subscribeManager->checkSubscriptionActivity($subscribe['DATE_TO']))
	{
		$row->addField('ACTIVE', Loc::getMessage('PSL_FILTER_YES'));
	}
	else
	{
		$row->addField('ACTIVE', Loc::getMessage('PSL_FILTER_NO'));
	}

	$urlBuilder->setIblockId((int)$subscribe['IBLOCK_ID']);
	if ($adminPage)
	{
		$editUrl = $urlBuilder->getElementDetailUrl(
			(int)$subscribe['ITEM_ID'],
			$urlParams
		);
	}
	else
	{
		$editUrl = $urlBuilder->getProductDetailUrl(
			(int)$subscribe['ITEM_ID'],
			$urlParams
		);
	}

	$row->addField('PRODUCT_NAME',
		'<a href="'.$editUrl.'">'.htmlspecialcharsbx($subscribe['PRODUCT_NAME']).'</a>');

	$actions = array();
	$actionUrl .= '&itemId='.$subscribe['ITEM_ID'];
	$actions[] = array(
		'ICON' => 'delete',
		'TEXT' => Loc::getMessage('PSL_ACTION_DELETE'),
		'ACTION' => "if(confirm('".GetMessageJS('PSL_ACTION_DELETE_CONFIRM')."')) ".
			$listObject->actionDoGroup($subscribe['ID'], 'delete', $actionUrl)
	);
	$actions[] = array(
		'TEXT' => Loc::getMessage('PSL_ACTION_ACTIVATE'),
		'ACTION' => $listObject->actionDoGroup($subscribe['ID'], 'activate')
	);
	$actions[] = array(
		'TEXT' => Loc::getMessage('PSL_ACTION_DEACTIVATE'),
		'ACTION' => $listObject->actionDoGroup($subscribe['ID'], 'deactivate')
	);

	$row->addActions($actions);
}
unset($row);

if (!empty($listUserData))
{
	$nameFormat = CSite::GetNameFormat();

	$userIterator = Main\UserTable::getList([
		'select' => [
			'ID',
			'LOGIN',
			'NAME',
			'LAST_NAME',
			'SECOND_NAME',
			'EMAIL',
			'TITLE',
		],
		'filter' => ['@ID' => array_keys($listUserData)],
	]);
	while ($user = $userIterator->fetch())
	{
		$urlToUser = $selfFolderUrl . "user_edit.php?ID=" . $user["ID"] . "&lang=" . LANGUAGE_ID;
		if ($publicMode)
		{
			$urlToUser = $selfFolderUrl . "sale_buyers_profile.php?USER_ID=" . $user["ID"] . "&lang=" . LANGUAGE_ID;
			$urlToUser = $adminSidePanelHelper->editUrlToPublicPage($urlToUser);
		}
		$userString =
			'<a href="' . $urlToUser . '">'
			. CUser::FormatName($nameFormat, $user, true, true)
			. '</a>'
		;
		foreach ($listUserData[$user['ID']] as $subscribeId)
		{
			$rowList[$subscribeId]->addField('USER_ID', $userString);
		}
	}
}

$listObject->addGroupActionTable(array(
	'delete' => Loc::getMessage('PSL_ACTION_DELETE'),
	'activate' => Loc::getMessage('PSL_ACTION_ACTIVATE'),
	'deactivate' => Loc::getMessage('PSL_ACTION_DEACTIVATE'),
));

$contextListMenu = array();
$listObject->setContextSettings(array("pagePath" => $selfFolderUrl."cat_subscription_list.php"));
$listObject->addAdminContextMenu($contextListMenu);

$listObject->checkListMode();

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php');

$listObject->DisplayFilter($filterFields);
$listObject->displayList();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");

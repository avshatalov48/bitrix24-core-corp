<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('catalog'))
{
	ShowError(GetMessage('CRM_CATALOG_MODULE_NOT_INSTALLED'));
	return;
}


global $USER, $APPLICATION;

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

/*
 * PATH_TO_VAT_LIST
 * PATH_TO_VAT_SHOW
 * PATH_TO_VAT_EDIT
 * VAT_ID
 * VAT_ID_PAR_NAME
 */

$arParams['PATH_TO_VAT_LIST'] = CrmCheckPath('PATH_TO_VAT_LIST', $arParams['PATH_TO_VAT_LIST'], '');
$arParams['PATH_TO_VAT_SHOW'] = CrmCheckPath('PATH_TO_VAT_SHOW', $arParams['PATH_TO_VAT_SHOW'], '?vat_id=#vat_id#&show');
$arParams['PATH_TO_VAT_EDIT'] = CrmCheckPath('PATH_TO_VAT_EDIT', $arParams['PATH_TO_VAT_EDIT'], '?vat_id=#vat_id#&edit');

$vatID = isset($arParams['VAT_ID']) ? intval($arParams['VAT_ID']) : 0;
if($vatID <= 0)
{
	$vatIDParName = isset($arParams['VAT_ID_PAR_NAME']) ? intval($arParams['VAT_ID_PAR_NAME']) : 0;

	if($vatIDParName <= 0)
		$vatIDParName = 'vat_id';

	$vatID = isset($_REQUEST[$vatIDParName]) ? intval($_REQUEST[$vatIDParName]) : 0;
}

$arVat = array();

if($vatID > 0)
{
	if(!($arVat = CCrmVat::GetByID($vatID)))
	{
		ShowError(GetMessage('CRM_VAT_NOT_FOUND'));
		@define('ERROR_404', 'Y');
		if($arParams['SET_STATUS_404'] === 'Y')
		{
			CHTTP::SetStatus("404 Not Found");
		}
		return;
	}
}

$arResult['VAT_ID'] = $vatID;
$arResult['VAT'] = $arVat;
$isEditMode = $vatID > 0 ? true : false;

$arResult['FORM_ID'] = 'CRM_VAT_EDIT';
$arResult['GRID_ID'] = 'CRM_VAT_EDIT';
$arResult['BACK_URL'] = CComponentEngine::MakePathFromTemplate(
	$arParams['PATH_TO_VAT_LIST'],
	array()
);

if(check_bitrix_sessid())
{
	if($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['save']) || isset($_POST['apply'])))
	{

		$vatID = isset($_POST['vat_id']) ? intval($_POST['vat_id']) : 0;

		$fields = array();

		if( $vatID <= 0 && isset($_POST['ID']))
			$vatID = intval(trim($_POST['ID']));

		if(isset($_POST['C_SORT']))
			$fields['C_SORT'] = $_POST['C_SORT'];

		if(isset($_POST['ACTIVE']))
			$fields['ACTIVE'] = $_POST['ACTIVE'];

		if(isset($_POST['NAME']))
			$fields['NAME'] = $_POST['NAME'];

		if(isset($_POST['RATE']))
			$fields['RATE'] = $_POST['RATE'];

		$arVat = CCrmVat::GetByID($vatID);

		$errorMsg = '';

		if(is_array($arVat))
		{
			$fields['ID'] = $vatID;

			if(!CCatalogVat::Set($fields))
			{
				if ($ex = $GLOBALS['APPLICATION']->GetException())
					$errorMsg = $ex->GetString();
				else
					$errorMsg = GetMessage('CRM_VAT_UPDATE_UNKNOWN_ERROR');
			}
		}
		else
		{
			$vatID = CCatalogVat::Set($fields);

			if(intval($vatID) <= 0)
			{
				if ($ex = $GLOBALS['APPLICATION']->GetException())
					$errorMsg = $ex->GetString();
				else
					$errorMsg = GetMessage('CRM_VAT_ADD_UNKNOWN_ERROR');
			}
		}

		if(strlen($errorMsg)<=0)
		{
			LocalRedirect(
				isset($_POST['apply'])
					? CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_VAT_EDIT'],
					array('vat_id' => $vatID)
				)
					: CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_VAT_LIST'],
					array('vat_id' => $vatID)
				)
			);
		}
		else
		{
			ShowError($errorMsg);
			$arVat = $fields;
		}
	}
	elseif ($_SERVER['REQUEST_METHOD'] == 'GET' &&  isset($_GET['delete']))
	{
		$vatID = isset($arParams['VAT_ID']) ? intval($arParams['VAT_ID']) : 0;
		$arVat = $vatID > 0 ? CCrmVat::GetByID($vatID) : null;
		if($arVat)
		{
			if(!CCatalogVat::Delete($vatID))
				ShowError(GetMessage('CRM_VAT_DELETE_UNKNOWN_ERROR'));
		}

		LocalRedirect(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_VAT_LIST'],
				array()
			)
		);
	}
}

$arResult['FIELDS'] = array();

if(strlen($arParams['VAT_ID']) > 0)
{
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'ID',
		'name' => GetMessage('CRM_VAT_FIELD_ID'),
		'value' => $vatID,
		'type' =>  'label'
	);
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'NAME',
	'name' =>  GetMessage('CRM_VAT_FIELD_NAME'),
	'value' => htmlspecialcharsbx($arVat['NAME']),
	'type' =>  'text'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'RATE',
	'name' =>  GetMessage('CRM_VAT_FIELD_RATE'),
	'value' => floatval($arVat['RATE']),
	'type' =>  'text'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ACTIVE',
	'name' =>  GetMessage('CRM_VAT_FIELD_ACTIVE'),
	'value' => $arVat['ACTIVE'] == 'Y',
	'type' =>  'checkbox'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'C_SORT',
	'name' =>  GetMessage('CRM_VAT_FIELD_C_SORT'),
	'value' => intval($arVat['C_SORT']),
	'type' =>  'text',
);

$this->IncludeComponentTemplate();
?>
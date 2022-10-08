<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arParams */

use Bitrix\Main\Loader,
	Bitrix\Catalog;

if (!Loader::includeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!Loader::includeModule('catalog'))
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

$excludeVatId = CCrmVat::getExcludeVatId();

$arResult['VAT_ID'] = $vatID;
$arResult['VAT'] = $arVat;
$isEditMode = $vatID > 0;

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
		$vatID = (int)($_POST['vat_id'] ?? 0);

		$fields = array();

		if( $vatID <= 0 && isset($_POST['ID']))
			$vatID = intval(trim($_POST['ID']));

		if(isset($_POST['SORT']))
		{
			$fields['SORT'] = (int)$_POST['SORT'];
		}
		elseif(isset($_POST['C_SORT']))
		{
			$fields['SORT'] = (int)$_POST['C_SORT']; // legacy code
		}

		if(isset($_POST['ACTIVE']))
			$fields['ACTIVE'] = $_POST['ACTIVE'];

		if(isset($_POST['NAME']))
			$fields['NAME'] = $_POST['NAME'];

		if ($vatID !== $excludeVatId)
		{
			if ($excludeVatId === null)
			{
				$value = $_POST['EXCLUDE_VAT'] ?? null;
				if (is_string($value))
				{
					$fields['EXCLUDE_VAT'] = $value;
				}
			}
			$value = $_POST['RATE'] ?? null;
			if (is_string($value))
			{
				$value = (float)$value;
				if ($value >= 0)
				{
					$fields['RATE'] = $value;
				}
			}
		}

		$arVat = CCrmVat::GetByID($vatID);

		$errorMsg = '';

		if (!empty($fields))
		{
			if (is_array($arVat))
			{
				$result = Catalog\Model\Vat::update($vatID, $fields);
				if (!$result->isSuccess())
				{
					$errorMsg = implode(' ', $result->getErrorMessages());
					if ($errorMsg === '')
					{
						$errorMsg = GetMessage('CRM_VAT_UPDATE_UNKNOWN_ERROR');
					}
				}
				unset($result);
			}
			else
			{
				$result = Catalog\Model\Vat::add($fields);
				if ($result->isSuccess())
				{
					$vatID = (int)$result->getId();
				}
				else
				{
					$errorMsg = implode(' ', $result->getErrorMessages());
					if ($errorMsg === '')
					{
						$errorMsg = GetMessage('CRM_VAT_ADD_UNKNOWN_ERROR');
					}
				}
				unset($result);
			}
		}

		if($errorMsg == '')
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
			if (isset($fields['SORT']))
			{
				$fields['C_SORT'] = $fields['SORT'];
			}
			$arVat = $fields;

		}
	}
	elseif ($_SERVER['REQUEST_METHOD'] == 'GET' &&  isset($_GET['delete']))
	{
		$vatID = (int)($arParams['VAT_ID'] ?? 0);
		$arVat = $vatID > 0 ? CCrmVat::GetByID($vatID) : null;
		if($arVat)
		{
			$result = Catalog\Model\Vat::delete($vatID);
			if (!$result->isSuccess())
			{
				$error = implode(' ', $result->getErrorMessages());
				if ($error === '')
				{
					$error = GetMessage('CRM_VAT_DELETE_UNKNOWN_ERROR');
				}
				ShowError($error);
			}
			unset($result);
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

if($arParams['VAT_ID'] <> '')
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

if ($excludeVatId === null)
{
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'EXCLUDE_VAT',
		'name' =>  GetMessage('CRM_VAT_FIELD_EXCLUDE_VAT'),
		'value' => $arVat['EXCLUDE_VAT'] === 'Y',
		'type' => 'checkbox',
	);
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'RATE',
	'name' =>  GetMessage('CRM_VAT_FIELD_RATE'),
	'value' => $arVat['EXCLUDE_VAT'] === 'Y' ? GetMessage('CRM_VAT_EMPTY') : $arVat['RATE'],
	'type' =>  $arVat['EXCLUDE_VAT'] === 'Y' ? 'custom' : 'text'
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

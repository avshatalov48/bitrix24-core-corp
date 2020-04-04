<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('catalog'))
{
	ShowError(GetMessage('CATALOG_MODULE_NOT_INSTALLED'));
	return;
}

$userPermissions = CCrmAuthorizationHelper::GetUserPermissions();
if (!CCrmAuthorizationHelper::CheckConfigurationUpdatePermission($userPermissions))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

global $APPLICATION;

$arParams['PATH_TO_MEASURE_LIST'] = CrmCheckPath('PATH_TO_MEASURE_LIST', $arParams['PATH_TO_MEASURE_LIST'], '');
$arParams['PATH_TO_MEASURE_EDIT'] = CrmCheckPath('PATH_TO_MEASURE_EDIT', $arParams['PATH_TO_MEASURE_EDIT'], '?measure_id=#measure_id#&edit');

$elementID = isset($arParams['ELEMENT_ID']) ? intval($arParams['ELEMENT_ID']) : 0;
if($elementID <= 0 && isset($_REQUEST['measure_id']))
{
	$elementID = intval($_REQUEST['measure_id']);
}
$arParams['ELEMENT_ID'] = $elementID;

$isEditMode = $elementID > 0;

if(!$isEditMode)
{
	$fields = array('ID' => 0);
}
else
{
	$select = array(
		'ID',
		'CODE',
		'MEASURE_TITLE',
		'SYMBOL_RUS',
		'SYMBOL_INTL',
		'SYMBOL_LETTER_INTL',
		'IS_DEFAULT',
	);

	$dbResult = CCatalogMeasure::GetList(array(), array('ID' => $elementID), false, false, $select);
	$fields = $dbResult->GetNext();

	if(!is_array($fields))
	{
		$arParams['ELEMENT_ID'] = 0;
		$fields = array('ID' => 0);
	}
}

$arResult['ELEMENT'] = $fields;
unset($fields);


if(check_bitrix_sessid())
{
	if($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['save']) || isset($_POST['saveAndView']) || isset($_POST['apply'])))
	{
		$errors = array();
		$code = isset($_REQUEST['CODE']) ? trim($_REQUEST['CODE']) : '';
		if($code === '')
		{
			$errors[] = GetMessage('CRM_MEASURE_ERR_CODE_EMPTY');
		}
		elseif(preg_match('/^[0-9]+$/', $code) !== 1)
		{
			$errors[] = GetMessage('CRM_MEASURE_ERR_CODE_INVALID');
		}
		else
		{
			$code = (int)$code;
		}

		$title = isset($_REQUEST['MEASURE_TITLE']) ? trim($_REQUEST['MEASURE_TITLE']) : '';
		if($title == '')
		{
			$errors[] = GetMessage('CRM_MEASURE_ERR_TITLE_EMPTY');
		}

		$fields = array(
			'CODE' => $code,
			'MEASURE_TITLE' => $title,
			'SYMBOL_RUS' => isset($_REQUEST['SYMBOL_RUS']) ? $_REQUEST['SYMBOL_RUS'] : '',
			'SYMBOL_INTL' => isset($_REQUEST['SYMBOL_INTL']) ? $_REQUEST['SYMBOL_INTL'] : '',
			'SYMBOL_LETTER_INTL' => isset($_REQUEST['SYMBOL_LETTER_INTL']) ? $_REQUEST['SYMBOL_LETTER_INTL'] : '',
			'IS_DEFAULT' => isset($_REQUEST['IS_DEFAULT']) && $_REQUEST['IS_DEFAULT'] == 'Y' ? 'Y' : 'N',
		);

		if(empty($errors))
		{
			if($elementID > 0)
			{
				if(CCatalogMeasure::update($elementID, $fields) === false)
				{
					$exception = $APPLICATION->GetException();
					if($exception)
					{
						$errors[] = $exception->GetString();
					}
					else
					{
						$errors[] = GetMessage('CRM_MEASURE_ERR_UPDATE');
					}
				}
			}
			else
			{
				$result = CCatalogMeasure::getList(array(), array('=CODE' => $code));
				if(is_array($result->Fetch()))
				{
					$errors[] = GetMessage('CRM_MEASURE_ERR_ALREADY_EXISTS', array('#CODE#' => $code));
				}
				else
				{
					$result = CCatalogMeasure::add($fields);
					if(is_int($result))
					{
						$elementID = $arParams['ELEMENT_ID'] = $result;
					}
					else
					{
						$exception = $APPLICATION->GetException();
						if($exception)
						{
							$errors[] = $exception->GetString();
						}
						else
						{
							$errors[] = GetMessage('CRM_MEASURE_ERR_CREATE');
						}
					}
				}
			}
			$fields['ID'] = $elementID;
		}

		$arResult['ELEMENT'] = $fields;
		if(!empty($errors))
		{
			ShowError(implode("\n", $errors));
		}
		else
		{
			LocalRedirect(
				isset($_POST['apply'])
					? CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_MEASURE_EDIT'], array('measure_id' => $elementID))
					: CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_MEASURE_LIST'])
			);
		}
	}
	elseif($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete']) && $elementID > 0)
	{
		CCatalogMeasure::delete($elementID);
		LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_MEASURE_LIST']));
	}
}

$arResult['FORM_ID'] = !empty($arParams['FORM_ID']) ? $arParams['FORM_ID'] : 'CRM_MEASURE_EDIT';
$arResult['GRID_ID'] = 'CRM_MEASURE_LIST';
$arResult['BACK_URL'] = $arParams['PATH_TO_MEASURE_LIST'];

$arResult['FIELDS'] = array();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_measure_info',
	'name' => GetMessage('CRM_SECTION_MEASURE_INFO'),
	'type' => 'section'
);


if($isEditMode)
{
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'ID',
		'name' => GetMessage('CRM_MEASURE_FIELD_ID'),
		'value' => isset($arResult['ELEMENT']['ID']),
		'type' => 'label'
	);
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IS_DEFAULT',
	'name' =>  GetMessage('CRM_MEASURE_FIELD_IS_DEFAULT'),
	'value' => isset($arResult['ELEMENT']['IS_DEFAULT']) ? $arResult['ELEMENT']['IS_DEFAULT'] : 'N',
	'type' =>  'checkbox'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CODE',
	'name' =>  GetMessage('CRM_MEASURE_FIELD_CODE'),
	'value' => isset($arResult['ELEMENT']['CODE']) ? $arResult['ELEMENT']['CODE'] : '',
	'type' =>  'text',
	'required' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'MEASURE_TITLE',
	'name' =>  GetMessage('CRM_MEASURE_FIELD_MEASURE_TITLE'),
	'value' => isset($arResult['ELEMENT']['MEASURE_TITLE']) ? $arResult['ELEMENT']['MEASURE_TITLE'] : '',
	'type' =>  'text',
	'required' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SYMBOL_RUS',
	'name' =>  GetMessage('CRM_MEASURE_FIELD_SYMBOL_RUS'),
	'value' => isset($arResult['ELEMENT']['SYMBOL_RUS']) ? $arResult['ELEMENT']['SYMBOL_RUS'] : '',
	'type' =>  'text'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SYMBOL_INTL',
	'name' =>  GetMessage('CRM_MEASURE_FIELD_SYMBOL_INTL'),
	'value' => isset($arResult['ELEMENT']['SYMBOL_INTL']) ? $arResult['ELEMENT']['SYMBOL_INTL'] : '',
	'type' =>  'text'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SYMBOL_LETTER_INTL',
	'name' =>  GetMessage('CRM_MEASURE_FIELD_SYMBOL_LETTER_INTL'),
	'value' => isset($arResult['ELEMENT']['SYMBOL_LETTER_INTL']) ? $arResult['ELEMENT']['SYMBOL_LETTER_INTL'] : '',
	'type' =>  'text'
);

$this->IncludeComponentTemplate();
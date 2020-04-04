<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!$USER->IsAuthorized())
{
	$APPLICATION->AuthForm(GetMessage('FRLM_NEED_AUTH'));
	return false;
}

if (!CModule::IncludeModule('form'))
{
	ShowError('FRLM_MODULE_NOT_INSTALLED');
	return false;
}

if (!is_array($arParams['FORMS']))
	$arParams['FORMS'] = array();
else
	TrimArr($arParams['FORMS']);

$arParams['NUM_RESULTS'] = intval($arParams['NUM_RESULTS']);
if($arParams['NUM_RESULTS'] <= 0)
	$arParams['NUM_RESULTS'] = 50;

$arResult['FORMS'] = array();
$arResult['RESULTS'] = array();

if (count($arParams['FORMS']) <= 0)
{
	$dbRes = CForm::GetList($by = 'sort', $order = 'asc', array('SITE' => SITE_ID), $is_filtered);
	while ($arRes = $dbRes->GetNext())
	{
		$arParams['FORMS'][] = $arRes['ID'];
		$arResult['FORMS'][$arRes['ID']] = $arRes;
	}

}

foreach ($arParams['FORMS'] as $FORM_ID)
{
	if (is_array($arResult['FORMS'][$FORM_ID]))
	{
		$arForm = $arResult['FORMS'][$FORM_ID];
	}
	else
	{
		$dbRes = CForm::GetByID($FORM_ID);
		$arForm = $dbRes->GetNext();
	}

	if ($arForm)
	{
		if ($arParams['LIST_URL'])
			$arForm['__LINK'] = str_replace('#FORM_ID#', $FORM_ID, $arParams['LIST_URL']);

		$arResult['FORMS'][$FORM_ID] = $arForm;
		$arResult['RESULTS'][$FORM_ID] = array();

		$dbRes = CFormResult::GetList($FORM_ID, $by = 's_timestamp', $order = 'desc', array('USER_ID' => $USER->GetID()), $is_filtered, 'Y', $arParams['NUM_RESULTS']);
		$bFirst = true;
		while ($arRes = $dbRes->GetNext())
		{
			//if ($FORM_ID == 6) print_r($arRes);
			if ($bFirst)
			{
				$arResult['FORMS'][$FORM_ID]['__LAST_TS'] = MakeTimeStamp($arRes['TIMESTAMP_X']);
				$bFirst = false;
			}

			$arValues = CFormResult::GetDataByID($arRes['ID'], array(), $arRes1 = null, $arAnswers = null);

			//if ($FORM_ID == 6) print_r($arValues);

			reset ($arValues);
			list(, $first_res) = each($arValues);
			$arRes['__TITLE'] = trim($first_res[0]['USER_TEXT'] ? $first_res[0]['USER_TEXT'] : $first_res[0]['MESSAGE']);

			$arRes['__RIGHTS'] = CFormResult::GetPermissions($arRes['ID'], $status);

			if ($arParams['EDIT_URL'] && in_array('EDIT', $arRes['__RIGHTS']))
				$arRes['__LINK'] = str_replace(array('#FORM_ID#', '#RESULT_ID#'), array($FORM_ID, $arRes['ID']), $arParams['EDIT_URL']);
			elseif ($arParams['VIEW_URL'])
				$arRes['__LINK'] = str_replace(array('#FORM_ID#', '#RESULT_ID#'), array($FORM_ID, $arRes['ID']), $arParams['VIEW_URL']);

			$arResult['RESULTS'][$FORM_ID][] = $arRes;
		}
	}

	if (!is_array($arResult['RESULTS'][$FORM_ID]) || count($arResult['RESULTS'][$FORM_ID]) <= 0)
	{
		unset($arResult['FORMS'][$FORM_ID]);
		unset($arResult['RESULTS'][$FORM_ID]);
	}
}

//echo '<pre>'; print_r($arResult['RESULTS'][6]); /*print_r($arResult['FORMS'][6]);*/ echo '</pre>';

if(!function_exists('BX_FSBT')){function BX_FSBT($a,$b){$q='__LAST_TS';$c=$a[$q];$d=$b[$q];return($c==$d?0:($c<$d?1:-1));}};
uasort($arResult['FORMS'], 'BX_FSBT');

$this->IncludeComponentTemplate();
?>
<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;

global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");

$arResult['GRID_DATA'] = $arColumns = array();
foreach ($arResult['HEADERS'] as $arHead)
{
	$arColumns[$arHead['id']] = false;
}
foreach($arResult['TAX_RATES'] as $key => &$arTaxRate)
{
	$arActions = array();

	if($arResult['CAN_EDIT'])
	{
		if(Loader::includeModule('sale') && CSaleLocation::isLocationProEnabled())
		{
			$width = 1024;
			$height = 768;
			$resizable = 'true';
		}
		else
		{
			$width = 498;
			$height = 275;
			$resizable = 'false';
		}

		$taxRateEditDialog = "javascript:(new BX.CDialog({'content_url':'/bitrix/components/bitrix/crm.config.tax.rate.edit/box.php?FORM_ID=".$arParams['TAX_FORM_ID']."&TAX_ID=".$arResult['TAX_ID']."&ID=".$key."', 'width':'".$width."', 'height':'".$height."', 'resizable':".$resizable." })).Show(); return false;";

		$arActions[] =  array(
			'ICONCLASS' => 'edit',
			'TITLE' => GetMessage('CRM_TAXRATE_EDIT_TITLE'),
			'TEXT' => GetMessage('CRM_TAXRATE_EDIT'),
			'ONCLICK' => $taxRateEditDialog,
			'DEFAULT' => true
		);
	}

	if ($arResult['CAN_DELETE'])
	{
		$arActions[] = array('SEPARATOR' => true);
		$arActions[] =  array(
			'ICONCLASS' => 'delete',
			'TITLE' => GetMessage('CRM_TAXRATE_DELETE_TITLE'),
			'TEXT' => GetMessage('CRM_TAXRATE_DELETE'),
			'ONCLICK' => 'crm_taxrate_delete_grid(\''.CUtil::JSEscape(GetMessage('CRM_TAXRATE_DELETE_TITLE')).'\', \''.CUtil::JSEscape(sprintf(GetMessage('CRM_TAXRATE_DELETE_CONFIRM'), htmlspecialcharsbx($arTaxRate['NAME']))).'\', \''.CUtil::JSEscape(GetMessage('CRM_TAXRATE_DELETE')).'\', \''.CUtil::JSEscape($arTaxRate['PATH_TO_TAXRATE_DELETE']).'\')'
		);
	}

	$arResult['GRID_DATA'][] = array(
		'id' => $key,
		'actions' => $arActions,
		'data' => $arTaxRate,
		'editable' => $arResult['CAN_EDIT'] ? true : $arColumns,
		'columns' => array(
			'ACTIVE' => $arTaxRate['ACTIVE'],
			'TIMESTAMP_X' => $arTaxRate['TIMESTAMP_X'],
			'NAME' => htmlspecialcharsbx($arTaxRate['NAME']),
			'PERSON_TYPE_ID' => $arTaxRate['PERSON_TYPE_ID'],
			'VALUE' => $arTaxRate['VALUE'],
			'IS_IN_PRICE' => $arTaxRate['IS_IN_PRICE'],
			'APPLY_ORDER' => $arTaxRate['APPLY_ORDER']
		)
	);
}
unset($arTaxRate);
$APPLICATION->IncludeComponent(
	'bitrix:main.interface.grid',
	'',
	array(
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => $arResult['HEADERS'],
		'SORT' => $arResult['SORT'],
		'SORT_VARS' => $arResult['SORT_VARS'],
		'ROWS' => $arResult['GRID_DATA'],
		'FOOTER' =>
		array(
			array(
				'title' => GetMessage('CRM_ALL'),
				'value' => $arResult['ROWS_COUNT']
			)
		),
		'EDITABLE' => false,
		'ACTIONS' => array(),
		'ACTION_ALL_ROWS'=>false,
		'NAV_OBJECT' => $arResult['TAX_RATES'],
		'FORM_ID' => $arResult['FORM_ID'],
		'TAB_ID' => $arResult['TAB_ID'],
		'SHOW_FORM_TAG' => false,
		'AJAX_MODE' => 'N'
	),
	$component
);
?>
<script type="text/javascript">
	function crm_taxrate_delete_grid(title, message, btnTitle, path)
	{
		var d =
			new BX.CDialog(
				{
					title: title,
					head: '',
					content: message,
					resizable: false,
					draggable: true,
					height: 70,
					width: 300
				}
			);

		var _BTN = [
			{
				title: btnTitle,
				id: 'crmOk',
				'action': function ()
				{
					window.location.href = path;
					BX.WindowManager.Get().Close();
				}
			},
			BX.CDialog.btnCancel
		];
		d.ClearButtons();
		d.SetButtons(_BTN);
		d.Show();
	}
</script>

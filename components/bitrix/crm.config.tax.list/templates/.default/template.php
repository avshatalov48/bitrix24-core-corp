<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');

$arResult['GRID_DATA'] = $arColumns = array();
foreach ($arResult['HEADERS'] as $arHead)
{
	$arColumns[$arHead['id']] = false;
}
foreach($arResult['TAXIES'] as $key => &$arTax)
{
	$arActions = array();
	/*
	$arActions[] =  array(
		'ICONCLASS' => 'view',
		'TITLE' => GetMessage('CRM_TAX_SHOW_TITLE'),
		'TEXT' => GetMessage('CRM_TAX_SHOW'),
		'ONCLICK' => 'jsUtils.Redirect([], \''.CUtil::JSEscape($arTax['PATH_TO_TAX_SHOW']).'\');',
		'DEFAULT' => true
	);
	*/
	if($arResult['CAN_EDIT'])
	{
		$arActions[] =  array(
			'ICONCLASS' => 'view',
			'TITLE' => GetMessage('CRM_TAX_EDIT_TITLE'),
			'TEXT' => GetMessage('CRM_TAX_EDIT'),
			'ONCLICK' => 'jsUtils.Redirect([], \''.CUtil::JSEscape($arTax['PATH_TO_TAX_EDIT']).'\');',
			'DEFAULT' => true
		);
	}

	if ($arResult['CAN_DELETE'])
	{
		$arActions[] = array('SEPARATOR' => true);
		$arActions[] =  array(
			'ICONCLASS' => 'delete',
			'TITLE' => GetMessage('CRM_TAX_DELETE_TITLE'),
			'TEXT' => GetMessage('CRM_TAX_DELETE'),
			'ONCLICK' => 'crm_tax_delete_grid(\''.CUtil::JSEscape(GetMessage('CRM_TAX_DELETE_TITLE')).'\', \''.CUtil::JSEscape(sprintf(GetMessage('CRM_TAX_DELETE_CONFIRM'), htmlspecialcharsbx($arTax['NAME']))).'\', \''.CUtil::JSEscape(GetMessage('CRM_TAX_DELETE')).'\', \''.CUtil::JSEscape($arTax['PATH_TO_TAX_DELETE']).'\')'
		);
	}

	$arResult['GRID_DATA'][] = array(
		'id' => $key,
		'actions' => $arActions,
		'data' => $arTax,
		'editable' => $arResult['CAN_EDIT'] ? true : $arColumns,
		'columns' => array(
			'NAME' => '<a target="_self" href="'.$arTax['PATH_TO_TAX_EDIT'].'">'.htmlspecialcharsbx($arTax['NAME']).'</a>',
			'TIMESTAMP_X' => $arTax['TIMESTAMP_X'],
			'LID' => $arTax['LID'],
			'CODE' => $arTax['CODE'],
			'STAV' => $arTax['STAV']
		)
	);
}
unset($arTax);

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
		'EDITABLE' => $arResult['CAN_EDIT'],
		'ACTIONS' =>
			array(
				'delete' => $arResult['CAN_DELETE'],
				'list' => array()
			),
		'ACTION_ALL_ROWS'=>true,
		'NAV_OBJECT' => $arResult['TAXIES'],
		'FORM_ID' => $arResult['FORM_ID'],
		'TAB_ID' => $arResult['TAB_ID'],
		'AJAX_MODE' => 'N'
	),
	$component
);

?>

<script type="text/javascript">

<?if(!CCrmLocations::isLocationsCreated()):?>
	crmShowSetLocationsDialog();
<?endif;?>

	function crm_tax_delete_grid(title, message, btnTitle, path)
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

	function crmShowSetLocationsDialog()
	{
		var d =
			new BX.CDialog(
				{
					title: "<?=GetMessage('CRM_TAX_LOCATIONS')?>",
					head: "",
					content: "<?=GetMessage('CRM_TAX_LOCATIONS_CONTENT')?>",
					resizable: false,
					draggable: true,
					height: 70,
					width: 350
				}
			);

		var _BTN = [

			{
				title: "<?=GetMessage('CRM_TAX_LOCATIONS_REDIRECT')?>",
				id: "crmLocCreate",
				"action": function()
				{
					window.location.href = "/crm/configs/locations/";
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
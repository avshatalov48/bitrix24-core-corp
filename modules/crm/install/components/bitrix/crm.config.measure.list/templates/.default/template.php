<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');

$arResult['GRID_DATA'] = $arColumns = array();
foreach ($arResult['HEADERS'] as $arHead)
{
	$arColumns[$arHead['id']] = false;
}
foreach($arResult['MEASURES'] as $key => &$arMeasure)
{
	$arActions = array();
	/*
	$arActions[] =  array(
		'ICONCLASS' => 'view',
		'TITLE' => GetMessage('CRM_MEASURE_SHOW_TITLE'),
		'TEXT' => GetMessage('CRM_MEASURE_SHOW'),
		'ONCLICK' => 'jsUtils.Redirect([], \''.CUtil::JSEscape($arMeasure['PATH_TO_MEASURE_SHOW']).'\');',
		'DEFAULT' => true
	);
	*/
	if($arResult['CAN_EDIT'])
	{
		$arActions[] =  array(
			'ICONCLASS' => 'view',
			'TITLE' => GetMessage('CRM_MEASURE_EDIT_TITLE'),
			'TEXT' => GetMessage('CRM_MEASURE_EDIT'),
			'ONCLICK' => 'jsUtils.Redirect([], \''.CUtil::JSEscape($arMeasure['PATH_TO_MEASURE_EDIT']).'\');',
			'DEFAULT' => false
		);
	}
	if ($arResult['CAN_DELETE'])
	{
		//$arActions[] = array('SEPARATOR' => true);
		$arActions[] =  array(
			'ICONCLASS' => 'delete',
			'TITLE' => GetMessage('CRM_MEASURE_DELETE_TITLE'),
			'TEXT' => GetMessage('CRM_MEASURE_DELETE'),
			'ONCLICK' => "crm_measure_delete_grid('".CUtil::JSEscape(GetMessage('CRM_MEASURE_DELETE_TITLE'))."', '".CUtil::JSEscape(GetMessage('CRM_MEASURE_DELETE_CONFIRM', array('#MEASURE_TITLE#' => $arMeasure['MEASURE_TITLE'])))."', '".CUtil::JSEscape(GetMessage('CRM_MEASURE_DELETE'))."', '".CUtil::JSEscape($arMeasure['PATH_TO_DELETE'])."')"
		);
	}

	$arMeasure['IS_DEFAULT'] =  GetMessage($arMeasure['IS_DEFAULT'] === 'Y' ? 'MAIN_YES' : 'MAIN_NO');
	$arResult['GRID_DATA'][] = array(
		'id' => $key,
		'actions' => $arActions,
		'data' => $arMeasure,
		'editable' => $arResult['CAN_EDIT'] ? true : $arColumns
		//'columns' => array()
	);
}
unset($arMeasure);

$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.grid',
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
		'ACTION_ALL_ROWS' => false,
		'NAV_OBJECT' => $arResult['MEASURES'],
		'FORM_ID' => $arResult['FORM_ID'],
		'TAB_ID' => $arResult['TAB_ID'],
		'AJAX_MODE' => 'N'
	),
	$component
);
?>
<script type="text/javascript">
	function crm_measure_delete_grid(title, message, btnTitle, path)
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

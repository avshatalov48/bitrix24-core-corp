<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->AddHeadScript('/bitrix/js/crm/interface_grid.js');

$arResult['GRID_DATA'] = $arColumns = array();
foreach ($arResult['HEADERS'] as $arHead)
{
	$arColumns[$arHead['id']] = false;
}
foreach($arResult['PROPS'] as $key => &$arProp)
{
	$arActions = array();

	if($arResult['CAN_EDIT'])
	{
		$arActions[] =  array(
			'ICONCLASS' => 'edit',
			'TITLE' => GetMessage('CRM_PRODUCTPROP_EDIT_TITLE'),
			'TEXT' => GetMessage('CRM_PRODUCTPROP_EDIT'),
			'ONCLICK' => 'jsUtils.Redirect([], \''.CUtil::JSEscape($arProp['PATH_TO_PRODUCTPROPS_EDIT']).'\');',
			'DEFAULT' => true
		);
	}

	if ($arResult['CAN_DELETE'])
	{
		$arActions[] = array('SEPARATOR' => true);
		$arActions[] =  array(
			'ICONCLASS' => 'delete',
			'TITLE' => GetMessage('CRM_PRODUCTPROP_DELETE_TITLE'),
			'TEXT' => GetMessage('CRM_PRODUCTPROP_DELETE'),
			'ONCLICK' => 'crm_productprop_delete_grid(\''.CUtil::JSEscape(GetMessage('CRM_PRODUCTPROP_DELETE_TITLE')).'\', \''.CUtil::JSEscape(sprintf(GetMessage('CRM_PRODUCTPROP_DELETE_CONFIRM'), htmlspecialcharsbx($arProp['NAME']))).'\', \''.CUtil::JSEscape(GetMessage('CRM_PRODUCTPROP_DELETE')).'\', \''.CUtil::JSEscape($arProp['PATH_TO_PRODUCTPROPS_DELETE']).'\')'
		);
	}

	$gridDataItem = array(
		'id' => $key,
		'actions' => $arActions,
		'data' => $arProp,
		'editable' => $arResult['CAN_EDIT'] ? true : $arColumns,
	);
	if ($arResult['CAN_EDIT'])
	{
		$gridDataItem['columns']['NAME'] =
			'<a href="'.$arProp['PATH_TO_PRODUCTPROPS_EDIT'].'">'.$arProp['NAME'].'</a>';
	}
	$arResult['GRID_DATA'][] = $gridDataItem;
	unset($gridDataItem);
}
unset($arProp);

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
		'ACTION_ALL_ROWS'=>true,
		'NAV_OBJECT'=>$arResult['NAV_RESULT'],
		'FORM_ID' => $arResult['FORM_ID'],
		'TAB_ID' => $arResult['TAB_ID'],
		'AJAX_MODE' => 'N',
		'FILTER' => $arResult['FILTER'],
		'FILTER_PRESETS' => $arResult['FILTER_PRESETS']
	),
	$component
);

?>
<script type="text/javascript">
	function crm_productprop_delete_grid(title, message, btnTitle, path)
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

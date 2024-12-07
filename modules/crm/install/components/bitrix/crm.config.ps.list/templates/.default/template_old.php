<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;
\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');

$arResult['GRID_DATA'] = $arColumns = array();
foreach ($arResult['HEADERS'] as $arHead)
{
	$arColumns[$arHead['id']] = false;
}
foreach($arResult['PAY_SYSTEMS'] as $key => &$arPS)
{
	$arActions = array();

	if($arResult['CAN_EDIT'])
	{
		$arActions[] =  array(
			'ICONCLASS' => 'view',
			'TITLE' => GetMessage('CRM_PS_EDIT_TITLE'),
			'TEXT' => GetMessage('CRM_PS_EDIT'),
			'ONCLICK' => 'jsUtils.Redirect([], \''.CUtil::JSEscape($arPS['PATH_TO_PS_EDIT']).'\');',
			'DEFAULT' => false
		);
	}

	if ($arResult['CAN_DELETE'])
	{
		$arActions[] = array('SEPARATOR' => true);
		$arActions[] =  array(
			'ICONCLASS' => 'delete',
			'TITLE' => GetMessage('CRM_PS_DELETE_TITLE'),
			'TEXT' => GetMessage('CRM_PS_DELETE'),
			'ONCLICK' => 'crm_ps_delete_grid(\''.CUtil::JSEscape(GetMessage('CRM_PS_DELETE_TITLE')).'\', \''.CUtil::JSEscape(sprintf(GetMessage('CRM_PS_DELETE_CONFIRM'), htmlspecialcharsbx($arPS['NAME']))).'\', \''.CUtil::JSEscape(GetMessage('CRM_PS_DELETE')).'\', \''.CUtil::JSEscape($arPS['PATH_TO_PS_DELETE']).'\')'
		);
	}

	$arResult['GRID_DATA'][] = array(
		'id' => $key,
		'actions' => $arActions,
		'data' => $arPS,
		'editable' => $arResult['CAN_EDIT'] ? true : $arColumns,
		'columns' => array(
			'NAME' => '<a target="_self" href="'.$arPS['PATH_TO_PS_EDIT'].'">'.$arPS['NAME'].'</a>',
			'ACTIVE' => $arPS['ACTIVE'],
			'PERSON_TYPE_NAME' => $arPS['PERSON_TYPE_NAME'],
			'SORT' => $arPS['SORT']
		)
	);
}
unset($arPS);

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
		'ACTION_ALL_ROWS'=>false,
		'NAV_OBJECT' => $arResult['PAY_SYSTEMS'],
		'FORM_ID' => $arResult['FORM_ID'],
		'TAB_ID' => $arResult['TAB_ID'],
		'AJAX_MODE' => 'N'
	),
	$component
);
?>
<script>
	function crm_ps_delete_grid(title, message, btnTitle, path)
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

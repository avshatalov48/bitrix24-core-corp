<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');

$arResult['GRID_DATA'] = $arColumns = array();
foreach ($arResult['HEADERS'] as $arHead)
{
	$arColumns[$arHead['id']] = false;
}
foreach($arResult['VATS'] as $key => &$arVat)
{
	$arActions = array();
	/*
	$arActions[] =  array(
		'ICONCLASS' => 'view',
		'TITLE' => GetMessage('CRM_VAT_SHOW_TITLE'),
		'TEXT' => GetMessage('CRM_VAT_SHOW'),
		'ONCLICK' => 'jsUtils.Redirect([], \''.CUtil::JSEscape($arVat['PATH_TO_VAT_SHOW']).'\');',
		'DEFAULT' => true
	);
	*/

	if($arResult['CAN_EDIT'])
	{
		$arActions[] =  array(
			'ICONCLASS' => 'view',
			'TITLE' => GetMessage('CRM_VAT_EDIT_TITLE'),
			'TEXT' => GetMessage('CRM_VAT_EDIT'),
			'ONCLICK' => 'jsUtils.Redirect([], \''.CUtil::JSEscape($arVat['PATH_TO_VAT_EDIT']).'\');',
			'DEFAULT' => true
		);
	}

	if ($arResult['CAN_DELETE'])
	{
		$arActions[] = array('SEPARATOR' => true);
		$arActions[] =  array(
			'ICONCLASS' => 'delete',
			'TITLE' => GetMessage('CRM_VAT_DELETE_TITLE'),
			'TEXT' => GetMessage('CRM_VAT_DELETE'),
			'ONCLICK' => 'crm_vat_delete_grid(\''.CUtil::JSEscape(GetMessage('CRM_VAT_DELETE_TITLE')).'\', \''.CUtil::JSEscape(sprintf(GetMessage('CRM_VAT_DELETE_CONFIRM'), htmlspecialcharsbx($arVat['NAME']))).'\', \''.CUtil::JSEscape(GetMessage('CRM_VAT_DELETE')).'\', \''.CUtil::JSEscape($arVat['PATH_TO_VAT_DELETE']).'\')'
		);
	}

	$arResult['GRID_DATA'][] = array(
		'id' => $key,
		'actions' => $arActions,
		'data' => $arVat,
		'editable' => $arResult['CAN_EDIT'] ? true : $arColumns,
		'columns' => array(
			'C_SORT' => $arVat['C_SORT'],
			'ACTIVE' => $arVat['ACTIVE'],
			'NAME' => '<a target="_self" href="'.$arVat['PATH_TO_VAT_EDIT'].'">'.htmlspecialcharsbx($arVat['NAME']).'</a>',
			'RATE' => $arVat['RATE']
		)
	);
}
unset($arVat);

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
		'NAV_OBJECT' => $arResult['VATS'],
		'FORM_ID' => $arResult['FORM_ID'],
		'TAB_ID' => $arResult['TAB_ID'],
		'AJAX_MODE' => 'N'
	),
	$component
);
?>
<script type="text/javascript">
	function crm_vat_delete_grid(title, message, btnTitle, path)
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

<?
if ($arResult['VAT_MODE']) :
	?><div><?
	$formId = 'crm_form_product_row_tax_uniform';
	$checkBoxId = 'crm_checkbox_product_row_tax_uniform';
	$checkBoxName = $arResult['AJAX_PARAM_NAME'];
	?>
	<form id="<?=$formId?>" method="POST" action="<?=POST_FORM_ACTION_URI?>">
		<div>
			<div style="display: inline-block;"><input id="<?=$checkBoxId?>" type="checkbox" name="<?=$checkBoxName?>"<?= ($arResult['PRODUCT_ROW_TAX_UNIFORM'] === 'Y') ? ' checked="checked"' : '' ?> />
			</div><div style="width: 97%; display: inline-block; vertical-align: middle; padding: 0 0 4px 4px;"><?= htmlspecialcharsbx(GetMessage('CRM_PRODUCT_ROW_TAX_UNIFORM_TITLE')) ?>
			</div>
		</div>
		<div class="crm-dup-control-type-info" style="margin-top: 10px; max-width: none;"><?= htmlspecialcharsbx(GetMessage('CRM_PRODUCT_ROW_TAX_UNIFORM_ALERT')) ?></div>
	</form>
	<script type="text/javascript">
		BX.ready(function () {
			var form =BX('<?=$formId?>');
			var check = BX('<?=$checkBoxId?>');
			var url = form.getAttribute("action");
			if (form && url && check)
			{
				BX.bind(check, 'click',
					function () {
						var checked = (this.checked) ? "Y" : "N";
						BX.ajax.post(url, {"sessid": "<?=CUtil::JSEscape(bitrix_sessid())?>", "<?=$checkBoxName?>": checked}, function () {});
					}
				);
			}
		});
	</script>
	</div><?
endif;    // if ($arResult['VAT_MODE']) :
?>
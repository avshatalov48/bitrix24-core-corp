<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)die();
global $APPLICATION;

$APPLICATION->SetAdditionalCSS('/bitrix/themes/.default/crm-entity-show.css');

$arResult['GRID_DATA'] = $arColumns = array();
foreach ($arResult['HEADERS'] as $arHead)
{
	$arColumns[$arHead['id']] = false;
}

foreach($arResult['ITEMS'] as &$item)
{
	$arActions = array();

	if($arResult['CAN_EDIT'] && $item['CAN_EDIT'])
	{
		$arActions[] =  array(
			'ICONCLASS' => 'view',
			'TITLE' => GetMessage('CRM_MAIL_TEMPLATE_EDIT_TITLE'),
			'TEXT' => GetMessage('CRM_MAIL_TEMPLATE_EDIT'),
			'HREF' => $item['PATH_TO_EDIT'],
			'DEFAULT' => false
		);
	}

	if ($arResult['CAN_DELETE'] && $item['CAN_DELETE'])
	{
		$arActions[] = array('SEPARATOR' => true);
		$arActions[] =  array(
			'ICONCLASS' => 'delete',
			'TITLE' => GetMessage('CRM_MAIL_TEMPLATE_DELETE_TITLE'),
			'TEXT' => GetMessage('CRM_MAIL_TEMPLATE_DELETE'),
			'ONCLICK' => "crm_mail_template_delete_grid('"
				.CUtil::JSEscape(GetMessage('CRM_MAIL_TEMPLATE_DELETE_TITLE'))
				."', '"
				.CUtil::JSEscape(GetMessage('CRM_MAIL_TEMPLATE_DELETE_CONFIRM_MSGVER_1', ['#TITLE#' => $item['TITLE']]))."', '"
				.CUtil::JSEscape(GetMessage('CRM_MAIL_TEMPLATE_DELETE'))."', '"
				.CUtil::JSEscape($item['PATH_TO_DELETE'])
				."')"
		);
	}

	$arResult['GRID_DATA'][] = array(
		'id' => $item['~ID'],
		'actions' => $arActions,
		'data' => $item,
		'editable' => $arResult['CAN_EDIT'] ? true : $arColumns,
		'columns' => array(
			'TITLE' => $item['CAN_EDIT'] ? '<a target="_self" href="'.htmlspecialcharsbx($item['PATH_TO_EDIT']).'">'.$item['TITLE'].'</a>' : $item['TITLE'],
			'CREATED' => FormatDate('SHORT', MakeTimeStamp($item['~CREATED'])),
			'LAST_UPDATED' => FormatDate('SHORT', MakeTimeStamp($item['~LAST_UPDATED']))
		)
	);
}
unset($item);

if($arResult['NEED_FOR_CONVERTING'])
{
	$messageViewID = $arResult['MESSAGE_VIEW_ID'];
	if($messageViewID !== '')
	{
		$this->SetViewTarget($messageViewID, 100);
	}
	?><div class="crm-view-message"><?= GetMessage('CRM_MAIL_TEMPLATE_NEED_FOR_CONVERTING', array('#URL_EXECUTE_CONVERTING#' => htmlspecialcharsbx($arResult['CONV_EXEC_URL']), '#URL_SKIP_CONVERTING#' => htmlspecialcharsbx($arResult['CONV_SKIP_URL']))) ?></div><?
	if($messageViewID !== '')
	{
		$this->EndViewTarget();
	}
}

$snippet = new \Bitrix\Main\Grid\Panel\Snippet();
$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	array(
		'GRID_ID' => $arResult['GRID_ID'],

		'AJAX_MODE' => 'Y',
		'AJAX_OPTION_HISTORY' => 'N',

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
		'NAV_OBJECT' => $arResult['ITEMS'],
		'FORM_ID' => $arResult['FORM_ID'],
		'TAB_ID' => $arResult['TAB_ID'],

		'ACTION_PANEL' => array(
			'GROUPS' => array(
				array('ITEMS' =>
					array(
						$snippet->getEditButton(),
						$snippet->getRemoveButton()
					),
				),
			),
		),

		'TOTAL_ROWS_COUNT' => $arResult['ROWS_COUNT'],
	),
	$component
);
?><script type="text/javascript">
	function crm_mail_template_delete_grid(title, message, btnTitle, path)
	{
		var d = new BX.PopupWindow(
			'delete_mail_template', null,
			{
				titleBar: title,
				width: 580,
				closeIcon: true,
				closeByEsc: true,
				overlay: true,
				lightShadow: true
			}
		);

		var _BTN = [
			new BX.PopupWindowButton({
				text: btnTitle,
				className: 'popup-window-button-accept',
				events: {
					click: function ()
					{
						BX.addClass(this.buttonNode, 'popup-window-button-wait');
						window.location.href = path;
					}
				}
			}),
			new BX.PopupWindowButton({
				text: BX.message('JS_CORE_WINDOW_CANCEL'),
				className: 'popup-window-button',
				events: {
					click: function()
					{
						this.popupWindow.close();
					}
				}
			})
		];

		d.setContent(message);
		d.setButtons(_BTN);
		d.show();
	}
</script><?

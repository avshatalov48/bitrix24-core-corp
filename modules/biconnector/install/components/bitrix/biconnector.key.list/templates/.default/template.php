<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Bitrix vars
 * @global CMain $APPLICATION
 * @param array $arParams
 * @param array $arResult
 * @param CBitrixComponentTemplate $this
 */

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load([
	'sidepanel',
	'ui.buttons',
	'ui.icons',
	'ui.notification',
	'ui.fonts.opensans',
]);

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass . ' ' : '') . 'pagetitle-toolbar-field-view');
$this->SetViewTarget('inside_pagetitle');

if ($arResult['CAN_WRITE'])
{
?>
<div class="pagetitle-container pagetitle-align-right-container <?=$pagetitleAlignRightContainer?>">
	<a href="<?=$arParams['KEY_ADD_URL']?>" class="ui-btn ui-btn-primary"><?=Loc::getMessage('CT_BBKL_TOOLBAR_ADD')?></a>
</div>
<?php
}

$this->endViewTarget();

$arResult['HEADERS'] = [
	[
		'id' => 'ID',
		'name' => Loc::getMessage('CT_BBKL_COLUMN_ID'),
		'default' => true,
		'editable' => false,
	],
	[
		'id' => 'ACTIVE',
		'name' => Loc::getMessage('CT_BBKL_COLUMN_ACTIVE'),
		'default' => true,
		'editable' => false,
	],
	[
		'id' => 'ACCESS_KEY',
		'name' => Loc::getMessage('CT_BBKL_COLUMN_ACCESS_KEY'),
		'default' => true,
		'editable' => false,
	],
	[
		'id' => 'APPLICATION',
		'name' => Loc::getMessage('CT_BBKL_COLUMN_APPLICATION'),
		'default' => true,
		'editable' => false,
	],
];

if (count($arResult['CONNECTIONS']) > 1)
{
	$arResult['HEADERS'][] = [
		'id' => 'CONNECTION',
		'name' => Loc::getMessage('CT_BBKL_COLUMN_CONNECTION'),
		'default' => false,
		'editable' => false,
	];
}

$arResult['HEADERS'][] = [
	'id' => 'DATE_CREATE',
	'name' => Loc::getMessage('CT_BBKL_COLUMN_DATE_CREATE'),
	'default' => true,
	'editable' => false,
];

$arResult['HEADERS'][] = [
	'id' => 'CREATED_BY',
	'name' => Loc::getMessage('CT_BBKL_COLUMN_CREATED_BY'),
	'default' => true,
	'editable' => false,
];

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	[
		'GRID_ID' => $arResult['GRID_ID'],
		'COLUMNS' => $arResult['HEADERS'],
		'ROWS' => $arResult['ROWS'],
		'SORT' => $arResult['SORT'],
		'AJAX_MODE' => 'Y',
		'AJAX_OPTION_HISTORY' => 'N',
		'AJAX_OPTION_JUMP' => 'N',
		'ALLOW_ROWS_SORT' => false,
		'SHOW_ROW_CHECKBOXES' => false,
		'SHOW_SELECTED_COUNTER' => false,
		'SHOW_TOTAL_COUNTER' => false,
		'EDITABLE' => false,
	],
	$component,
	['HIDE_ICONS' => 'Y']
);
?>
<script>
	function copyText(btn, copiedText)
	{
		const access_key = btn.previousElementSibling.previousElementSibling;
		const textarea = document.createElement('textarea');
		textarea.value = access_key.value;
		textarea.setAttribute('readonly', '');
		textarea.style.position = 'absolute';
		textarea.style.left = '-9999px';
		document.body.appendChild(textarea);
		textarea.select();

		try {
			document.execCommand('copy');
			BX.UI.Notification.Center.notify({
				content: copiedText,
				autoHideDelay: 2000,
			});
		}
		catch(err)
		{
			BX.UI.Notification.Center.notify({
				content: 'Oops, unable to copy',
				autoHideDelay: 2000,
			});
		}

		textarea.remove();

		return false;
	}

	function showText(btn, showText, hideText)
	{
		const access_key = btn.previousElementSibling;
		if (access_key.type == 'password')
		{
			access_key.type = 'text';
			btn.firstChild.data = hideText;
		}
		else
		{
			access_key.type = 'password';
			btn.firstChild.data = showText;
		}

		return false;
	}

	BX.ready(function ()
	{
		if (BX.SidePanel.Instance)
		{
			BX.SidePanel.Instance.bindAnchors(top.BX.clone({
				rules: [
					{
						condition: [
							<?=CUtil::phpToJSObject($arParams['KEY_ADD_URL'])?>,
							<?=CUtil::phpToJSObject($arParams['KEY_LIST_URL'])?>
						]
					},
				]
			}));
		}
	});
</script>
<?php
if (!\Bitrix\BIConnector\LimitManager::getInstance()->checkLimitWarning())
{
	$APPLICATION->IncludeComponent('bitrix:biconnector.limit.lock', '');
}

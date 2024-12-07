<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/**
 * @var $arParams array
 * @var $arResult array
 * @var $arResult['NAV_OBJECT'] CAllDBResult
 * @var $APPLICATION CMain
 * @var $USER CUser
 */
CJSCore::Init(['voximplant.common', 'ui.design-tokens']);
$numbersC = CVoxImplantConfig::GetPortalNumbers(true, true);
$portalNumber = CVoxImplantConfig::GetPortalNumber();
$numbers = array('' => GetMessage("VI_NUMBERS_DEFAULT")) + $numbersC;

$arRows = array();
foreach ($arResult["USERS"] as $k => $user)
{
	$userNameHtml = '
		<table id="user_'.$user['ID'].'" style="border-collapse: collapse; border: none; ">
			<tr>
				<td style="border: none !important; padding: 0px !important; ">
					<div style="width: 32px; height: 32px; border-radius: 50%; overflow: hidden;">
						<a href="'.$user['DETAIL_URL'].'">'.$user['PHOTO_THUMB'].'</a>
					</div>
				</td>
				<td style="border: none !important; padding: 0px 0px 0px 7px !important; vertical-align: top; ">
					<a href="'.$user['DETAIL_URL'].'" target="_top">'.CUser::formatName(CSite::getNameFormat(), $user, true, true).'</a><br>
					'.htmlspecialcharsbx($user['WORK_POSITION']).'
				</td>
			</tr>
		</table>';

	//$arResult['USERS'][$k]['NAME_HTML'] = $userNameHtml;
	$arCols = [
		'NAME' => $userNameHtml,
		'UF_PHONE_INNER' => '<span id="innerphone_'.$user['ID'].'">'.$user["UF_PHONE_INNER"].'</span>',
		'UF_VI_BACKPHONE' => '<span id="backphone_'.$user['ID'].'">'.(
				array_key_exists($user["UF_VI_BACKPHONE"], $numbers) ? $numbers[$user["UF_VI_BACKPHONE"]] : GetMessage('VI_NUMBERS_DEFAULT')).'</span>'.
				'<span id="backphone_'.$user['ID'].'_value" style="display:none;">'.$user["UF_VI_BACKPHONE"].'</span>',
	];

	if(!\Bitrix\Voximplant\Limits::isRestOnly())
	{
		$arCols['UF_VI_PHONE'] =
			'<span id="vi_phone_' . $user['ID'] . '"' . ($user["UF_VI_PHONE"] == "Y" ? ' class="bx-vi-phone-enable"' : '').'>'.
				($user["UF_VI_PHONE"] == "Y" ? GetMessage('VI_NUMBERS_PHONE_DEVICE_ENABLE') : GetMessage('VI_NUMBERS_PHONE_DEVICE_DISABLE')).
			'</span>';
	}

	$arRows[$user['ID']] = [
		'data' => $user,
		'columns' => $arCols,
		'editable' => false,
		'actions' => [
			[
				"TITLE" => GetMessage("VI_NUMBERS_CONFIG"),
				"TEXT" => GetMessage("VI_NUMBERS_CONFIG"),
				"DEFAULT" => true,
				"ONCLICK" => "(new BX.Voximplant.UserEditor({userId: " . $user['ID'] . "})).show();",
			],
		]
	];
}
$arResult['ROWS'] = $arRows;

$arHeaders = [
	['id' => 'NAME', 'name' => GetMessage('VI_NUMBERS_GRID_NAME'), 'default' => true, 'editable' => false],
	['id' => 'UF_PHONE_INNER', 'name' => GetMessage('VI_NUMBERS_GRID_CODE'), 'default' => true, 'editable' => false],
	['id' => 'UF_VI_BACKPHONE', 'name' => GetMessage('VI_NUMBERS_GRID_PHONE'), 'default' => true, 'editable' => false],
];
if(!\Bitrix\Voximplant\Limits::isRestOnly())
{
	$arHeaders[] = array('id' => 'UF_VI_PHONE', 'name' => GetMessage('VI_NUMBERS_GRID_PHONE_DEVICE'), 'default' => true, 'editable' => false);
}

$isBitrix24Template = (SITE_TEMPLATE_ID == "bitrix24");
if($isBitrix24Template)
{
	$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
	$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "")."pagetitle-toolbar-field-view");
	$this->SetViewTarget("inside_pagetitle", 0);
	?><div class="pagetitle-container pagetitle-flexible-space"><?
}

$APPLICATION->IncludeComponent(
	"bitrix:main.ui.filter",
	"",
	array(
		"GRID_ID" => $arResult["GRID_ID"],
		"FILTER_ID" => $arResult["FILTER_ID"],
		"FILTER" => $arResult["FILTER"],
		"FILTER_PRESETS" => $arResult["FILTER_PRESETS"] ?? null,
		"ENABLE_LIVE_SEARCH" => false,
		"ENABLE_LABEL" => true
	),
	$component,
	array()
);

if($isBitrix24Template)
{
	?></div><?
	$this->EndViewTarget();
}

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	array(
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => $arHeaders,
		'ROWS' => $arRows,
		'NAV_OBJECT' => $arResult['NAV_OBJECT'],
		'SORT' => $arResult['SORT'],
		'TOTAL_ROWS_COUNT' => $arResult['ROWS_COUNT'],
		'SHOW_CHECK_ALL_CHECKBOXES' => false,
		'SHOW_ROW_CHECKBOXES' => false,
		'SHOW_SELECTED_COUNTER' => false,
		'AJAX_MODE' => 'Y',
		'AJAX_ID' => CAjax::GetComponentID('bitrix:voximplant.numbers', '.default', ''),
		'AJAX_OPTION_JUMP' => 'N',
		'AJAX_OPTION_HISTORY' => 'N',
	)
);
?>

<script>
	BX.message({
		VI_NUMBERS_CREATE_TITLE : '<?=GetMessageJS("VI_NUMBERS_CREATE_TITLE")?>',
		VI_NUMBERS_ERR_AJAX : '<?=GetMessageJS("VI_NUMBERS_ERR_AJAX")?>',
		VI_NUMBERS_ERROR : '<?=GetMessageJS("VI_NUMBERS_ERROR")?>',
		VI_NUMBERS_GRID_CODE : '<?=GetMessageJS("VI_NUMBERS_GRID_CODE")?>',
		VI_NUMBERS_GRID_PHONE : '<?=GetMessageJS("VI_NUMBERS_GRID_PHONE")?>',
		VI_NUMBERS_GRID_PHONE_DEVICE : '<?=GetMessageJS("VI_NUMBERS_GRID_PHONE_DEVICE")?>',
		VI_NUMBERS_PHONE_DEVICE_ENABLE : '<?=GetMessageJS("VI_NUMBERS_PHONE_DEVICE_ENABLE")?>',
		VI_NUMBERS_PHONE_DEVICE_DISABLE : '<?=GetMessageJS("VI_NUMBERS_PHONE_DEVICE_DISABLE")?>',
		VI_NUMBERS_PHONE_CONNECT : '<?=GetMessageJS("VI_NUMBERS_PHONE_CONNECT")?>',
		VI_NUMBERS_PHONE_CONNECT_ON : '<?=GetMessageJS("VI_NUMBERS_PHONE_CONNECT_ON")?>',
		VI_NUMBERS_PHONE_CONNECT_OFF : '<?=GetMessageJS("VI_NUMBERS_PHONE_CONNECT_OFF")?>',
		VI_NUMBERS_PHONE_CONNECT_INFO : '<?=GetMessageJS("VI_NUMBERS_PHONE_CONNECT_INFO")?>',
		VI_NUMBERS_PHONE_CONNECT_SERVER : '<?=GetMessageJS("VI_NUMBERS_PHONE_CONNECT_SERVER")?>',
		VI_NUMBERS_PHONE_CONNECT_LOGIN : '<?=GetMessageJS("VI_NUMBERS_PHONE_CONNECT_LOGIN")?>',
		VI_NUMBERS_PHONE_CONNECT_PASSWORD : '<?=GetMessageJS("VI_NUMBERS_PHONE_CONNECT_PASSWORD")?>',
		VI_NUMBERS_SAVE : '<?=GetMessageJS("VI_NUMBERS_SAVE")?>',
		VI_NUMBERS_CANCEL : '<?=GetMessageJS("VI_NUMBERS_CANCEL")?>',
		VI_NUMBERS_URL : '<?=$this->__component->GetPath()?>/ajax.php?act='
	});

	BX.Voximplant.UserEditor.setDefaults({
		gridId: '<?= $arResult['GRID_ID']?>',
		numbers: <?=CUtil::PhpToJSObject($numbers)?>,
		users: <?=CUtil::PhpToJSObject($arResult['USERS'])?>,
		isPhoneAllowed: <?= $arResult['IS_PHONE_ALLOWED'] ? 'true' : 'false'?>
	});

	BX.addCustomEvent(window, 'Grid::beforeRequest', function(gridData, requestParams)
	{
		BX.Voximplant.UserEditor.setDefaults({
			lastGridUrl: requestParams.url
		});
	});
</script>

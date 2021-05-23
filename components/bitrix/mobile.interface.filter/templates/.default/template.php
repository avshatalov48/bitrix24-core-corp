<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

if (is_array($arResult['FIELDS']) && !empty($arResult['FIELDS']))
{
	$APPLICATION->IncludeComponent(
		'bitrix:main.interface.form',
		'mobile',
		array(
			'FORM_ID' => "mobile_iterface_filter",
			'TABS' => array(array(
				"fields" => $arResult['FIELDS']
			))
		),
		$component,
		array('HIDE_ICONS' => 'Y')
	);
}
$arJsParams = array(
	"gridId" => $arParams["GRID_ID"],
	"eventName" => $arResult['EVENT_NAME'],
	"ajaxPath" => "/mobile/?mobile_action=mobile_grid_filter",
	"formId" => "mobile_iterface_filter",
	"formFields" => $arResult['FIELDS_ID']
);
?>
<script>
	app.pullDown({
		enable:   true,
		pulltext: '<?=GetMessageJS('M_FILTER_PULL_TEXT');?>',
		downtext: '<?=GetMessageJS('M_FILTER_DOWN_TEXT');?>',
		loadtext: '<?=GetMessageJS('M_FILTER_LOAD_TEXT');?>',
		callback: function()
		{
			app.reload();
		}
	});
	BXMobileApp.UI.Page.TopBar.title.setText('<?=GetMessageJS("M_FILTER_TITLE")?>');
	BXMobileApp.UI.Page.TopBar.title.show();

	BX.Mobile.Grid.Filter.init(<?=CUtil::PhpToJSObject($arJsParams)?>);

	window.BXMobileApp.UI.Page.TopBar.updateButtons({
		ok: {
			type: "back_text",
			callback: function(){
				BX.Mobile.Grid.Filter.apply();
			},
			name: "<?=GetMessageJS("M_FILTER_BUTTON_APPLY")?>",
			bar_type: "navbar",
			position: "right"
		}
	});
</script>


<?if(!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$control_id = $arParams['CONTROL_ID'];
?>
<div class="bx-ius-layout" style="">
<div class="bx-ius-input" style="width:205px; float:left;">
<?
$APPLICATION->IncludeComponent('bitrix:main.lookup.input', 'users', array(
	'CONTROL_ID' => $control_id,
	
	'INPUT_NAME' => $arParams['~INPUT_NAME'],
	'INPUT_NAME_STRING' => $arParams['~INPUT_NAME_STRING'],
	'INPUT_VALUE_STRING' => $arParams['~INPUT_VALUE_STRING'],
	'INPUT_NAME_SUSPICIOUS' => $arParams['~INPUT_NAME_SUSPICIOUS'],
	
	'MULTIPLE' => $arParams['MULTIPLE'],
	'START_TEXT' => GetMessage('IUS_START_TEXT'),
	'TEXTAREA_MAX_HEIGHT' => $arParams['~TEXTAREA_MAX_HEIGHT'],
	'TEXTAREA_MIN_HEIGHT' => $arParams['~TEXTAREA_MIN_HEIGHT'],
	
	//These params will go throught ajax call to ajax.php in template
	"SOCNET_GROUP_ID" => $arParams["~SOCNET_GROUP_ID"],
	"EXTERNAL" => $arParams["EXTERNAL"],
	
), $component->__parent, array('HIDE_ICONS' => 'Y'));
?>
</div>
<script>
    function WDAddUser2Filter_<?=$control_id?>(user)
    {
        user.NAME = user.NAME.replace(/&lt;/g, "<").replace(/&gt;/g, ">");
        jsMLI_<?=$control_id?>.AddValue(user);
    }
</script>
<?
if ($arParams['EXTERNAL'] == 'I' && intval($arParams["SOCNET_GROUP_ID"]) <= 0):

	$name = $APPLICATION->IncludeComponent(
		'bitrix:intranet.user.search',
		'.default',
		array(
            'ONSELECT' => 'WDAddUser2Filter_'.$control_id,
			'MULTIPLE' => $arParams['MULTIPLE'],
			'SHOW_BUTTON' => 'N',
			'GET_FULL_INFO' => 'Y',
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
?>
<div class="bx-ius-button" style="float:left;">
	<a href="javascript:void(0)" onclick="<?=$name?>.SetValue([]); <?=$name?>.Show()" class="bx-ius-structure-link">&nbsp;</a>
</div>
<?
endif;
?>
<br style="clear:left" />
</div>

<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
$this->IncludeLangFile();

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.buttons',
	'ui.buttons.icons',
	'ui.notification',
	'ui.alerts',
]);

CJSCore::Init(['sidepanel', 'loader']);
?>
<div class="docs-region-error-message" id="edit-region-error-message"<?if($arResult['ERROR']){?> style="display: block;"><?=htmlspecialcharsbx($arResult['ERROR']);}else{?>><?}?></div>
<div class="docs-region-wrap">
	<div class="docs-region-container">
		<label class="docs-region-label"><?=Loc::getMessage('DOCGEN_REGION_EDIT_LANGUAGE');?></label>
		<div class="docs-region-select-wrap">
			<select class="docs-region-select" id="edit-region-culture-select" autocomplete="off">
				<option value="0"><?=Loc::getMessage('DOCGEN_REGION_EDIT_EMPTY');?></option>
				<? foreach ($arResult["cultures"] as $culture)
				{?>
					<option value="<?=htmlspecialcharsbx($culture['ID']);?>"
						<?=($arResult['region'] && $arResult['region']['LANGUAGE_ID'] == $culture['LANGUAGE_ID']) ? 'selected="selected"' : ''; ?>
					><?= htmlspecialcharsbx($culture['NAME']); ?></option>
				<?}?>
			</select>
		</div>
	</div>
</div>
<input type="hidden" name="LANGUAGE_ID" value="<?=($arResult['region'] ? $arResult['region']['LANGUAGE_ID'] : '');?>" id="edit-region-language-input" />
<div class="docs-region-wrap">
	<div class="docs-region-container">
		<label class="docs-region-label"><?=Loc::getMessage('DOCGEN_REGION_EDIT_TITLE');?></label>
		<input class="docs-region-input" name="TITLE" value="<?=htmlspecialcharsbx($arResult['region']['TITLE']);?>" id="edit-region-title-input" />
	</div>
</div>
<div class="docs-region-wrap">
	<div class="docs-region-container">
		<label class="docs-region-label"><?=Loc::getMessage('DOCGEN_REGION_EDIT_FORMAT_DATE');?></label>
		<div class="docs-region-select-wrap">
			<select class="docs-region-select" id="edit-region-format-date-select" autocomplete="off">
				<? foreach ($arResult["dateFormats"] as $format)
				{?>
					<option <?=($arResult['region'] && $arResult['region']['FORMAT_DATE'] == $format) ? 'selected="selected"' : ''; ?>
					><?= htmlspecialcharsbx($format); ?></option>
				<?}?>
			</select>
		</div>
	</div>
</div>
<div class="docs-region-wrap">
	<div class="docs-region-container">
		<label class="docs-region-label"><?=Loc::getMessage('DOCGEN_REGION_EDIT_FORMAT_TIME');?></label>
		<div class="docs-region-select-wrap">
			<select class="docs-region-select" id="edit-region-format-time-select" autocomplete="off">
				<? foreach ($arResult["timeFormats"] as $format)
				{?>
					<option <?=($arResult['region'] && $arResult['region']['FORMAT_TIME'] == $format) ? 'selected="selected"' : ''; ?>
					><?= htmlspecialcharsbx($format); ?></option>
				<?}?>
			</select>
		</div>
	</div>
</div>
<div class="docs-region-wrap">
	<div class="docs-region-container">
		<label class="docs-region-label"><?=Loc::getMessage('DOCGEN_REGION_EDIT_FORMAT_NAME');?></label>
		<div class="docs-region-select-wrap">
			<select class="docs-region-select" id="edit-region-format-name-select" autocomplete="off">
				<? foreach ($arResult["nameFormats"] as $format => $name)
				{?>
					<option value="<?=htmlspecialcharsbx($format);?>" <?=($arResult['region'] && $arResult['region']['FORMAT_NAME'] == $format) ? 'selected="selected"' : ''; ?>
					><?= htmlspecialcharsbx($name); ?></option>
				<?}?>
			</select>
		</div>
	</div>
</div>
<?if(!empty($arResult['phrases']))
{
?>
<div class="docs-region-phrases-title"><?=Loc::getMessage('DOCGEN_REGION_EDIT_PHRASES_TITLE');?></div>
	<?foreach($arResult['phrases'] as $code => $title)
	{
		?>
		<div class="docs-region-wrap">
			<div class="docs-region-container">
				<label class="docs-region-label"><?=htmlspecialcharsbx($title);?></label>
				<input class="docs-region-input docs-region-phrase-input" name="<?=htmlspecialcharsbx($code);?>" value="<?=htmlspecialcharsbx($arResult['region']['phrases'][$code]);?>" />
			</div>
		</div>
		<?
	}?>
<?}?>
<script>
	BX.ready(function()
	{
		BX.DocumentGenerator.Region.init(<?=CUtil::PhpToJSObject($arResult);?>);
		<?='BX.message('.\CUtil::PhpToJSObject(\Bitrix\Main\Localization\Loc::loadLanguageFile(__FILE__)).');'?>
	});
</script>
<?php
$buttons = [
	[
		'TYPE' => 'save',
		'ONCLICK' => 'BX.DocumentGenerator.Region.save(event)',
	],
	[
		'TYPE' => 'close',
		'LINK' => $arResult['closeUrl'],
	]
];
if($arResult['region'] && $arResult['region']['ID'] > 0)
{
	$buttons[] = [
		'TYPE' => 'remove',
		'ONCLICK' => 'BX.DocumentGenerator.Region.delete(event)',
	];
}
$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
	'ALIGN' => 'left',
	'BUTTONS' => $buttons,
]);
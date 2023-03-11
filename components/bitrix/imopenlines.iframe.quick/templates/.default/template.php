<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Imopenlines\Limit;

use Bitrix\Main\UI\Extension;
use Bitrix\Main\Localization\Loc;
/**
 * @var array $arResult
 * @var array $arParams
 * @var \CMain $APPLICATION
 * @var \CBitrixComponentTemplate $this
 * @var \BotcontrollerIframeQuick $component
 */
?>
<html>
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta charset="utf-8" />
<?php
Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.info-helper',
	'ajax'
]);
	Loc::loadMessages(__FILE__);
	$APPLICATION->ShowHead();
	$APPLICATION->ShowCSS(true, true);
	$APPLICATION->ShowHeadStrings();
	$APPLICATION->ShowHeadScripts();
?>
</head>
<body class="imopenlines-iframe-quick <?=($arResult['DARK_MODE'] === true ? 'imopenlines-iframe-quick-dark' : '')?>">
<?if($arResult['CAN_USE_QUICK_ANSWERS'] === true):?>
<div class="imopenlines-iframe-quick-info quick-hidden" id="quick-info-container">
	<div class="imopenlines-iframe-quick-info-header">
		<?=Loc::getMessage('IMOL_QUICK_ANSWERS_INFO_TITLE_NEW');?>
	</div>
	<ul class="imopenlines-iframe-quick-info-list">
		<li class="imopenlines-iframe-quick-info-list-item"><?=Loc::getMessage('IMOL_QUICK_ANSWERS_INFO_LIST_1');?></li>
		<li class="imopenlines-iframe-quick-info-list-item"><a class="imopenlines-iframe-quick-link" id="quick-info-create-message"><?=Loc::getMessage('IMOL_QUICK_ANSWERS_INFO_LIST_2');?></a></li>
		<li class="imopenlines-iframe-quick-info-list-item"><?=Loc::getMessage('IMOL_QUICK_ANSWERS_INFO_LIST_3');?></li>
	</ul>
</div>
<div class="imopenlines-iframe-quick-search-wrap quick-hidden" id="quick-search-container">
	<div class="imopenlines-iframe-quick-search-notification quick-hidden" id="quick-search-notification"><?=Loc::getMessage('IMOL_QUICK_ANSWERS_EDIT_SUCCESS');?></div>
	<div class="imopenlines-iframe-quick-menu">
		<?php
		$APPLICATION->includeComponent(
			"bitrix:main.interface.buttons",
			"",
			array(
				"ID" => "search_category_list",
				"DISABLE_SETTINGS" => true,
				"ITEMS" => $arResult['BUTTONS'],
				"THEME" => "small",
			),
			$component,
			['HIDE_ICONS' => true]
		);
		?>
	</div>
	<div class="imopenlines-iframe-quick-control-block">
		<div class="imopenlines-iframe-quick-search" id="quick-search">
			<input type="text" placeholder="<?=Loc::getMessage('IMOL_QA_IFRAME_SEARCH')?>" class="imopenlines-iframe-quick-search-input" id="quick-search-input" value="<?= htmlspecialcharsbx($arResult['SEARCH']) ?>">
		</div>
		<?php if($arParams['CAN_CREATE'] === true): ?>
			<div class="imopenlines-iframe-quick-search-button imopenlines-iframe-quick-search-add" id="quick-create-message"></div>
		<?php endif; ?>
		<div class="imopenlines-iframe-quick-search-button imopenlines-iframe-quick-search-settings" id="quick-all-url"></div>
	</div>

	<div class="imopenlines-iframe-quick-result quick-hidden" id="quick-result">
	</div>
	<div class="imopenlines-iframe-quick-search-progress" id="quick-search-progress">
		<div class="imopenlines-iframe-quick-search-progress-inner">
			<svg class="imopenlines-iframe-quick-search-progress-loader" viewBox="25 25 50 50">
				<circle class="imopenlines-iframe-quick-search-progress-loader-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/>
				<circle class="imopenlines-iframe-quick-search-progress-loader-inner-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/>
			</svg>
			<div class="imopenlines-iframe-quick-search-progress-text"><?=Loc::getMessage('IMOL_QUICK_ANSWERS_SEARCH_PROGRESS');?></div>
		</div>
	</div>
	<div class="imopenlines-iframe-quick-search-not-found quick-hidden" id="quick-search-not-found">
		<div class="imopenlines-iframe-quick-search-not-found-inner">
			<div class="imopenlines-iframe-quick-search-not-found-smile">:(</div>
			<div class="imopenlines-iframe-quick-search-not-found-text"><?=Loc::getMessage('IMOL_QUICK_ANSWERS_NOT_FOUND');?></div>
		</div>
	</div>
</div>

<div class="imopenlines-iframe-quick-edit quick-hidden" id="quick-edit-container">
	<div class="imopenlines-iframe-quick-edit-header">
		<div class="imopenlines-iframe-quick-edit-title"><?=Loc::getMessage('IMOL_QUICK_ANSWERS_EDIT_SECTION_TITLE');?>:</div>
		<div id="quick-edit-section-select" class="imopenlines-iframe-quick-edit-select-container">
			<div class="imopenlines-iframe-quick-edit-select" id="quick-edit-category"><?=Loc::getMessage('IMOL_QUICK_ANSWERS_EDIT_ALL');?></div>
			<ul class="imopenlines-iframe-quick-category-list" id="edit-category-list">
				<?foreach($arResult['SECTIONS'] as $id => $section)
				{?>
					<li data-id="<?=$id;?>" class="imopenlines-iframe-quick-category-item<?
					if(isset($section['SELECTED']) && $section['SELECTED'] === true)
					{
						?> imopenlines-iframe-quick-category-item-selected<?
					}
					?>"><?=$section['NAME'];?></li>
				<?}?>
			</ul>
		</div>
	</div>
	<div id="quick-edit-result" class="imopenlines-iframe-quick-edit-result quick-hidden"></div>
	<div class="imopenlines-iframe-quick-edit-textarea-container">
		<textarea id="quick-edit-text" class="imopenlines-iframe-quick-edit-textarea" name="text" placeholder="<?=Loc::getMessage('IMOL_QUICK_ANSWERS_EDIT_TEXT_PLACEHOLDER');?>"></textarea>
		<input type="hidden" name="id" id="quick-edit-id" value="" />
	</div>
	<div class="imopenlines-iframe-quick-edit-buttons">
		<button class="imopenlines-iframe-quick-edit-button imopenlines-iframe-quick-edit-save" id="quick-edit-save"><?=Loc::getMessage('IMOL_QUICK_ANSWERS_EDIT_CREATE');?></button>
		<button class="imopenlines-iframe-quick-edit-button imopenlines-iframe-quick-edit-cancel" id="quick-edit-cancel"><?=Loc::getMessage('IMOL_QUICK_ANSWERS_EDIT_CANCEL');?></button>
	</div>
</div>
<?endif;?>

<script>
	BX.ready(function()
	{
		window.quickAnswersManagerInstance = new quickAnswersManager(
			{
				ajaxUrl: '<?=($this->getComponent()->getPath().'/ajax.php')?>',
				allUrl: '<?=$arResult['ALL_URL'];?>',
				sections: <?=CUtil::PhpToJSObject($arResult['SECTIONS']);?>,
				allCount: <?=(int)$arResult['ALL_COUNT'];?>,
				lineId: <?=(int)$arResult['IMOP_ID'];?>,
				canUse: <?=CUtil::PhpToJSObject($arResult['CAN_USE_QUICK_ANSWERS']);?>
			},
			function()
			{
				BX.message({
					'MORE': '<?=Loc::getMessage('IMOL_QA_IFRAME_MORE');?>',
					'LANG': '<?=$arParams['LANG'];?>',
					'IMOL_QUICK_ANSWERS_NOT_FOUND': '<?=CUtil::JSEscape(Loc::getMessage('IMOL_QUICK_ANSWERS_NOT_FOUND'));?>',
					'IMOL_QUICK_ANSWERS_EDIT_CREATE': '<?=CUtil::JSEscape(Loc::getMessage('IMOL_QUICK_ANSWERS_EDIT_CREATE'));?>',
					'IMOL_QUICK_ANSWERS_EDIT_UPDATE': '<?=CUtil::JSEscape(Loc::getMessage('IMOL_QUICK_ANSWERS_EDIT_UPDATE'));?>',
					'IMOL_QUICK_ANSWERS_EDIT_ERROR_EMPTY_TEXT': '<?=CUtil::JSEscape(Loc::getMessage('IMOL_QUICK_ANSWERS_EDIT_ERROR_EMPTY_TEXT'));?>',
					'IMOL_QUICK_ANSWERS_INFO_HELPER_LIMIT': '<?=CUtil::JSEscape(Limit::INFO_HELPER_LIMIT_CONTACT_CENTER_QUICK_ANSWERS);?>'
				});
			}
		);
	});
</script>
</body>
</html>
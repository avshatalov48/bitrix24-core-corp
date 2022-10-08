<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'no-background');

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/main/dd.js');

\Bitrix\Main\UI\Extension::load([
	'rpa.component',
	'color_picker',
	'ui.alerts',
	'ui.design-tokens',
	'ui.fonts.opensans',
]);

if($this->getComponent()->getErrors())
{
	foreach($this->getComponent()->getErrors() as $error)
	{
		/** @var \Bitrix\Main\Error $error */
		?>
		<div><?=htmlspecialcharsbx($error->getMessage());?></div>
		<?php
	}

	return;
}

?>
<div class="rpa-stage-list">
	<div class="ui-alert ui-alert-danger" style="display: none;">
		<span class="ui-alert-message" id="rpa-stages-errors"></span>
		<span class="ui-alert-close-btn" onclick="this.parentNode.style.display = 'none';"></span>
	</div>
	<div class="rpa-status-content" id="rpa-stages-component-container">
		<div class="rpa-stage rpa-initial-stage">
			<div class="rpa-stage-title"><?=Loc::getMessage('RPA_STAGES_FIRST_STAGE_LABEL');?></div>
			<div data-role="rpa-stages-first"></div>
		</div>
		<div class="rpa-stage droppable">
			<div class="rpa-stage-title"><?=Loc::getMessage('RPA_STAGES_COMMON_STAGES_LABEL');?></div>
			<div class="rpa-stages-common" data-role="rpa-stages-common"></div>
			<a class="rpa-stage-addphase draghandle" data-role="rpa-stage-common-add">+
				<span><?=Loc::getMessage('RPA_STAGES_STAGE_ADD');?></span>
			</a>
		</div>
		<div class="rpa-stage-final">
			<div class="rpa-stage-final-title">
				<span class="rpa-stage-final-title-sub"><?=Loc::getMessage('RPA_STAGES_FINAL_STAGES_TITLE');?></span>
			</div>
			<div class="rpa-stage-final-result">
				<div class="rpa-stage-final-success"><?=Loc::getMessage('RPA_STAGES_SUCCESS_STAGE_TITLE');?></div>
				<div class="rpa-stage-final-failure"><?=Loc::getMessage('RPA_STAGES_FAIL_STAGE_TITLE');?></div>
			</div>
			<div class="rpa-stage-final-column">
				<div class="rpa-stage rpa-stage-success">
					<div class="rpa-stage-title"><?=Loc::getMessage('RPA_STAGES_SUCCESS_STAGE_LABEL');?></div>
					<div data-role="rpa-stages-success"></div>
				</div>
			</div>
			<div class="rpa-stage-final-column">
				<div class="rpa-stage rpa-stage-failure droppable">
					<div class="rpa-stage-title"><?=Loc::getMessage('RPA_STAGES_FAIL_STAGE_LABEL');?></div>
					<div class="rpa-stages-fail" data-role="rpa-stages-fail"></div>
					<a class="rpa-stage-addphase draghandle" data-role="rpa-stage-fail-add">+
						<span><?=Loc::getMessage('RPA_STAGES_STAGE_ADD');?></span>
					</a>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
BX.ready(function()
{
	<?='BX.message('.\CUtil::PhpToJSObject(\Bitrix\Main\Localization\Loc::loadLanguageFile(__FILE__)).');'?>
	<?='BX.message('.\CUtil::PhpToJSObject($arResult['messages']).');'?>
	var params = <?=CUtil::PhpToJSObject($arResult['params'], false, false, true);?>;
	params.errorsContainer = document.getElementById('rpa-stages-errors');
	var component = new BX.Rpa.StagesComponent(document.getElementById('rpa-stages-component-container'), params);
	component.init();

	var dragDropItemContainer = new BX.Rpa.DragDropItemContainer(document.querySelector('.rpa-stages-common'));
	dragDropItemContainer.init();

	var dragDropItemContainer2 = new BX.Rpa.DragDropItemContainer(document.querySelector('.rpa-stages-fail'));
	dragDropItemContainer2.init();

});
</script>

<div class="rpa-stage-btn">
	<?
	global $APPLICATION;
	$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
		'BUTTONS' => [
			'save',
			'cancel' => $arResult['backUrl'],
		],
	]);?>
</div>
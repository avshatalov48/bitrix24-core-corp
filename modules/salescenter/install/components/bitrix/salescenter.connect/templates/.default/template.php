<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load(['sidepanel', "ui.buttons", 'salescenter.manager', 'loader']);
?>
<div class="salescenter-wrapper">
	<div class="salescenter-main-section">
		<div class="salescenter-main-header">
			<div class="salescenter-main-header-left-block">
				<div class="salescenter-logo-container">
					<img class="salescenter-logo" src="<?=$templateFolder?>/images/logo.png" alt="">
				</div>
			</div>
			<div class="salescenter-main-header-right-block">
				<div class="salescenter-main-header-title"><?=Loc::getMessage('SALESCENTER_CONNECT_TEMPLATE_TITLE');?></div>
				<div class="salescenter-description"><?=Loc::getMessage('SALESCENTER_CONNECT_TEMPLATE_DESCRIPTION');?></div>
				<div class="salescenter-link-container">
					<a class="salescenter-link" onclick="BX.Salescenter.Manager.openHowItWorks(event);"><?=Loc::getMessage('SALESCENTER_CONNECT_TEMPLATE_HOW_LINK');?></a>
				</div>
				<div class="salescenter-link-container">
					<a class="salescenter-link" onclick="BX.Salescenter.Manager.openHowToConfigOpenLines(event);"><?=Loc::getMessage('SALESCENTER_CONNECT_TEMPLATE_HOW_SOCIAL');?></a>
				</div>
				<div class="salescenter-button-container">
					<button class="ui-btn ui-btn-md ui-btn-primary" id="bx-salescenter-connect-button"><?= Loc::getMessage('SALESCENTER_CONNECT');?></button>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="salescenter-wrapper">
	<div class="salescenter-section">
		<div class="salescenter-header">
			<div class="salescenter-header-title"><?=Loc::getMessage('SALESCENTER_CONNECT_TEMPLATE_HOW');?></div>
		</div>
		<hr class="salescenter-separator">
		<div class="salescenter-description"><?=Loc::getMessage('SALESCENTER_CONNECT_TEMPLATE_HOW_DESCRIPTION');?></div>
		<div class="salescenter-img-container">
			<img class="img-response" src="<?=$templateFolder?>/images/preview.png" alt="">
		</div>
	</div>
</div>

<script>
	BX.ready(function()
	{
		var options = <?=CUtil::PhpToJSObject($arResult)?>;
		BX.Salescenter.Connection.init(options);
	});
</script>
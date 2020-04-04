<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();
?>


<?php if (\Bitrix\Main\Loader::includeModule('bitrix24') && !\Bitrix\Bitrix24\Feature::isFeatureEnabled("report_open_lines")): ?>
	<div class="reports-stub">
		<div class="reports-stub-content">
			<div class="reports-stub-title"><?=\Bitrix\Main\Localization\Loc::getMessage('IMOPENLINES_REPORT_PAGE_LIMITATION_POPUP_TEXT_1')?></div>
			<p class="reports-stub-text"><?=\Bitrix\Main\Localization\Loc::getMessage('IMOPENLINES_REPORT_PAGE_LIMITATION_POPUP_TEXT_2_NEW')?></p>
			<img class="reports-stub-img" src="<?=$templateFolder?>/images/reports-stub.png" alt="">
		</div>
	</div>
	<?php \CBitrix24::showTariffRestrictionButtons('report_open_lines'); ?>
<?php else: ?>
	<?php
	$stepperHtml = \Bitrix\Main\Update\Stepper::getHtml('imopenlines');
	?>

	<?
	if ($stepperHtml): ?>
		<div class="imopenlines-report-stepper-container">
			<?= $stepperHtml ?>
		</div>
	<? endif; ?>
	<?php

	/** @var CAllMain $APPLICATION */
	$APPLICATION->IncludeComponent(
		'bitrix:report.visualconstructor.board.base',
		'',
		array(
			'BOARD_ID' => \Bitrix\ImOpenLines\Integrations\Report\EventHandler::getOpenLinesBoardId(),
			'IS_DEFAULT_MODE_DEMO' => true
		),
		null,
		array()
	);
endif; ?>

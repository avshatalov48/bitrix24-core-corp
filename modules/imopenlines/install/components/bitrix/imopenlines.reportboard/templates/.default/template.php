<?php
use \Bitrix\Imopenlines\Limit;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');
?>

<?php if (!Limit::canUseReport()): ?>
	<div class="reports-stub">
		<div class="reports-stub-content">
			<div class="reports-stub-title"><?=\Bitrix\Main\Localization\Loc::getMessage('IMOPENLINES_REPORT_PAGE_LIMITATION_POPUP_TEXT_1')?></div>
			<p class="reports-stub-text"><?=\Bitrix\Main\Localization\Loc::getMessage('IMOPENLINES_REPORT_PAGE_LIMITATION_POPUP_TEXT_2_NEW')?></p>
			<img class="reports-stub-img" src="<?=$templateFolder?>/images/reports-stub.png" alt="">
		</div>
	</div>
	<?php \CBitrix24::showTariffRestrictionButtons(Limit::OPTION_REPORT); ?>
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

	$boardId = \Bitrix\ImOpenLines\Integrations\Report\EventHandler::getOpenLinesBoardId();
	$filter = new \Bitrix\ImOpenLines\Integrations\Report\VisualConstructor\Helper\Filter($boardId);

	/** @var CAllMain $APPLICATION */
	$APPLICATION->IncludeComponent(
		'bitrix:report.visualconstructor.board.base',
		'',
		array(
			'BOARD_ID' => $boardId,
			'IS_DEFAULT_MODE_DEMO' => true,
			'FILTER' => $filter
		),
		null,
		array()
	);
endif; ?>

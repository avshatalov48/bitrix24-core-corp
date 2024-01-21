<?php

/**
 * @var CBitrixComponentTemplate $this
 * @var string $templateFolder
 * @var array $arParams
 * @var array $arResult
 * @global \CMain $APPLICATION
 */

use Bitrix\Imopenlines\Limit;
use Bitrix\Main\Localization\Loc;
use Bitrix\ImOpenLines\Integrations\Report;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');


if (!Limit::canUseReport())
{
	?>
	<div class="reports-stub">
		<div class="reports-stub-content">
			<div class="reports-stub-title"><?=Loc::getMessage('IMOPENLINES_REPORT_PAGE_LIMITATION_POPUP_TEXT_1')?></div>
			<p class="reports-stub-text"><?=Loc::getMessage('IMOPENLINES_REPORT_PAGE_LIMITATION_POPUP_TEXT_2_NEW')?></p>
			<img class="reports-stub-img" src="<?=$templateFolder?>/images/reports-stub.png" alt="">
		</div>
	</div>
	<?

	\CBitrix24::showTariffRestrictionButtons();
}
else
{
	$stepperHtml = \Bitrix\Main\Update\Stepper::getHtml('imopenlines');
	if ($stepperHtml)
	{
		?>
		<div class="imopenlines-report-stepper-container">
			<?= $stepperHtml ?>
		</div>
		<?
	}

	$boardId = Report\EventHandler::getOpenLinesBoardId();
	$filter = new Report\VisualConstructor\Helper\Filter($boardId);

	$APPLICATION->IncludeComponent(
		'bitrix:report.visualconstructor.board.base',
		'',
		[
			'BOARD_ID' => $boardId,
			'IS_DEFAULT_MODE_DEMO' => true,
			'FILTER' => $filter
		],
		null,
		[]
	);
}
?>
<?php if ($arResult['META_RESTRICTION_LABEL']): ?>
	<div class="intranet-contact-center-extremist-organization-label">
		<span><?= Loc::getMessage("IMOPENLINES_REPORT_PAGE_META_RESTRICTION_RU") ?></span>
	</div>
<?php endif ?>

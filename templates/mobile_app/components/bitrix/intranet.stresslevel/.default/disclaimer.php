<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load(['ui.buttons']);

?><div class="mobile-text-body">
	<div class="mobile-text-wrapper">
		<div class="mobile-text-wrapper-inner">
			<div class="mobile-text-title"><?=Loc::getMessage('INTRANET_STRESSLEVEL_TEMPLATE_DISCLAIMER_ATTENTION')?></div><?

			echo Loc::getMessage('INTRANET_STRESSLEVEL_TEMPLATE_DISCLAIMER_'.mb_strtoupper($arResult['DISCLAIMER_TYPE']).'_TEXT', [
				'#P#' => '<p class="mobile-text">',
				'#A_TERMS_BEGIN#' => '<a href="'.Loc::getMessage('INTRANET_STRESSLEVEL_TEMPLATE_DISCLAIMER_TERMS_LINK').'" target="_blank">',
				'#A_TERMS_END#' => '</a>',
				'#A_PRIVACY_BEGIN#' => '<a href="'.Loc::getMessage('INTRANET_STRESSLEVEL_TEMPLATE_DISCLAIMER_PRIVACY_LINK').'" target="_blank">',
				'#A_PRIVACY_END#' => '</a>'
			]);

		?></div>
	</div>
	<div class="disclaimer-mobile-button-container">
		<div class="mobile-text-button">
			<button id="disclaimer-accept" class="ui-btn  ui-btn-primary"><?=Loc::getMessage('INTRANET_STRESSLEVEL_TEMPLATE_DISCLAIMER_BUTTON_Y')?></button>
		</div>
		<div class="mobile-text-button">
			<button id="disclaimer-reject" class="ui-btn ui-btn-link"><?=Loc::getMessage('INTRANET_STRESSLEVEL_TEMPLATE_DISCLAIMER_BUTTON_N')?></button>
		</div>
	</div>
</div>
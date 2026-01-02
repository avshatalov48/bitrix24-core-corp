<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Filter\HeaderSections;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Tour\MobilePromoter\MobilePromoterCustomSection;
use Bitrix\Crm\Tour\Permissions\AutomatedSolution;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use Bitrix\UI\Toolbar\Manager;

/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 */

Extension::load([
	'ui.dialogs.messagebox',
	'ui.alerts',
	'crm_common',
	'crm.settings-button-extender',
	'crm.entity-list.panel',
	'crm.activity.grid-activities-manager',
	'crm.badge',
	'ui.design-tokens',
	'ui.tooltip',
	'crm.entity-list.binder',
]);

$isRecurring = ($arParams['isRecurring'] ?? false);

if (!$isRecurring && $arResult['customSectionId'] > 0)
{
	echo MobilePromoterCustomSection::getInstance()
		->setCustomSection($arResult['customSectionId'])
		->build()
	;
}

$assets = Asset::getInstance();
$assets->addJs('/bitrix/js/crm/progress_control.js');
$assets->addJs('/bitrix/js/crm/dialog.js');
$assets->addCss('/bitrix/themes/.default/crm-entity-show.css');
$assets->addJs('/bitrix/js/crm/interface_grid.js');

if ($this->getComponent()->getErrors())
{
	foreach ($this->getComponent()->getErrors() as $error)
	{
		/** @var \Bitrix\Main\Error $error */
		?>
		<div class="ui-alert ui-alert-danger">
			<span class="ui-alert-message"><?= $error->getMessage() ?></span>
		</div>
		<?php
	}

	return;
}

echo CCrmViewHelper::RenderItemStatusSettings(
	$arParams['entityTypeId'],
	$arResult['categoryId'] ?? null,
);

/** @see \Bitrix\Crm\Component\Base::addTopPanel() */
$this->getComponent()->addTopPanel($this);

/** @see \Bitrix\Crm\Component\Base::addToolbar() */
$this->getComponent()->addToolbar($this);

if (!$isRecurring)
{
	echo AutomatedSolution::getInstance()
		->setEntityTypeId($arParams['entityTypeId'])
		->build()
	;
}
?>

<div class="ui-alert ui-alert-danger" style="display: none;">
	<span class="ui-alert-message" id="crm-type-item-list-error-text-container"></span>
	<span class="ui-alert-close-btn" onclick="this.parentNode.style.display = 'none';"></span>
</div>

<div class="crm-type-item-list-wrapper" id="crm-type-item-list-wrapper">
	<div
		id="crm-type-item-list-container"
		class="crm-type-item-list-container<?= $arResult['grid'] ? ' crm-type-item-list-grid' : '' ?>"
	>
		<?php
		if ($arResult['grid'])
		{
			echo '<div id="crm-type-item-list-progress-bar-container"></div>';

			if (($arParams['isEmbedded'] ?? false) === true)
			{
				$toolbarId = sprintf('crm-item-list-embedded-toolbar-%d', $arParams['entityTypeId']);
				$toolbar = Manager::getInstance()->createToolbar($toolbarId, []);

				if (!empty($arResult['filter']))
				{
					$toolbar->addFilter($arResult['filter']);
				}

				foreach ($arResult['interfaceToolbar']['buttons'] as $button)
				{
					$toolbar->addButton($button, \Bitrix\UI\Toolbar\ButtonLocation::AFTER_TITLE);
				}

				$toolbar->hideTitle();
			?>
				<div class="crm-list-top-toolbar">
				<?php
 					$GLOBALS["APPLICATION"]->IncludeComponent(
						'bitrix:ui.toolbar',
						'',
						[
							'TOOLBAR_ID' => $toolbarId,
						],
					);
				?>
				</div>
			<?php
			}
			$navigationHtml = '';
			if (isset($arResult['pagination']) && is_array($arResult['pagination']))
			{
				ob_start();
				$APPLICATION->IncludeComponent(
					'bitrix:crm.pagenavigation',
					'',
					$arResult['pagination'],
				);
				$navigationHtml = ob_get_clean();
			}

			$arResult['grid']['NAV_STRING'] = $navigationHtml;
			$arResult['grid']['HEADERS_SECTIONS'] = HeaderSections::getInstance()
				->filterGridSupportedSections($arResult['grid']['HEADERS_SECTIONS'] ?? [])
			;

			$APPLICATION->IncludeComponent(
				'bitrix:main.ui.grid',
				'',
				$arResult['grid'],
			);
		}
		?>
	</div>
</div>

<?php

$messages = array_merge(Container::getInstance()->getLocalization()->loadMessages(), Loc::loadLanguageFile(__FILE__));

if (!empty($arResult['restrictedFieldsEngine']))
{
	Extension::load(['crm.restriction.filter-fields']);

	echo $arResult['restrictedFieldsEngine'];
}
?>

<script>
	BX.ready(() => {
		BX.message(<?= Json::encode($messages) ?>);

		const params = <?= CUtil::PhpToJSObject($arResult['jsParams'], false, false, true) ?>;
		params.errorTextContainer = document.getElementById('crm-type-item-list-error-text-container');
		params.progressBarContainerId = 'crm-type-item-list-progress-bar-container';
		(new BX.Crm.ItemListComponent(params)).init();

		<?php if (isset($arResult['RESTRICTED_VALUE_CLICK_CALLBACK'])): ?>
			BX.addCustomEvent(window, 'onCrmRestrictedValueClick', () => {
				<?= $arResult['RESTRICTED_VALUE_CLICK_CALLBACK'] ?>
			});
		<?php endif; ?>

		<?php if (isset($arParams['PARENT_ENTITY_TYPE_ID'])): ?>
			BX.Crm.Binder.Manager.Instance.initializeBinder(
				<?= $arParams['PARENT_ENTITY_TYPE_ID'] ?>,
				<?= $arParams['PARENT_ENTITY_ID'] ?>,
				<?= CUtil::JSEscape($arResult['jsParams']['entityTypeId']) ?>,
				'<?= CUtil::JSEscape($arResult['jsParams']['gridId']) ?>'
			);
		<?php endif; ?>
	});
</script>

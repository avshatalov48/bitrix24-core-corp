<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;
use Bitrix\UI\Helpdesk;

/**
 * @global \CMain $APPLICATION
 * @var array $arResult
 */

global $APPLICATION;

Extension::load([
	'date',
	'popup',
	'ajax',
	'tooltip',
	'ui.fonts.opensans',
	'sidepanel',
	'ui.hint',
]);

$asset = Asset::getInstance();

$asset->addJs('/bitrix/js/crm/slider.js');
$asset->addCss('/bitrix/js/crm/css/slider.css');
$asset->addCss('/bitrix/js/crm/css/crm.css');
$asset->addCss('/bitrix/themes/.default/crm-entity-show.css');

$asset->addCss('/bitrix/themes/.default/bitrix24/crm-entity-show.css');

$guid = $arResult['GUID']."_editor";
$prefix = mb_strtolower($guid);
$activityEditorID = "{$prefix}_editor";
$readOnly = $arResult['READ_ONLY'];
$containerId = "{$guid}_container";

$wrapperId = "wrapper_".mb_strtolower($prefix);

$editorContext = [
	'CATEGORY_ID' => $arResult['CATEGORY_ID'],
	'PARAMS' => $arResult['CONTEXT_PARAMS'],
];

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass . ' ' : '') . 'no-background');
?>

<div id="<?=htmlspecialcharsbx($wrapperId)?>" class="crm-check-correction-wrapper">
	<?php
	$APPLICATION->IncludeComponent(
		'bitrix:crm.entity.editor',
		'',
		[
			'ENTITY_TYPE_ID' => \CCrmOwnerType::CheckCorrection,
			'ENTITY_ID' => $arResult['ENTITY_ID'],
			'READ_ONLY' => $arResult['ENTITY_ID'] > 0,
			'INITIAL_MODE' => $arResult['INITIAL_MODE'],
			'GUID' => $guid,
			'CONFIG_ID' => $arResult['EDITOR_CONFIG_ID'],
			'ENTITY_CONFIG' => $arResult['ENTITY_CONFIG'],
			'ENTITY_CONTROLLERS' => $arResult['ENTITY_CONTROLLERS'],
			'ENTITY_FIELDS' => $arResult['ENTITY_FIELDS'],
			'ENTITY_DATA' => $arResult['ENTITY_DATA'],
			'ENABLE_MODE_TOGGLE' => false,
			'ENABLE_PAGE_TITLE_CONTROLS' => false,
			'ENABLE_CONFIG_CONTROL' => false,
			'ENABLE_CONFIGURATION_UPDATE' => false,
			'ENABLE_BOTTOM_PANEL' => false,
			'ENABLE_CONFIG_SCOPE_TOGGLE' => false,
			'DISABLE_REST' => true,
			'ENABLE_USER_FIELD_CREATION' => $arResult['ENABLE_USER_FIELD_CREATION'],
			'SERVICE_URL' => '/bitrix/components/bitrix/crm.check.correction.details/ajax.php?'.bitrix_sessid_get(),
			'EXTERNAL_CONTEXT_ID' => $arResult['EXTERNAL_CONTEXT_ID'],
			'CONTEXT_ID' => $arResult['CONTEXT_ID'],
			'CONTEXT' => $editorContext,
		]
	);
	?>
</div>

<script>
	BX.addCustomEvent(window, "BX.Crm.EntityEditorSection:onLayout", function (section, eventArgs) {
		if (eventArgs.id === "main")
		{
			<?php
			if (Loader::includeModule('ui')):
				?>
				const helpdeskLink = BX.create(
					'A',
					{
						'attrs': {
							'href': '<?= (new Helpdesk\Url())->getByCodeArticle('12301946') ?>',
							'className': 'ui-link ui-link-dashed',
							'style': 'width: -webkit-max-content;width: -moz-max-content;width: max-content;'
						},
						'text': '<?= CUtil::JSescape(Loc::getMessage('CRM_CHECK_CORRECTION_HELPDESK_TITLE')) ?>',
					}
				);
				eventArgs.customNodes.push(helpdeskLink);
				<?php
			endif;
			?>
		}

		if (eventArgs.id === "sum")
		{
			// add the hint node; adjust the section's style so that the hint is immediately to the right of the title
			const hintNode = BX.UI.Hint.createNode('<?= CUtil::JSescape(Loc::getMessage('CRM_CHECK_CORRECTION_SUM_HINT')) ?>')
			eventArgs.customNodes.push(hintNode);
			section.getWrapper().querySelector('.ui-entity-editor-header-title').style = 'width: unset;';
			section.getWrapper().querySelector('.ui-entity-editor-section-header').style = 'justify-content: unset;';
		}
	});

	BX.addCustomEvent(window, "onCrmEntityCreate", function () {
		BX.SidePanel.Instance.getSliderByWindow(window).close();
		BX.SidePanel.Instance.postMessage(window, 'CRM:CheckCorrection:onSave');
	});
</script>

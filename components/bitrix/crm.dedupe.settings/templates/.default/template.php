<?php
use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CCrmEntityProgressBarComponent $component */

$APPLICATION->setTitle(Loc::getMessage('CRM_DEDUPE_WIZARD_SCANNING_CONFIG_TITLE'));

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.forms',
]);

?>

<div id="crmDedupeWizardSettings"></div>

<script>
	BX.ready(function()
	{
		BX.Crm.DedupeWizardConfigurationDialog.messages =
			{
				scopeCaption: "<?=GetMessageJS("CRM_DEDUPE_WIZARD_SCANNING_CONFIG_SCOPE")?>",
				criterionCaption: "<?=GetMessageJS("CRM_DEDUPE_WIZARD_SCANNING_CONFIG_CRITERION")?>",
				selectAll: "<?=GetMessageJS("CRM_DEDUPE_WIZARD_SELECT_ALL")?>",
				unselectAll: "<?=GetMessageJS("CRM_DEDUPE_WIZARD_UNSELECT_ALL")?>"
			};
		BX.Crm.DedupeWizardConfigurationDialog.create(
			"<?=CUtil::JSEscape($arResult['GUID'])?>",
			{
				componentName: "<?= $this->getComponent()->getName() ?>",
				container: "crmDedupeWizardSettings",
				config: <?=CUtil::PhpToJSObject($arResult['CONFIG'])?>,
				typeInfos: <?=CUtil::PhpToJSObject($arResult['TYPE_INFOS'])?>,
				scopeInfos: <?=CUtil::PhpToJSObject($arResult['SCOPE_LIST_ITEMS'])?>
			}
		);
	})
</script>
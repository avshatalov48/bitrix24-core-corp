<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CCrmEntityProgressBarComponent $component */

$guid = $arResult['GUID'];
$containerId = "{$guid}_container";
$items = isset($arResult['ITEMS']) ? $arResult['ITEMS'] : array();
$entityID = $arResult['ENTITY_ID'];
$entityTypeID = $arResult['ENTITY_TYPE_ID'];
$currentStepID = $arResult['CURRENT_STEP_ID'];
$currentSemantics = $arResult['CURRENT_SEMANTICS'];

$currentColor = $arResult['CURRENT_COLOR'];
$defaultBackgroundColor = $arResult['DEFAULT_BACKGROUND_COLOR'];
if($currentColor === '')
{
	$currentColor = $defaultBackgroundColor;
}

\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');

//Render progress manager settings
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/common.js');
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/progress_control.js');
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/dialog.js');
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/partial_entity_editor.js');

if($entityTypeID === CCrmOwnerType::Deal)
{
	echo \CCrmViewHelper::RenderDealStageSettings($arParams['EXTRAS']['CATEGORY_ID']);
}
elseif($entityTypeID === CCrmOwnerType::Quote)
{
	echo \CCrmViewHelper::RenderQuoteStatusSettings();
}
elseif($entityTypeID === CCrmOwnerType::Order)
{
	echo \CCrmViewHelper::RenderOrderStatusSettings();
}
elseif($entityTypeID === CCrmOwnerType::OrderShipment)
{
	echo \CCrmViewHelper::RenderOrderShipmentStatusSettings();
}
elseif($entityTypeID === CCrmOwnerType::Lead)
{
	echo \CCrmViewHelper::RenderLeadStatusSettings();
}

// $backgroundImageCss = "url(data:image/svg+xml;charset=UTF-8,%3csvg width='295' height='32' viewBox='0 0 295 32' fill='none' xmlns='http://www.w3.org/2000/svg'%3e%3cmask id='mask0_2_11' style='mask-type:alpha' maskUnits='userSpaceOnUse' x='0' y='0' width='295' height='32'%3e%3cpath fill='#COLOR1#' d='M0 2.9961C0 1.3414 1.33554 0 2.99805 0L285.905 7.15256e-07C287.561 7.15256e-07 289.366 1.25757 289.937 2.80757L295 16.5505L290.007 29.2022C289.397 30.7474 287.567 32 285.905 32H2.99805C1.34227 32 0 30.6657 0 29.0039V2.9961Z'/%3e%3c/mask%3e%3cg mask='url(%23mask0_2_11)'%3e%3cpath fill='#COLOR2#' d='M0 2.9961C0 1.3414 1.33554 0 2.99805 0L285.905 7.15256e-07C287.561 7.15256e-07 289.366 1.25757 289.937 2.80757L295 16.5505L290.007 29.2022C289.397 30.7474 287.567 32 285.905 32H2.99805C1.34227 32 0 30.6657 0 29.0039V2.9961Z'/%3e%3cpath d='M0 30H295V32H0V30Z' fill='#COLOR1#'/%3e%3c/g%3e%3c/svg%3e) 3 10 3 3 fill repeat";
$backgroundImageCss = "url('data:image/svg+xml;charset=UTF-8,%3csvg width=%27295%27 height=%2732%27 viewBox=%270 0 295 32%27 fill=%27none%27 xmlns=%27http://www.w3.org/2000/svg%27%3e%3cmask id=%27mask0_2_11%27 style=%27mask-type:alpha%27 maskUnits=%27userSpaceOnUse%27 x=%270%27 y=%270%27 width=%27295%27 height=%2732%27%3e%3cpath fill=%27#COLOR2#%27 d=%27M0 2.9961C0 1.3414 1.33554 0 2.99805 0L285.905 7.15256e-07C287.561 7.15256e-07 289.366 1.25757 289.937 2.80757L295 16.5505L290.007 29.2022C289.397 30.7474 287.567 32 285.905 32H2.99805C1.34227 32 0 30.6657 0 29.0039V2.9961Z%27/%3e%3c/mask%3e%3cg mask=%27url(%23mask0_2_11)%27%3e%3cpath fill=%27#COLOR2#%27 d=%27M0 2.9961C0 1.3414 1.33554 0 2.99805 0L285.905 7.15256e-07C287.561 7.15256e-07 289.366 1.25757 289.937 2.80757L295 16.5505L290.007 29.2022C289.397 30.7474 287.567 32 285.905 32H2.99805C1.34227 32 0 30.6657 0 29.0039V2.9961Z%27/%3e%3cpath d=%27M0 30H295V32H0V30Z%27 fill=%27#COLOR1#%27/%3e%3c/g%3e%3c/svg%3e') 3 10 3 3 fill repeat";
// $backgroundImageCss = 'url(data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2216px%22%20height%3D%2232px%22%20viewBox%3D%220%200%2016%2032%22%20version%3D%221.1%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20xmlns%3Axlink%3D%22http%3A//www.w3.org/1999/xlink%22%3E%3Cdefs%3E%3Cpath%20d%3D%22M0%2C2.99610022%20C0%2C1.34139976%201.3355407%2C0%202.99805158%2C0%20L6.90478569%2C0%20C8.56056385%2C0%2010.3661199%2C1.25756457%2010.9371378%2C2.80757311%20L16%2C16.5505376%20L11.0069874%2C29.2022189%20C10.3971821%2C30.7473907%208.56729657%2C32%206.90478569%2C32%20L2.99805158%2C32%20C1.34227341%2C32%200%2C30.6657405%200%2C29.0038998%20L0%2C2.99610022%20Z%22%20id%3D%22Bg%22/%3E%3C/defs%3E%3Cg%20id%3D%22Bar%22%20stroke%3D%22none%22%20fill-rule%3D%22evenodd%22%3E%3Cmask%20fill%3D%22white%22%20id%3D%22mask%22%3E%3Cuse%20xlink%3Ahref%3D%22%23Bg%22/%3E%3C/mask%3E%3Cuse%20fill%3D%22#COLOR2#%22%20xlink%3Ahref%3D%22%23Bg%22/%3E%3Cpolygon%20id%3D%22Ln%22%20fill%3D%22#COLOR1#%22%20mask%3D%22url%28%23mask%29%22%20points%3D%220%2030%2016%2030%2016%2032%200%2032%22/%3E%3C/g%3E%3C/svg%3E) 3 10 3 3 fill repeat';

?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.Crm.PartialEditorDialog.messages =
				{
					entityHasInaccessibleFields: "<?= CUtil::JSEscape(Loc::getMessage('CRM_ENTITY_ED_PROG_HAS_INACCESSIBLE_FIELDS')) ?>",
				};

			BX.Crm.EntityDetailProgressStep.backgroundImageCss = "<?=CUtil::JSEscape($backgroundImageCss)?>";
			BX.Crm.EntityDetailProgressStep.defaultBackgroundColor = "<?=CUtil::JSEscape($defaultBackgroundColor)?>";
			BX.Crm.EntityDetailProgressControl.defaultColors =
				{
					process: "<?=Bitrix\Crm\Color\PhaseColorScheme::PROCESS_COLOR?>",
					success: "<?=Bitrix\Crm\Color\PhaseColorScheme::SUCCESS_COLOR?>",
					failure: "<?=Bitrix\Crm\Color\PhaseColorScheme::FAILURE_COLOR?>",
					apology: "<?=Bitrix\Crm\Color\PhaseColorScheme::FAILURE_COLOR?>"
				};

			BX.Crm.EntityDetailProgressControl.create(
				"<?=CUtil::JSEscape($guid)?>",
				{
					entityTypeId: <?=$entityTypeID?>,
					entityId: <?=$entityID?>,
					entityFieldName: "<?=CUtil::JSEscape($arResult['ENTITY_FIELD_NAME'])?>",
					currentStepId: "<?=CUtil::JSEscape($currentStepID)?>",
					currentSemantics: "<?=CUtil::JSEscape($currentSemantics)?>",
					stepInfoTypeId: "<?=CUtil::JSEscape($arResult['STEP_INFO_TYPE_ID'])?>",
					canConvert: <?=$arResult['CAN_CONVERT'] ? 'true' : 'false'?>,
					conversionTypeId: <?=CUtil::PhpToJSObject($arResult['CONVERSION_TYPE_ID'])?>,
					conversionScheme: <?=CUtil::PhpToJSObject($arResult['CONVERSION_SCHEME'])?>,
					readOnly: <?=$arResult['READ_ONLY'] ? 'true' : 'false'?>,
					containerId: "<?=CUtil::JSEscape($containerId)?>",
					serviceUrl: "<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>",
					terminationTitle: "<?=CUtil::JSEscape($arResult['TERMINATION_TITLE'])?>",
					verboseMode: <?=$arResult['VERBOSE_MODE'] ? 'true' : 'false'?>
				}
			);

			BX.Crm.PartialEditorDialog.entityEditorUrls =
				{
					"<?=CCrmOwnerType::DealName?>": "<?='/bitrix/components/bitrix/crm.deal.details/ajax.php?'.bitrix_sessid_get()?>",
					"<?=CCrmOwnerType::LeadName?>": "<?='/bitrix/components/bitrix/crm.lead.details/ajax.php?'.bitrix_sessid_get()?>"
				};
		}
	);
</script>
<div class="crm-entity-section crm-entity-section-status-wrap">
	<div class="crm-entity-section-status-container">
		<div id="<?=htmlspecialcharsbx($containerId)?>" class="crm-entity-section-status-container-flex">
			<?foreach($items as $item)
			{
				$statusID = htmlspecialcharsbx($item['STATUS_ID']);
				$name = htmlspecialcharsbx($item['NAME']);
				$color = htmlspecialcharsbx($item['COLOR']);
				$isPassed = $item['IS_PASSED'];
				$isVisible = $item['IS_VISIBLE'];

				?><div data-id="<?=$statusID?>" class="crm-entity-section-status-step"<?=!$isVisible ? ' style="display:none;"' : ''?>>
				<div class="crm-entity-section-status-step-item"><?
					if($isPassed)
					{
						$stepColor = urlencode($currentColor);
						$stepBackgroundImageCss = str_replace(
							array('#COLOR1#', '#COLOR2#'),
							$stepColor,
							$backgroundImageCss
						);
						?>
						<div data-base-color="<?=$color?>" class="crm-entity-section-status-step-item-text" style="border-image: <?=$stepBackgroundImageCss?>;">
						<?=$name?>
						</div>
					<?}
					else
					{
						$stepColor = urlencode($color);
						$stepBackgroundImageCss = str_replace(
							array('#COLOR1#', '#COLOR2#'),
							array($stepColor, urlencode($defaultBackgroundColor)),
							$backgroundImageCss
						);
						?><div data-base-color="<?=$color?>" class="crm-entity-section-status-step-item-text" style="border-image: <?=$stepBackgroundImageCss?>;">
						<?=$name?>
						</div>
					<?}?>
				</div>
				</div><?
			}?>
		</div>
	</div>
</div>
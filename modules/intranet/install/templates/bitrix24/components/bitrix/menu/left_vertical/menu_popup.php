<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>

<div class="left-menu-popup-wrapper" id="left-menu-preset-popup" style="display: none">
	<form method="POST" name="left-menu-preset-form">
		<!--<div class="left-menu-popup-close" onclick="BX.PopupWindowManager.getCurrentPopup().close();">
			<div class="left-menu-popup-close-item"></div>
		</div>--><!--left-menu-popup-close-->
		<div class="left-menu-popup-header">
			<span class="left-menu-popup-header-item"><?=GetMessage("MENU_PRESET_TITLE")?></span>
		</div><!--left-menu-popup-header-->
		<div class="left-menu-popup-description">
			<span class="left-menu-popup-description-item"><?=GetMessage("MENU_PRESET_DESC")?></span>
		</div><!--left-menu-popup-description-->
		<div class="left-menu-popup-card-container">
			<?if (CModule::IncludeModule("crm") && CCrmPerms::IsAccessEnabled()):?>
			<label class="left-menu-popup-card-item js-left-menu-preset-item<?if ($arResult["CURRENT_PRESET_ID"] == "crm"):?> left-menu-popup-selected<?endif?>" for="presetTypeCrm">
				<div class="left-menu-popup-card-item-title"><?=GetMessage("MENU_PRESET_CRM_TITLE")?></div>
				<div class="left-menu-popup-card-item-icon-box left-menu-popup-icon-crm">
					<div class="left-menu-popup-card-item-icon"></div>
				</div>
				<div class="left-menu-popup-card-item-info"><?=GetMessage("MENU_PRESET_CRM_DESC11")?></div>
				<div class="left-menu-popup-card-item-description"><?=GetMessage("MENU_PRESET_CRM_DESC2")?></div>
				<input type="radio" name="presetType" value="crm" id="presetTypeCrm" <?if ($arResult["CURRENT_PRESET_ID"] == "crm"):?>checked<?endif?> style="display: none">
			</label>
			<?endif?>

			<?
			if (\Bitrix\Main\Loader::includeModule("socialnetwork"))
			{
				$arUserActiveFeatures = CSocNetFeatures::GetActiveFeatures(SONET_ENTITY_USER, $GLOBALS["USER"]->GetID());
				$arSocNetFeaturesSettings = CSocNetAllowed::GetAllowedFeatures();

				$isFeatureAllowed =
					array_key_exists("tasks", $arSocNetFeaturesSettings) &&
					array_key_exists("allowed", $arSocNetFeaturesSettings["tasks"]) &&
					in_array(SONET_ENTITY_USER, $arSocNetFeaturesSettings["tasks"]["allowed"]) &&
					is_array($arUserActiveFeatures) &&
					in_array("tasks", $arUserActiveFeatures)
				;
				if ($isFeatureAllowed):
				?>
				<label class="left-menu-popup-card-item js-left-menu-preset-item<?if ($arResult["CURRENT_PRESET_ID"] == "tasks"):?> left-menu-popup-selected<?endif?>" for="presetTypeTasks">
					<div class="left-menu-popup-card-item-title"><?=GetMessage("MENU_PRESET_TASKS_TITLE1")?></div>
					<div class="left-menu-popup-card-item-icon-box left-menu-popup-icon-task">
						<div class="left-menu-popup-card-item-icon"></div>
					</div>
					<div class="left-menu-popup-card-item-info"><?=GetMessage("MENU_PRESET_TASKS_DESC11")?></div>
					<div class="left-menu-popup-card-item-description"><?=GetMessage("MENU_PRESET_TASKS_DESC2")?></div>
					<input type="radio" name="presetType" value="tasks" id="presetTypeTasks" <?if ($arResult["CURRENT_PRESET_ID"] == "tasks"):?>checked<?endif?> style="display: none">
				</label>
				<?
				endif;
			}
			?>

			<label class="left-menu-popup-card-item js-left-menu-preset-item<?if ($arResult["CURRENT_PRESET_ID"] == "social"):?> left-menu-popup-selected<?endif?>" for="presetTypeSocial" >
				<div class="left-menu-popup-card-item-title"><?=GetMessage("MENU_PRESET_SOCIAL_TITLE1")?></div>
				<div class="left-menu-popup-card-item-icon-box left-menu-popup-icon-communication">
					<div class="left-menu-popup-card-item-icon"></div>
				</div>
				<div class="left-menu-popup-card-item-info"><?=GetMessage("MENU_PRESET_SOCIAL_DESC11")?></div>
				<div class="left-menu-popup-card-item-description"><?=GetMessage("MENU_PRESET_SOCIAL_DESC2")?></div>
				<input type="radio" name="presetType" value="social" id="presetTypeSocial" <?if ($arResult["CURRENT_PRESET_ID"] == "social"):?>checked<?endif?> style="display: none">
			</label>

			<?if (
				\Bitrix\Main\Loader::includeModule("bitrix24") && \Bitrix\Main\ModuleManager::isModuleInstalled("landing")
				&& (
					in_array(\CBitrix24::getPortalZone(), array("ru", "ua", "kz", "by"))
					|| Bitrix\Bitrix24\Release::isAvailable("landing")
				)
			):?>
			<label class="left-menu-popup-card-item js-left-menu-preset-item <?if ($arResult["CURRENT_PRESET_ID"] == "sites"):?> left-menu-popup-selected<?endif?>" for="presetTypeSites">
				<div class="left-menu-popup-card-item-title"><?=GetMessage("MENU_PRESET_SITES_TITLE")?></div>
				<div class="left-menu-popup-card-item-icon-box left-menu-popup-icon-website">
					<div class="left-menu-popup-card-item-icon"></div>
				</div>
				<div class="left-menu-popup-card-item-info"><?=GetMessage("MENU_PRESET_SITES_DESC1")?></div>
				<div class="left-menu-popup-card-item-description"><?=GetMessage("MENU_PRESET_SITES_DESC2")?></div>
				<input type="radio" name="presetType" value="sites" id="presetTypeSites" <?if ($arResult["CURRENT_PRESET_ID"] == "sites"):?>checked<?endif?> style="display: none">
			</label>
			<?endif?>
		</div><!--left-menu-popup-card-container-->
	</form>
	<div class="left-menu-popup-border"></div>
</div><!--left-menu-popup-wrapper-->
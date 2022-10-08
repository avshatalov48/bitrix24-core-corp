<?php if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'clipboard',
]);

$APPLICATION->setTitle(Loc::getMessage('CRM_CONFIG_PLG_TITLE_'.mb_strtoupper($arParams['CMS_ID'])));
?>

<div class="crm-config-external-plugins-wrapper">
	<div class="crm-config-external-plugins-title-main">
		<span class="crm-config-external-plugins-title-main-item"><?= Loc::getMessage('CRM_CONFIG_PLG_TITLE')?></span>
	</div>
	<div class="crm-config-external-plugins-main">
		<div class="crm-config-external-plugins-main-desc">
			<div class="crm-config-external-plugins-main-desc-item"><?= Loc::getMessage('CRM_CONFIG_PLG_DESC')?></div>
			<div class="crm-config-external-plugins-main-desc-item"><?= Loc::getMessage('CRM_CONFIG_PLG_DESC2')?></div>
		</div>
		<div class="crm-config-external-plugins-block crm-config-external-plugins-blue-block">
			<div class="crm-config-external-plugins-content">
				<span class="crm-config-external-plugins-content-text">
					<?= Loc::getMessage('CRM_CONFIG_PLG_STEP1', array(
						'#A1#' => '<a href="'.$arParams['APP_URL'].'" class="crm-config-external-plugins-content-link" target="_blank""">',
						'#A2#' => '</a>'
					))?>
				</span>
			</div>
		</div><!--crm-config-external-plugins-block-->
		<div id="crm-config-external-plugins-linkblock" data-cms="<?= $arParams['CMS_ID']?>" class="crm-config-external-plugins-block crm-config-external-plugins-green-block<?= $arResult['CONNECTOR_URL']=='' ? '' : ' crm-config-external-plugins-button-state'?>">
			<div class="crm-config-external-plugins-content">
				<span class="crm-config-external-plugins-content-text"><?= Loc::getMessage('CRM_CONFIG_PLG_STEP2_1')?></span>
				<div class="crm-config-external-plugins-content-button" id="crm-config-external-plugins-linkbutton">
					<div class="crm-config-external-plugins-content-button-item"><?= Loc::getMessage('CRM_CONFIG_PLG_STEP2_2')?></div>
				</div>
				<div class="crm-config-external-plugins-content-input">
					<div class="crm-config-external-plugins-content-input-inner">
						<input id="crm-config-external-plugins-linkinput" type="text" class="crm-config-external-plugins-content-input-item" value="<?= htmlspecialcharsbx($arResult['CONNECTOR_URL'])?>">
						<span id="crm-config-external-plugins-linkcopy" class="crm-config-external-plugins-content-input-icon"></span>
					</div>
					<div class="crm-config-external-plugins-content-input-link" id="crm-config-external-plugins-linkdelete"><?= Loc::getMessage('CRM_CONFIG_PLG_STEP2_3')?></div>
				</div>
			</div>
			<div class="crm-config-external-plugins-block-desc"><?= Loc::getMessage('CRM_CONFIG_PLG_STEP2_4')?></div>
		</div><!--crm-config-external-plugins-block-->
		<div class="crm-config-external-plugins-block crm-config-external-plugins-grey-block">
			<div class="crm-config-external-plugins-content">
				<span class="crm-config-external-plugins-content-text"><?= Loc::getMessage('CRM_CONFIG_PLG_STEP3')?></span>
				<div class="crm-config-external-plugins-content-img">
					<img src="<?= $this->__folder?>/images/<?= $arParams['CMS_ID']?>_<?= LANGUAGE_ID == 'ru' ? 'ru' : 'en'?>.png" class="crm-config-external-plugins-content-img-item">
				</div>
			</div>
		</div><!--crm-config-external-plugins-block-->
		<div class="crm-config-external-plugins-block crm-config-external-plugins-aqua-block">
			<div class="crm-config-external-plugins-content">
				<div class="crm-config-external-plugins-content-text"><?= Loc::getMessage('CRM_CONFIG_PLG_STEP4')?></div>
				<div class="crm-config-external-plugins-main-desc-item"><?= Loc::getMessage('CRM_CONFIG_PLG_STEP4_2')?></div>
			</div>
		</div><!--crm-config-external-plugins-block-->
	</div><!--crm-config-external-plugins-main-->
</div><!--crm-config-external-plugins-wrapper-->


<script type="text/javascript">
	BX.ready(function(){
		BX.CrmConfigExternalPlugins.create();
	});
</script>

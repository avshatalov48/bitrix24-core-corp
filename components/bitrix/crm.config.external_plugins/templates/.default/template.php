<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;

$request = $arResult['REQUEST'];
$uri = new \Bitrix\Main\Web\Uri($request->getRequestUri());

$APPLICATION->setTitle(Loc::getMessage('CRM_CONFIG_PLG_TITLE'));
?>

<div class="crm-config-external-plugins-desc-container">
	<div class="crm-config-external-plugins-desc-head">
		<div class="crm-config-external-plugins-desc-title">
			<span class="crm-config-external-plugins-desc-title-item"><?= Loc::getMessage('CRM_CONFIG_PLG_TITLE')?></span>
		</div>
		<?/*<div class="crm-config-external-plugins-desc-close-icon">
			<span class="crm-config-external-plugins-desc-close-icon-item"></span>
		</div>*/?>
	</div><!--crm-config-external-plugins-desc-head-->
	<div class="crm-config-external-plugins-desc-main">
		<div class="crm-config-external-plugins-desc-visual-block">
			<span class="crm-config-external-plugins-desc-visual-block-item"></span>
		</div>
		<div class="crm-config-external-plugins-desc-list-block">
			<ul class="crm-config-external-plugins-desc-list">
				<li class="crm-config-external-plugins-desc-list-item"><?= Loc::getMessage('CRM_CONFIG_PLG_DESC1')?></li>
				<li class="crm-config-external-plugins-desc-list-item"><?= Loc::getMessage('CRM_CONFIG_PLG_DESC2')?></li>
				<li class="crm-config-external-plugins-desc-list-item" style="color: #cdcdcd;">
					<?= Loc::getMessage('CRM_CONFIG_PLG_DESC3')?>
					<span style="bottom: 0.8ex;  color: #ade307; font-size: 0.9em;  line-height: 1;  position: relative;   vertical-align: baseline;"><?= Loc::getMessage('CRM_CONFIG_PLG_SOON')?></span>
				</li>
				<li class="crm-config-external-plugins-desc-list-item" style="color: #cdcdcd;">
					<?= Loc::getMessage('CRM_CONFIG_PLG_DESC4')?>
					<span style="bottom: 0.8ex;  color: #ade307; font-size: 0.9em;  line-height: 1;  position: relative;   vertical-align: baseline;"><?= Loc::getMessage('CRM_CONFIG_PLG_SOON')?></span>
				</li>
			</ul>
		</div>
	</div><!--crm-config-external-plugins-desc-main-->
</div><!--crm-config-external-plugins-desc-container-->

<div class="crm-config-external-plugins-platform-container">
	<div class="crm-config-external-plugins-platform-title">
		<span class="crm-config-external-plugins-platform-title-item"><?= Loc::getMessage('CRM_CONFIG_PLG_SELECT_CMS')?></span>
	</div>
	<div class="crm-config-external-plugins-platform">
		<?/*if ($arResult['B24_LANG'] == 'ru'):?>
		<div class="crm-config-external-plugins-platform-item icon-1c-bitrix">
			<a href="<?= $uri->addParams(array('cms' => '1cbitrix'))->getUri()?>" class="crm-config-external-plugins-platform-icon"></a>
		</div>
		<?endif;*/?>
		<div class="crm-config-external-plugins-platform-item icon-drupal">
			<a href="<?= $uri->addParams(array('cms' => 'drupal7'))->getUri()?>" class="crm-config-external-plugins-platform-icon"></a>
		</div>
		<div class="crm-config-external-plugins-platform-item icon-joomla">
			<a href="<?= $uri->addParams(array('cms' => 'joomla'))->getUri()?>" class="crm-config-external-plugins-platform-icon"></a>
		</div>
		<div class="crm-config-external-plugins-platform-item icon-wp">
			<a href="<?= $uri->addParams(array('cms' => 'wordpress'))->getUri()?>" class="crm-config-external-plugins-platform-icon"></a>
		</div>
		<div class="crm-config-external-plugins-platform-item icon-magento">
			<span data-href="<?= $uri->addParams(array('cms' => 'magento2'))->getUri()?>" class="crm-config-external-plugins-platform-icon" style="opacity: 0.1"></span>
			<span style="color: #ade307; font-size: 1em; line-height: 1; position: absolute;">&nbsp;&nbsp;<?= Loc::getMessage('CRM_CONFIG_PLG_SOON')?></span>
		</div>
	</div>
</div><!--crm-config-external-plugins-platform-container-->
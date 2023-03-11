<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
use Bitrix\Main\Localization\Loc;

if (empty($arResult['INFO_CONNECTION']))
{
	$arResult['INFO_CONNECTION'] = [
		'ID' => '',
		'URL' => '',
		'NAME' => '',
		'ESHOP_URL' => '',
	];
}

?>
<div class="imconnector-field-container">
	<div class="imconnector-field-section">
		<div class="imconnector-field-main-title">
			<?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_INFO')?>
		</div>
		<div class="imconnector-field-box">
			<div class="imconnector-field-box-entity-row">
				<div class="imconnector-field-box-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_BOT_NAME')?>
				</div>
				<div class="imconnector-field-box-entity-text-bold">
					<?=htmlspecialcharsbx($arResult['INFO_CONNECTION']['NAME'])?>
				</div>
			</div>
			<div class="imconnector-field-box-entity-row">
				<div class="imconnector-field-box-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_BOT_LINK')?>
				</div>
				<a href="<?=htmlspecialcharsbx($arResult['INFO_CONNECTION']['URL'])?>"
				   class="imconnector-field-box-entity-link"
				   target="_blank">
					<?=htmlspecialcharsbx($arResult['INFO_CONNECTION']['URL'])?>
				</a>
				<span class="imconnector-field-box-entity-icon-copy-to-clipboard"
					  data-text="<?=htmlspecialcharsbx(CUtil::JSEscape($arResult['INFO_CONNECTION']['URL']))?>"></span>
			</div>
			<?php if (!empty($arResult['INFO_CONNECTION']['ESHOP_URL'])):?>
			<div class="imconnector-field-box-entity-row">
				<div class="imconnector-field-box-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_TELEGRAMBOT_ESHOP_LINK')?>
				</div>
				<a href="<?=htmlspecialcharsbx($arResult['INFO_CONNECTION']['ESHOP_URL'])?>"
				   class="imconnector-field-box-entity-link"
				   target="_blank">
					<?=htmlspecialcharsbx($arResult['INFO_CONNECTION']['ESHOP_URL'])?>
				</a>
				<span class="imconnector-field-box-entity-icon-copy-to-clipboard"
				      data-text="<?=htmlspecialcharsbx(CUtil::JSEscape($arResult['INFO_CONNECTION']['ESHOP_URL']))?>"></span>
			</div>
			<?php endif?>
		</div>
	</div>
</div>

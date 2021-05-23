<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}
use \Bitrix\Main\Localization\Loc;
?>
<div class="imconnector-field-container">
	<div class="imconnector-field-section">
		<div class="imconnector-field-main-title">
			<?=Loc::getMessage('IMCONNECTOR_COMPONENT_OLX_INFO')?>
		</div>
		<div class="imconnector-field-box">
			<div class="imconnector-field-box-entity-row">
				<div class="imconnector-field-box-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_OLX_USER_ID')?>
				</div>
					<?=htmlspecialcharsbx($arResult['FORM']['INFO_CONNECTION']['id'])?>
			</div>
			<div class="imconnector-field-box-entity-row">
				<div class="imconnector-field-box-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_OLX_USER_EMAIL')?>
				</div>
				<?=htmlspecialcharsbx($arResult['FORM']['INFO_CONNECTION']['email'])?>
			</div>
			<div class="imconnector-field-box-entity-row">
				<div class="imconnector-field-box-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_OLX_USER_DOMAIN')?>
				</div>
				<a href="<?=htmlspecialcharsbx($arResult['FORM']['INFO_CONNECTION']['zone'])?>">
					<?=htmlspecialcharsbx($arResult['FORM']['INFO_CONNECTION']['zone'])?>
				</a>

			</div>
		</div>
	</div>
</div>

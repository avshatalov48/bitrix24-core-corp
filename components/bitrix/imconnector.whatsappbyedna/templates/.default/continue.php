<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;
?>

<div class="imconnector-field-container">
	<div class="imconnector-field-section imconnector-field-section-social">
		<div class="imconnector-field-box">
			<div class="connector-icon ui-icon ui-icon-service-<?=$iconCode?>"><i></i></div>
		</div>
		<div class="imconnector-field-box">
			<?php if (empty($arResult['STATUS'])): ?>
				<div class="imconnector-field-main-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_CONNECT_COMMON_TITLE' . $arResult['LOC_REGION_POSTFIX'])?>
				</div>
				<div class="imconnector-field-box-content">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_CONNECT_INSTRUCTION' . $arResult['LOC_REGION_POSTFIX'], [
						'#LINK_START#' => '<a class="imconnector-field-box-link" id="imconnector-whatsappbyedna-link-help">',
						'#LINK_END#' => '</a>',
					])?>
				</div>
			<?php else: ?>
				<?php /* Edna WhatsApp connected */ ?>
				<div class="imconnector-field-main-subtitle">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_CONNECTED' . $arResult['LOC_REGION_POSTFIX'])?>
				</div>
				<div class="imconnector-field-box-content">
					<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_FINAL_FORM_DESCRIPTION_MSGVER_1')?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php include 'messages.php'; ?>
	<?php include 'form.php'; ?>

</div>
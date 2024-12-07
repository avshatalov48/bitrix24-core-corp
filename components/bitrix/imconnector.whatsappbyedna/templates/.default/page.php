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
				<?php if (!empty($arResult['STATUS'])): ?>
					<?php /* Edna WhatsApp connected */ ?>
					<?php include 'page-connected.php'; ?>
				<?php else: ?>
					<div class="imconnector-field-main-subtitle">
						<?=Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_INDEX_TITLE')?>
					</div>
					<?php if (!empty($arResult['ACTIVE_STATUS'])): ?>
						<?php /* Setup not finished */ ?>
						<?php include 'page-not-finished.php'; ?>
					<?php else: ?>
						<?php /* Setup not started */ ?>
						<?php include 'start.php'; ?>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>
	</div>
<?php include 'messages.php'; ?>

<?php if (!$arResult['STATUS']): ?>
	<div class="imconnector-field-container">
		<div class="imconnector-field-section">
			<?php include 'connection-help.php'; ?>
		</div>
	</div>
<?php endif; ?>
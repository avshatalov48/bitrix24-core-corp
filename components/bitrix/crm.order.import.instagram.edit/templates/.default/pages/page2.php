<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 *  @var array $arParams
 *  @var array $arResult
 *  @var CBitrixComponent $component
 *  @var CBitrixComponentTemplate $this
 *  @var string $templateName
 *  @var string $templateFolder
 *  @var string $componentPath
 */
?>
	<div class="crm-order-instagram-edit-block">
		<div class="crm-order-instagram-edit-header">
			<span class="crm-order-instagram-edit-header-logo"></span>
			<div class="crm-order-instagram-edit-content-inner">
				<div class="crm-order-instagram-edit-title">
					<?=$component->getLocalizationMessage('CRM_OIIE_INSTAGRAM_TITLE')?>
				</div>
				<div class="crm-order-instagram-edit-desc">
					<span class="crm-order-instagram-edit-decs-text">
						<?=$component->getLocalizationMessage('CRM_OIIE_DESCRIPTION')?>
					</span>
				</div>
			</div>
		</div>
	</div>
<? include 'messages.php'; ?>
<?
if ($arResult['ACTIVE_STATUS'])
{
	?>
	<div class="crm-order-instagram-edit-block">
		<div class="crm-order-instagram-edit-content-inner">
			<div class="crm-order-instagram-edit-section">
				<?=$component->getLocalizationMessage('CRM_OIIE_AUTHORIZATION')?>
			</div>
			<div class="crm-order-instagram-edit-desc">
				<span class="crm-order-instagram-edit-decs-text">
					<?=$component->getLocalizationMessage('CRM_OIIE_LOG_IN_UNDER_AN_ADMINISTRATOR_ACCOUNT_PAGE')?>
				</span>
			</div>
		</div>
		<?
		if ($arResult['FORM']['USER']['URI'] != '')
		{
			?>
			<div class="crm-order-instagram-edit-btn">
				<button class="ui-btn ui-btn-primary"
						onclick="BX.util.popup('<?=$arResult['FORM']['USER']['URI']?>', 700, 525)">
					<?=$component->getLocalizationMessage('CRM_OIIE_AUTHORIZE')?>
				</button>
				<button class="ui-btn ui-btn-light-border show-preloader-button"
						data-entity="create-store-link"
						style="display: none;">
					<?=$component->getLocalizationMessage('CRM_OIIE_CREATE_WITHOUT_CONNECTION')?>
				</button>
			</div>
			<?
		}
		?>
	</div>
	<?
}
?>
<div class="ui-alert ui-alert-success ui-alert-icon-warning ui-alert-success-instagram-app">
	<span class="ui-alert-message">
		<?=$component->getLocalizationMessage("CRM_OIIE_RTFM_NOTE", [
			"#LINK_START#" => '<span class="instagram-app-link" onclick="showInstagramHelp(event);">',
			"#LINK_END#" => '</span>',
		])?>
	</span>
</div>
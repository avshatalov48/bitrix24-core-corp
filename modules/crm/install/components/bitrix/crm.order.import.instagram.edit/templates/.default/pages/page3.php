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
	<div class="crm-order-instagram-edit-content-inner">
		<div class="crm-order-instagram-edit-section">
			<?=$component->getLocalizationMessage('CRM_OIIE_SELECT_THE_PAGE')?>
		</div>
		<?
		foreach ($arResult['FORM']['PAGES'] as $page)
		{
			if (empty($page['ACTIVE']))
			{
				$logo = !empty($page['INFO']['INSTAGRAM']['PROFILE_PICTURE_URL'])
					? " style='background-image: url(\"".$page["INFO"]["INSTAGRAM"]["PROFILE_PICTURE_URL"]."\")'"
					: '';
				?>
				<div class="crm-order-instagram-edit-connect">
					<? if (empty($page['INFO']['INSTAGRAM']['USERNAME'])): ?>
					<span class="crm-order-instagram-edit-user">
					<? else: ?>
					<a href="https://instagram.com/<?=$page['INFO']['INSTAGRAM']['USERNAME']?>/"
							target="_blank"
							class="crm-order-instagram-edit-user">
					<? endif; ?>
						<span class="crm-order-instagram-edit-user-img"<?=$logo?>></span>
						<span class="crm-order-instagram-edit-user-name">
							<?=$page['INFO']['INSTAGRAM']['NAME']?>
						</span>
					<? if (empty($page['INFO']['INSTAGRAM']['USERNAME'])): ?>
					</span>
					<? else: ?>
					</a>
					<? endif; ?>
					<form action="<?=$arResult["URL"]["SIMPLE_FORM"]?>" method="post">
						<input type="hidden"
								name="<?=$arResult["CONNECTOR"]?>_form"
								value="true">
						<input type="hidden" name="page_id" value="<?=$page["INFO"]["ID"]?>">
						<?=bitrix_sessid_post();?>
						<button type="submit"
								name="<?=$arResult["CONNECTOR"]?>_authorization_page"
								class="ui-btn ui-btn-sm ui-btn-primary"
								value="<?=$component->getLocalizationMessage('CRM_OIIE_SETTINGS_TO_CONNECT')?>">
							<?=$component->getLocalizationMessage('CRM_OIIE_SETTINGS_TO_CONNECT')?>
						</button>
					</form>
				</div>
			<?
			}
		}
		?>
	</div>
</div>
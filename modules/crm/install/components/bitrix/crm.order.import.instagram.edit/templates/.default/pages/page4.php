<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;

/**
 *  @var array $arParams
 *  @var array $arResult
 *  @var CBitrixComponent $component
 *  @var CBitrixComponentTemplate $this
 *  @var string $templateName
 *  @var string $templateFolder
 *  @var string $componentPath
 */

$logo = !empty($arResult['FORM']['PAGE']['INSTAGRAM']['PROFILE_PICTURE_URL'])
	? " style='background-image: url(\"".$arResult['FORM']['PAGE']['INSTAGRAM']['PROFILE_PICTURE_URL']."\")'"
	: '';
?>

<div class="crm-order-instagram-edit-block">
	<div class="crm-order-instagram-edit-content-inner">
		<div class="crm-order-instagram-edit-section">
			<?=$component->getLocalizationMessage('CRM_OIIE_INSTAGRAM_CONNECTED_ACCOUNT')?>
		</div>
		<div class="crm-order-instagram-edit-connect">
			<? if (empty($arResult['FORM']['PAGE']['INSTAGRAM']['USERNAME'])): ?>
			<span class="crm-order-instagram-edit-user">
			<? else: ?>
			<a href="https://instagram.com/<?=$arResult['FORM']['PAGE']['INSTAGRAM']['USERNAME']?>/"
					target="_blank"
					class="crm-order-instagram-edit-user">
			<? endif; ?>
				<span class="crm-order-instagram-edit-user-img"<?=$logo?>></span>
				<span class="crm-order-instagram-edit-user-name">
					<?=$arResult['FORM']['PAGE']['INSTAGRAM']['NAME']?>
				</span>
			<? if (empty($arResult['FORM']['PAGE']['INSTAGRAM']['USERNAME'])): ?>
			</span>
			<? else: ?>
			</a>
			<? endif; ?>
			<form action="<?=$arResult["URL"]["SIMPLE_FORM"]?>"
					method="post"
					id="delete_page_<?=$arResult["CONNECTOR"]?>">
				<input type="hidden" name="<?=$arResult["CONNECTOR"]?>_form" value="true">
				<input type="hidden" name="page_id" value="<?=$arResult["FORM"]["PAGE"]["ID"]?>">
				<input type="hidden"
						name="<?=$arResult["CONNECTOR"]?>_del_page"
						value="<?=$component->getLocalizationMessage('CRM_OIIE_DEL_REFERENCE')?>">
				<?=bitrix_sessid_post();?>
			</form>
			<button class="ui-btn ui-btn-sm ui-btn-light-border"
					onclick="popupShowDisconnectPage(<?=CUtil::PhpToJSObject($arResult["CONNECTOR"])?>)">
				<?=$component->getLocalizationMessage('CRM_OIIE_DEL_REFERENCE')?>
			</button>
		</div>
	</div>
	<?
	if (count($arResult['FORM']['PAGES']) > 1)
	{
		?>
		<div class="crm-order-instagram-edit-content-inner">
			<div class="crm-order-instagram-edit-dropdown-button" id="toggle-list">
				<?=$component->getLocalizationMessage('CRM_OIIE_INSTAGRAM_OTHER_ACCOUNTS')?>
			</div>
			<div class="crm-order-instagram-edit-box-hidden" id="hidden-list" style="display: none;">
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
							<form action="<?=$arResult["URL"]["SIMPLE_FORM"]?>"
									method="post"
									id="change_page_<?=$arResult["CONNECTOR"]?>">
								<input type="hidden" name="<?=$arResult["CONNECTOR"]?>_form"
										value="true">
								<input type="hidden" name="page_id"
										value="<?=$page["INFO"]["ID"]?>">
								<input type="hidden" name="<?=$arResult["CONNECTOR"]?>_authorization_page"
										value="<?=$component->getLocalizationMessage('CRM_OIIE_SETTINGS_TO_CONNECT')?>">
								<?=bitrix_sessid_post();?>
							</form>
							<button class="ui-btn ui-btn-sm ui-btn-light-border"
									onclick="popupShowChangePage(<?=CUtil::PhpToJSObject($arResult["CONNECTOR"])?>)">
								<?=$component->getLocalizationMessage('CRM_OIIE_SETTINGS_TO_CONNECT')?>
							</button>
						</div>
						<?
					}
				}
				?>
			</div>
		</div>
		<?
	}
	?>
</div>
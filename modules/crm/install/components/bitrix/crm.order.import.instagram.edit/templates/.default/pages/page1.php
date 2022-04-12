<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 * @var CBitrixComponentTemplate $this
 * @var string $templateName
 * @var string $templateFolder
 * @var string $componentPath
 */
?>
<div class="crm-order-instagram-edit-block">
	<div class="crm-order-instagram-edit-header">
		<span class="crm-order-instagram-edit-header-logo"></span>
		<div class="crm-order-instagram-edit-content-inner">
			<div class="crm-order-instagram-edit-title"><?=$component->getLocalizationMessage('CRM_OIIE_INSTAGRAM_CONNECTED')?></div>
			<div class="crm-order-instagram-edit-desc">
				<span class="crm-order-instagram-edit-decs-text">
					<?=$component->getLocalizationMessage('CRM_OIIE_CHANGE_ANY_TIME')?>
				</span>
			</div>
			<div class="crm-order-instagram-edit-btn">
				<a href="<?=$arResult["URL"]["SIMPLE_FORM"]?>"
						data-slider-ignore-autobinding="true"
						class="ui-btn ui-btn-primary show-preloader-button">
					<?=$component->getLocalizationMessage('CRM_OIIE_SETTINGS_CHANGE_SETTING')?>
				</a>
				<button class="ui-btn ui-btn-light-border"
						onclick="popupShowDisconnectImport(<?=CUtil::PhpToJSObject($arResult["CONNECTOR"])?>)">
					<?=$component->getLocalizationMessage('CRM_OIIE_SETTINGS_DISABLE')?>
				</button>
			</div>
		</div>
	</div>
</div>
<?
include 'messages.php';

if (!empty($arResult["FORM"]["USER"]["INFO"]))
{
	?>
	<div class="crm-order-instagram-edit-block">
		<div class="crm-order-instagram-edit-content-inner">
			<div class="crm-order-instagram-edit-section"><?=$component->getLocalizationMessage('CRM_OIIE_INFO')?></div>
			<div class="crm-order-instagram-edit-desc">
				<?
				if (!empty($arResult["FORM"]["USER"]["INFO"]["URL"]))
				{
					?>
					<span class="crm-order-instagram-edit-decs-text">
						<?=$component->getLocalizationMessage('CRM_OIIE_FACEBOOK_CONNECTED_ACCOUNT')?> -
						<a href="<?=$arResult["FORM"]["USER"]["INFO"]["URL"]?>"
								target="_blank"
								class="crm-order-instagram-edit-decs-link">
							<?=$arResult["FORM"]["USER"]["INFO"]["NAME"]?>
						</a>
					</span>
					<?
				}

				if (!empty($arResult["FORM"]["PAGE"]["URL"]))
				{
					?>
					<span class="crm-order-instagram-edit-decs-text">
						<?=$component->getLocalizationMessage('CRM_OIIE_FACEBOOK_CONNECTED_PAGE')?> -
						<a href="<?=$arResult["FORM"]["PAGE"]["URL"]?>"
								target="_blank"
								class="crm-order-instagram-edit-decs-link">
							<?=$arResult["FORM"]["PAGE"]["NAME"]?>
						</a>
					</span>
					<?
				}

				if (!empty($arResult["FORM"]["PAGE"]['INSTAGRAM']['USERNAME']))
				{
					?>
					<span class="crm-order-instagram-edit-decs-text">
						<?=$component->getLocalizationMessage('CRM_OIIE_INSTAGRAM_CONNECTED_ACCOUNT')?> -
						<a href="https://instagram.com/<?=$arResult["FORM"]["PAGE"]['INSTAGRAM']['USERNAME']?>/"
								target="_blank"
								class="crm-order-instagram-edit-decs-link">
							<?=$arResult["FORM"]["PAGE"]['INSTAGRAM']['NAME']?>
						</a>
					</span>
					<?
				}
				?>
			</div>
		</div>
	</div>
	<?
}
<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
/** @var $this \CBitrixComponentTemplate */
/** @var CMain $APPLICATION */
/** @var array $arResult*/
/** @var array $arParams*/

include('toolbar.php');
?>

<div class="crm-features-list">
<?php foreach ($arResult['features'] as $category):?>
	<h2><?=htmlspecialcharsbx($category['name'])?></h2>

	<?php foreach ($category['items'] as $feature):?>
	<div class="crm-features-list-item">
		<div class="crm-features-list-item-value">
			<span data-role="feature-switcher" data-id="<?=htmlspecialcharsbx($feature['id'])?>" data-checked="<?php if ($feature['enabled']):?>Y<?php endif?>"></span>
		</div>
		<div class="crm-features-list-item-name">
			<div>
				<?=htmlspecialcharsbx($feature['name'])?>
				<?php if($feature['secretLink'] ?? null):?>
				<span class="crm-features-list-item-copy" data-url="<?=htmlspecialcharsbx($feature['secretLink'])?>" title="<?=htmlspecialcharsbx(\Bitrix\Main\Localization\Loc::getMessage('CRM_FEATURE_LIST_COPY_LINK'))?>"></span>
				<?php endif?>
			</div>
			<div class="crm-features-list-item-id"><?=htmlspecialcharsbx($feature['id'])?></div>

		</div>
	</div>
	<?php endforeach?>
<?php endforeach?>
</div>

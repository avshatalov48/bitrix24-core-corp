<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load([
	"ui.switcher",
	"ui.notification"
]);

/** @var array $arResult */
?>
<div class="crm-features-list">
<?php foreach ($arResult['items'] as $category):?>
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

<script>
	BX.ready(() => {
		BX.message(<?=\Bitrix\Main\Web\Json::encode([
			'crmFeatureListCopiedToClipboard' => \Bitrix\Main\Localization\Loc::getMessage('CRM_FEATURE_LIST_LINK_COPIED')
		])?>)
	})
</script>

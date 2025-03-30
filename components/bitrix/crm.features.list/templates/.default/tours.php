<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
/** @var $this \CBitrixComponentTemplate */
/** @var CMain $APPLICATION */
/** @var array $arResult*/
/** @var array $arParams*/

$APPLICATION->SetTitle( \Bitrix\Main\Localization\Loc::getMessage('CRM_FEATURE_PAGE_TITLE'));
include('toolbar.php');
?>

<div class="crm-features-list-item --tour-switcher">
	<div class="crm-features-list-item-value">
		<span data-role="tour-switcher" data-checked="<?php if ($arResult['toursEnabled']):?>Y<?php endif?>"></span>
	</div>
	<div class="crm-features-list-item-name">
		<div>
			<?=\Bitrix\Main\Localization\Loc::getMessage('CRM_FEATURE_LIST_SHOW_TOURS');?>
		</div>
	</div>
</div>

<div class="crm-features-list">
	<?php foreach ($arResult['tours'] as $category):?>
		<h2><?=htmlspecialcharsbx($category['name'])?></h2>

		<?php foreach ($category['items'] as $tour):?>
			<div class="crm-features-list-item --tour">
				<div class="crm-features-list-item-name">
					<div>
						<?=$tour['name']?>
						<?php if($tour['secretLink'] ?? null):?>
							<span class="crm-features-list-item-copy" data-url="<?=htmlspecialcharsbx($tour['secretLink'])?>" title="<?=htmlspecialcharsbx(\Bitrix\Main\Localization\Loc::getMessage('CRM_TOUR_LIST_COPY_LINK'))?>"></span>
						<?php endif?>
					</div>
					<div class="crm-features-list-item-description"><?=$tour['description']?></div>
					<div class="crm-features-list-item-id"><?=htmlspecialcharsbx($tour['id'])?></div>
				</div>
				<div class="crm-features-list-item-action">
					<span class="ui-btn ui-btn-sm ui-btn-secondary-light" data-role="tour-reset" data-tour-id="<?=htmlspecialcharsbx($tour['id'])?>">Сбросить просмотры</span>
				</div>
			</div>
		<?php endforeach?>
	<?php endforeach?>
</div>

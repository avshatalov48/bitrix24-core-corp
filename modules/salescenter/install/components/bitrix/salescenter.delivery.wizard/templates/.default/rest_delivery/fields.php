<?php
/**
 * @var array $arParams
 * @var array $arResult
 * @var CMain $APPLICATION
 * @var CUser $USER
 * @var SaleOrderAjax $component
 * @var string $templateFolder
 */

use Bitrix\Main\Localization\Loc;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
?>

<div class="salescenter-delivery-install-section-no-bottom-margin">
	<div class="salescenter-delivery-install-content-block">
		<?php if(is_array($arResult['serviceConfig'])):?>
			<?php foreach($arResult['serviceConfig'] as $sectionKey => $configSection):?>
				<?php $title = $configSection['TITLE'] ?? Loc::getMessage('DELIVERY_SERVICE_SETTINGS'); ?>
				<h2 class="sales-center-delivery-install-title"><?=$title?></h2>
				<?php if(isset($configSection["ITEMS"]) && is_array($configSection["ITEMS"])):?>
					<?php foreach($configSection["ITEMS"] as $name => $params):?>
						<?php if(isset($params['HIDDEN']) && $params['HIDDEN'] === true):?>
							<?=\Bitrix\Sale\Internals\Input\Manager::getEditHtml("CONFIG[".$sectionKey."][".$name."]", $params)?>
						<?php else:?>
							<label class="ui-ctl-label-text">
								<?=htmlspecialcharsbx($params["NAME"])?>
							</label>
							<div class="ui-ctl ui-ctl-textbox ui-ctl-w75 ui-ctl-element salescenter-delivery-install-input" style="margin-bottom: 17px;">
								<?=\Bitrix\Sale\Internals\Input\Manager::getEditHtml("CONFIG[".$sectionKey."][".$name."]", $params)?>
							</div>
						<?php endif;?>
					<?php endforeach;?>
				<?php endif;?>
			<?php endforeach;?>
		<?php endif;?>
	</div>
</div>


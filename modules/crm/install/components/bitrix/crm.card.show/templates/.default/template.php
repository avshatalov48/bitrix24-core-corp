<?php

use Bitrix\Main\Web\Uri;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/common.js');
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/progress_control.js');

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
]);

\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/crm.css');
?>

<input type="hidden" value="<?=htmlspecialcharsbx($arResult['ENTITY']['VK_PROFILE'])?>" data-role="crm-card-vk-profile">

<?if ($arResult['SIMPLE']):?>
	<div class="crm-card-show-main">
		<div class="crm-card-show-user">
			<? if(isset($arResult['ENTITY']['PHOTO_URL'])): ?>
				<div class="crm-card-show-user-item" style="background-image: url(<?=$arResult['ENTITY']['PHOTO_URL']?>)"></div>
			<?else: ?>
				<div class="crm-card-show-user-item"></div>
			<?endif?>
		</div><!--crm-card-show-user-->
		<div class="crm-card-show-user-name">
			<div class="crm-card-show-user-name-item">
				<? if($arResult['ENTITY']['SHOW_URL']): ?>
					<a class="crm-card-show-user-name-link" href="<?=htmlspecialcharsbx($arResult['ENTITY']['SHOW_URL'])?>" target="_blank" data-use-slider="<?= ($arResult['SLIDER_ENABLED'] ? 'Y' : 'N')?>">
						<?=htmlspecialcharsbx($arResult['ENTITY']['FORMATTED_NAME'])?>
					</a>
				<? else: ?>
					<span class="crm-card-show-user-name-link">
						<?=htmlspecialcharsbx($arResult['ENTITY']['FORMATTED_NAME'])?>
					</span>
				<? endif ?>
			</div>
			<?if($arResult['ENTITY']['POST']):?>
				<div class="crm-card-show-user-name-desc"><?=htmlspecialcharsbx($arResult['ENTITY']['POST'])?></div>
			<?endif?>
			<?if($arResult['ENTITY']['COMPANY_TITLE']):?>
				<div class="crm-card-show-user-name-desc"><?=htmlspecialcharsbx($arResult['ENTITY']['COMPANY_TITLE'])?></div>
			<?endif?>
		</div><!--crm-card-show-user-name-->
		<? if($arResult['ENTITY']['RESPONSIBLE']): ?>
			<div class="crm-card-show-user-responsible">
				<div class="crm-card-show-user-responsible-title"><?= GetMessage('CRM_CARD_RESPONSIBLE')?>:</div>
				<div class="crm-card-show-user-responsible-user">
					<? if($arResult['ENTITY']['RESPONSIBLE']['PHOTO'] != ''): ?>
						<div class="ui-icon ui-icon-common-user crm-card-show-user-responsible-user-icon"><i style="background-image: url(<?= Uri::urnEncode($arResult['ENTITY']['RESPONSIBLE']['PHOTO'])?>)"></i></div>
					<? else: ?>
						<div class="ui-icon ui-icon-common-user crm-card-show-user-responsible-user-icon"><i></i></div>
					<? endif ?>
					<div class="crm-card-show-user-responsible-user-info">
						<a class="crm-card-show-user-responsible-user-name" href="<?=$arResult['ENTITY']['RESPONSIBLE']['PROFILE_PATH']?>" target="_blank">
							<?= htmlspecialcharsbx($arResult['ENTITY']['RESPONSIBLE']['NAME'])?>
						</a>
						<div class="crm-card-show-user-responsible-user-info-position">
							<?= htmlspecialcharsbx($arResult['ENTITY']['RESPONSIBLE']['POST'])?>
						</div>
					</div>
				</div>
			</div>
		<? endif ?>
		<div class="crm-card-show-user-settings">
			<div class="crm-card-show-user-settings-item"></div>
		</div><!--crm-card-show-user-settings-->
	</div><!--crm-card-show-main-->
<?else:?>
	<div id="crm-card-detail-container" class="crm-card-show-detail crm-card-custom-scroll">
		<div class="crm-card-show-detail-header">
			<div class="crm-card-show-detail-header-user">
				<div id="crm-card-user-photo" class="crm-card-show-detail-header-user-image">
					<? if(isset($arResult['ENTITY']['PHOTO_URL'])): ?>
						<div class="crm-card-show-detail-header-user-image-item" style="background-image: url('<?=$arResult['ENTITY']['PHOTO_URL']?>')"></div>
					<? else: ?>
						<div class="crm-card-show-detail-header-user-image-item"></div>
					<? endif ?>
				</div>
				<div class="crm-card-show-detail-header-user-info">
					<a href="<?=htmlspecialcharsbx($arResult['ENTITY']['SHOW_URL'])?>" target="_blank" data-use-slider="<?= ($arResult['SLIDER_ENABLED'] ? 'Y' : 'N')?>">
						<div class="crm-card-show-detail-header-user-name">
							<?=htmlspecialcharsbx($arResult['ENTITY']['FORMATTED_NAME'])?>
						</div>
					</a>
					<?if($arResult['ENTITY']['POST']):?>
						<div class="crm-card-show-detail-header-user-item"><?=htmlspecialcharsbx($arResult['ENTITY']['POST'])?></div>
					<?endif?>
					<?if($arResult['ENTITY']['COMPANY_TITLE']):?>
						<div class="crm-card-show-detail-header-user-item"><?=htmlspecialcharsbx($arResult['ENTITY']['COMPANY_TITLE'])?></div>
					<?endif?>
				</div>
			</div><!--crm-card-show-detail-header-user-->
			<div class="crm-card-show-detail-header-user-status">
				<div class="crm-card-show-detail-header-user-status-item"><?/*=GetMessage('CRM_CARD_CONSTANT_CLIENT')*/?></div>
			</div><!--crm-card-show-detail-header-user-status-->
		</div><!--crm-card-show-detail-header-->
		<div class="crm-card-show-detail-info">
			<div class="crm-card-show-detail-info-inner">
				<div id="crm-card-extended-info" class="crm-card-show-detail-info-content">
					<? if(is_array($arResult['ENTITY']['ACTIVITIES']) && count($arResult['ENTITY']['ACTIVITIES']) > 0): ?>
						<div class="crm-card-show-detail-info-wrap">
							<div class="crm-card-show-detail-info-title crm-card-show-title-main">
								<div class="crm-card-show-detail-info-title-item">
									<a href="<?=htmlspecialcharsbx($arResult['ENTITY']['ACTIVITY_LIST_URL'])?>" target="_blank">
										<?=GetMessage('CRM_CARD_ACTIVITIES')?>
									</a>
								</div>
							</div>
							<? foreach ($arResult['ENTITY']['ACTIVITIES'] as $activity): ?>
								<div class="crm-card-show-detail-info-block">
									<div class="crm-card-show-detail-info-name">
										<div class="crm-card-show-detail-info-name-item">
											<a href="<?=htmlspecialcharsbx($activity['SHOW_URL'])?>" target="_blank" data-use-slider="Y">
												<?=htmlspecialcharsbx($activity['SUBJECT'])?>
											</a>
										</div>
									</div>
									<div class="crm-card-show-detail-info-desc">
										<div class="crm-card-show-detail-info-desc-item"><?=htmlspecialcharsbx($activity['DEADLINE'])?></div>
									</div>
								</div><!--crm-card-show-detail-info-block-->
							<? endforeach ?>
						</div>
					<? endif ?>

					<? if(is_array($arResult['ENTITY']['DEALS']) && count($arResult['ENTITY']['DEALS']) > 0): ?>
						<div class="crm-card-show-detail-info-wrap">
							<div class="crm-card-show-detail-info-title crm-card-show-title-main">
								<div class="crm-card-show-detail-info-title-item">
									<a href="<?=htmlspecialcharsbx($arResult['ENTITY']['DEAL_LIST_URL'])?>" target="_blank"><?=GetMessage('CRM_CARD_DEALS')?></a>
								</div>
							</div>
							<? foreach ($arResult['ENTITY']['DEALS'] as $deal): ?>
								<div class="crm-card-show-detail-info-main-inner">
									<div class="crm-card-show-detail-info-main-content">
										<div class="crm-card-show-detail-info-block">
											<div class="crm-card-show-detail-info-name">
												<div class="crm-card-show-detail-info-name-item">
													<a href="<?=htmlspecialcharsbx($deal['SHOW_URL'])?>" target="_blank" data-use-slider="<?= ($arResult['SLIDER_ENABLED'] ? 'Y' : 'N')?>">
														<?=htmlspecialcharsbx($deal['TITLE'])?>
													</a>
												</div>
											</div>
											<div class="crm-card-show-detail-info-desc">
												<div class="crm-card-show-detail-info-desc-item"><?=$deal['FORMATTED_OPPORTUNITY']?></div>
											</div>
										</div>
									</div><!--crm-card-show-detail-info-main-content-->
									<div class="crm-card-show-detail-info-main-status">
										<?= CCrmViewHelper::RenderDealStageControl(
											array(
												'ENTITY_ID' => $deal['ID'],
												'CURRENT_ID' => $deal['STAGE_ID'],
												'CATEGORY_ID' => $deal['CATEGORY_ID'],
												'READ_ONLY' => true
											)) ?>
									</div><!--crm-card-show-detail-info-main-status-->
								</div><!--crm-card-show-detail-info-main-inner-->
							<? endforeach ?>
						</div>
					<? endif ?>

					<? if(is_array($arResult['ENTITY']['INVOICES']) && count($arResult['ENTITY']['INVOICES']) > 0): ?>
						<div class="crm-card-show-detail-info-wrap">
							<div class="crm-card-show-detail-info-title crm-card-show-title-main">
								<div class="crm-card-show-detail-info-title-item">
									<a href="<?=htmlspecialcharsbx($arResult['ENTITY']['INVOICE_LIST_URL'])?>" target="_blank">
										<?=\CCrmOwnerType::GetCategoryCaption(\CCrmOwnerType::Invoice);?>
									</a>
								</div>
							</div>
							<? foreach ($arResult['ENTITY']['INVOICES'] as $invoice): ?>
								<div class="crm-card-show-detail-info-main-inner">
									<div class="crm-card-show-detail-info-main-content">
										<div class="crm-card-show-detail-info-block">
											<div class="crm-card-show-detail-info-name">
												<div class="crm-card-show-detail-info-name-item">
													<a href="<?=htmlspecialcharsbx($invoice['SHOW_URL'])?>" target="_blank" data-use-slider="<?= ($arResult['SLIDER_ENABLED'] ? 'Y' : 'N')?>">
														<?=htmlspecialcharsbx($invoice['ORDER_TOPIC']).' '.GetMessage('CRM_CARD_INVOICE_DATE_FROM').' '.htmlspecialcharsbx($invoice['DATE_BILL'])?>
													</a>
												</div>
											</div>
											<div class="crm-card-show-detail-info-desc">
												<div class="crm-card-show-detail-info-desc-item"><?= $invoice['PRICE_FORMATTED']?></div>
											</div>
										</div>
									</div><!--crm-card-show-detail-info-main-content-->
									<div class="crm-card-show-detail-info-main-status">
										<?= CCrmViewHelper::RenderInvoiceStatusControl(
											array(
												'ENTITY_ID' => $invoice['ID'],
												'CURRENT_ID' => $invoice['STATUS_ID'],
												'READ_ONLY' => true
											)) ?>
									</div><!--crm-card-show-detail-info-main-status-->
								</div><!--crm-card-show-detail-info-main-inner-->
							<? endforeach ?>
						</div>
					<? endif ?>

					<? if(is_array($arResult['ENTITY']['SMART_INVOICES']) && count($arResult['ENTITY']['SMART_INVOICES']) > 0): ?>
						<?echo \CCrmViewHelper::RenderItemStatusSettings(\CCrmOwnerType::SmartInvoice, null);?>
						<div class="crm-card-show-detail-info-wrap">
							<div class="crm-card-show-detail-info-title crm-card-show-title-main">
								<div class="crm-card-show-detail-info-title-item">
									<a href="<?=htmlspecialcharsbx($arResult['ENTITY']['SHOW_URL'])?>" target="_blank">
										<?=\CCrmOwnerType::GetCategoryCaption(\CCrmOwnerType::SmartInvoice);?>
									</a>
								</div>
							</div>
							<? foreach ($arResult['ENTITY']['SMART_INVOICES'] as $invoice): ?>
								<div class="crm-card-show-detail-info-main-inner">
									<div class="crm-card-show-detail-info-main-content">
										<div class="crm-card-show-detail-info-block">
											<div class="crm-card-show-detail-info-name">
												<div class="crm-card-show-detail-info-name-item">
													<a href="<?=htmlspecialcharsbx($invoice['SHOW_URL'])?>" target="_blank" data-use-slider="Y">
														<?=htmlspecialcharsbx($invoice['HEADING'])?>
													</a>
												</div>
											</div>
											<div class="crm-card-show-detail-info-desc">
												<div class="crm-card-show-detail-info-desc-item"><?= $invoice['PRICE_FORMATTED']?></div>
											</div>
										</div>
									</div><!--crm-card-show-detail-info-main-content-->
									<div class="crm-card-show-detail-info-main-status">
										<?= CCrmViewHelper::RenderProgressControl(
											[
												'ENTITY_TYPE_NAME' => \CCrmOwnerType::SmartInvoiceName,
												'ENTITY_TYPE_ID' => \CCrmOwnerType::SmartInvoice,
												'ENTITY_ID' => $invoice['ID'],
												'CURRENT_ID' => $invoice['STAGE_ID'],
												'READ_ONLY' => true,
												'CATEGORY_ID' => $invoice['CATEGORY_ID'],
											]) ?>
									</div><!--crm-card-show-detail-info-main-status-->
								</div><!--crm-card-show-detail-info-main-inner-->
							<? endforeach ?>
						</div>
					<? endif ?>

					<? if($arResult['ENTITY']['RESPONSIBLE']): ?>
						<div class="crm-card-show-detail-info-wrap">
							<div class="crm-card-show-user-responsible crm-card-show-user-responsible-detail-info">
								<div class="crm-card-show-user-responsible-title"><?= GetMessage('CRM_CARD_RESPONSIBLE')?>:</div>
								<div class="crm-card-show-user-responsible-user">
									<? if($arResult['ENTITY']['RESPONSIBLE']['PHOTO'] != ''): ?>
										<div class="ui-icon ui-icon-common-user crm-card-show-user-responsible-user-icon"><i style="background-image: url(<?= Uri::urnEncode($arResult['ENTITY']['RESPONSIBLE']['PHOTO'])?>)"></i></div>
									<? else: ?>
										<div class="ui-icon ui-icon-common-user crm-card-show-user-responsible-user-icon"><i></i></div>
									<? endif ?>
									<div class="crm-card-show-user-responsible-user-info">
										<a class="crm-card-show-user-responsible-user-name" href="<?=$arResult['ENTITY']['RESPONSIBLE']['PROFILE_PATH']?>" target="_blank">
											<?= htmlspecialcharsbx($arResult['ENTITY']['RESPONSIBLE']['NAME'])?>
										</a>
										<div class="crm-card-show-user-responsible-user-info-position">
											<?= htmlspecialcharsbx($arResult['ENTITY']['RESPONSIBLE']['POST'])?>
										</div>
									</div>
								</div>
							</div>
						</div>
					<? endif ?>

				</div><!--crm-card-show-detail-info-content-->
			</div><!--crm-card-show-detail-info-inner-->
		</div><!--crm-card-show-detail-info-->
	</div><!--crm-card-show-detail-->
	<script>
		BX.ready(function()
		{
			var extendedNode = BX('crm-card-extended-info');
			if(extendedNode)
			{
				if(extendedNode.clientHeight == 304)
				{
					BX.addClass(BX('crm-card-detail-container'), 'crm-card-show-detail-compact');
				}
				var photoNode = BX('crm-card-user-photo');
				if(photoNode)
				{
					photoNode.style.width = photoNode.clientHeight.toString() + 'px';
				}
			}
		})
	</script>
<?endif?>



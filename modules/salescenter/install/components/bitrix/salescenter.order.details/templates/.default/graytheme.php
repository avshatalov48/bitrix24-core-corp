<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var $arParams[]
 * @var $arResult[]
 * @var $APPLICATION
 */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Text\HtmlFilter;

Extension::load(["ui.fonts.ruble", "ui.icons.disk", "ui.icons.generator"]);

CJSCore::Init(array('clipboard', 'fx'));

$APPLICATION->SetTitle("");

if (!empty($arResult['ERRORS']['FATAL']))
{
	$component = $this->__component;
	foreach ($arResult['ERRORS']['FATAL'] as $code => $error)
	{
		?>
		<div class="page-description"><?= $error ?></div>
		<?php
	}
}
else
{
	if (!empty($arResult['ERRORS']['NONFATAL']))
	{
		foreach ($arResult['ERRORS']['NONFATAL'] as $error)
		{
			?>
			<div class="page-description"><?= $error ?></div>
			<?php
		}
	}
	?>
	<section class="order row gray-theme">
		<div class="col p-0 salescenter-order-details">
			<?php if ($arParams['SHOW_HEADER'] === 'Y'): ?>
				<div class="salescenter-order-details__header">
				<span class="salescenter-order-details__header-text">
					<?= $arParams['HEADER_TITLE'] ?>
				</span>
				</div>
				<div class="salescenter-order-details__info-order">
					<?= str_replace(' ', '&nbsp;', Loc::getMessage(
						'SOD_SUB_PAYMENT_TITLE_SHORT',
						["#ACCOUNT_NUMBER#" => htmlspecialcharsbx($arResult["ACCOUNT_NUMBER"])]
					)) ?>
				</div>
			<?php endif; ?>
			<!--	Sections that need to export in all another shop landings (maybe) -->
			<div class="salescenter-order-details__section">
				<!-- Region cart -->
				<div class="salescenter-order-details__product_items">
					<?php foreach ($arResult['BASKET'] as $basketItem)
					{
						if (empty($basketItem['PICTURE']['SRC']))
						{
							$fileName = ($arParams['TEMPLATE_MODE'] === 'darkmode') ? 'item-black.svg' : 'item-white.svg';
							$src = "/bitrix/components/bitrix/salescenter.order.details/templates/.default/images/{$fileName}";
						}
						else
						{
							$src = htmlspecialcharsbx($basketItem['PICTURE']['SRC']);
						}
						?>
						<div class="salescenter-order-details__product_item">
							<div class="salescenter-order-details__product_image">
								<img src="<?=$src?>" alt="" class="salescenter-order-details__product_img">
							</div>
							<div class="salescenter-order-details__product_info">
								<div class="salescenter-order-details__product_name">
									<?= htmlspecialcharsbx($basketItem['NAME']) ?>
								</div>
								<?php if (isset($basketItem['PROPERTIES']) && count($basketItem['PROPERTIES']) > 0):?>
									<div class="salescenter-order-details__product_additional">
										<?php foreach ($basketItem['PROPERTIES'] as $property):?>
											<?php if (!empty($property['VALUE'])): ?>
												<span>
												<?= htmlspecialcharsbx($property['VALUE']) ?>
												</span>
											<?php endif; ?>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
							<div class="salescenter-order-details__product_total">
								<div class="salescenter-order-details__product_price"><?= $basketItem['FORMATED_PRICE'] ?></div>
								<?php if ($basketItem['DISCOUNT_PRICE'] > 0): ?>
									<div class="salescenter-order-details__product_old-price"><?= $basketItem['FORMATED_BASE_PRICE'] ?></div>
								<?php endif; ?>
								<div class="salescenter-order-details__product_quantity"><?= (float)$basketItem['QUANTITY'] ?> <?= htmlspecialcharsbx($basketItem['MEASURE_NAME']) ?></div>
							</div>
						</div>
						<?
					}
					?>
				</div>
				<!-- end region -->

				<!-- region total -->
				<div class="salescenter-order-details__order-info">
					<div class="salescenter-order-details__order-info_name"><?= Loc::getMessage('SOD_COMMON_SUM_NEW') ?></div>
					<div class="salescenter-order-details__order-info_details">
						<?php if (
							$arResult["BASE_PRODUCT_SUM_FORMATED"] !== ''
							&& ($arResult['BASE_PRODUCT_SUM']
								> $arResult['PRODUCT_SUM'])
						): ?>
							<span class="salescenter-order-details__product_old-price"><?= $arResult['BASE_PRODUCT_SUM_FORMATED'] ?></span>
						<?php endif; ?>
						<span class="salescenter-order-details__product_price"><?= $arResult['PRODUCT_SUM_FORMATED'] ?></span>
					</div>
				</div>
				<?php if (!empty($arResult["DISCOUNT_VALUE_FORMATED"])) : ?>
					<div class="salescenter-order-details__order-info">
						<div class="salescenter-order-details__order-info_name"><?= Loc::getMessage('SOD_COMMON_DISCOUNT') ?></div>
						<div class="salescenter-order-details__order-info_details">
							<span class="salescenter-order-details__product_saving"><?= $arResult['DISCOUNT_VALUE_FORMATED'] ?></span>
						</div>
					</div>
				<?php endif; ?>
				<?php if (!empty($arResult['SHIPMENT'])): ?>
					<div class="salescenter-order-details__order-info">
						<div class="salescenter-order-details__order-info_name">
							<?= Loc::getMessage('SOD_DELIVERY') ?> (<?= HtmlFilter::encode($arResult['SHIPMENT']['DELIVERY_NAME']) ?>)
						</div>
						<?
						$deliveryText = Loc::getMessage('SOD_FREE');
						if ((float)($arResult['SHIPMENT']["PRICE_DELIVERY"]) > 0)
						{
							$deliveryText = $arResult['SHIPMENT']["PRICE_DELIVERY_FORMATTED"];
						}
						?>
						<div class="salescenter-order-details__order-info_details">
							<div class="alescenter-order-details__product_price"><?= $deliveryText ?></div>
						</div>
					</div>
				<?php endif; ?>
				<div class="salescenter-order-details__total">
					<div class="salescenter-order-details__total_text"><?= Loc::getMessage('SOD_SUMMARY') ?></div>
					<div class="salescenter-order-details__total_sum"><?= $arResult['PRICE_FORMATED'] ?></div>
				</div>
			</div>
			<?php if (!empty($arResult['DOCUMENT'])):
				$pdfId = $arResult['DOCUMENT']['pdf']['id'] ?? 0;
				$docxId = $arResult['DOCUMENT']['docx']['id'] ?? 0;
				$extension = $pdfId > 0 ? 'pdf' : 'docx';
				?>
				<div class="page-section order-document-container">
					<div class="page-section-title"><?=Loc::getMessage('SPOD_DOCUMENT_TITLE');?></div>
					<div class="order-document">
						<div class="order-document-file-icon"></div>
						<script>
							BX.ready(function()
							{
								var iconExtension = new BX.UI.Icons.Generator.FileIcon({
									size: 37,
									align: "left",
									name: "<?=$extension?>"
								});

								iconExtension.renderTo(document.body.querySelector(".order-document-file-icon"));
							});
						</script>
						<div class="order-document-description">
							<div class="order-document-title"><?=htmlspecialcharsbx($arResult['DOCUMENT']['title'])?></div>
						</div>
					</div>
					<div class="order-document-actions">
						<?php if ($extension === 'pdf'):?>
						<div class="salescenter-order-details__item-button">
							<a
								target="_blank"
								href="<?=$arResult['DOCUMENT']['showUrl']?>"
							>
								<?=Loc::getMessage('SPOD_DOCUMENT_ACTION_OPEN')?>
							</a>
						</div>
						<?php endif;?>
						<div
							class="salescenter-order-details__item-button"
						>
							<a
								target="_blank"
								href="<?=$arResult['DOCUMENT'][$extension]['url']?>"
							>
								<?=Loc::getMessage('SPOD_DOCUMENT_ACTION_DOWNLOAD')?>
							</a>
						</div>
						<div
							class="salescenter-order-details__item-button"
							data-role="document-share-action"
							data-title="<?=CUtil::JSEscape($arResult['DOCUMENT'][$extension]['title'])?>"
							data-url="<?=CUtil::JSEscape($arResult['DOCUMENT'][$extension]['url'])?>"
						>
							<a href="javascript:void(0);">
								<?=Loc::getMessage('SPOD_DOCUMENT_ACTION_SHARE')?>
							</a>
						</div>
					</div>
				</div>
			<?endif;?>
			<?php
			if ($arResult['PAYMENT'])
			{
				$paymentComponentParams = [
					"PAYMENT_ID" => $arResult['PAYMENT']['ID'],
					"INCLUDED_IN_ORDER_TEMPLATE" => "Y",
					"ALLOW_PAYMENT_REDIRECT" => "Y",
					"ACTIVE_DATE_FORMAT" => "d F Y, H:i",
					"USER_CONSENT" => $arParams['USER_CONSENT'] ?? null,
					"USER_CONSENT_ID" => $arParams['USER_CONSENT_ID'] ?? null,
					"USER_CONSENT_IS_CHECKED" => $arParams['USER_CONSENT_IS_CHECKED'] ?? null,
					"USER_CONSENT_IS_LOADED" => $arParams['USER_CONSENT_IS_LOADED'] ?? null,
					"ALLOW_SELECT_PAY_SYSTEM" => $arParams["ALLOW_SELECT_PAYMENT_PAY_SYSTEM"] ?? null,
					"TEMPLATE_MODE" => "graymode",
				];

				$APPLICATION->IncludeComponent("bitrix:salescenter.payment.pay", "", $paymentComponentParams);
			}
			?>
		</div>
	</section>
	<script>
		BX.ready(function() {
			var shareAction = document.querySelector('[data-role="document-share-action"]');
			if (shareAction)
			{
				var shareData = {
					title: shareAction.dataset.title,
					url: shareAction.dataset.url,
				};
				if (navigator.share && navigator.canShare && navigator.canShare(shareData))
				{
					BX.bind(shareAction, 'click', function() {
						navigator.share(shareData)
					});
				}
				else
				{
					BX.remove(shareAction);
				}
			}
		});
	</script>
	<?php
}

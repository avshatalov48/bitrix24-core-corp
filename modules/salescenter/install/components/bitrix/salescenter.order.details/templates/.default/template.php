<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

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
	<section class="order row <?= ($arParams['TEMPLATE_MODE'] === 'darkmode') ? 'bx-dark' : '' ?>">
		<div class="col p-0">
			<?php if ($arParams['SHOW_HEADER'] === 'Y'): ?>
				<div class="order-list-header d-flex justify-content-between align-items-center">
					<div class="order-list-header-title">
						<?= $arParams['HEADER_TITLE'] ?>
					</div>
					<div class="order-list-header-order">
						<?= str_replace(' ', '&nbsp;', Loc::getMessage(
							'SOD_SUB_PAYMENT_TITLE_SHORT',
							["#ACCOUNT_NUMBER#" => htmlspecialcharsbx($arResult["ACCOUNT_NUMBER"])]
						)) ?>
					</div>
				</div>
			<?php endif; ?>

			<!--region cart-->
			<div class="order-list-container">
				<div class="order-list">
					<?php foreach ($arResult['BASKET'] as $basketItem)
					{
						$src = htmlspecialcharsbx($basketItem['PICTURE']['SRC']);
						if ($basketItem['PICTURE']['SRC'] == '')
						{
							$fileName = ($arParams['TEMPLATE_MODE'] === 'darkmode') ? 'item-black.svg' : 'item-white.svg';
							$src = "/bitrix/components/bitrix/salescenter.order.details/templates/.default/images/{$fileName}";
						}
						?>
						<div class="order-list-item d-flex justify-content-start align-items-start">
							<div class="col-auto pl-0 pr-0 order-item-image-container">
								<img class="order-item-image" src="<?= $src ?>" alt="">
							</div>
							<div class="col order-item-title">
								<?= htmlspecialcharsbx($basketItem['NAME']) ?>
								<?php if (isset($basketItem['PROPERTIES']) && count($basketItem['PROPERTIES']) > 0):?>
									<div class="order-item-properties">
										<?php foreach ($basketItem['PROPERTIES'] as $property):?>
											<div class="order-item-property">
												<?= htmlspecialcharsbx($property['NAME']) ?>: <?= htmlspecialcharsbx($property['VALUE']) ?>
											</div>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
							<div class="col pr-0 order-item-info">
								<div class="order-item-price">
									<?= $basketItem['FORMATED_PRICE'] ?>
								</div>
								<?php if ($basketItem['DISCOUNT_PRICE'] > 0): ?>
									<div class="order-item-price-old"><?= $basketItem['FORMATED_BASE_PRICE'] ?></div>
								<?php endif; ?>
								<div class="order-item-quantity"><?= (float)$basketItem['QUANTITY'] ?>
									<?= htmlspecialcharsbx($basketItem['MEASURE_NAME']) ?>
								</div>

							</div>
						</div>
						<?
					}
					?>
				</div>
			</div>
			<!--endregion-->

			<!--region total-->
			<div class="order-total-container">
				<div class="order-total">
					<table>
						<tr class="order-total-price-row">
							<td class="order-total-item order-total-item-total-price">
								<?= Loc::getMessage('SOD_COMMON_SUM_NEW') ?>
							</td>
							<td class="order-total-value">
								<div class="order-total-price"><?= $arResult['PRODUCT_SUM_FORMATED'] ?></div>
								<?php if (
									$arResult["BASE_PRODUCT_SUM_FORMATED"] !== ''
									&& ($arResult['BASE_PRODUCT_SUM']
										> $arResult['PRODUCT_SUM'])
								): ?>
									<div class="order-total-price-old"><?= $arResult['BASE_PRODUCT_SUM_FORMATED'] ?></div>
								<?php endif; ?>
							</td>
						</tr>
						<?php if (!empty($arResult["DISCOUNT_VALUE_FORMATED"])) : ?>
							<tr class="order-total-price-old-row">
								<td class="order-total-item"><?= Loc::getMessage(
										'SOD_COMMON_DISCOUNT'
									) ?></td>
								<td class="order-total-value">
									<div class="order-total-sale-price"><?= $arResult['DISCOUNT_VALUE_FORMATED'] ?></div>
								</td>
							</tr>
						<?php endif; ?>

						<?php if (!empty($arResult['SHIPMENT'])): ?>
							<tr class="order-total-delivery-row">
								<td class="order-total-item">
									<?= Loc::getMessage('SOD_DELIVERY') ?> (<?= HtmlFilter::encode($arResult['SHIPMENT']['DELIVERY_NAME']) ?>)
								</td>
								<?
								$deliveryText = Loc::getMessage('SOD_FREE');
								$deliveryClass = 'order-total-delivery-price';
								if ((float)($arResult['SHIPMENT']["PRICE_DELIVERY"]) > 0)
								{
									$deliveryText = $arResult['SHIPMENT']["PRICE_DELIVERY_FORMATTED"];
									$deliveryClass = 'order-total-price';
								}
								?>
								<td class="order-total-value">
									<div class="<?=$deliveryClass?>"><?=$deliveryText?></div>
								</td>
							</tr>
						<?php endif; ?>

					</table>
				</div>
				<div class="order-total-result d-flex align-items-center justify-content-between">
					<div class="order-total-result-name"><?= Loc::getMessage('SOD_SUMMARY') ?></div>
					<div class="order-total-result-value"><?= $arResult['PRICE_FORMATED'] ?></div>
				</div>
			</div>
			<!--endregion-->

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
									name: "<?=$extension;?>"
								});

								iconExtension.renderTo(document.body.querySelector(".order-document-file-icon"));
							});
						</script>
						<div class="order-document-description">
							<div class="order-document-title"><?=htmlspecialcharsbx($arResult['DOCUMENT']['title']);?></div>
							</div>
						</div>
						<div class="order-document-actions">
							<?php if ($extension === 'pdf'):?>
								<div class="order-document-actions-action">
									<a
										target="_blank"
										href="<?=$arResult['DOCUMENT']['showUrl']?>"
									>
										<?=Loc::getMessage('SPOD_DOCUMENT_ACTION_OPEN')?>
									</a>
								</div>
							<?php endif;?>
							<div class="order-document-actions-action">
								<a
									target="_blank"
									href="<?=$arResult['DOCUMENT'][$extension]['url']?>"
								>
									<?=Loc::getMessage('SPOD_DOCUMENT_ACTION_DOWNLOAD')?>
								</a>
							</div>
							<div
								class="order-document-actions-action"
								data-role="document-share-action"
								data-title="<?=CUtil::JSEscape($arResult['DOCUMENT'][$extension]['title'])?>"
								data-url="<?=CUtil::JSEscape($arResult['DOCUMENT'][$extension]['url'])?>"
							>
								<a href="javascript:void(0);"><?=Loc::getMessage('SPOD_DOCUMENT_ACTION_SHARE')?></a>
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
					"ACTIVE_DATE_FORMAT" => "d F Y, H:m",
					"USER_CONSENT" => $arParams['USER_CONSENT'],
					"USER_CONSENT_ID" => $arParams['USER_CONSENT_ID'],
					"USER_CONSENT_IS_CHECKED" => $arParams['USER_CONSENT_IS_CHECKED'],
					"USER_CONSENT_IS_LOADED" => $arParams['USER_CONSENT_IS_LOADED'],
					"ALLOW_SELECT_PAY_SYSTEM" => $arParams["ALLOW_SELECT_PAYMENT_PAY_SYSTEM"],
				];

				$APPLICATION->IncludeComponent("bitrix:salescenter.payment.pay", "", $paymentComponentParams);
			}
			?>

			<div class="order-list-title">
				<?= Loc::getMessage('SOD_SUB_PAYMENT_TITLE', array(
					"#ACCOUNT_NUMBER#" => htmlspecialcharsbx($arResult["ACCOUNT_NUMBER"]),
					"#DATE_ORDER_CREATE#" => $arResult["DATE_BILL_FORMATTED"],
				)) ?>
			</div>
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
?>


<?
use Bitrix\Main\Localization\Loc,
	Bitrix\Main\Page\Asset;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
Loc::loadMessages(__FILE__);

/** @var $arResult array */
/** @global APPLICATION CMain */
global $APPLICATION;

if(isset($arResult['CUSTOMIZATION']) && $arResult['CUSTOMIZATION']['BACKGROUND_IMAGE_PATH'])
{
	$APPLICATION->SetPageProperty(
		"BodyClass",
		$APPLICATION->GetPageProperty("BodyClass") . ' page-theme-image'
	);
	$additionalCssString = ".page-theme-image {" .
		"\n" . 'background-image: url("' . $arResult['CUSTOMIZATION']['BACKGROUND_IMAGE_PATH'] . '");' .
		"\n" . '}';

	\Bitrix\Main\Page\Asset::getInstance()->addString(
		'<style type="text/css">' . "\n" . $additionalCssString . "\n" . '</style>'
	);
}

if (!empty($arResult["errorMessage"]))
{
	if (!is_array($arResult["errorMessage"]))
	{
		?>
		<div id="pub-template-error" class="error-block" style="display: block;">
			<div id="pub-template-error-title" class="error-block-title"><?=$arResult["errorMessage"]?></div>
		</div>
		<?
	}
	else
	{
		foreach ($arResult["errorMessage"] as $errorMessage)
		{
			?>
			<div id="pub-template-error" class="error-block" style="display: block;">
				<div id="pub-template-error-title" class="error-block-title"><?=$errorMessage?></div>
			</div>
			<?
		}
	}
}
else
{
	Asset::getInstance()->addJs("/bitrix/js/main/utils.js");
	\CJSCore::init(["loader", "documentpreview", "popup", "ui.fonts.opensans"]);
	?>
	<div id="crm-invoice-payment-client-wrapper" class="crm-invoice-payment-client-wrapper <?=(!isset($arResult['PAY_SYSTEM_PAID_ARRAY']) && !array_key_exists('PAY_SYSTEM_TEMPLATE', $arResult))?"crm-invoice-payment-deliver":""?>">
		<div class="crm-invoice-payment-client-template">
			<?
			if ($arParams['IS_AJAX_PAY'] === 'Y')
			{
				echo $arResult['BILL_TEMPLATE'];
			}
			elseif (empty($arResult['PAY_SYSTEM_PAID_ARRAY']) && !array_key_exists('PAY_SYSTEM_TEMPLATE', $arResult))
			{
			?>
				<?if ($arResult['USE_FRAME'] === 'Y'):?>
					<iframe id="crm-invoice-payment-template-frame" frameborder="0"></iframe>
				<?else:?>
					<div class="docs-preview-img" id="crm-document-image" style="width: 800px; min-height: 800px;"></div>
				    <script>
					    BX.ready(function () {
						    var options = <?=\CUtil::PhpToJSObject($arResult['FILE_PARAMS']);?>;
							options.imageContainer = BX('crm-document-image');
							<?if (!isset($arResult['FILE_PARAMS']['imageUrl'])):?>
							    BX('crm-invoice-button-download').style='pointer-events: none; opacity: 0.5';
								BX('crm-invoice-button-print').style='pointer-events: none; opacity: 0.5';
								options.onReady = function () {
									BX('crm-invoice-button-download').style='pointer-events: all; opacity: 1';
									BX('crm-invoice-button-print').style='pointer-events: all; opacity: 1';
								};
						    <?endif;?>
							var document = new BX.DocumentGenerator.DocumentPreview(options);
						});
					</script>
					<?
					$APPLICATION->IncludeComponent("bitrix:pull.request", "", [], false, ["HIDE_ICONS" => "Y"]);
					?>
				<?endif;?>
			<?
			}
			?>
		</div>
		<?
		if ($arParams['IS_AJAX_PAY'] !== 'Y' && empty($arResult['PAY_SYSTEM_PAID_ARRAY']) && !array_key_exists('PAY_SYSTEM_TEMPLATE', $arResult))
		{
			?>
			<div class="crm-invoice-payment-bar">
				<div class="crm-invoice-payment-button">
					<a id = "crm-invoice-button-download" class="crm-invoice-payment-button-download <?=(LANGUAGE_ID === 'de' ? "crm-invoice-payment-button-de":"")?>"
					   href="<?=$arResult['BUTTONS']['SAVE']?>" download><?=Loc::getMessage('CIPC_TPL_BUTTON_LOAD')?></a>
					<a id = "crm-invoice-button-print" class="crm-invoice-payment-button-print <?=(LANGUAGE_ID === 'de' ? "crm-invoice-payment-button-de":"")?>"
					   onclick="<?=$arResult['BUTTONS']['PRINT']?>"><?=Loc::getMessage('CIPC_TPL_BUTTON_PRINT')?></a>
				</div>
				<div class="crm-invoice-payment-total">
					<div class="crm-invoice-payment-total-title"><?=Loc::getMessage('CIPC_TPL_SUM_PAYMENT')?></div>
					<div class="crm-invoice-payment-total-sum"><?=$arResult['SUM']?></div>
				</div>
				<?
				if (!empty($arResult['PAYSYSTEMS_LIST']))
				{
					?>
					<div class="crm-invoice-payment-system">
						<div class="crm-invoice-payment-system-title"><?=Loc::getMessage('CIPC_TPL_PAY_FOR')?>:</div>
						<div class="crm-invoice-payment-system-array crm-invoice-payment-client-pp-company-graf-container">
							<?
							foreach ($arResult['PAYSYSTEMS_LIST'] as $key => $paySystem)
							{
								?>
								<div class="crm-invoice-payment-system-image-block">
									<div class="crm-invoice-payment-system-image"
										 style="background-image: url(<?=$paySystem['LOGOTIP']?>);background-size:contain;">
										<input id="id-1"
											   name="PAY_SYSTEM_ID"
											   value="<?=$paySystem['ID']?>"
											   type="hidden">
									</div>
									<div class="crm-invoice-payment-system-name" >
										<?=htmlspecialcharsbx($paySystem['NAME'])?>
									</div>
								</div>
								<?
							}
							?>
						</div>
					</div>
					<div class="crm-invoice-payment-system-template"></div>
					<div>
						<a class="crm-invoice-payment-system-return-list"><?=Loc::getMessage('CIPC_TPL_RETURN_LIST')?></a>
					</div>
					<?
				}
				if (!empty($arResult['BANK_PROPERTIES']))
				{
					?>
					<div class="crm-invoice-payment-requisites">
						<div class="crm-invoice-payment-requisites-title"><?=Loc::getMessage('CIPC_TPL_BANK_PROPS')?>:</div>
						<table class="crm-invoice-payment-requisites-table">
							<?
							foreach ($arResult['BANK_PROPERTIES'] as $keyName => $property)
							{
								if ($keyName === 'SELLER_COMPANY_NAME')
								{
									?>
									<tr>
										<td class="crm-invoice-payment-requisites-dark" colspan="2">
											<?=htmlspecialcharsbx($property['VALUE'])?>
										</td>
									</tr>
									<?
								}
								elseif (!empty($property['VALUE']))
								{
									?>
									<tr>
										<td><?=htmlspecialcharsbx($property['NAME'])?></td>
										<td><?=htmlspecialcharsbx($property['VALUE'])?></td>
									</tr>
									<?
								}
							}
							?>
						</table>
					</div>
					<?
				}
				$javascriptParams = array(
					"url" => "/pub/payment.php",
					"templateFolder" => CUtil::JSEscape($templateFolder),
					"accountNumber" => $arParams['ACCOUNT_NUMBER'],
					"hash" => $arParams['HASH'],
					"templateBill" => $arResult['BILL_TEMPLATE'],
					"useFrame" => $arResult['USE_FRAME'],
					"returnUrl" => CUtil::JSEscape($arParams["RETURN_URL"]),
				);
				$javascriptParams = CUtil::PhpToJSObject($javascriptParams);
				?>
				<script>
					var sc = new BX.crmInvoicePaymentClient(<?=$javascriptParams?>);
				</script>
			</div>
			<?
			if (in_array(LANGUAGE_ID, array('ru', 'ua')))
			{
				$logoClass = 'crm-invoice-payment-copy-logo';
			}
			else
			{
				$logoClass = 'crm-invoice-payment-copy-logo-en';
			}
			if ( \Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24'))
			{
				?>
				<div class="crm-invoice-payment-copy">
					<?
						if (
							!\Bitrix\Crm\Settings\InvoiceSettings::allowDisableSign() ||
							\Bitrix\Crm\Settings\InvoiceSettings::getCurrent()->getEnableSignFlag()
						)
						{
							echo Loc::getMessage('CIPC_TPL_BITRIX_SIGN',array("#LOGO#"=>'<a href='.$arResult['BUTTONS']['B24'].'><span class='.$logoClass.'></span></a>'));
						}
					?>
				</div>
				<?
			}
		}
		if (isset($arResult['PAY_SYSTEM_PAID_ARRAY']))
		{
			?>
			<div class="crm-invoice-payment-paid-table-wrapper">
				<table class="crm-invoice-payment-paid-table">
					<tr>
						<td class="crm-invoice-payment-paid-count-name" colspan="2">
							<?=Loc::getMessage('CIPC_TPL_PAID_TITLE',array(
								"#INVOICE_ID#"=>$arResult['PAY_SYSTEM_PAID_ARRAY']['ACCOUNT_NUMBER'],
								"#DATE_BILL#"=>$arResult['PAY_SYSTEM_PAID_ARRAY']['DATE_BILL']
							))?>
						</td>
					</tr>
					<tr class="crm-invoice-payment-paid-table-empty-row"></tr>
					<tr>
						<td class="crm-invoice-payment-paid-count">
							<?=Loc::getMessage('CIPC_TPL_PAID_SYSTEM')?>:
						</td>
						<td>
							<?=htmlspecialcharsbx($arResult['PAY_SYSTEM_PAID_ARRAY']['PAY_SYSTEM_NAME'])?>
						</td>
					</tr>
					<tr>
						<td class="crm-invoice-payment-paid-count">
							<?=Loc::getMessage('CIPC_TPL_PAID_SUM')?>:
						</td>
						<td>
							<?=$arResult['SUM']?>
						</td>
					</tr>
					<tr>
						<td class="crm-invoice-payment-paid-count">
							<?=Loc::getMessage('CIPC_TPL_PAID_DATE')?>:
						</td>
						<td>
							<?=$arResult['PAY_SYSTEM_PAID_ARRAY']['DATE_PAID']?>
						</td>
					</tr>
				</table>
			</div>
			<?
		}
		?>
		<?if (array_key_exists('PAY_SYSTEM_TEMPLATE', $arResult)):?>
			<div class="crm-invoice-payment-paid-table-wrapper">
				<table class="crm-invoice-payment-paid-table">
					<tr>
						<td class="crm-invoice-payment-paid-count-name" colspan="2">
							<?=$arResult['PAY_SYSTEM_TEMPLATE'];?>
						</td>
					</tr>
				</table>
			</div>
		<?endif;?>
	</div>
	<?
}
?>




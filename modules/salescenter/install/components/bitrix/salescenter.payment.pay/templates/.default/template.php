<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\UI\Extension,
	Bitrix\Main\Page\Asset;

CJSCore::Init(array("popup"));

Extension::load(["ui.fonts.ruble"]);
$payment = $arResult['PAYMENT'];
$currentPaySystem = $payment['PAY_SYSTEM_INFO'];
$additionalContainerClasses = ($arParams['INCLUDED_IN_ORDER_TEMPLATE'] === 'Y')
	? 'order-payment-sibling-container'
	: (($arParams['TEMPLATE_MODE'] === 'darkmode') ? 'bx-dark' : '');
$messages = Loc::loadLanguageFile(__FILE__);

if (!empty($arResult["errorMessage"]))
{
	if (!is_array($arResult["errorMessage"]))
	{
		?>
		<div class="page-description"><?= $arResult["errorMessage"] ?></div>
		<?
	}
	else
	{
		foreach ($arResult["errorMessage"] as $errorMessage)
		{
			?>
			<div class="page-description"><?= $errorMessage ?></div>
			<?
		}
	}
}
elseif ($payment['PAID'] === 'Y')
{
	$title = Loc::getMessage('SPP_PAID_TITLE', [
		'#ACCOUNT_NUMBER#' => htmlspecialcharsbx($payment['ACCOUNT_NUMBER']),
		'#DATE_INSERT#' => $payment['DATE_BILL_FORMATTED'],
	]);
	?>
	<div class="order-payment-container <?= $additionalContainerClasses ?> mb-4">
		<div class="order-payment-title"><?= $title ?></div>
		<div class="order-payment-inner d-flex align-items-center justify-content-between">
			<div class="order-payment-operator">
				<img src="<?= $currentPaySystem['LOGOTIP'] ?>" alt="">
			</div>
			<div class="order-payment-status d-flex align-items-center">
				<div class="order-payment-status-ok"></div>
				<div><?= Loc::getMessage('SPP_PAID') ?></div>
			</div>
			<div class="order-payment-price"><?= Loc::getMessage('SPP_SUM', ['#SUM#' => $payment['FORMATTED_SUM']]) ?></div>
		</div>
	</div>
	<?
}
else
{
	$id = str_shuffle(substr($arResult['SIGNED_PARAMS'], 0, 10));
	$wrapperId = "payment_container_$id";
	$submitButtonClass = "landing-block-node-button";
	$userConsentEventName = 'bx-spp-submit';
	$title = Loc::getMessage('SPP_PAID_TITLE', [
		'#ACCOUNT_NUMBER#' => htmlspecialcharsbx($payment['ACCOUNT_NUMBER']),
		'#DATE_INSERT#' => $payment['DATE_BILL_FORMATTED'],
	]);
	if ($arParams['ALLOW_SELECT_PAY_SYSTEM'] !== 'Y')
	{
		?>
		<div class="order-payment-container order-payment-sibling-container <?= $additionalContainerClasses ?> mb-4"
			 id="<?= $wrapperId ?>">
			<div class="order-payment-title"><?= $title ?></div>
			<div class="order-payment-inner d-flex align-items-center justify-content-between">
				<div class="order-payment-operator">
					<img src="<?= $currentPaySystem['LOGOTIP'] ?>" alt="">
				</div>

				<div class="order-payment-price"><?= Loc::getMessage('SPP_SUM',
						['#SUM#' => $payment['FORMATTED_SUM']]) ?></div>
			</div>
			<hr>
			<?
			if ($arResult['USER_CONSENT'] === 'Y')
			{
				$APPLICATION->IncludeComponent(
					'bitrix:main.userconsent.request',
					'',
					array(
						'ID' => $arResult['USER_CONSENT_ID'],
						'IS_CHECKED' => ($arResult['USER_CONSENT_IS_CHECKED'] === 'Y') ? 'Y' : 'N',
						'IS_LOADED' => 'N',
						'AUTO_SAVE' => 'N',
						'SUBMIT_EVENT_NAME' => $userConsentEventName,
						'REPLACE' => array(
							'button_caption' => Loc::getMessage('SPP_PAY_BUTTON'),
						),
					)
				);
			}
			?>
			<div class="order-payment-buttons-container">
				<button class="<?= $submitButtonClass ?> text-uppercase btn btn-xl pr-7 pl-7 u-btn-primary g-font-weight-700 g-font-size-12 g-rounded-50">
					<?= Loc::getMessage('SPP_PAY_BUTTON'); ?>
				</button>
			</div>
		</div>
		<?
		$settings = array(
			"selectedPaySystemId" => $currentPaySystem['ID'],
			"paySystemData" => [$currentPaySystem],
			"containerId" => $wrapperId,
			"consentEventName" => $userConsentEventName,
			"isAllowedSubmitting" => ($arResult['USER_CONSENT'] === 'Y' && $arResult['USER_CONSENT_IS_CHECKED']),
			"submitButtonSelector" => "button.$submitButtonClass",
			"url" => CUtil::JSEscape($this->__component->GetPath() . '/ajax.php'),
			"signedParameters" => $this->getComponent()->getSignedParameters(),
		);
		$settings = CUtil::PhpToJSObject($settings);
		?>
		<script>
			BX.message(<?=CUtil::PhpToJSObject($messages)?>);
			BX.ready(function ()
			{
				BX.SalesCenter.Component.PaymentPayInner.create("<?=$id?>", <?=$settings?>);
			});
		</script>
		<?
	}
	elseif (!empty($arResult['PAYSYSTEMS_LIST']))
	{
		$paySystemListClassName = "order-payment-method-list";
		$paySystemDescriptionClassName = "order-payment-method-description";
		$title = Loc::getMessage('SPP_SELECT_PAYMENT_TITLE');
		if ($arParams['VIEW_MODE'] === 'Y')
		{
			$additionalContainerClasses .= " order-payment-view-mode";
			$title = '';
		}
		?>
		<div class="page-section order-payment-method-container <?= $additionalContainerClasses ?> mb-4"
			 id="<?= $wrapperId ?>">
			<?php if ($title !== ''): ?>
				<div class="page-section-title"><?= $title ?></div>
			<?php endif; ?>
			<div class="page-section-inner">
				<div class="row align-items-stretch justify-content-start <?= $paySystemListClassName ?>"></div>
				<hr>
				<div class="<?= $paySystemDescriptionClassName ?>"></div>
				<hr>
				<?
				if ($arParams['VIEW_MODE'] !== 'Y')
				{
					if ($arResult['USER_CONSENT'] === 'Y')
					{
						$APPLICATION->IncludeComponent(
							'bitrix:main.userconsent.request',
							'',
							array(
								'ID' => $arResult['USER_CONSENT_ID'],
								'IS_CHECKED' => ($arResult['USER_CONSENT_IS_CHECKED'] === 'Y') ? 'Y' : 'N',
								'IS_LOADED' => 'N',
								'AUTO_SAVE' => 'N',
								'SUBMIT_EVENT_NAME' => $userConsentEventName,
								'REPLACE' => array(
									'button_caption' => Loc::getMessage('SPP_PAY_BUTTON'),
								),
							)
						);
					}
					?>
					<div class="order-payment-buttons-container">
						<button class="<?= $submitButtonClass ?> text-uppercase btn btn-xl pr-7 pl-7 u-btn-primary g-font-weight-700 g-font-size-12 g-rounded-50">
							<?= Loc::getMessage('SPP_PAY_BUTTON'); ?>
						</button>
					</div>
					<?
				}
				?>
			</div>
		</div>

		<?
		$first = current($arResult['PAYSYSTEMS_LIST']);
		$settings = array(
			"selectedPaySystemId" => (int)$currentPaySystem['PAY_SYSTEM_ID'] > 0 ? (int)$currentPaySystem['PAY_SYSTEM_ID'] : (int)$first['ID'],
			"paySystemData" => $arResult['PAYSYSTEMS_LIST'],
			"containerId" => $wrapperId,
			"viewMode" => ($arParams['VIEW_MODE'] === 'Y'),
			"consentEventName" => $userConsentEventName,
			"isAllowedSubmitting" => ($arResult['USER_CONSENT'] === 'Y' && $arResult['USER_CONSENT_IS_CHECKED'] === 'Y'),
			"paySystemBlockSelector" => ".$paySystemListClassName",
			"descriptionBlockSelector" => ".$paySystemDescriptionClassName",
			"submitButtonSelector" => "button.$submitButtonClass",
			"url" => CUtil::JSEscape($this->__component->GetPath() . '/ajax.php'),
			"signedParameters" => $this->getComponent()->getSignedParameters(),
		);
		$settings = CUtil::PhpToJSObject($settings);
		?>
		<script>
			BX.message(<?=CUtil::PhpToJSObject($messages)?>);
			BX.ready(function ()
			{
				BX.SalesCenter.Component.PaymentPayList.create("<?=$id?>", <?=$settings?>);
			});
		</script>
		<?
	}
}


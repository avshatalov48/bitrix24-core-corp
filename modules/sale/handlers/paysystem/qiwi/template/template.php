<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

if (isset($_POST["SET_NEW_PHONE"]))
	$params['BUYER_PERSON_PHONE'] = trim($_POST["NEW_PHONE"]);
?>

<div class="mb-4">
	<?if (!preg_match('/^\+7\d{10}$/', $params['BUYER_PERSON_PHONE'])):?>
		<?if ($params['BUYER_PERSON_PHONE']):?>
			<div class="alert alert-danger mb-3"><?=Loc::getMessage("SALE_HPS_QIWI_INCORRECT_PHONE_NUMBER")?></div>
			<div class="mb-1"><?=htmlspecialcharsbx(Loc::getMessage("SALE_HPS_QIWI_INPUT_PHONE"))?></div>
		<?endif;?>
		<form  action="<?=POST_FORM_ACTION_URI?>" method="post" class="form-inline">
			<div class="form-group mb-0 mr-3">
				<input type="text" name="NEW_PHONE" size="30" value="+7" placeholder="+7" class="form-control"/>
			</div>
			<input type="submit" class="btn btn-primary pl-4 pr-4" name="SET_NEW_PHONE" value="<?= Loc::getMessage("SALE_HPS_QIWI_SEND_PHONE")?>" />
		</form>
	<?else:?>
		<form action="<?=$params['URL']?>" method="post">
			<p>
				<?=Loc::getMessage("SALE_HPS_QIWI_SUMM_TO_PAY")?>:
				<?if (Loader::includeModule("currency")):?>
					<strong><?=CCurrencyLang::CurrencyFormat($params['PAYMENT_SHOULD_PAY'], $params['PAYMENT_CURRENCY'], true);?></strong>
				<?else:?>
					<strong><?=htmlspecialcharsbx($params['SHOULD_PAY']);?> <?=htmlspecialcharsbx($params['CURRENCY'])?></strong>
				<?endif;?>
			</p>
			<input type="hidden" name="to" value="<?=htmlspecialcharsbx($params['BUYER_PERSON_PHONE']);?>"/>
			<input type="hidden" name="from" value="<?=htmlspecialcharsbx($params['QIWI_SHOP_ID']);?>"/>
			<input type="hidden" name="summ" value="<?=htmlspecialcharsbx($params['PAYMENT_SHOULD_PAY']);?>"/>
			<input type="hidden" name="currency" value="<?=htmlspecialcharsbx($params['PAYMENT_CURRENCY']);?>"/>
			<input type="hidden" name="comm" value="<?=htmlspecialcharsbx(Loc::getMessage("SALE_HPS_QIWI_COMMENT", array("#ID#" => $params['PAYMENT_ID'])))?>"/>
			<input type="hidden" name="txn_id" value="<?=htmlspecialcharsbx($params['PAYMENT_ID']);?>"/>
			<input type="hidden" name="successUrl" value="<?=htmlspecialcharsbx($params['QIWI_SUCCESS_URL']);?>"/>
			<input type="hidden" name="failUrl" value="<?=htmlspecialcharsbx($params['QIWI_FAIL_URL']);?>"/>
			<input type="hidden" name="lifetime" value="<?=htmlspecialcharsbx($params['QIWI_BILL_LIFETIME']);?>"/>
			<input type="submit" class="btn btn-primary pl-4 pr-4" value="<?=Loc::getMessage("SALE_HPS_QIWI_DO_BILL");?>" />
		</form>
	<?endif?>
</div>

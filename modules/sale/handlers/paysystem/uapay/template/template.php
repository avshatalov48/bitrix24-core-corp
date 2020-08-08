<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$sum = round($params['SUM'], 2);
?>
<div class="mb-4" >
	<p><?=Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_UAPAY_DESCRIPTION');?></p>
	<p><?=Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_UAPAY_SUM')." ".SaleFormatCurrency($sum, $params['CURRENCY']);?></p>
	<div class="d-flex align-items-center mb-3">
		<div class="col-auto pl-0">
			<a class="btn btn-lg btn-success" style="border-radius: 32px;" href="<?=$params['URL'];?>"><?=Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_UAPAY_BUTTON_PAID')?></a>
		</div>
		<div class="col pr-0"><?=Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_UAPAY_REDIRECT_MESS');?></div>
	</div>
	<p><?=Loc::getMessage('SALE_HANDLERS_PAY_SYSTEM_UAPAY_WARNING_RETURN');?></p>
</div>
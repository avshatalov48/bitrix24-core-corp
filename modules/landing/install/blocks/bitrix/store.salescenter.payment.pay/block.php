<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
?>
<section class="landing-block g-pt-50 g-pb-50">
	<div class="landing-block-container">
		<?$APPLICATION->IncludeComponent(
			'bitrix:salescenter.payment.pay',
			'.default',
			[
				'ALLOW_SELECT_PAY_SYSTEM' => 'Y',
				'TEMPLATE_MODE' => 'lightmode'
			]
		);?>
	</div>
</section>



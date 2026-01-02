<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load([
	'ui.buttons',
	'ui.lottie',
	'ui.qrauthorization',
	'main.qrcode',
	'ui.mobile-promoter',
	'main.core',
	'ui.banner-dispatcher',
]);

$options = $arParams['~OPTIONS'];

?>

	<script>
		BX.ready(() => {
			const htmlContent = '<?= CUtil::JSEscape($options['content']) ?>'
			const content = document.createElement('div');
			content.innerHTML = htmlContent.trim();
			let mobile = new BX.UI.MobilePromoter({
				position: {
					right: 30,
					bottom: 30,
				},
				qrContent: '<?= $options['link']?>',
				title: '<?= CUtil::JSEscape($options['title']) ?>',
				content: content,
				analytics: {
					c_section: '<?= $options['analytics']['c_section'] ?>',
				}
			})

			BX.UI.BannerDispatcher.normal.toQueue((onDone) => {
				mobile.getPopup().subscribe('onAfterClose', (event) => {
					onDone();

					BX.userOptions.save('<?=$options['optionCategory']?>', '<?=$options['optionNameLastShowTime']?>', null, <?=time()?>);
					BX.userOptions.save('<?=$options['optionCategory']?>', '<?=$options['optionNameShowCount']?>', null, <?=$options['numberOfViews'] + 1?>);
				});
				mobile.show();
			});
		});
	</script>

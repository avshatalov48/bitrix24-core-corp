<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var CBitrixComponentTemplate $this
 * @var array $arResult
 */

use Bitrix\Main\Web\Json;

$reportReady = (!empty($arResult['reportData']['REPORT_DATA']));

$frame = $this->createFrame()->begin('');

if ($reportReady)
{
	$runtimeInfo = CTimeMan::initRuntimeInfo();
	
	CJSCore::Init(['timeman']);
}
?>

<?php if ($reportReady): ?>
<script type="text/javascript">
	BX.ready(() => {
		BX.Runtime.loadExtension('ui.banner-dispatcher')
			.then(({ BannerDispatcher }) => {
				const autoLauncherId = 'timeman-report-form';
				BannerDispatcher.normal.toQueue(
					() => {
						BX.Event.EventEmitter.subscribeOnce('onTimemanInit', () => {
							window.BXTIMEMAN.ShowFormWeekly(
								<?= Json::encode($arResult['reportData']) ?>,
								autoLauncherId
							);
						});
						
						BX.timeman('bx_tm', <?= Json::encode($runtimeInfo) ?>, '<?= SITE_ID ?>');
					},
					{
						id: autoLauncherId,
					},
				);
			});
	});
</script>
<?php endif ?>

<?php
$frame->end();

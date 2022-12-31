<?

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var $this \CBitrixComponentTemplate
 * @var $arResult
 * @var $arParams
 */

Loc::loadMessages(__FILE__);

$dynamicArea = new \Bitrix\Main\Composite\StaticArea('left-menu-whats-new');
$dynamicArea->startDynamicArea();
?>

<style>
	.left-menu-new-structure-bg {
		background-image: url("data:image/svg+xml,%3Csvg width='658' height='387' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill='%23EAF9FE' d='M1 0h656v387H1z'/%3E%3Cmask id='a' style='mask-type:alpha' maskUnits='userSpaceOnUse' x='310' y='0' width='347' height='387'%3E%3Cpath fill='%23C4C4C4' d='M310 0h347v387H310z'/%3E%3C/mask%3E%3Cg mask='url(%23a)'%3E%3Cpath opacity='.6' fill-rule='evenodd' clip-rule='evenodd' d='M705.868 407.931H446.771a89.87 89.87 0 0 1-6.591-.242c-58.557-1.331-105.612-49.12-105.615-107.876a107.871 107.871 0 0 1 31.734-76.334 108.187 108.187 0 0 1 36.099-23.787 113.819 113.819 0 0 1 33.147-89.373 114.275 114.275 0 0 1 80.743-33.32c38.784.046 73.025 19.402 93.604 48.946 9.772-3.456 20.292-5.334 31.251-5.328 48.413.058 88.176 36.89 92.786 83.993 46.388 10.093 81.102 51.337 81.061 100.65-.047 56.828-46.233 102.865-103.174 102.842a105.655 105.655 0 0 1-5.948-.171Z' fill='%23fff' fill-opacity='.1' stroke='%23fff' stroke-width='1.855'/%3E%3C/g%3E%3Cpath opacity='.6' fill-rule='evenodd' clip-rule='evenodd' d='M562.581 338.659h-54.266c-.465 0-.925-.017-1.381-.051-12.264-.28-22.119-10.342-22.12-22.712a22.774 22.774 0 0 1 6.646-16.072 22.656 22.656 0 0 1 7.561-5.008 24.028 24.028 0 0 1 6.943-18.817 23.871 23.871 0 0 1 16.911-7.016c8.123.01 15.294 4.086 19.604 10.306a19.47 19.47 0 0 1 6.546-1.122c10.139.012 18.468 7.767 19.433 17.684 9.716 2.125 16.986 10.809 16.978 21.191-.01 11.965-9.683 21.658-21.609 21.653-.419 0-.834-.012-1.246-.036ZM54.012 346.88H37.506c-.141 0-.281-.005-.42-.015-3.73-.086-6.728-3.165-6.728-6.95a6.99 6.99 0 0 1 2.022-4.918 6.876 6.876 0 0 1 2.3-1.533 7.376 7.376 0 0 1 2.112-5.758 7.238 7.238 0 0 1 5.143-2.146 7.242 7.242 0 0 1 5.963 3.153 5.867 5.867 0 0 1 1.99-.343c3.085.003 5.618 2.376 5.912 5.411 2.955.65 5.167 3.308 5.164 6.485-.003 3.661-2.945 6.627-6.573 6.625-.127 0-.253-.004-.379-.011ZM106.207 76.05H72.695c-.287 0-.571-.011-.853-.032-7.574-.175-13.66-6.467-13.66-14.202a14.33 14.33 0 0 1 4.104-10.05 13.973 13.973 0 0 1 4.67-3.131 15.12 15.12 0 0 1 4.287-11.766 14.65 14.65 0 0 1 10.443-4.388c5.017.007 9.446 2.555 12.107 6.444a11.892 11.892 0 0 1 4.043-.701c6.261.008 11.405 4.857 12.001 11.058 6 1.329 10.49 6.759 10.485 13.25-.007 7.482-5.98 13.543-13.345 13.54-.259 0-.515-.008-.77-.022ZM466.691 67.712h-21.008c-.18 0-.358-.007-.534-.02a8.767 8.767 0 0 1-8.564-8.763 8.77 8.77 0 0 1 5.5-8.133 9.252 9.252 0 0 1 9.235-9.967 9.24 9.24 0 0 1 7.589 3.976 7.568 7.568 0 0 1 10.057 6.39 8.362 8.362 0 0 1-2.275 16.516Z' fill='%23fff'/%3E%3Cg filter='url(%23b)'%3E%3Cpath fill-rule='evenodd' clip-rule='evenodd' d='M218.16 282.707H87.11c-1.12 0-2.232-.042-3.333-.123-29.617-.677-53.418-24.998-53.42-54.899a55.065 55.065 0 0 1 16.052-38.846 54.685 54.685 0 0 1 18.258-12.105 58.104 58.104 0 0 1 16.766-45.483 57.62 57.62 0 0 1 40.839-16.957c19.616.024 36.936 9.874 47.344 24.909a47.002 47.002 0 0 1 15.807-2.711c24.486.029 44.598 18.773 46.93 42.744 23.462 5.136 41.02 26.126 41 51.221-.024 28.921-23.384 52.349-52.185 52.337-1.009 0-2.012-.03-3.008-.087Z' fill='url(%23c)' shape-rendering='crispEdges'/%3E%3C/g%3E%3Crect x='56.944' y='137.204' width='162.292' height='117.063' rx='8.062' fill='%2321B9E9'/%3E%3Crect x='64.945' y='146.96' width='146.329' height='94.046' rx='2.015' fill='%23F8F9FA'/%3E%3Crect x='64.945' y='146.96' width='146.329' height='94.046' rx='2.015' fill='%23fff' fill-opacity='.2'/%3E%3Cg opacity='.3'%3E%3Cpath opacity='.2' d='M201.631 156.029c0-1.503-35.261 30.256-35.261 30.242l-18.95-6.321-16.027 16.09-13.98-5.513-43.238 34.04c-.751.592-.333 1.8.624 1.8l126.832-.012v-70.326Z' fill='%2355D0E0'/%3E%3Cpath d='m75.33 223.943 39.537-33.53 17.127 4.964 15.444-15.747 15.824 6.246 37.351-29.901' stroke='%2355D0E0' stroke-width='3.948' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/g%3E%3Cg opacity='.3'%3E%3Cpath opacity='.2' d='m188.867 196.77-26.994 13.897-17.262-6.115-19.916 16.678-19.917-7.227-30.096 20.014h114.402c0-27.883-.217-37.247-.217-37.247Z' fill='%239DCF00'/%3E%3Cpath d='m74.647 232.573 29.3-17.18 20.841 6.652 20.397-17.294 17.294 6.208 25.718-14.189' stroke='%238FBC00' stroke-width='3.948' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/g%3E%3Cpath d='M38.34 251.606a7.095 7.095 0 0 0 7.095 7.095h185.35a7.094 7.094 0 0 0 7.094-7.095.886.886 0 0 0-.886-.886H39.227a.887.887 0 0 0-.887.886Z' fill='%2316A2CF'/%3E%3Crect opacity='.2' x='128.779' y='244.659' width='18.624' height='2.661' rx='1.33' fill='%23fff'/%3E%3Cg filter='url(%23d)'%3E%3Crect x='72.021' y='155.09' width='108.195' height='44.342' rx='8.062' fill='%23fff'/%3E%3Crect x='111.042' y='163.072' width='43.455' height='3.547' rx='1.774' fill='%23DFE0E3'/%3E%3Crect x='111.042' y='171.053' width='43.455' height='3.547' rx='1.774' fill='%23DFE0E3'/%3E%3Crect x='111.042' y='179.035' width='31.04' height='3.547' rx='1.774' fill='%23DFE0E3'/%3E%3Crect x='80.89' y='162.184' width='21.284' height='21.284' rx='7.054' fill='%2365DEEE'/%3E%3Cpath opacity='.4' fill-rule='evenodd' clip-rule='evenodd' d='M93.227 172.218a2.66 2.66 0 1 0-3.39 0c-2.108.7-3.627 2.593-3.627 4.157 0 1.959 2.383 2.66 5.322 2.66 2.938 0 5.32-.701 5.32-2.66 0-1.564-1.518-3.457-3.625-4.157Z' fill='%23fff'/%3E%3C/g%3E%3Cg filter='url(%23e)'%3E%3Cpath d='M185.325 158.679c16.037 0 28.783 4.534 38.238 13.602 9.531 9.067 14.297 21.487 14.297 37.26 0 18.669-5.711 33.032-17.134 43.09-11.422 10.058-27.761 15.087-49.016 15.087-17.022 0-31.02-2.56-41.994-7.68-1.69-.788-2.712-2.515-2.712-4.38V233.22c0-2.285 2.447-3.761 4.528-2.818 4.886 2.213 10.336 4.098 16.35 5.656 7.943 1.981 15.469 2.972 22.579 2.972 21.408 0 32.111-8.839 32.111-26.517 0-16.84-11.082-25.26-33.245-25.26-4.009 0-8.434.42-13.276 1.258-9.552 1.503-13.544 1.056-20.546-2.327-3.215-1.553-5.042-4.993-4.781-8.554l5.469-74.629a5.039 5.039 0 0 1 5.025-4.67h80.257a5.039 5.039 0 0 1 5.038 5.039v19.868a5.039 5.039 0 0 1-5.038 5.039h-54.191l-3.063 32.803 3.971-.8c4.614-1.067 10.325-1.601 17.133-1.601Z' fill='url(%23f)' shape-rendering='crispEdges'/%3E%3C/g%3E%3Cg filter='url(%23g)'%3E%3Crect x='197.065' y='184.354' width='47.003' height='77.155' rx='8.062' fill='%23798087'/%3E%3C/g%3E%3Cpath fill-rule='evenodd' clip-rule='evenodd' d='M212.162 187.903h-7.518a4.031 4.031 0 0 0-4.031 4.031v61.999a4.031 4.031 0 0 0 4.031 4.031h31.846a4.031 4.031 0 0 0 4.031-4.031v-61.999a4.03 4.03 0 0 0-4.031-4.031h-7.478a2.73 2.73 0 0 1-2.73 2.73h-11.391a2.73 2.73 0 0 1-2.729-2.73Z' fill='%23fff'/%3E%3Crect x='215.134' y='198.924' width='16.179' height='3.547' rx='1.774' fill='%23DFE0E3'/%3E%3Crect x='215.134' y='209.601' width='12.592' height='3.547' rx='1.774' fill='%23DFE0E3'/%3E%3Crect x='215.134' y='220.242' width='16.179' height='3.547' rx='1.774' fill='%23DFE0E3'/%3E%3Crect x='215.134' y='230.918' width='12.592' height='3.547' rx='1.774' fill='%23DFE0E3'/%3E%3Crect x='215.134' y='241.561' width='16.025' height='3.547' rx='1.774' fill='%23DFE0E3'/%3E%3Crect x='205.046' y='197.657' width='6.917' height='6.917' rx='3.459' fill='%232FC6F6'/%3E%3Crect x='205.046' y='208.121' width='6.917' height='6.917' rx='3.459' fill='%239DCF00'/%3E%3Crect x='205.046' y='218.586' width='6.917' height='6.917' rx='3.459' fill='%2355D0E0'/%3E%3Crect x='205.046' y='229.052' width='6.917' height='6.917' rx='3.459' fill='%23FFA900'/%3E%3Crect x='205.046' y='239.516' width='6.917' height='6.917' rx='3.459' fill='%23FF5752'/%3E%3Cdefs%3E%3Cfilter id='b' x='.126' y='93.132' width='303.458' height='228.963' filterUnits='userSpaceOnUse' color-interpolation-filters='sRGB'%3E%3CfeFlood flood-opacity='0' result='BackgroundImageFix'/%3E%3CfeColorMatrix in='SourceAlpha' values='0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0' result='hardAlpha'/%3E%3CfeOffset dy='9.069'/%3E%3CfeGaussianBlur stdDeviation='15.116'/%3E%3CfeComposite in2='hardAlpha' operator='out'/%3E%3CfeColorMatrix values='0 0 0 0 0.025382 0 0 0 0 0.275095 0 0 0 0 0.358333 0 0 0 0.06 0'/%3E%3CfeBlend in2='BackgroundImageFix' result='effect1_dropShadow_3048_267014'/%3E%3CfeBlend in='SourceGraphic' in2='effect1_dropShadow_3048_267014' result='shape'/%3E%3C/filter%3E%3Cfilter id='d' x='65.975' y='153.074' width='120.287' height='56.434' filterUnits='userSpaceOnUse' color-interpolation-filters='sRGB'%3E%3CfeFlood flood-opacity='0' result='BackgroundImageFix'/%3E%3CfeColorMatrix in='SourceAlpha' values='0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0' result='hardAlpha'/%3E%3CfeOffset dy='4.031'/%3E%3CfeGaussianBlur stdDeviation='3.023'/%3E%3CfeComposite in2='hardAlpha' operator='out'/%3E%3CfeColorMatrix values='0 0 0 0 0.321569 0 0 0 0 0.360784 0 0 0 0 0.411765 0 0 0 0.1 0'/%3E%3CfeBlend in2='BackgroundImageFix' result='effect1_dropShadow_3048_267014'/%3E%3CfeBlend in='SourceGraphic' in2='effect1_dropShadow_3048_267014' result='shape'/%3E%3C/filter%3E%3Cfilter id='e' x='118.943' y='94.3' width='126.979' height='185.51' filterUnits='userSpaceOnUse' color-interpolation-filters='sRGB'%3E%3CfeFlood flood-opacity='0' result='BackgroundImageFix'/%3E%3CfeColorMatrix in='SourceAlpha' values='0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0' result='hardAlpha'/%3E%3CfeOffset dy='4.031'/%3E%3CfeGaussianBlur stdDeviation='4.031'/%3E%3CfeComposite in2='hardAlpha' operator='out'/%3E%3CfeColorMatrix values='0 0 0 0 0.183456 0 0 0 0 0.291667 0 0 0 0 0.0267361 0 0 0 0.1 0'/%3E%3CfeBlend in2='BackgroundImageFix' result='effect1_dropShadow_3048_267014'/%3E%3CfeBlend in='SourceGraphic' in2='effect1_dropShadow_3048_267014' result='shape'/%3E%3C/filter%3E%3Cfilter id='g' x='189.004' y='178.308' width='63.126' height='93.279' filterUnits='userSpaceOnUse' color-interpolation-filters='sRGB'%3E%3CfeFlood flood-opacity='0' result='BackgroundImageFix'/%3E%3CfeColorMatrix in='SourceAlpha' values='0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0' result='hardAlpha'/%3E%3CfeOffset dy='2.015'/%3E%3CfeGaussianBlur stdDeviation='4.031'/%3E%3CfeComposite in2='hardAlpha' operator='out'/%3E%3CfeColorMatrix values='0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.15 0'/%3E%3CfeBlend in2='BackgroundImageFix' result='effect1_dropShadow_3048_267014'/%3E%3CfeBlend in='SourceGraphic' in2='effect1_dropShadow_3048_267014' result='shape'/%3E%3C/filter%3E%3ClinearGradient id='c' x1='151.855' y1='114.294' x2='151.855' y2='282.794' gradientUnits='userSpaceOnUse'%3E%3Cstop stop-color='%23fff'/%3E%3Cstop offset='1' stop-color='%23fff' stop-opacity='.74'/%3E%3C/linearGradient%3E%3ClinearGradient id='f' x1='182.432' y1='98.331' x2='182.432' y2='267.718' gradientUnits='userSpaceOnUse'%3E%3Cstop stop-color='%23ACE632'/%3E%3Cstop offset='1' stop-color='%23ACE632' stop-opacity='.66'/%3E%3C/linearGradient%3E%3C/defs%3E%3C/svg%3E");
	}

	.left-menu-new-structure-title-box {
		font-family: "Open Sans", "Helvetica Neue", Helvetica, Arial, sans-serif;
		flex: 1;
		display: flex;
		justify-content: center;
		height: 100%;
		flex-direction: column;
		white-space: normal;
		align-items: flex-end;
	}

	.left-menu-new-structure-title {
		width: 50%;
		color: #333333;
		font-size: 29px;
		line-height: 36px;
		box-sizing: border-box;
		padding-right: 30px;
	}

	.left-menu-new-structure-hint {
		width: 50%;
		color: rgba(51, 51, 51, 0.4);
		margin-top: 20px;
		font-size: 19px;
		box-sizing: border-box;
		padding-right: 30px;
	}
</style>

<?
$currentDir = $this->getFolder() . '/whats-new';

$getVideo = function($code) use($currentDir) {
	if (LANGUAGE_ID === 'ru')
	{
		return [
			'attrs' => [
				'poster' => $currentDir . '/screenshots/ru/' . $code . '.png',
			],
			'sources' => [
				['src' => '//video.1c-bitrix.ru/bitrix24/whats-new/' . $code . '.mp4', 'type' => 'video/mp4'],
				['src' => '//video.1c-bitrix.ru/bitrix24/whats-new/' . $code . '.webm', 'type' => 'video/webm'],
			],
		];
	}

	return Loc::getMessage('LEFT_MENU_WHATS_NEW_VIDEO_' . strtoupper($code));
};

?>
<script>
setTimeout(() => {
	if (BX.Main && BX.Main.PopupManager && BX.Main.PopupManager.isAnyPopupShown())
	{
		return;
	}

	BX.Runtime.loadExtension('ui.dialogs.whats-new').then(exports => {
		const { WhatsNew } = exports;
		const popup = new WhatsNew({
			slides: [
				{
					className: 'left-menu-new-structure-bg',
					html: `
						<div class="left-menu-new-structure-title-box">
							<div class="left-menu-new-structure-title"><?=getMessageJs('LEFT_MENU_WHATS_NEW_WELCOME_TEXT')?></div>
							<div class="left-menu-new-structure-hint"><?=getMessageJs('LEFT_MENU_WHATS_NEW_WELCOME_SUBTEXT')?></div>
						</div>
					`,
				},
				{
					title: '<?=getMessageJs('LEFT_MENU_WHATS_NEW_CRM_TITLE')?>',
					description: '<?=getMessageJs('LEFT_MENU_WHATS_NEW_CRM_DESC')?>',
					video: <?=\CUtil::phpToJSObject($getVideo('crm'))?>,
					autoplay: true,
				},
				{
					title: '<?=getMessageJs('LEFT_MENU_WHATS_NEW_SITES_TITLE')?>',
					video: <?=\CUtil::phpToJSObject($getVideo('sites'))?>,
					autoplay: true,
				},
				{
					title: '<?=getMessageJs('LEFT_MENU_WHATS_NEW_AUTOMATION_TITLE')?>',
					description: '<?=getMessageJs('LEFT_MENU_WHATS_NEW_AUTOMATION_DESC')?>',
					image: '<?=($currentDir . getMessageJs('LEFT_MENU_WHATS_NEW_SCREENSHOT_AUTOMATION'))?>',
				},
				{
					title: '<?=getMessageJs('LEFT_MENU_WHATS_NEW_COMPANY_TITLE')?>',
					description: '<?=getMessageJs('LEFT_MENU_WHATS_NEW_COMPANY_DESC')?>',
					image: '<?=($currentDir . getMessageJs('LEFT_MENU_WHATS_NEW_SCREENSHOT_COMPANY'))?>',
				},
				{
					title: '<?=getMessageJs('LEFT_MENU_WHATS_NEW_COLLABORATION_TITLE')?>',
					video: <?=\CUtil::phpToJSObject($getVideo('collaboration'))?>,
					autoplay: true,
				},
			],
			popupOptions: {
				width: 720,
				height: 530,
			},
			events: {
				onDestroy: () => {

					BX.userOptions.save('intranet', 'left_menu_whats_new_dialog', 'closed', 'Y');

					BX.Runtime.loadExtension('ui.tour').then((exports) => {
						const { Guide } = exports;
						const guide = new Guide({
							onEvents: true,
							steps: [
								{
									target: '#search-textbox-input',
									text: '<?=getMessageJs('LEFT_MENU_WHATS_NEW_SEARCH_HINT')?>',
								},
							],
						});

						guide.getPopup().setAutoHide(true);
						guide.showNextStep();
						guide.getPopup().setOffset( { offsetLeft: 35 });
						guide.getPopup().setAngle( { offset: 0 });
						guide.getPopup().adjustPosition();

					});
				},
			},
		});

		popup.show();
	});
}, 1000);
</script>

<?
$dynamicArea->finishDynamicArea();
<?php

/** @var array<string, mixed> $arParams */
/** @var array<string, mixed> $arResult */

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use Bitrix\UI\Buttons\Button;
use Bitrix\UI\Buttons\Color;
use Bitrix\UI\Buttons\Size;
use Bitrix\Disk\Document\Flipchart\Configuration;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die;

$containerId = 'flipchart-wrapper';

Loader::includeModule('socialnetwork');

Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.buttons',
	'popup',
	'main.core',
	'disk.sharing-legacy-popup',
	'disk.external-link',
	'loader',
	'socnetlogdest',
]);

$APPLICATION->SetTitle($arResult['DOCUMENT_NAME']);

$isMobile = $arResult['DISPLAY_VARIANT'] === 'mobile';

$sharingButtonHtml = '';
if (!$isMobile && $arResult['SHOULD_SHOW_SHARING_BUTTON'])
{
	$setupSharingButton = Button::create();
	$wayToSharing = [
		[
			'id' => 'ext-link',
			'html' => '<div class="disk-fe-office-access-setting-popup-icon-box">'
				. '<div class="ui-icon-set --share-1"></div>'
				. '<div> ' . Loc::getMessage("DISK_BOARDS_HEADER_BTN_SHARING_EXT_LINK_MSGVER_1") . ' </div>'
				. '</div>',
			'dataset' => [
				'shouldBlockExternalLinkFeature' => (int)$arResult['SHOULD_BLOCK_EXTERNAL_LINK_FEATURE'],
				'blockerExternalLinkFeature' => $arResult['BLOCKER_EXTERNAL_LINK_FEATURE'] ?: '',
			],
		],
		[
			'id' => 'sharing',
			'html' => '<div class="disk-fe-office-access-setting-popup-icon-box">'
				. '<div class="ui-icon-set --person-plus-3"></div>'
				. '<div> ' . Loc::getMessage('DISK_BOARDS_HEADER_BTN_SHARING_SHARE_MSGVER_1') . ' </div>'
				. '</div>',
		],
	];

	$setupSharingButton
		->setText(Loc::getMessage('DISK_FLIPCHART_EDITOR_ACCESS_RIGHTS_MSGVER_1'))
		->addClass('disk-fe-flipchart-btn-access-setting')
		->setSize(Size::SMALL)
		->setColor(Color::PRIMARY)
		->setRound()
		->setDropdown()
		->setMenu([
			'className' => 'disk-fe-flipchart__popup',
			'autoHide' => true,
			'closeEsc' => true,
			'offsetTop' => 5,
			'offsetLeft' => 0,
			'animation' => 'fading-slide',
			'overlay' => [
				'opacity' => 0,
			],
			'disableScroll' => true,
			'items' => $wayToSharing,
		])
	;

	$sharingButtonHtml = $setupSharingButton->render(false);
}
?>

<div data-id="<?= $containerId ?>-wrapper">
	<?php if (!$isMobile)
		{ ?>
	<div class="disk-fe-office-header">
		<div class="disk-fe-office-header-left">
			<div class="disk-fe-flipchart-header-logo-box">
				<?php
				if ($arResult['LANGUAGE'] === 'ru')
				{
				?>
					<a href="<?= $arResult['HEADER_LOGO_URL'] ?>" class="disk-fe-flipchart-header-logo" target="_blank"></a>
				<?php
				}
				else
				{
				?>
					<a href="<?= $arResult['HEADER_LOGO_URL'] ?>" class="disk-fe-flipchart-header-logo --eng" target="_blank"></a>
				<?php
				}
				?>
				<span class="disk-fe-flipchart-header-logo-name"><?= Loc::getMessage('DISK_FLIPCHART_EDITOR_HEADER_BOARDS') ?></span>
			</div>
			<div class="disk-fe-office-header-mode">
				<span class="disk-fe-office-header-mode-text"><?= htmlspecialcharsbx($arResult['DOCUMENT_NAME']) ?></span>
			</div>
		</div>
		<div class="disk-fe-office-header-right">
			<div class="disk-fe-office-header-online"><?= Loc::getMessage('DISK_FLIPCHART_EDITOR_AUTOSAVE') ?></div>
			<?= $sharingButtonHtml?>
		</div>
	</div>
	<?php } ?>
	<div data-id="<?= $containerId ?>" style="height: calc(<?=$isMobile ? '100dvh' : '100vh - 70px' ?>);">
		<div class="boards-editor-wrapper" id="flipchart-editor" style="height: 100%"></div>
	</div>
</div>

<?php
if (Configuration::isReloadBoardAfterInactivityEnabled())
{
?>
<script>

	let lastActiveTime = Date.now();

	document.addEventListener("visibilitychange", function() {
		if (document.visibilityState === "visible") {
			if (Date.now() - lastActiveTime > <?=Configuration::getReloadBoardAfterInactivityDelay() ?>)
			{
				window.document.location.reload();
			}
		}
	});

	document.addEventListener("mousemove", function() {
		console.log('move');
		if (Date.now() - lastActiveTime > <?=(Configuration::getReloadBoardAfterInactivityDelay() * 2) ?>)
		{
			window.document.location.reload();
		}
		lastActiveTime = Date.now();
	});

	window.addEventListener("message", e => {
		if (e.data?.event === "boardChanged" && document.visibilityState === "visible")
		{
			lastActiveTime = Date.now();
		}
	})

</script>
<?php
}
?>

<?php
if ($isMobile)
{
?>
<script>
	let m = document.querySelector('meta[name="viewport"]');
	if (!m)
	{
		m = document.createElement('meta');
		m.name = 'viewport';
		document.querySelector('head').append(m);
	}
	m.content = 'width=400px, initial-scale=0.8';
</script>
<?php } ?>

<script>

	let url = new URL(location.href);
	url.searchParams.delete('c_element');
	const boardElementId = url.searchParams.get('elementId');
	url.searchParams.delete('elementId');
	history.replaceState(null, '', url.toString());

	new BX.Disk.Flipchart.Board({
		panelButtonUniqIds: {
			setupSharing: '<?= isset($setupSharingButton) ? $setupSharingButton->getUniqId() : '' ?>',
		},
		boardData: {
			id: <?= $arResult['ORIGINAL_DOCUMENT_ID'] ?>,
			name: '<?= \CUtil::JSEscape($arResult['DOCUMENT_NAME']) ?>',
			uniqueCode: '<?= \CUtil::JSEscape($arResult['FILE_UNIQUE_CODE']) ?>',
		},
		sharingControlType: '<?= $arResult['SHARING_CONTROL_TYPE'] ?>',
		unifiedLinkAccessOnly: <?= Json::encode($arResult['UNIFIED_LINK_ACCESS_ONLY']) ?>,
	})

	BX.ready(() => {
		const sdk = new BX.Disk.Flipchart.SDK({
			containerId: 'flipchart-editor',
			appUrl: '<?= $arResult['APP_URL'] ?>',
			token: '<?= $arResult['TOKEN'] ?>',
			lang: '<?= $arResult['LANGUAGE'] ?>',
			boardUrl: window.location.origin + window.location.pathname,
			ui: {
				colorTheme: 'flipBitrixLight',
				openTemplatesModal: <?= Json::encode($arResult['SHOW_TEMPLATES_MODAL']) ?>,
				exportAsFile: true,
				spinner: 'circular',
				userKickable: true,
				confirmUserKick: false,
				disable: false,
				scrollToElement: boardElementId,
			},
			permissions: {
				accessLevel: '<?= $arResult['ACCESS_LEVEL'] ?>',
				editBoard: <?= Json::encode($arResult['EDIT_BOARD']) ?>,
			},
			boardData: {
				fileUrl: '<?= $arResult['DOCUMENT_URL'] ?>',
				documentId: '<?= $arResult['DOCUMENT_ID'] ?>',
				sessionId: '<?= $arResult['SESSION_ID'] ?>',
				fileName: '<?= CUtil::JSEscape($arResult['DOCUMENT_NAME_WITHOUT_EXTENSION']) ?>',
			},
			events: {
				onBoardRenamed(newName) {

					notifyMobileApp({ boardName: newName });

					return BX.ajax.runAction('disk.api.file.rename', {
						data: {
							fileId: <?= $arResult['ORIGINAL_DOCUMENT_ID'] ?>,
							newName: newName + '.board'
						}
					});

				}
			}
		});
		sdk.init();

		notifyMobileApp({boardName: '<?= CUtil::JSEscape($arResult['DOCUMENT_NAME_WITHOUT_EXTENSION']) ?>'});
	})

	function notifyMobileApp({boardName}) {
		if (window.BXMobileApp?.Events) {
			BXMobileApp.Events.postToComponent('onBoardUpdate', { boardName });
		}
	}

	const loader = new BX.Loader({
		target: document.querySelector(".boards-editor-wrapper")
	});

	loader.show();

	window.addEventListener("message", e => {
		if (e.data?.event === "waitSDKParams")
		{
			loader.hide();
		}
	})

</script>

<?php

/** @var array<string, mixed> $arParams */
/** @var array<string, mixed> $arResult */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Disk\Document\Models\DocumentSession;
use Bitrix\UI\Buttons\Button;
use Bitrix\UI\Buttons\Color;
use Bitrix\UI\Buttons\Size;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$containerId = 'flipchart-wrapper';

Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.buttons',
	'popup',
	'main.core',
	'disk.sharing-legacy-popup',
	'disk.external-link',
	'loader',
]);

$APPLICATION->SetTitle($arResult['DOCUMENT_NAME']);

$setupSharingButton = Button::create();
$wayToSharing = [
	[
		'id' => 'ext-link',
		'html' => '<div class="disk-fe-office-access-setting-popup-icon-box">'
			. '<div class="ui-icon-set --share-1"></div>'
			. '<div> ' . Loc::getMessage("DISK_BOARDS_HEADER_BTN_SHARING_EXT_LINK") .' </div>'
			.'</div>',
		'dataset' => [
			'shouldBlockExternalLinkFeature' => (int)$arResult['SHOULD_BLOCK_EXTERNAL_LINK_FEATURE'],
			'blockerExternalLinkFeature' => $arResult['BLOCKER_EXTERNAL_LINK_FEATURE'] ?: '',
		],
	],
	[
		'id' => 'sharing',
		'html' => '<div class="disk-fe-office-access-setting-popup-icon-box">'
			. '<div class="ui-icon-set --person-plus-3"></div>'
			. '<div> ' . Loc::getMessage('DISK_BOARDS_HEADER_BTN_SHARING_SHARE') .' </div>'
			.'</div>',
	],
];

$setupSharingButton
	->setText(Loc::getMessage('DISK_FLIPCHART_EDITOR_ACCESS_RIGHTS'))
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
		'items' => $wayToSharing
	])
;
?>

<div data-id="<?= $containerId ?>-wrapper">
	<div class="disk-fe-office-header">
		<div class="disk-fe-office-header-left">
			<div class="disk-fe-flipchart-header-logo-box">
				<a href="<?=$arResult['HEADER_LOGO_URL'] ?>" class="disk-fe-flipchart-header-logo" target="_blank"></a>
				<span class="disk-fe-flipchart-header-logo-name"><?= Loc::getMessage('DISK_FLIPCHART_EDITOR_HEADER_BOARDS') ?></span>
			</div>
			<div class="disk-fe-office-header-mode">
				<span class="disk-fe-office-header-mode-text"><?= htmlspecialcharsbx($arResult['DOCUMENT_NAME']) ?></span>
			</div>
		</div>
		<div class="disk-fe-office-header-right">
			<div class="disk-fe-office-header-online"><?= Loc::getMessage('DISK_FLIPCHART_EDITOR_AUTOSAVE') ?></div>
			<?php
				if ($arResult['DOCUMENT_SESSION']->getType() === DocumentSession::TYPE_EDIT)
				{
					echo $setupSharingButton->render(false);
				}
			?>
		</div>
	</div>
	<div data-id="<?= $containerId ?>" style="height: calc(100vh - 70px);">
		<div class="boards-editor-wrapper" id="flipchart-editor" style="height: 100%"></div>
	</div>
</div>

<script>
	new BX.Disk.Flipchart.Board({
		panelButtonUniqIds: {
			setupSharing: '<?= $setupSharingButton->getUniqId() ?>',
		},
		boardData: {
			id: <?= $arResult['ORIGINAL_DOCUMENT_ID'] ?>,
			name: '<?= \CUtil::JSEscape($arResult['DOCUMENT_NAME']) ?>',
		},
	})

	BX.ready(() => {
		const sdk = new BX.Disk.Flipchart.SDK({
			containerId: 'flipchart-editor',
			appUrl: '<?=$arResult['APP_URL'] ?>',
			token: '<?=$arResult['TOKEN'] ?>',
			lang: '<?=$arResult['LANGUAGE'] ?>',
			ui: {
				colorTheme: 'flipBitrixLight',
				openTemplatesModal: <?=$arResult['SHOW_TEMPLATES_MODAL'] ? 'true' : 'false' ?>,
				exportAsFile: false,
				spinner: 'circular',
			},
			permissions: {
				accessLevel: '<?=$arResult['ACCESS_LEVEL'] ?>',
				editBoard: <?=$arResult['EDIT_BOARD'] ? 'true' : 'false' ?>,
			},
			boardData: {
				fileUrl: '<?=$arResult['DOCUMENT_URL'] ?>',
				documentId: '<?=$arResult['DOCUMENT_ID'] ?>',
				sessionId: '<?=$arResult['SESSION_ID'] ?>',
				fileName: '<?=CUtil::JSEscape($arResult['DOCUMENT_NAME_WITHOUT_EXTENSION']) ?>',
			},
			events: {
				onFlipRenamed(newName) {
					console.log('Flip Renamed', newName)

					return BX.ajax.runAction('disk.api.file.rename', {
						data: {
							fileId: <?=$arResult['ORIGINAL_DOCUMENT_ID'] ?>,
							newName: newName + '.board'
						}
					});

				}
			}
		});
		sdk.init();
	})

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

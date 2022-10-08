<?php

/** @var $this CBitrixComponentTemplate */
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var BaseComponent $component */

use Bitrix\Disk\Document\GoogleHandler;
use Bitrix\Disk\Document\LocalDocumentController;
use Bitrix\Disk\Document\Office365Handler;
use Bitrix\Disk\Document\OnlyOffice\Editor\ConfigBuilder;
use Bitrix\Disk\Document\OnlyOffice\OnlyOfficeHandler;
use Bitrix\Disk\Internals\BaseComponent;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;
use Bitrix\UI\Buttons\Button;
use Bitrix\UI\Buttons\Color;
use Bitrix\UI\Buttons\Icon;
use Bitrix\UI\Buttons\Size;
use Bitrix\UI\Buttons\Tag;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arResult */
Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'disk',
	'disk.document',
	'im',
	'disk.viewer.actions',
	'disk.users',
	'disk.sharing-legacy-popup',
	'disk.onlyoffice-promo-popup',
	'disk.external-link',
	'ui.forms',
	'ui.alerts',
	'main.loader',
	'pull.client',
	'ui.info-helper',
	'ui.buttons',
	'ui.buttons.icons',
	'ui.icons.b24',
	'ui.notification',
	'popup',
    'ui.dialogs.messagebox',
]);

Asset::getInstance()->addString('<script type="text/javascript" src="' . $arResult['SERVER'] . '/web-apps/apps/api/documents/api.js"></script>');
$containerId = 'editorForm'.$this->randString();
$headerText = Loc::getMessage('DISK_FILE_EDITOR_ONLYOFFICE_HEADER_MODE_VIEW');
if ($arResult['EDITOR']['MODE'] === ConfigBuilder::MODE_EDIT)
{
	$headerText = Loc::getMessage('DISK_FILE_EDITOR_ONLYOFFICE_HEADER_MODE_EDIT');
}

$editMode = $arResult['EDITOR']['MODE'] === ConfigBuilder::MODE_EDIT;
if ($arResult['EXTERNAL_LINK_MODE'])
{
    $editButton = Bitrix\UI\Buttons\SaveButton::create();
    $editButton->setRound();
}
else
{
    $editButton = Bitrix\UI\Buttons\Split\SaveButton::create([
        'classList' => ['ui-btn-round', 'disk-fe-office-btn-edit'],
    ]);
    $badgeNew = Loc::getMessage('DISK_FILE_EDITOR_ONLYOFFICE_BADGE_NEW_EDITOR_BITRIX24');
    $editButtonItems = [];

	foreach ($arResult['DOCUMENT_HANDLERS'] as $i => $documentHandler)
	{
        $editButtonItems[$i] = [
	        'id' => $documentHandler['code'],
	        'text' => $documentHandler['name'],
	        'className' => "disk-fe-office-edit-popup-item disk-fe-office-icon-{$documentHandler['code']}",
        ];

		if ($documentHandler['code'] === OnlyOfficeHandler::getCode())
		{
		    $editButtonItems[$i]['html'] = "<span class=\"disk-fe-office-edit-badge\">{$badgeNew}</span><span>{$documentHandler['name']}</span>";
            unset($editButtonItems[$i]['text']);
		}
	}
    $editButton->setMenu([
        'items' => $editButtonItems,
    ]);
}

$editButton
    ->setText(Loc::getMessage('DISK_FILE_EDITOR_ONLYOFFICE_HEADER_BTN_EDIT'))
    ->setIcon(Icon::EDIT)
    ->setSize(Size::SMALL)
;

if (!$arResult['EDITOR']['ALLOW_EDIT'])
{
	$editButton = Button::create()
        ->setRound()
        ->setDisabled()
        ->setIcon(Icon::EDIT)
		->setText(Loc::getMessage('DISK_FILE_EDITOR_ONLYOFFICE_HEADER_BTN_EDIT'))
		->setTag(Tag::LINK)
		->setSize(Size::SMALL)
		->setColor(Color::LIGHT_BORDER)
        ->addAttribute('title', Loc::getMessage('DISK_FILE_EDITOR_ONLYOFFICE_HEADER_BTN_EDIT_LACK_PERM'))
	;
}

$setupSharingButton = Button::create();
$wayToSharing = [
	[
		'id' => 'ext-link',
		'text' => Loc::getMessage('DISK_FILE_EDITOR_ONLYOFFICE_HEADER_BTN_SHARING_EXT_LINK'),
		'className' => 'disk-fe-office-access-setting-popup-item disk-fe-office-icon-docs',
		'dataset' => [
			'shouldBlockExternalLinkFeature' => (int)$arResult['SHOULD_BLOCK_EXTERNAL_LINK_FEATURE'],
			'blockerExternalLinkFeature' => $arResult['BLOCKER_EXTERNAL_LINK_FEATURE'] ?: '',
		],
	],
	[
		'id' => 'sharing',
		'text' => Loc::getMessage('DISK_FILE_EDITOR_ONLYOFFICE_HEADER_BTN_SHARING_SHARE'),
		'className' => 'disk-fe-office-access-setting-popup-item disk-fe-office-icon-link',
	],
];
if (empty($arResult['SHARING_CONTROL_TYPE']))
{
    unset($wayToSharing[1]);
}

$setupSharingButton
	->setText(Loc::getMessage('DISK_FILE_EDITOR_ONLYOFFICE_HEADER_BTN_SHARING'))
	->addClass('disk-fe-office-header-btn-access-setting')
	->setSize(Size::SMALL)
	->setColor(Color::LIGHT_BORDER)
	->setRound()
	->setDropdown(false)
	->setMenu([
		'items' => $wayToSharing
	])
;

if ($editMode)
{
	$setupSharingButton
		->setColor(Color::PRIMARY)
	;
}
$downloadButton = null;
if ($arResult['EXTERNAL_LINK_MODE'] && !empty($arParams['LINK_TO_DOWNLOAD']))
{
	$setupSharingButton = null;
	$downloadButton = Button::create();
	$downloadButton
        ->setTag(Tag::LINK)
		->setSize(Size::SMALL)
		->setColor(Color::LIGHT_BORDER)
		->setRound()
        ->setText(Loc::getMessage('DISK_FILE_EDITOR_ONLYOFFICE_HEADER_BTN_DOWNLOAD'))
        ->setLink($arParams['LINK_TO_DOWNLOAD'])
        ->addAttribute('target', '_blank')
    ;
}

$headerLogoClass = '';
if (Context::getCurrent()->getLanguage() !== 'ru')
{
    $headerLogoClass = 'disk-fe-office-header-logo--eng';
}
$GLOBALS['APPLICATION']->SetTitle($arResult['OBJECT']['NAME']);
?>

<div data-id="<?= $containerId ?>-wrapper">
	<div class="disk-fe-office-header">
		<div class="disk-fe-office-header-left">
			<a href="<?= $arResult['HEADER_LOGO_LINK'] ?>" class="disk-fe-office-header-logo <?= $headerLogoClass ?>" target="_blank"></a>
			<div class="disk-fe-office-header-mode">
				<span class="disk-fe-office-header-mode-text"><?= $headerText ?></span>
			</div>
		</div>
		<div class="disk-fe-office-header-right">
			<?php if (!$editMode): ?>
				<?= $editButton ? $editButton->render(false) : '' ?>
			<?php endif ?>
            <?= $downloadButton ? $downloadButton->render(false) : '' ?>
			<div class="disk-fe-office-header-control-box">
				<div class="disk-fe-office-header-online"><?= Loc::getMessage('DISK_FILE_EDITOR_ONLYOFFICE_HEADER_ONLINE_LABEL') ?></div>
				<div data-id="<?= $containerId ?>-user-box">
				</div>
				<?= $setupSharingButton ? $setupSharingButton->render(false) : '' ?>
			    <?php if ($arParams['SHOW_BUTTON_OPEN_NEW_WINDOW']): ?>
                    <a href="<?= $arResult['LINK_OPEN_NEW_WINDOW'] ?>" target="_blank" class="disk-fe-office-header-resize-btn" title="<?= Loc::getMessage('DISK_FILE_EDITOR_ONLYOFFICE_HEADER_NEW_TAB') ?>"></a>
			    <?php endif ?>
			</div>
		</div>
	</div>
	<div data-id="<?= $containerId ?>" style="height: calc(100vh - 70px);">
		<div id="<?= $containerId ?>-editor" data-id="<?= $containerId ?>-editor"></div>
	</div>
</div>

<script>
	<?='BX.message(' . \CUtil::PhpToJSObject(Loc::loadLanguageFile(__FILE__)) . ');'?>

	new BX.Disk.Editor.OnlyOffice({
		targetNode: document.querySelector('[data-id="<?= $containerId ?>"]'),
		userBoxNode: document.querySelector('[data-id="<?= $containerId ?>-user-box"]'),
		editorJson: <?= $arResult['EDITOR_JSON'] ?>,
		editorNode: document.querySelector('[data-id="<?= $containerId ?>-editor"]'),
		editorWrapperNode: document.querySelector('[data-id="<?= $containerId ?>-wrapper"]'),
		panelButtonUniqIds: {
			edit: '<?= ($arResult['EDITOR']['ALLOW_EDIT'] && $editButton) ? $editButton->getUniqId() : '' ?>',
			setupSharing: '<?= $setupSharingButton ? $setupSharingButton->getUniqId() : '' ?>'
		},
        pullConfig: <?=CUtil::PhpToJsObject($arResult['PULL_CONFIG'])?>,
		publicChannel: '<?= $arResult['PUBLIC_CHANNEL'] ?>',
        linkToEdit: '<?= $arResult['EDITOR']['ALLOW_EDIT']? $arResult['LINK_TO_EDIT'] : '' ?>',
        linkToView: '<?= $arResult['LINK_OPEN_NEW_WINDOW'] ?>',
        linkToDownload: '<?= $arResult['LINK_TO_DOWNLOAD'] ?>',
		documentSession: {
			id: <?= $arResult['DOCUMENT_SESSION']['ID'] ?>,
			hash: '<?= $arResult['DOCUMENT_SESSION']['HASH'] ?>',
		},
		object: {
			id: <?= $arResult['OBJECT']['ID'] ?>,
			name: '<?= \CUtil::JSEscape($arResult['OBJECT']['NAME']) ?>',
			size: '<?= $arResult['OBJECT']['SIZE'] ?>',
		},
		attachedObject: {
			id: <?= $arResult['ATTACHED_OBJECT']['ID'] ?: 'null' ?>,
		},
        sharingControlType: '<?= $arResult['SHARING_CONTROL_TYPE'] ?>',
		currentUser: <?= $arResult['CURRENT_USER'] ?>
	});
</script>

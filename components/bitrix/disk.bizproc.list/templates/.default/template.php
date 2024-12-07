<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
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
/** @var \Bitrix\Disk\Internals\BaseComponent $component */
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
CJSCore::Init([
	'disk',
	'ui.buttons'
]);

$patternToEditTemplate = $arParams['EDIT_URL'];
$patternToEditTemplate = mb_substr($patternToEditTemplate, 0, mb_strpos($patternToEditTemplate, '#'));
?>
<? $this->setViewTarget("inside_pagetitle", 10); ?>
	<div class="pagetitle-container pagetitle-flexible-space" style="overflow: hidden;">
		<div class="pagetitle-container pagetitle-align-right-container">
			<span id="bx-disk-create-bp" class="ui-btn ui-btn-primary ui-btn-dropdown"><?= Loc::getMessage('DISK_BIZPROC_LIST_CREATE_BP') ?></span>
		</div>
	</div>
<? $this->endViewTarget(); ?>


<? if (empty($arResult['GRID_TEMPLATES'])): ?>
<div class='wd-help-list selected'>
	<?=str_replace("#HREF#",'"'.$APPLICATION->getCurPageParam("action=createDefault&".bitrix_sessid_get(), array("action", "sessid")).'"',Loc::getMessage("WD_EMPTY"))?>
</div>
<? else:
	$APPLICATION->IncludeComponent(
		'bitrix:main.interface.grid',
		'',
		array(
			'GRID_ID' => 'bizproc_list_'.$arParams['DOCUMENT_TYPE'],
			'HEADERS' => array(
				array('id' => 'NAME', 'name' => Loc::getMessage('BPATT_NAME'), 'default' => true),
				array('id' => 'MODIFIED', 'name' => Loc::getMessage('BPATT_MODIFIED'), 'default' => true),
				array('id' => 'USER', 'name' => Loc::getMessage('BPATT_USER'), 'default' => true),
				array('id' => 'AUTO_EXECUTE', 'name' => Loc::getMessage('BPATT_AUTO_EXECUTE'), 'default' => true)
			),
			'SORT' => array('by' => 'name', 'order' => 'asc'),
			'ROWS' => $arResult['GRID_TEMPLATES'],
			'FOOTER' => array(array('title' => Loc::getMessage('BPATT_ALL'), 'value' => count($arResult['GRID_TEMPLATES']))),
			'EDITABLE' => false,
			'ACTIONS' => array(
				'delete' => true
			),
			'ACTION_ALL_ROWS' => false,
			'NAV_OBJECT' => $arResult['NAV_RESULT'],
			'AJAX_MODE' => 'N',
		),
	($this->__component->__parent ? $this->__component->__parent : $component));
endif;

if($arResult['CREATE_NEW_TEMPLATES']):?>
	<br />
	<?=str_replace("#HREF#",'"'.$APPLICATION->getCurPageParam("action=createDefault&".bitrix_sessid_get(), array("action", "sessid")).'"',Loc::getMessage("WD_EMPTY_NEW"))?>
<? endif;
if (Loader::includeModule('bizprocdesigner')):?>
<br />
<div class='wd-help-list selected'>
	<?= Loc::getMessage('BPATT_HELP1_TEXT')?><br />
	<?= Loc::getMessage('BPATT_HELP2_TEXT')?>
</div>
<?endif;
if($arResult['PROMPT_OLD_TEMPLATE']): ?>
<div class='bx-disk-prompt-old-template'>
	<p><?= Loc::getMessage('PROMPT_OLD_TEMPLATE') ?></p>
</div>
<? endif;?>

<script>
	BX(function () {
		new BX.Disk.Component.BizprocList({
			layout: {
				createButton: BX('bx-disk-create-bp')
			},
			createItems: [
				{
					text: "<?= Loc::getMessage("DISK_BIZPROC_STATUS_TEXT") ?>",
					href: "<?= $arParams['CREATE_URL_BLANK'] . '?&init=statemachine' ?>"
				},
				{
					text: "<?= Loc::getMessage("DISK_BIZPROC_SERIAL_TEXT") ?>",
					href: "<?= $arParams['CREATE_URL_BLANK'] ?>"
				}
			],
			linkPatterns: {
				createBlank: "<?= $arParams['CREATE_URL_BLANK'] ?>",
				editUrl: "<?= $patternToEditTemplate ?>"
			}
		});

	});
</script>

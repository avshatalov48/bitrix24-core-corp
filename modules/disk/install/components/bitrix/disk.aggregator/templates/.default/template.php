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

\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/main/public_tools.js');

$buttons = array(
	array(
		"TEXT" => Loc::getMessage("DISK_AGGREGATOR_ND"),
		"TITLE" => Loc::getMessage("DISK_AGGREGATOR_ND"),
		"ID" => "bx-disk-show-network-drive-url",
	),
);
if($USER->isAdmin())
{
	$sNewFolderPath = $APPLICATION->getCurPage(false);
	while(!is_dir(str_replace(array("///", "//"), "/", $_SERVER['DOCUMENT_ROOT'] . $sNewFolderPath)))
	{
		$sNewFolderPath = implode('/', array_slice(explode('/', $sNewFolderPath), 0, -1));
	}
	if(strlen($sNewFolderPath) > 0 && substr($sNewFolderPath, -1, 1) !== '/')
	{
		$sNewFolderPath .= '/';
	}

	$urlCreateLibrary = $APPLICATION->getPopupLink(array(
		'URL' => '/bitrix/admin/public_file_new.php?' . http_build_query(array(
			'wiz_template' => 'disk_common',
			'lang' => LANGUAGE_ID,
			'site' => LANG,
			'newFolder' => 'Y',
			'path' => $sNewFolderPath,
			'back_url' => $APPLICATION->getCurPage()
		))
	));

	$buttons[] = array(
		"TEXT" => Loc::getMessage("DISK_AGGREGATOR_CREATE_STORAGE"),
		"TITLE" => Loc::getMessage("DISK_AGGREGATOR_CREATE_STORAGE"),
		"LINK" => 'javascript:' . $urlCreateLibrary,
	);
}
$APPLICATION->includeComponent('bitrix:disk.interface.toolbar', '', array(
	'TOOLBAR_ID' => 'agg_toolbar',
	'BUTTONS' => $buttons,
), $component, array('HIDE_ICONS' => 'Y'));
?>
<br />
<div class="bx-disk-aggregator-common-div">
	<ul>
		<?
		foreach ($arResult["COMMON_DISK"] as $key=>$data):
			if ($key == 'GROUP' || $key == 'USER' || $key == 'EXTRANET_USER'): ?>
				<li class="bx-disk-aggregator-list-folder">
					<img src="/bitrix/images/disk/default_folder.png" class="bx-disk-aggregator-icon-main" />
					<p class="bx-disk-aggregator-p-link" id="<?= $data["ID"] ?>"><?= htmlspecialcharsbx($data["TITLE"]) ?></p>
				</li>
			<?
			else:
			?>
				<li class="bx-disk-aggregator-list">
					<img src="<?= $data["ICON"] ?>" class="bx-disk-aggregator-icon-main" />
					<a class="bx-disk-aggregator-a-link" href="<?= $data["URL"] ?>"><?= htmlspecialcharsbx($data["TITLE"]) ?></a>
				</li>
			<?endif;
		endforeach;
		?>
	</ul>
</div>

<input type="hidden" id="bx-disk-da-site-id" value="<?= SITE_ID ?>" />
<input type="hidden" id="bx-disk-da-site-dir" value="<?= SITE_DIR ?>" />

<div id='bx-disk-group-div' style="display:none;" class="bx-disk-aggregator-group-div"></div>
<div id='bx-disk-user-div' style="display:none;" class="bx-disk-aggregator-group-div"></div>
<div id='bx-disk-extranet-user-div' style="display:none;" class="bx-disk-aggregator-group-div"></div>

<div class="bx-disk-aggregator-description-div">
	<p><?=Loc::getMessage("DISK_AGGREGATOR_DESCRIPTION") ?></p>
	<p><?=Loc::getMessage("DISK_AGGREGATOR_NETWORK_DRIVE") ?></p>
</div>

<? $linkOnNetworkDrive = CUtil::JSescape($arResult["NETWORK_DRIVE_LINK"]); ?>

<script>
	BX(function () {
		BX.Disk['AggregatorClass_<?= $component->getComponentId() ?>'] = new BX.Disk.AggregatorClass({});
		BX.bind(BX('bx-disk-aggregator-user-link'), 'click', function()
		{
			BX.hide(BX('bx-disk-group-div'));
			BX.hide(BX('bx-disk-extranet-user-div'));
			BX.Disk['AggregatorClass_<?= $component->getComponentId() ?>'].getListStorage('getListStorage', 'user');
		});
		BX.bind(BX('bx-disk-aggregator-extranet-user-link'), 'click', function()
		{
			BX.hide(BX('bx-disk-group-div'));
			BX.hide(BX('bx-disk-user-div'));
			BX.Disk['AggregatorClass_<?= $component->getComponentId() ?>'].getListStorage('getListStorage', 'extranet-user');
		});
		BX.bind(BX('bx-disk-aggregator-group-link'), 'click', function()
		{
			BX.hide(BX('bx-disk-user-div'));
			BX.hide(BX('bx-disk-extranet-user-div'));
			BX.Disk['AggregatorClass_<?= $component->getComponentId() ?>'].getListStorage('getListStorage', 'group');
		});
		BX.bind(BX('bx-disk-show-network-drive-url'), 'click', function(e)
		{
			BX.Disk['AggregatorClass_<?= $component->getComponentId() ?>'].showNetworkDriveConnect({link: '<?= $linkOnNetworkDrive ?>'});
			return BX.PreventDefault(e);
		});
	});
	BX.message({
		DISK_AGGREGATOR_TITLE_NETWORK_DRIVE: '<?= GetMessageJS("DISK_AGGREGATOR_TITLE_NETWORK_DRIVE") ?>',
		DISK_AGGREGATOR_TITLE_NETWORK_DRIVE_DESCR_MODAL: '<?= GetMessageJS("DISK_AGGREGATOR_TITLE_NETWORK_DRIVE_DESCR_MODAL") ?>',
		DISK_AGGREGATOR_BTN_CLOSE: '<?= GetMessageJS("DISK_AGGREGATOR_BTN_CLOSE") ?>'
	});
</script>
<?
$APPLICATION->IncludeComponent('bitrix:disk.help.network.drive','');
?>
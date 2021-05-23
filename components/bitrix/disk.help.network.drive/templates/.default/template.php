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

$serverParams = $arResult["SERVER_PARAMS"];
if ($serverParams['CLIENT_OS'] == 'Windows XP' || in_array($serverParams['CLIENT_OS'], array('Windows 2000', 'Windows 2003')))
{
	if (!$serverParams['SECURE']): ?>
		<div id="bx-disk-network-drive-full" style="display:none;" class="bx-disk-network-drive-full-style">
			<p>
				<a href="javascript:void(0);" class="" id="bx-disk-network-drive-link">
					<?= Loc::getMessage("DISK_NETWORK_DRIVE_SHAREDDRIVE_TITLE");?>
				</a>
			</p>
			<div class="bx-disk-network-drive-style" id="bx-disk-network-drive" style="display:none;">
				<?=
					Loc::getMessage('DISK_NETWORK_DRIVE_CONNECTOR_HELP_MAPDRIVE',
						array(
							'#TEMPLATE_FOLDER#' => $templateFolder,
							'#TEMPLATE_LINK#' => $arResult["TEMPLATE_LINK"])
					);
				?>
			</div>
			<? if ($serverParams['AUTH_MODE'] == 'BASIC'): ?>
				<p><?= Loc::getMessage("DISK_NETWORK_DRIVE_REGISTERPATCH", array("#LINK#" => "/bitrix/webdav/xp.reg"));?></p>
			<? endif;?>
		</div>
	<? endif;
}
elseif ($serverParams['CLIENT_OS'] == 'Windows 2008' || $serverParams['CLIENT_OS'] == 'Windows Vista')
{ ?>
	<div id="bx-disk-network-drive-full" style="display:none;" class="bx-disk-network-drive-full-style">
		<p>
			<a href="javascript:void(0);" class="" id="bx-disk-network-drive-link">
				<?= Loc::getMessage("DISK_NETWORK_DRIVE_SHAREDDRIVE_TITLE");?>
			</a>
		</p>
		<div class="bx-disk-network-drive-style" id="bx-disk-network-drive" style="display:none;">
			<?=
				Loc::getMessage('DISK_NETWORK_DRIVE_CONNECTOR_HELP_MAPDRIVE',
					array(
						'#TEMPLATE_FOLDER#' => $templateFolder,
						'#TEMPLATE_LINK#' => $arResult["TEMPLATE_LINK"])
				);
			?>
		</div>
		<? if ($serverParams['AUTH_MODE'] == 'BASIC'): ?>
			<p><?= Loc::getMessage("DISK_NETWORK_DRIVE_REGISTERPATCH", array("#LINK#" => "/bitrix/webdav/vista.reg"));?></p>
		<? endif;?>
	</div>
<? }
elseif ($serverParams['CLIENT_OS'] == 'Windows 7' || $serverParams['CLIENT_OS'] == 'Windows 8')
{?>
	<div id="bx-disk-network-drive-full" style="display:none;" class="bx-disk-network-drive-full-style">
		<? if ($serverParams['SECURE']): ?>
			<label class="bx-disk-popup-label" id="bx-disk-network-drive-secure-label"><?= Loc::getMessage("DISK_NETWORK_DRIVE_USECOMMANDLINE");?></label>
			<input type="text" class="bx-disk-popup-input" id="bx-disk-network-drive-input-secure" value="net use z: <?= $arResult["NETWORK_DRIVE_LINK"] ?> /user:<?= $arResult["USER_LOGIN"] ?> *" />
			<div id="bx-disk-network-drive"></div>
		<? else: ?>
			<p>
				<a href="javascript:void(0);" class="" id="bx-disk-network-drive-link">
					<?= Loc::getMessage("DISK_NETWORK_DRIVE_SHAREDDRIVE_TITLE");?>
				</a>
			</p>
			<div class="bx-disk-network-drive-style" id="bx-disk-network-drive" style="display:none;">
				<?=
					Loc::getMessage('DISK_NETWORK_DRIVE_CONNECTOR_HELP_MAPDRIVE',
					array(
						'#TEMPLATE_FOLDER#' => $templateFolder,
						'#TEMPLATE_LINK#' => $arResult["TEMPLATE_LINK"])
					);
				?>
			</div>
		<? endif; ?>
		<? if ($serverParams['AUTH_MODE'] == 'BASIC'): ?>
			<p><?= Loc::getMessage("DISK_NETWORK_DRIVE_REGISTERPATCH", array("#LINK#" => "/bitrix/webdav/vista.reg"));?></p>
		<? endif;?>
	</div>
<?}
elseif ($serverParams['CLIENT_OS'] == 'Linux')
{ ?>
	<div id="bx-disk-network-drive-full" style="display:none;" class="bx-disk-network-drive-full-style">
		<div id="bx-disk-network-drive"></div>
	</div>
<? }
elseif ($serverParams['CLIENT_OS'] == 'Mac')
{ ?>
	<div id="bx-disk-network-drive-full" style="display:none;" class="bx-disk-network-drive-full-style">
		<a href="javascript:void(0);" class="" id="bx-disk-network-drive-link">
			<?= Loc::getMessage("DISK_NETWORK_DRIVE_MACOS_TITLE");?>
		</a>
		<div class="bx-disk-network-drive-style" id="bx-disk-network-drive" style="display:none;">
			<?= Loc::getMessage('DISK_NETWORK_DRIVE_HELP_OSX', array('#TEMPLATE_FOLDER#' => $templateFolder)); ?>
		</div>
	</div>
<? }
elseif ($serverParams['CLIENT_OS'] == 'Windows')
{ ?>
	<div id="bx-disk-network-drive-full" style="display:none;" class="bx-disk-network-drive-full-style">
		<div id="bx-disk-network-drive"></div>
	</div>
<? }
else
{ ?>
	<div id="bx-disk-network-drive-full" style="display:none;" class="bx-disk-network-drive-full-style">
		<div id="bx-disk-network-drive"></div>
	</div>
<? } ?>
<script>
	BX(function () {
		BX.Disk['HelpNetworkDriveClass_<?= $component->getComponentId() ?>'] = new BX.Disk.HelpNetworkDriveClass({});
		BX.bind(BX('bx-disk-network-drive-link'), 'click', function()
		{
			BX.Disk['HelpNetworkDriveClass_<?= $component->getComponentId() ?>'].showContent(BX('bx-disk-network-drive'));
		});
	});
	function ShowImg(sImgPath, width, height, alt)
	{
		var scroll = 'no';
		var top=0, left=0;
		if(width > screen.width-10 || height > screen.height-28)
			scroll = 'yes';
		if(height < screen.height-28)
			top = Math.floor((screen.height - height)/2-14);
		if(width < screen.width-10)
			left = Math.floor((screen.width - width)/2);
		width = Math.min(width, screen.width-10);
		height = Math.min(height, screen.height-28);
		alt = encodeURI(alt);
		window.open('/bitrix/tools/imagepg.php?alt='+alt+'&img='+sImgPath,'','scrollbars='+scroll+',resizable=yes, width='+width+',height='+height+',left='+left+',top='+top);
	}
</script>
<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

\Bitrix\Main\UI\Extension::load("ui.fonts.opensans");
/** @var array $arResult */

echo '<script>BX.message('.\CUtil::PhpToJSObject($arResult['MESSAGES']).');</script>';
if($arResult['STATUS'] == 'PROCESS')
{?>
	<div class="transformer-uf-file-container transformer-uf-file-loader-video transformer-uf-file-container-<?=$arResult['COMMAND_ID'];?>"<?
		if($arResult['REFRESH_URL'])
		{
			?> data-bx-refresh-url="<?=CUtil::JSEscape($arResult['REFRESH_URL']);?>"<?
		}?>
	    >
		<div class="transformer-uf-file-loader-container">
			<h3 class="transformer-uf-file-loader-title"><?=htmlspecialcharsbx($arResult['TITLE']);?></h3>
			<?if(!empty($arResult['DESC']))
			{?>
			<div class="transformer-uf-file-loader-desc"><?=htmlspecialcharsbx($arResult['DESC']);?></div>
			<?}?>
			<div class="transformer-uf-file-loader-inner">
				<svg class="transformer-uf-file-circular" viewBox="25 25 50 50">
					<circle class="transformer-uf-file-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"></circle>
					<circle class="transformer-uf-file-inner-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"></circle>
				</svg>
				<div class="transformer-uf-file-loader-button"></div>
			</div>
		</div>
	</div>
<?}
elseif($arResult['STATUS'] == 'ERROR')
{?>
	<div class="transformer-uf-file-container transformer-uf-file-disable transformer-uf-file-container-<?=$arResult['COMMAND_ID'];?>">
		<div class="transformer-uf-file-loader-container">
			<h3 class="transformer-uf-file-loader-title"><?=htmlspecialcharsbx($arResult['TITLE']);?></h3>
			<?if(!empty($arResult['DESC']))
			{?>
				<div class="transformer-uf-file-loader-desc"><?=htmlspecialcharsbx($arResult['DESC']);?></div>
			<?}?>
			<div class="transformer-uf-file-loader-inner">
				<div class="transformer-uf-file-loader-button transformer-uf-file-loader-button-sad"></div>
				<?if(!empty($arResult['TRANSFORM_URL']))
				{?>
				<div class="transformer-uf-file-loader-link-conteiner">
					<a href="<?=htmlspecialcharsbx($arResult['TRANSFORM_URL']);?>" class="transformer-uf-file-loader-link" onclick="BX.Transformer.onTransformLinkClick(event);"><?=htmlspecialcharsbx($arResult['TRANSFORM_URL_TEXT']);?></a>
				</div>
				<?}?>
			</div>
		</div>
	</div>
<?
}
elseif($arResult['STATUS'] == 'NOT_STARTED')
{
	?>
	<div class="transformer-uf-file-container transformer-uf-file-disable">
		<div class="transformer-uf-file-loader-container">
			<h3 class="transformer-uf-file-loader-title"><?=htmlspecialcharsbx($arResult['TITLE']);?></h3>
			<?if(!empty($arResult['DESC']))
			{?>
				<div class="transformer-uf-file-loader-desc"><?=htmlspecialcharsbx($arResult['DESC']);?></div>
			<?}?>
			<div class="transformer-uf-file-loader-inner">
				<div class="transformer-uf-file-loader-button"></div>
				<?if(!empty($arResult['TRANSFORM_URL']))
				{?>
					<div class="transformer-uf-file-loader-link-conteiner">
						<a href="<?=htmlspecialcharsbx($arResult['TRANSFORM_URL']);?>" class="transformer-uf-file-loader-link" onclick="BX.Transformer.onTransformLinkClick(event);"><?=htmlspecialcharsbx($arResult['TRANSFORM_URL_TEXT']);?></a>
					</div>
				<?}?>
			</div>
		</div>
	</div>
	<?
}?>
<script>
BX.ready(function()
{
	BX.loadCSS('<?=CUtil::GetAdditionalFileURL($this->__folder.'/style.css');?>');
	BX.loadScript('<?=CUtil::GetAdditionalFileURL($this->__folder.'/script.js');?>');
	if(BX.PULL)
	{
		BX.PULL.extendWatch('TRANSFORMATIONCOMPLETE<?= $arResult['COMMAND_ID'];?>');
	}
});
</script>

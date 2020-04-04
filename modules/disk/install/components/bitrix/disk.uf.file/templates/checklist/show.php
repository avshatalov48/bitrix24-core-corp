<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\UI\Extension;

Loc::loadMessages(__FILE__);

Extension::load([
	'ui.icons.disk',
	'ui.tooltip',
	'ui.viewer',
	'disk.document',
	'disk.viewer.actions',
]);

foreach($arResult['IMAGES'] as $id => $file)
{
	$width = $file['THUMB']['width'];
	$height = $file['THUMB']['height'];
	$name = HtmlFilter::encode($file['NAME']);

	?><div class="tasks-checklist-item-attachment-file" id="disk-attach-<?=$file['ID']?>" data-bx-id="<?=$file['ID']?>">
		<div class="tasks-checklist-item-attachment-file-cover" style="width: <?=$width?>px; height: <?=$height?>px;">
			<img id="disk-attach-image-<?=$file['ID']?>"
				 title="<?=$name?>"
				 src="<?=$file['THUMB']['src']?>"
				 width="<?=$width?>"
				 height="<?=$height?>"
				 alt="<?=$name?>"
				 bx-attach-file-id="<?=$file['FILE_ID']?>"
				 <?=$file['ATTRIBUTES_FOR_VIEWER']?>
				 <?php if ($file['XML_ID']):?> bx-attach-xml-id="<?=$file['XML_ID']?>"<?php endif;?>
				 <?php if (!empty($file['ORIGINAL']))
				 {?>
					 data-bx-full="<?=$file['ORIGINAL']['src']?>"
					 data-bx-full-width="<?=$file['ORIGINAL']['width']?>"
					 data-bx-full-height="<?=$file['ORIGINAL']['height']?>"
					 data-bx-full-size="<?=$file['SIZE']?>"<?php
				 }?>
			/>
			<a class="tasks-checklist-item-attachment-file-download" href="<?=HtmlFilter::encode($file['DOWNLOAD_URL'])?>"></a>
		</div>
		<div class="tasks-checklist-item-attachment-file-name">
			<label class="tasks-checklist-item-attachment-file-name-text"><?=$name?></label>
		</div>
		<div class="tasks-checklist-item-attachment-file-size">
			<label class="tasks-checklist-item-attachment-file-size-text"><?=$file['SIZE']?></label>
		</div>
	</div><?php
}

foreach($arResult['FILES'] as $file)
{
	$name = HtmlFilter::encode($file['NAME']);

	?><div class="tasks-checklist-item-attachment-file" id="disk-attach-<?=$file['ID']?>"
		   data-bx-id="<?=$file['ID']?>" data-bx-extension="<?=HtmlFilter::encode($file['EXTENSION'])?>">
		<div class="tasks-checklist-item-attachment-file-cover">
			<div class="ui-icon ui-icon-file"
				 id="disk-attach-file-<?=$file['ID']?>" <?=$file['ATTRIBUTES_FOR_VIEWER']?> title="<?=$name?>">
				<i></i>
			</div>
			<a class="tasks-checklist-item-attachment-file-download" href="<?=HtmlFilter::encode($file['DOWNLOAD_URL'])?>"></a>
		</div>
		<div class="tasks-checklist-item-attachment-file-name">
			<label class="tasks-checklist-item-attachment-file-name-text"><?=$name?></label>
		</div>
		<div class="tasks-checklist-item-attachment-file-size">
			<label class="tasks-checklist-item-attachment-file-size-text"><?=$file['SIZE']?></label>
		</div>
	</div><?php
}

foreach($arResult['DELETED_FILES'] as $file)
{
	$name = HtmlFilter::encode($file['NAME']);

	?><div class="tasks-checklist-item-attachment-file" id="disk-attach-<?=$file['ID']?>"
		   data-bx-id="<?=$file['ID']?>" data-bx-deleted="<?=true?>" data-bx-extension="<?=HtmlFilter::encode($file['EXTENSION'])?>">
		<div class="tasks-checklist-item-attachment-file-cover" title="<?=$name?>"
			 <?=($file['IMAGE']? 'style="background-image: url('.HtmlFilter::encode($file['PREVIEW_URL']).')"' : '')?>><?php
			if (!$file['IMAGE'])
			{
				?><div class="ui-icon ui-icon-file" id="disk-attach-file-<?=$file['ID']?>"><i></i></div><?php
			}
		?></div>
		<div class="tasks-checklist-item-attachment-file-name">
			<label class="tasks-checklist-item-attachment-file-name-text"><?=$name?></label>
		</div>
		<div class="tasks-checklist-item-attachment-file-size">
			<label class="tasks-checklist-item-attachment-file-size-text"><?=$file['SIZE']?></label>
		</div><?php
		if ($file['CAN_RESTORE'] && $file['TRASHCAN_URL'])
		{
			?><div class="tasks-checklist-item-attachment-file-revert">
				<a href="<?=HtmlFilter::encode($file['TRASHCAN_URL'])?>"><?=Loc::getMessage('DISK_UF_FILE_CHECKLIST_RESTORE')?></a>
			</div><?php
		}
	?></div><?php
}
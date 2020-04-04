<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
use Bitrix\Main\Localization\Loc;
$this->IncludeLangFile("message.php");
$title = Loc::getMessage("DFU_UPLOAD_TITLE2", array(
	"#NUMBER#" => "<span id=\"#id#Number\">0</span>",
	"#COUNT#" => "<span id=\"#id#Count\">0</span>"
));
$m = array(
	"DFU_UPLOAD_CANCELED" => Loc::getMessage("DFU_UPLOAD_CANCELED"),
	"DFU_REPLACE" => Loc::getMessage("DFU_REPLACE"),
	"DFU_CANCEL" => Loc::getMessage("DFU_CANCEL"),
	"DFU_DUPLICATE" => Loc::getMessage("DFU_DUPLICATE"),
	"DFU_DND1" => Loc::getMessage("DFU_DND1"),
	"DFU_DND2" => Loc::getMessage("DFU_DND2")
);
?>
<script>
	BX.message({
		DFU_SAVE_BP : '<?=getMessageJS("DFU_SAVE_BP_DIALOG")?>',
		DFU_CLOSE : '<?=getMessageJS("DFU_CLOSE")?>',
		DFU_UPLOAD : '<?=getMessageJS("DFU_UPLOAD")?>',
		DFU_UPLOAD_TITLE1 : '<?=getMessageJS("DFU_UPLOAD_TITLE1")?>',
		DFU_DND_TEMPLATE : '<?=CUtil::JSEscape(str_replace(array("\n", "\t"), "",
<<<HTML
<span class="bx-disk wd-fa-add-file-light">
	<span class="wd-fa-add-file-light-text">
		<span class="wd-fa-add-file-light-title">
			<span class="wd-fa-add-file-light-title-text">{$m["DFU_DND1"]}</span>
		</span>
		<span class="wd-fa-add-file-light-descript">{$m["DFU_DND2"]}</span>
	</span>
</span>
HTML
		))?>',
		DFU_TEMPLATE : '<?=CUtil::JSEscape(str_replace(array("\n", "\t"), "",
<<<HTML
	<div class="bx-disk-popup-upload-title">{$title}</div>
	<div class="bx-disk-upload-file-section">
		<table class="bx-disk-upload-file-list" id="#id#PlaceHolder">
		</table>
	</div>
HTML
		))?>',
		DFU_NODE_TEMPLATE : '<?=CUtil::JSEscape(str_replace(array("\n", "\t"), "",
<<<HTML
<tr>
	<td class="bx-disk-popup-upload-file-progress-container-td">
		<div class="bx-disk-popup-upload-file-progress-container">
			<div class="bx-disk-popup-upload-file-progress-line" id="#id#Progress"></div>
			<div class="bx-disk-popup-upload-file-progress-filename">#name#</div>
		</div>
	</td>
	<td class="bx-disk-popup-upload-file-progress-container-lasttd">
		<span class="bx-disk-popup-upload-file-progress-btn-cencel" id="#id#Cancel"></span>
	</td>
</tr>
HTML
		))?>',
		DFU_NODE_TEMPLATE_DONE : '<?=CUtil::JSEscape(str_replace(array("\n", "\t"), "",
<<<HTML
<tr>
	<td class="bx-disk-popup-upload-file-progress-container-td">
		<div class="bx-disk-popup-upload-file-progress-container">
			<div class="bx-disk-popup-upload-file-progress-line-end"></div>
			<div class="bx-disk-popup-upload-file-progress-filename">#name#</div>
		</div>
	</td>
	<td class="bx-disk-popup-upload-file-progress-container-lasttd">
		<span class="bx-disk-popup-upload-file-progress-btn-end" id="#id#Done"></span>
	</td>
</tr>
HTML
		))?>',
		DFU_NODE_TEMPLATE_CANCELED : '<?=CUtil::JSEscape(str_replace(array("\n", "\t"), "",
<<<HTML
<tr class="bx-disk-popup-upload-file-progress-event">
	<td class="bx-disk-popup-upload-file-progress-container-td">
		<div class="bx-disk-popup-upload-file-progress-container">
			<div class="bx-disk-popup-upload-file-progress-line-cancel">{$m["DFU_UPLOAD_CANCELED"]}</div>
			<div class="bx-disk-popup-upload-file-progress-filename">#name#</div>
		</div>
	</td>
	<td class="bx-disk-popup-upload-file-progress-container-lasttd">
		<span class="bx-disk-popup-upload-file-progress-btn-cencel" id="#id#Cancel"></span>
		<span class="bx-disk-popup-upload-file-progress-btn-decencel" id="#id#Restore"></span>
	</td>
</tr>
HTML
		))?>',
		DFU_NODE_TEMPLATE_ERROR : '<?=CUtil::JSEscape(str_replace(array("\n", "\t"), "",
<<<HTML
<tr class="bx-disk-popup-upload-file-progress-event">
	<td class="bx-disk-popup-upload-file-progress-container-td">
		<div class="bx-disk-popup-upload-file-progress-container">
			<div class="bx-disk-popup-upload-file-progress-line-error">#error#</div>
			<div class="bx-disk-popup-upload-file-progress-filename error">#name#</div>
		</div>
	</td>
	<td class="bx-disk-popup-upload-file-progress-container-lasttd">
		<span class="bx-disk-popup-upload-file-progress-btn-error" id="#id#Cancel"></span>
	</td>
</tr>
HTML
		))?>',
		DFU_NODE_TEMPLATE_ERROR_DOUBLE : '<?=CUtil::JSEscape(str_replace(array("\n", "\t"), "",
<<<HTML
<tr class="bx-disk-popup-upload-file-progress-event">
	<td class="bx-disk-popup-upload-file-progress-container-td mess">
		<div class="bx-disk-popup-upload-file-progress-container">
			<div class="bx-disk-popup-upload-file-progress-line-error">{$m["DFU_DUPLICATE"]}</div>
			<div class="bx-disk-popup-upload-file-progress-filename">#name#</div>
		</div>
	</td>
	<td class="bx-disk-popup-upload-file-progress-container-lasttd">
		<a class="bx-disk-btn bx-disk-btn-small bx-disk-btn-gray mb0" href="#" id="#id#Replace">{$m["DFU_REPLACE"]}</a>
		<span class="bx-disk-popup-upload-file-progress-btn-cencel" id="#id#Cancel"></span>
	</td>
</tr>
HTML
		))?>'
	});
</script>
<?
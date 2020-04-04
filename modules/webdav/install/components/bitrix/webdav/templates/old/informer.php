<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!$GLOBALS["USER"]->IsAuthorized())
	return true;

$file = trim(preg_replace("'[\\\\/]+'", "/", (dirname(__FILE__)."/lang/".LANGUAGE_ID."/informer.php")));
__IncludeLang($file);
$url_help = trim($url_help);
$url_base = trim($url_base);

$arParams["SHOW_NOTE"] .= str_replace(
	array("#BASE_URL#", "#HELP_URL#"),
	array("<a href='".$url_base."' onclick=\"prompt('".GetMessage("WD_ANCHOR_TITLE").":', '".
		$url_base."'); return false;\">".$url_base."</a>", $url_help),
	GetMessage("WD_HELP_TEXT"));

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/".strToLower($GLOBALS["DB"]->type)."/favorites.php");
$arText = array(
	"note_1" => $arParams["SHOW_NOTE"], 
	"note_2" => str_replace("#HREF#", $url_help, GetMessage("NOTE_2")), 
	"note_3" => str_replace("#HREF#", $url_help, GetMessage("NOTE_3")));
$arParams["informer"] = CUserOptions::GetOption('webdav', 'note', array('show' => true, 'step' => 1));
$arParams["informer"] = array(
	"show" => ((is_set($arParams["informer"], "show") && ($arParams["informer"]["show"] == false || 
		$arParams["informer"]["show"] === "false")) ? false : true), 
	"step" => $arParams["informer"]["step"] > 0 ? $arParams["informer"]["step"] : 1, 
	"steps" => count($arText));

if (!$arParams["informer"]["show"])
	return true; 

$APPLICATION->AddHeadScript('/bitrix/js/main/admin_tools.js');

?>
<script>
step = '<?=$arParams["informer"]["step"]?>';
WDInformerText = <?=CUtil::PhpToJSObject($arText)?>;

function BXWdStepBnr(obText, obPrev, obNext, nav)
{
	nav = (nav == 'prev' ? 'prev' : 'next');
	if (nav == 'next' && window.step < <?=$arParams["informer"]["steps"]?>) 
		window.step++;
	else if (nav == 'prev' && window.step > 1) 
		window.step--;
	else
		nav = 'current';
	var iStep = window.step;
	if (nav == 'next')
	{
		obPrev.style.display = '';
		obNext.style.display = (iStep < <?=$arParams["informer"]["steps"]?> ? '' : 'none');
	}
	else
	{
		obPrev.style.display = (iStep > 1 ? '' : 'none');
		obNext.style.display = '';
	}
	obText.innerHTML = WDInformerText['note_' + iStep];
	if (null != jsUserOptions)
	{
		if(!jsUserOptions.options)
			jsUserOptions.options = new Object();
		jsUserOptions.options['webdav.note.step'] = ['webdav', 'note', 'step', iStep, false];
		jsUserOptions.SendData(null);
	}
}

</script>
<div class="wd-infobox wd-info-banner"><?
	?><div class="wd-infobox-inner"><?
		?><div class="wd-info-banner-head"><?
			?><a href="#banner" class="btn-close" onclick="BXWdCloseBnr(this.parentNode.parentNode.parentNode);return false;" <?
				?>title="<?=GetMessage('WD_BANNER_CLOSE')?>"></a></div>
		<div class="wd-info-banner-body">
			<table cellpadding="0" border="0" class="wd-info-banner-body">
				<tr>
					<th class="wd-info-banner-icon" rowspan="2">
						<a class="wd-info-banner-icon"></a>
					</th>
					<td class="wd-info-banner-content">
						<div class="wd-info-banner-content" id="wd_informer_text">
							<?=$arText["note_".$arParams["informer"]["step"]]?>
						</div>
					</td>
				</tr>
				<tr>
					<td class="wd-info-banner-buttons"><?
					?><a href="#next" onclick="BXWdStepBnr(document.getElementById('wd_informer_text'), this.nextSibling, this, 'next'); <?
						?> return false;" class="bx-bnr-button" <?
						?><?=($arParams["informer"]["step"] >= $arParams["informer"]["steps"] ? "style='display:none;'" : "")?><?
						?>><?=GetMessage("WD_NEXT_ADVICE")?></a><?
					?><a href="#prev" onclick="BXWdStepBnr(document.getElementById('wd_informer_text'), this, this.previousSibling, 'prev'); <?
						?>return false;" class="bx-bnr-button" <?
						?><?=($arParams["informer"]["step"] <= 1 ? "style='display:none;'" : "")?><?
						?>><?=GetMessage("WD_PREV_ADVICE")?></a><?
					?>
					</td>
				</tr>
			</table>
		</div>
	</div>
</div>

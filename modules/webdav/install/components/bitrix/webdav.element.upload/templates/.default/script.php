<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!function_exists("___webdav_uploader"))
{
	function ___webdav_uploader()
	{
		static $bWebdavUploadIsFirstOnPage = true;
		$res = $bWebdavUploadIsFirstOnPage;
		$bWebdavUploadIsFirstOnPage = false;
		return $res;
	}
}

?>
<script>
function BeforeUploadLink<?=$arParams["INDEX_ON_PAGE"]?>(){
	if (!window.oParams['<?=$arParams["INDEX_ON_PAGE"]?>']['object'])
		InitLink<?=$arParams["INDEX_ON_PAGE"]?>();
	window.oParams['<?=$arParams["INDEX_ON_PAGE"]?>']['object'].BeforeUpload();}
function AfterUploadLink<?=$arParams["INDEX_ON_PAGE"]?>(htmlPage){
	if (!window.oParams['<?=$arParams["INDEX_ON_PAGE"]?>']['object'])
		InitLink<?=$arParams["INDEX_ON_PAGE"]?>();
	window.oParams['<?=$arParams["INDEX_ON_PAGE"]?>']['object'].AfterUpload(htmlPage);}
function ChangeSelectionLink<?=$arParams["INDEX_ON_PAGE"]?>(){
	if (!window.oParams['<?=$arParams["INDEX_ON_PAGE"]?>']['object'])
		InitLink<?=$arParams["INDEX_ON_PAGE"]?>();
	window.oParams['<?=$arParams["INDEX_ON_PAGE"]?>']['object'].ChangeSelection();}
function ChangeFileCountLink<?=$arParams["INDEX_ON_PAGE"]?>(){
	if (!window.oParams['<?=$arParams["INDEX_ON_PAGE"]?>']['object'])
		InitLink<?=$arParams["INDEX_ON_PAGE"]?>();
	window.oParams['<?=$arParams["INDEX_ON_PAGE"]?>']['object'].ChangeFileCount();}
function InitLink<?=$arParams["INDEX_ON_PAGE"]?>(){
	window.oParams['<?=$arParams["INDEX_ON_PAGE"]?>']['object'] = new FileUploaderClass();
	window.oParams['<?=$arParams["INDEX_ON_PAGE"]?>']['object'].Init('<?=$arParams["INDEX_ON_PAGE"]?>');}
</script>
<?

// This block showed only once on page
$res = ___webdav_uploader();
if (!$res):
	return true;
endif;

if (!$Browser["isOpera"]):
	$GLOBALS['APPLICATION']->AddHeadString('<script src="/bitrix/image_uploader/iuembed.js"></script>', true);
	include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/image_uploader/version.php");
	include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/image_uploader/localization.php");
endif;

$file = trim(preg_replace("'[\\\\/]+'", "/", (dirname(__FILE__)."/lang/".LANGUAGE_ID."/script.php")));
__IncludeLang($file);

?>
<script>
if (typeof oParams != "object")
	oParams = {};
oParams['main'] = {
	'user_id' : <?=intVal($GLOBALS["USER"]->GetID())?>,
	'show_tags' : '<?=($arParams["SHOW_TAGS"] == "Y" ? "Y" : "N")?>'};
if (phpVars == null || typeof(phpVars) != "object")
	var phpVars = {'COOKIES' : ""};
if (typeof iu != "object")
	iu = {};
if (typeof t != "object")
	t = {};

function IUChangeMode(){
	IUSendData('save=view_mode&position=change');
	return true;}

function IUSendData(value){
<?
	if ($GLOBALS["USER"]->IsAuthorized()):
?>
	var url = '/bitrix/components/bitrix/webdav.element.upload/user_settings.php?sessid=<?=bitrix_sessid()?>&' + value;
	var TID = jsAjax.InitThread();
	jsAjax.Send(TID, url);
	return true;
<?
	else:
?>
	return false;
<?
	endif;
?>
}

<?
	if (!$Browser["isOpera"])
	{
?>
function to_init()
{
	var bInitImageUploader = false;
	try	{
		bInitImageUploader = (typeof IUCommon == "object" && IUCommon != null && WDUtilsIsLoaded == true ? true : false);
		IUCommon.debugLevel = 2;
	} catch(e){}

	if (bInitImageUploader != true)
	{
		setTimeout(to_init, 100);
		return;
	}
	else
	{
		var tmp = navigator.userAgent.toLowerCase();
		var bWinIsIE = (tmp.indexOf("msie")!=-1 && (tmp.indexOf("opera")==-1));
		var bJavaEnabled = navigator.javaEnabled();
		for (var index in oParams)
		{
			if (!oParams[index]['index'] || oParams[index]['inited'])
			{
				continue;
			}

			if (oParams[index]['mode'] != 'form')
			{
				if (!bJavaEnabled && !bWinIsIE)
				{
					if (document.getElementById('uploader_' + index))
					{
						document.getElementById('uploader_' + index).innerHTML = '<?=CUtil::JSEscape(
							'<div class="nojava"><span class="starrequired">'.GetMessage("IU_ATTENTION").'</span> '.
								str_replace(
									"#HREF_SIMPLE#",
									$APPLICATION->GetCurPageParam(
										"change_view_mode_data=Y&save=view_mode&view_mode=form&".bitrix_sessid_get(),
										array("change_view_mode_data", "save", "view_mode", "sessid")),
									GetMessage("IU_DISABLED_JAVA"))
								)?></div>';
						oParams[index]['applet_inited'] = true;
						oParams[index]['thumbnail_inited'] = true;
						oParams[index]['inited'] = true;
					}
				}
				else if (!oParams[index]['applet_inited'])
				{
					oParams[index]['applet_inited'] = true;
					oParams[index]['attempt'] = 0;
					InitImageUploader(index, oParams[index]['mode']);
					setTimeout(to_init, 500);
				}
				else if (oParams[index]['mode'] == 'applet')
				{
					var Uploader = getImageUploader("ImageUploader" + index + '');
					if (Uploader)
					{
						oParams[index]['thumbnail_inited'] = true;
						oParams[index]['inited'] = true;
						InitThumbnailWriter(index);
					}
					else
					{
						oParams[index]['attempt']++;
						if (oParams[index]['attempt'] < 10) {
							setTimeout(to_init, 500);
						} else {
							document.getElementById('thumbnail_' + index).innerHTML = "";
						}
					}
				}
			}
			else
			{
				oParams[index]['object'] = new UploadLineClass();
				oParams[index]['object'].Init({
						"SourceFile" : {"type" : "file", "title" : "<?=GetMessage("File")?>"},
						"Title" : {"type" : "text", "title" : "<?=GetMessage("Title")?>"},
			<?
				if ($arParams["SHOW_TAGS"] == "Y" && IsModuleInstalled("search")):
			?>
						"Tag" : {"type" : "text", "title" : "<?=GetMessage("Tags")?>", "use_search" : "Y"},
			<?
				elseif ($arParams["SHOW_TAGS"] == "Y"):
			?>
						"Tag" : {"type" : "text", "title" : "<?=GetMessage("Tags")?>"},
			<?
				endif;
			?>
						"Description" : {"type" : "textarea", "title" : "<?=GetMessage("Description")?>"}},
					document.getElementById('file_object_div_' + index),
					document.getElementById('iu_upload_form_' + index),
					index);
				oParams[index]['inited'] = true;
			}
		}
	}
	return;
}

function InitImageUploader(index, view)
{
	iu[index] = new ImageUploaderWriter("ImageUploader" + index, "100%", 315);
		
	view = (view == 'classic' ? 'classic' : 'applet');

	iu[index].instructionsEnabled = true;
	iu[index].addEventListener("AfterUpload", "AfterUploadLink" + index);
	iu[index].addEventListener("UploadFileCountChange", "ChangeFileCountLink" + index);
	iu[index].addEventListener("BeforeUpload", "BeforeUploadLink" + index);
	iu[index].addEventListener("FullPageLoad", "InitLink" + index);
	iu[index].addParam("UploadSourceFile", "true");
	iu[index].addParam("FilesPerOnePackageCount", "1");
	if (view != 'classic')
	{
		iu[index].addEventListener("SelectionChange", "ChangeSelectionLink" + index);
		iu[index].addParam("ShowDescriptions", "false");
		iu[index].addParam("AllowRotate", "true");
		iu[index].addParam("PaneLayout", "OnePane");
		iu[index].addParam("UseSystemColors", "false");
		iu[index].addParam("BackgroundColor", "#ededed");
		iu[index].addParam("UploadPaneBackgroundColor", "#ededed");
		iu[index].addParam("UploadPaneBorderStyle", "none");
		iu[index].addParam("PreviewThumbnailBorderColor", "#afafaf");
		iu[index].addParam("PreviewThumbnailBorderHoverColor", "#91a7d3");
		iu[index].addParam("PreviewThumbnailActiveSelectionColor", "#ff8307");
		iu[index].addParam("DisplayNameActiveSelectedTextColor", "#000000");
		iu[index].addParam("PreviewThumbnailInactiveSelectionColor", "#ff8307");

		iu[index].addParam("ShowUploadListButtons", "false");
		iu[index].addParam("ShowButtons", "false");
		iu[index].addParam("FolderView", "Thumbnails");
		iu[index].addParam("UploadView", "Details");
	}
	else
	{
		iu[index].addParam("ShowDescriptions", "false");
		iu[index].addParam("PaneLayout", "TwoPanes");
		iu[index].addParam("ShowButtons", "false");
		iu[index].addParam("FolderView", "Details");
		iu[index].addParam("BackgroundColor", "#ffffff");
	}
	oParams[index]['type'] = iu[index].getControlType();
<?
if ($arParams["UPLOAD_MAX_FILESIZE"] > 0 && false):
?>
	iu.addParam("MaxFileSize", "<?=$arParams["UPLOAD_MAX_FILESIZE_BYTE"]?>");
<?
endif;
?>
	//Configure URL files are uploaded to.
	sAction = window.location.protocol + "//" + oParams[index]['url']['form'];
	iu[index].addParam("Action", sAction);
	iu[index].addParam("UserAgent", window.navigator.userAgent);
	iu[index].activeXControlCodeBase = "<?=$arAppletVersion["activeXControlCodeBase"]?>";
	iu[index].activeXClassId = "<?=$arAppletVersion["IuActiveXClassId"]?>";
	iu[index].activeXControlVersion = "<?=$arAppletVersion["IuActiveXControlVersion"]?>";
	//For Java applet only path to directory with JAR files should be specified (without file name).
	iu[index].javaAppletCodeBase = "<?=$arAppletVersion["javaAppletCodeBase"]?>";
	iu[index].javaAppletClassName = "<?=$arAppletVersion["javaAppletClassName"]?>";
	iu[index].javaAppletJarFileName = "<?=$arAppletVersion["javaAppletJarFileName"]?>";
	iu[index].javaAppletCached = true;
	iu[index].javaAppletVersion = "<?=$arAppletVersion["IuJavaAppletVersion"]?>";
	iu[index].addParam("LicenseKey", "Bitrix");
	iu[index].addParam("debugLevel", "2");

	iu[index].showNonemptyResponse = "off";

	language_resources.addParams(iu[index]);

	if (document.getElementById('uploader_' + index))
	{
		BX.addCustomEvent("onPopupWindowInit", function(uniquePopupId, bindElement, params) {
			res = BX.findParent(bindElement, {'className': 'image-uploader-settings'}, true);
			if(res != undefined)
			{
				if(!params.bindOptions)
				{
					params.bindOptions = {};
				}
				params.bindOptions.forceTop = true;
			}
		});
		document.getElementById('uploader_' + index).innerHTML = iu[index].getHtml();
	}
}
function InitThumbnailWriter(index)
{
	t[index] = new ThumbnailWriter("Thumbnail" + index, 120, 120);
	t[index].addParam("BackgroundColor", "#d8d8d8");
	//For ActiveX control full path to CAB file (including file name) should be specified.
	t[index].activeXControlCodeBase = "<?=$arAppletVersion["activeXControlCodeBase"]?>";
	t[index].activeXClassId = "<?=$arAppletVersion["ThumbnailActiveXClassId"]?>";
	t[index].activeXControlVersion = "<?=$arAppletVersion["ThumbnailActiveXControlVersion"]?>";
	//For Java applet only path to directory with JAR files should be specified (without file name).
	t[index].javaAppletCodeBase = "<?=$arAppletVersion["javaAppletCodeBase"]?>";
	t[index].javaAppletJarFileName = "<?=$arAppletVersion["javaAppletJarFileName"]?>";
	t[index].javaAppletCached = true;
	t[index].javaAppletVersion = "<?=$arAppletVersion["ThumbnailJavaAppletVersion"]?>";

	t[index].addParam("ParentControlName", "ImageUploader" + index);

	language_resources.addParams(t[index]);

	if (document.getElementById('thumbnail_' + index))
	{
		document.getElementById('thumbnail_' + index).innerHTML = t[index].getHtml();
	}
}
<?
	}
	else
	{
?>
function to_init()
{
	var bInitImageUploader = false;
	try
	{
		bInitImageUploader = (WDUtilsIsLoaded == true ? true : false);
	}
	catch(e){}

	if (bInitImageUploader != true)
	{
		setTimeout(to_init, 100);
		return;
	}
	else
	{
		for (var index in oParams)
		{
			if (oParams[index]['index'] && !oParams[index]['inited'])
			{
				oParams[index]['object'] = new UploadLineClass();
				oParams[index]['object'].Init({
						"SourceFile" : {"type" : "file", "title" : "<?=GetMessage("File")?>"},
						"Title" : {"type" : "text", "title" : "<?=GetMessage("Title")?>"},
			<?
				if ($arParams["SHOW_TAGS"] == "Y" && IsModuleInstalled("search")):
			?>
						"Tag" : {"type" : "text", "title" : "<?=GetMessage("Tags")?>", "use_search" : "Y"},
			<?
				elseif ($arParams["SHOW_TAGS"] == "Y"):
			?>
						"Tag" : {"type" : "text", "title" : "<?=GetMessage("Tags")?>"},
			<?
				endif;
			?>
						"Description" : {"type" : "textarea", "title" : "<?=GetMessage("Description")?>"}},
					document.getElementById('file_object_div_' + index),
					document.getElementById('iu_upload_form_' + index),
					index);
				oParams[index]['inited'] = true;
			}
		}
	}
	return;
}
<?
	}
?>
</script>

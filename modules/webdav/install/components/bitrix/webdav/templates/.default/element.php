<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?><?
$sTplDir = trim(preg_replace("'[\\\\/]+'", "/", (dirname(__FILE__)."/")));

$arInfo = include($sTplDir."tab_edit.php");

if ($arParams["WORKFLOW"] == "bizproc")
{
	include($sTplDir."tab_bizproc_history.php");
	include($sTplDir."tab_bizproc_document.php");
	include($sTplDir."tab_versions.php");
}
elseif ($arParams["WORKFLOW"] == "workflow")
{
	include($sTplDir."tab_workflow_history.php");
}
else
{
	include($sTplDir."tab_bizproc_history.php");
}

include($sTplDir."tab_comments.php");

$ob =&$arParams['OBJECT'];

$bShowPermissions = false;
if ($ob->e_rights )
{
	if (isset($arInfo['ELEMENT_ID']) && (intval($arInfo['ELEMENT_ID'])>0))
		$bShowPermissions = $ob->GetPermission('ELEMENT', $arInfo['ELEMENT_ID'], 'element_rights_edit');
}

if ($bShowPermissions)
	include($sTplDir."tab_permissions.php");

if (!$arParams["FORM_ID"]) $arParams["FORM_ID"] = "element";

if (
	! empty($this->__component->arResult['TABS'])
	&& ! empty($this->__component->arResult['DATA'])
)
{
	$APPLICATION->IncludeComponent(
		"bitrix:main.interface.form",
		"",
		array(
			"FORM_ID" => $arParams["FORM_ID"],
			"SHOW_FORM_TAG" => "N",
			"TABS" => $this->__component->arResult['TABS'],
			"DATA" => $this->__component->arResult['DATA'],
			"SHOW_SETTINGS" => false
		),
		($this->__component->__parent ? $this->__component->__parent : $component)
	);
	?>
	<script>

	function nextElementSibling( el ) {
		do { el = el.nextSibling } while ( el && el.nodeType !== 1 );
		return el;
	}
	BX(function() {
		if (bxForm_<?=$arParams["FORM_ID"]?>) {
			if (expand_link = BX('bxForm_<?=$arParams["FORM_ID"]?>_expand_link')) {
				BX.hide(expand_link);
				el = expand_link.nextElementSibling || nextElementSibling(expand_link);
				if (!! el)
					BX.hide(el);
			}
		}
	});
	</script>
<?
}
?>

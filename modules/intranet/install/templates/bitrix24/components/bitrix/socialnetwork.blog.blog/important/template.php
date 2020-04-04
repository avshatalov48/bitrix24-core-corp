<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->addExternalCss(SITE_TEMPLATE_PATH."/css/sidebar.css");

$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/rating.vote/templates/like/popup.css');
$uid = $this->randString();
$controller = "BX('blog-".$uid."')";
$arParams["OPTIONS"] = (is_array($arParams["OPTIONS"]) ? $arParams["OPTIONS"] : array());
$arRes = array("data" => array(),
		"page_settings" => array(
			"NavPageCount" => $arResult["NAV_RESULT"]->NavPageCount,
			"NavPageNomer" => $arResult["NAV_RESULT"]->NavPageNomer,
			"NavPageSize" => $arResult["NAV_RESULT"]->NavPageSize,
			"NavRecordCount" => $arResult["NAV_RESULT"]->NavRecordCount,
			"bDescPageNumbering" => $arResult["NAV_RESULT"]->bDescPageNumbering,
			"nPageSize" => $arResult["NAV_RESULT"]->NavPageSize)
	);
foreach($arResult["POST"] as $id => $res)
{
	$res = array(
		"id" => $res["ID"],
		"post_text" => TruncateText(($res["MICRO"] != "Y" ? $res["TITLE"]." ".$res["CLEAR_TEXT"] : $res["CLEAR_TEXT"]), $arParams["MESSAGE_LENGTH"]),
		"post_url" => $res["urlToPost"],
		"author_name" => $res["AUTHOR_NAME"],
		"author_avatar_style" => (!empty($res["AUTHOR_AVATAR"]["src"]) ? "url('".$res["AUTHOR_AVATAR"]["src"]."')" : ""),
		"author_avatar" => (!empty($res["AUTHOR_AVATAR"]["src"]) ? "style=\"background:url('".$res["AUTHOR_AVATAR"]["src"]."') no-repeat center; background-size: cover;\"" : ""),
		"author_url" => $res["urlToAuthor"]
	);

	if (!trim($res['post_text']))
		$res['post_text'] = getMessage('SBB_READ_EMPTY');

	$arRes["data"][] = $res;
}
if ($_REQUEST["AJAX_POST"] == "Y")
{
	$APPLICATION->RestartBuffer();
	echo CUtil::PhpToJSObject($arRes);
	die();
}
CUtil::InitJSCore(array("ajax"));
$arUser = (is_array($arResult["USER"]) ? $arResult["USER"] : array());
$btnTitle = GetMessage("SBB_READ_".$arUser["PERSONAL_GENDER"]);
$btnTitle = (!empty($btnTitle) ? $btnTitle : GetMessage("SBB_READ_"));
$res = reset($arRes["data"]);
$this->SetViewTarget("sidebar", 80);
$frame = $this->createFrame()->begin();
?>
<div class="sidebar-widget sidebar-imp-messages" id="blog-<?=$uid?>"<?if(empty($arRes["data"])){?> style="display:none;"<?}?>>
	<div class="sidebar-imp-mess-top"><?=GetMessage("SBB_IMPORTANT")?></div>
	<div class="sidebar-imp-mess-tmp-wrap">
		<div class="sidebar-imp-mess-tmp">
			<div class="sidebar-imp-mess" id="message-block-<?=$uid?>">
				<div class="sidebar-imp-mess-wrap" id="blog-leaf-<?=$uid?>">
					<div class="user-avatar sidebar-user-avatar user-default-avatar"<?if($res["author_avatar"]!==""){?> <?=$res["author_avatar"]?><?}?>></div>
					<a href="<?=$res["author_url"]?>" class="sidebar-imp-mess-title"><?=$res["author_name"]?></a>
					<a href="<?=$res["post_url"]?>" class="sidebar-imp-mess-text"><?=$res["post_text"]?></a>
				</div>
				<div class="sidebar-imp-mess-wrap" id="blog-text-<?=$uid?>">
					<div class="user-avatar sidebar-user-avatar user-default-avatar"<?if($res["author_avatar"]!==""){?> <?=$res["author_avatar"]?><?}?>></div>
					<a href="<?=$res["author_url"]?>" class="sidebar-imp-mess-title"><?=$res["author_name"]?></a>
					<a href="<?=$res["post_url"]?>" class="sidebar-imp-mess-text"><?=$res["post_text"]?></a>
				</div>
				<div id="blog-<?=$uid?>-template" class="sidebar-imp-mess-templates" style="display:none;">
					<div class="user-avatar sidebar-user-avatar user-default-avatar" data-bx-author-avatar="true"></div>
					<a href="__author_url__" class="sidebar-imp-mess-title">__author_name__</a>
					<a href="__post_url__" class="sidebar-imp-mess-text">__post_text__</a>
				</div>
				<div class="sidebar-imp-mess-bottom">
					<span id="blog-<?=$uid?>-btn" class="sidebar-imp-mess-btn"><?=$btnTitle?></span>
					<div class="sidebar-imp-mess-nav-block">
						<span class="sidebar-imp-mess-nav-arrow-l" id="blog-<?=$uid?>-right"></span>
						<span class="sidebar-imp-mess-nav-arrow-r" id="blog-<?=$uid?>-left"></span>
						<span id="blog-<?=$uid?>-current" class="sidebar-imp-mess-nav-current-page">1</span><?
							?><span class="sidebar-imp-mess-nav-separator">/</span><?
						?><span id="blog-<?=$uid?>-total" class="sidebar-imp-mess-nav-total-page"><?=$arResult["NAV_RESULT"]->NavRecordCount?></span>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?
$filter = $arParams["FILTER"];
// For composite mode
unset($filter["<=DATE_PUBLISH"]);
foreach ($filter as $filterKey => $filterValues)
{
	if (is_numeric($filterKey) && is_array($filterValues))
	{
		foreach ($filterValues as $complexFilterKey => $complexFilterValue)
		{
			if ($complexFilterKey == ">=UF_IMPRTANT_DATE_END")
			{
				unset($filter[$filterKey][$complexFilterKey]);
			}
		}
	}
}
?>
<script type="text/javascript">
BX.ready(function(){
	if (!!<?=$controller?> && ! <?=$controller?>.loaded)
	{
		<?=$controller?>.loaded = true;
		new BSBBW({
			'CID' : '<?=$uid?>',
			'controller': <?=$controller?>,
			'options' : <?=CUtil::PhpToJSObject($arParams["OPTIONS"])?>,
			'post_info' : {'template' : '<?=$this->__name?>', 'filter' : <?=CUtil::PhpToJSObject($filter)?>, 'avatar_size' : <?=intval($arParams["AVATAR_SIZE"])?>},
			'page_settings' : <?=CUtil::PhpToJSObject($arRes["page_settings"])?>,
			'nodes' : {
				'btn' : BX("blog-<?=$uid?>-btn"),
				'left' : BX("blog-<?=$uid?>-left"),
				'right' : BX("blog-<?=$uid?>-right"),
				'total' : BX("blog-<?=$uid?>-total"),
				'counter' : BX("blog-<?=$uid?>-current"),
				'block' : BX("message-block-<?=$uid?>"),
				'leaf' : BX("blog-leaf-<?=$uid?>"),
				'text' : BX("blog-text-<?=$uid?>"),
				'template' : BX("blog-<?=$uid?>-template")
			},
			'data' : <?=CUtil::PhpToJSObject($arRes["data"])?>,
			'url' : '<?=CUtil::JSEscape($arResult["urlToPosts"])?>'
		});
	}
});
</script>
<?
$frame->end();
$this->EndViewTarget();
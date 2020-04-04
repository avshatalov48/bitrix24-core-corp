<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<script>
var site_list_items= <?echo CUtil::PhpToJSObject($arResult["MENU_ITEMS"])?>;
var site_list_menu = new PopupMenu('site_list_div');
</script>
<div class="site-list"><a href="javascript:void(0);"  onclick="site_list_menu.ShowMenu(this, site_list_items);"><div><?echo $arResult["TITLE"]?></div></a></div>

<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die(); ?>
<div id='wd_aggregator_tree'>
<ul data-role="listview" data-inset="true">
<?
foreach ($arResult['STRUCTURE'] as $node)
{
	//$link = rtrim($arParams['SEF_FOLDER'],'/') . $node['PATH'];
	$link = ($node['PATH']);

	$arLink = explode('/', $link);
	foreach ($arLink as $i => &$lnk)
		$lnk = urlencode($lnk);
	$link = implode('/', $arLink);

	?><li><?
	if($node["TYPE"] == "file")
	{
		?><a data-icon="none" href="<?=$link?>" rel="external"><img class="ui-li-icon" src="<?=$templateFolder?>/images/icons/ic<?=substr($node["FILE_EXTENTION"], 1)?>.gif" border="0"><?=$node['NAME']?></a><?
	}
	elseif($node["TYPE"] == "up")
	{
		?><a href="<?=$link?>" data-rel="back"><img class="ui-li-icon" src="<?=$templateFolder?>/images/icons/up.gif" border="0">..</a><?
	}
	else
	{
		?><a href="<?=$link?>"><img class="ui-li-icon" src="<?=$templateFolder?>/images/icons/section.gif" border="0"><?=$node['NAME']?></a><?
	}
	?></li><?
}
?>
</ul>
</div>

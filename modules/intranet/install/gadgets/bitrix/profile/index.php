<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();


if (!CModule::IncludeModule("socialnetwork"))
{
	return;
}

$arResult = CSocNetFeatures::GetActiveFeaturesNames(SONET_ENTITY_USER, $USER->GetID());
$p = array("user_id"=>$USER->GetID());

$arGadgetParams["PATH_TO_GENERAL"] = (isset($arGadgetParams["PATH_TO_GENERAL"])?$arGadgetParams["PATH_TO_GENERAL"]:"/company/personal/user/#user_id#/");
$arGadgetParams["PATH_TO_PROFILE_EDIT"] = (isset($arGadgetParams["PATH_TO_PROFILE_EDIT"])?$arGadgetParams["PATH_TO_PROFILE_EDIT"]:"/company/personal/user/#user_id#/edit/");
$arGadgetParams["PATH_TO_LOG"] = (isset($arGadgetParams["PATH_TO_LOG"])?$arGadgetParams["PATH_TO_LOG"]:"/company/personal/log/");
$arGadgetParams["PATH_TO_SUBSCR"] = (isset($arGadgetParams["PATH_TO_SUBSCR"])?$arGadgetParams["PATH_TO_SUBSCR"]:"/company/personal/subscribe/");
$arGadgetParams["PATH_TO_MSG"] = (isset($arGadgetParams["PATH_TO_MSG"])?$arGadgetParams["PATH_TO_MSG"]:"/company/personal/messages/");
$arGadgetParams["PATH_TO_GROUPS"] = (isset($arGadgetParams["PATH_TO_GROUPS"])?$arGadgetParams["PATH_TO_GROUPS"]:"/company/personal/user/#user_id#/groups/");
$arGadgetParams["PATH_TO_GROUP_NEW"] = (isset($arGadgetParams["PATH_TO_GROUP_NEW"])?$arGadgetParams["PATH_TO_GROUP_NEW"]:"/company/personal/user/#user_id#/groups/create/");
$arGadgetParams["PATH_TO_PHOTO"] = (isset($arGadgetParams["PATH_TO_PHOTO"])?$arGadgetParams["PATH_TO_PHOTO"]:"/company/personal/user/#user_id#/photo/");
$arGadgetParams["PATH_TO_PHOTO_NEW"] = (isset($arGadgetParams["PATH_TO_PHOTO_NEW"])?$arGadgetParams["PATH_TO_PHOTO_NEW"]:"/company/personal/user/#user_id#/photo/photo/0/action/upload/");
$arGadgetParams["PATH_TO_FORUM"] = (isset($arGadgetParams["PATH_TO_FORUM"])?$arGadgetParams["PATH_TO_FORUM"]:"/company/personal/user/#user_id#/forum/");
$arGadgetParams["PATH_TO_BLOG"] = (isset($arGadgetParams["PATH_TO_BLOG"])?$arGadgetParams["PATH_TO_BLOG"]:"/company/personal/user/#user_id#/blog/");
$arGadgetParams["PATH_TO_BLOG_NEW"] = (isset($arGadgetParams["PATH_TO_BLOG_NEW"])?$arGadgetParams["PATH_TO_BLOG_NEW"]:"/company/personal/user/#user_id#/blog/edit/new/");
$arGadgetParams["PATH_TO_CAL"] = (isset($arGadgetParams["PATH_TO_CAL"])?$arGadgetParams["PATH_TO_CAL"]:"/company/personal/user/#user_id#/calendar/");
$arGadgetParams["PATH_TO_TASK"] = (isset($arGadgetParams["PATH_TO_TASK"])?$arGadgetParams["PATH_TO_TASK"]:"/company/personal/user/#user_id#/tasks/");
$arGadgetParams["PATH_TO_TASK_NEW"] = (isset($arGadgetParams["PATH_TO_TASK_NEW"])?$arGadgetParams["PATH_TO_TASK_NEW"]:"/company/personal/user/#user_id#/tasks/task/edit/0/");
$arGadgetParams["PATH_TO_LIB"] = (
	isset($arGadgetParams["PATH_TO_LIB"])
		? $arGadgetParams["PATH_TO_LIB"]
		: (IsModuleInstalled('disk')
		? "/company/personal/user/#user_id#/disk/path/"
		: "/company/personal/user/#user_id#/files/lib/"
	)
);
?>

<ul>
<?if($arGadgetParams["SHOW_GENERAL"]!="N"):?>
<li><a href="<?=CComponentEngine::MakePathFromTemplate($arGadgetParams["PATH_TO_GENERAL"], $p)?>"><?echo GetMessage("GD_PROFILE_GENERAL")?></a>
<ul>
	<li><a href="<?=CComponentEngine::MakePathFromTemplate($arGadgetParams["PATH_TO_PROFILE_EDIT"], $p)?>"><?echo GetMessage("GD_PROFILE_CH_PROFILE")?></a>
	<li><a href="<?=CComponentEngine::MakePathFromTemplate($arGadgetParams["PATH_TO_LOG"], $p)?>"><?echo GetMessage("GD_PROFILE_LOG")?></a><?
	if (!IsModuleInstalled("im"))
	{
		?><li><a href="<?=CComponentEngine::MakePathFromTemplate($arGadgetParams["PATH_TO_MSG"], $p)?>"><?echo GetMessage("GD_PROFILE_MSG")?></a><?
	}
?></ul>
<?endif;?>
<?if($arGadgetParams["SHOW_GROUPS"]!="N"):?>
<li><a href="<?=CComponentEngine::MakePathFromTemplate($arGadgetParams["PATH_TO_GROUPS"], $p)?>"><?echo GetMessage("GD_PROFILE_GROUPS")?></a>
	<ul><li><a href="<?=CComponentEngine::MakePathFromTemplate($arGadgetParams["PATH_TO_GROUP_NEW"], $p)?>"><?echo GetMessage("GD_PROFILE_GROUP_NEW")?></a></ul>
<?endif?>


<?if($arGadgetParams["SHOW_BLOG"]!="N" && is_set($arResult, "blog")):?>
<li><a href="<?=CComponentEngine::MakePathFromTemplate($arGadgetParams["PATH_TO_BLOG"], $p)?>"><?echo GetMessage("GD_PROFILE_BLOG")?></a>
	<ul><li><a href="<?=CComponentEngine::MakePathFromTemplate($arGadgetParams["PATH_TO_BLOG_NEW"], $p)?>"><?echo GetMessage("GD_PROFILE_BLOG_NEW")?></a></ul>
<?endif;?>


<?if($arGadgetParams["SHOW_CAL"]!="N" && is_set($arResult, "calendar")):?>
<li><a href="<?=CComponentEngine::MakePathFromTemplate($arGadgetParams["PATH_TO_CAL"], $p)?>"><?echo GetMessage("GD_PROFILE_CAL")?></a>
<?endif;?>

<?if($arGadgetParams["SHOW_TASK"]!="N" && is_set($arResult, "tasks")):?>
<li><a href="<?=CComponentEngine::MakePathFromTemplate($arGadgetParams["PATH_TO_TASK"], $p)?>"><?echo GetMessage("GD_PROFILE_TASKS")?></a>
	<ul><li><a href="<?=CComponentEngine::MakePathFromTemplate($arGadgetParams["PATH_TO_TASK_NEW"], $p)?>"><?echo GetMessage("GD_PROFILE_TASK_NEW")?></a></ul>
<?endif;?>

<?if($arGadgetParams["SHOW_LIB"]!="N" && is_set($arResult, "files")):?>
<li><a href="<?=CComponentEngine::MakePathFromTemplate($arGadgetParams["PATH_TO_LIB"], $p)?>"><?echo GetMessage("GD_PROFILE_LIB")?></a>
<?endif;?>

<?if($arGadgetParams["SHOW_PHOTO"]!="N" && is_set($arResult, "photo")):?>
<li><a href="<?=CComponentEngine::MakePathFromTemplate($arGadgetParams["PATH_TO_PHOTO"], $p)?>"><?echo GetMessage("GD_PROFILE_PHOTO")?></a>
	<ul><li><a href="<?=CComponentEngine::MakePathFromTemplate($arGadgetParams["PATH_TO_PHOTO_NEW"], $p)?>"><?echo GetMessage("GD_PROFILE_PHOTO_NEW")?></a></ul>
<?endif;?>


<?if($arGadgetParams["SHOW_FORUM"]!="N" && is_set($arResult, "forum")):?>
<li><a href="<?=CComponentEngine::MakePathFromTemplate($arGadgetParams["PATH_TO_FORUM"], $p)?>"><?echo GetMessage("GD_PROFILE_FORUM")?></a>
<?endif;?>

</ul>

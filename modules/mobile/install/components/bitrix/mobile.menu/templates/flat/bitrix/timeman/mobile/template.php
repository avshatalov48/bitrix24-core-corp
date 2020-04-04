<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/**
 * @var array $arResult
 * @var array $arParams
 * @var \CBitrixComponentTemplate $this
 */
$statusClass = "default";
if ($arResult["START_INFO"]["STATE"] == "OPENED")
{
	$statusClass = "opened";
}
elseif ($arResult["START_INFO"]["STATE"] == "CLOSED")
{
	if ($arResult["START_INFO"]["CAN_OPEN"] == "REOPEN" || !$arResult["START_INFO"]["CAN_OPEN"])
	{
		$statusClass = "completed";
	}
	else
	{
		$statusClass = "start";
	}
}
elseif ($arResult["START_INFO"]["STATE"] == "PAUSED")
{
	$statusClass = "paused";
}
elseif ($arResult["START_INFO"]["STATE"] == "EXPIRED")
{
	$statusClass = "expired";
}
if (CModule::IncludeModule("pull"))
{
	CPullWatch::Add($USER->GetID(), 'TIMEMANWORKINGDAY_'.$USER->GetID(), true);
}

if ($this->__component->__parent)
	$this->__component->__parent->arParams["TIMEMAN"] = array(
		"START_INFO" => $arResult["START_INFO"],
		"STATUS" => $statusClass,
		"WORK_REPORT" => $arResult["WORK_REPORT"]
	);
$settings = CTimeManUser::instance()->GetSettings();
?>
<div class="menu-user-action menu-user-timeman menu-user-timeman-status-<?=$statusClass?>" data-bx-timeman-status="<?=$statusClass?>" id="menu-user-timeman">
	<span class="menu-user-timeman-status-title-opened"><?=GetMessage("TM_STATUS_WORK")?></span>
	<span class="menu-user-timeman-status-title-completed"><?=GetMessage("TM_STATUS_COMPLETED");?></span>
	<span class="menu-user-timeman-status-title-start"><?=GetMessage("TM_STATUS_START")?></span>
	<span class="menu-user-timeman-status-title-paused"><?=GetMessage("TM_STATUS_PAUSED")?></span>
	<span class="menu-user-timeman-status-title-expired"><?=GetMessage("TM_STATUS_EXPIRED")?></span>
	<script>
		BX.message({
			TM_NOTIF_EXPIRED : '<?=GetMessageJS("TM_NOTIF_EXPIRED")?>',
			TM_NOTIF_START : '<?=GetMessageJS("TM_NOTIF_START")?>'
		});
		BX.ready(function(){
			BX.MenuTimeman.instance(<?=CUtil::PhpToJSObject(array(
				"UF_TM_MAX_START" => $settings["UF_TM_MAX_START"]
			))?>);
		});
	</script>
</div>
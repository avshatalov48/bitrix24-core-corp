<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$templateId = $arResult['TEMPLATE_DATA']['ID'];
$role = $arResult['TEMPLATE_DATA']['ROLE'];
$editable = $arResult['TEMPLATE_DATA']['EDITABLE'];
$editableOrAuditor = $arResult['TEMPLATE_DATA']['EDITABLE_OR_AUDITOR'];
$multiple = $arResult['TEMPLATE_DATA']['MULTIPLE'];
$imAuditor = $arResult['TEMPLATE_DATA']['IM_AUDITOR'];

$id = ToLower($role).'-'.$templateId;

if (!$editableOrAuditor && $arResult['TEMPLATE_DATA']['EMPTY_LIST'])
{
	//User doesn't have a permission to change empty user list (except auditors).
	return;
}
?>

<div id="<?=$id?>" class="task-user-selector user-view-empty-true<?if(!$editable):?> readonly<?endif?><?if(!$multiple):?> single<?endif?><?if($imAuditor):?> imauditor<?endif?>">

    <div class="task-detail-sidebar-info-link" data-bx-id="<?if($editable):?>user-view-open-form<?elseif($role == 'AUDITORS'):?>user-view-toggle-auditor<?endif?>">

        <?if($editable):?>
            <span class="task-user-selector-change"><?=Loc::getMessage("TASKS_TTDP_TEMPLATE_USER_VIEW_CHANGE")?></span>
            <span class="task-user-selector-add"><?=Loc::getMessage("TASKS_TTDP_TEMPLATE_USER_VIEW_ADD")?></span>
        <?elseif($role == 'AUDITORS'):?>
            <span class="task-user-selector-enter-auditor"><?=Loc::getMessage("TASKS_TTDP_TEMPLATE_USER_VIEW_ENTER_AUDITOR")?></span>
            <span class="task-user-selector-leave-auditor"><?=Loc::getMessage("TASKS_TTDP_TEMPLATE_USER_VIEW_LEAVE_AUDITOR")?></span>
        <?endif?>

    </div>

    <div class="task-detail-sidebar-info-title <?if($multiple):?>task-detail-sidebar-info-title-line<?endif?>">
        <?=Loc::getMessage("TASKS_TTDP_TEMPLATE_USER_VIEW_".$role)?>
    </div>

    <div data-bx-id="user-view-items" <?if($multiple):?>class="task-detail-sidebar-info-users-list"<?endif?>>

        <?$i = 1;?>
        <?foreach($arResult["TEMPLATE_DATA"]["ITEMS"]['DATA'] as $j => $item):?>
            <?$last = $i == count($arResult["TEMPLATE_DATA"]["ITEMS"]['DATA']);?>

            <?if($editableOrAuditor && $last):?>
                <script type="text/html" data-bx-id="user-view-item">
            <?endif?>

			<div data-bx-id="user-view-item<?=(!$last ? ' user-view-item-'.$item['ID'] : '')?>" data-item-value="<?=htmlspecialcharsbx($item['ID'])?>" class="task-detail-sidebar-info-user task-detail-sidebar-info-user-<?=$item["USER_TYPE"]?>">

				<? if ($item["URL"] !== ""): ?>
				<a class="task-detail-sidebar-info-user-photo" data-bx-id="item-set-item-avatar" href="<?=$item["URL"]?>" target="_top"
					<?if ($item["AVATAR"] !== ""):?>
						style="background: url('<?=$item["AVATAR"]?>') center no-repeat; background-size: 40px;"
					<?endif?>></a>
				<? else:?>
				<span class="task-detail-sidebar-info-user-photo" data-bx-id="item-set-item-avatar"
					<?if ($item["AVATAR"] !== ""):?>
						style="background: url('<?=$item["AVATAR"]?>') center no-repeat; background-size: 40px;"
					<?endif?>></span>
				<? endif ?>

				<div class="task-detail-sidebar-info-user-title">
					<? if ($item["URL"] !== ""): ?>
						<a href="<?=$item["URL"]?>" class="task-detail-sidebar-info-user-name task-detail-sidebar-info-user-name-link"
						   target="_top"><?=htmlspecialcharsbx($item["NAME_FORMATTED"])?></a>
					<? else: ?>
						<span class="task-detail-sidebar-info-user-name"><?=htmlspecialcharsbx($item["NAME_FORMATTED"])?></span>
					<? endif ?>
					<div class="task-detail-sidebar-info-user-pos"><?=htmlspecialcharsbx($item["WORK_POSITION"])?></div>
					<span class="task-detail-sidebar-info-user-del" data-bx-id="user-view-item-delete" title="<?=Loc::getMessage('TASKS_TTDP_TEMPLATE_USER_VIEW_DELETE')?>"></span>
				</div>
            </div>

            <?if($editableOrAuditor && $last):?>
                </script>
                <?unset($arResult["TEMPLATE_DATA"]["ITEMS"]['DATA'][$j]);?>
            <?endif?>

            <?$i++;?>
        <?endforeach?>

    </div>

</div>

<?if($editableOrAuditor): // we need for js logic to make changing work?>

    <?
    $params = array(
        'id' => $id,
        'scope' => $id,
        'nameTemplate' => empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]),
        'data' => $arResult["TEMPLATE_DATA"]["ITEMS"]['DATA'],
        'taskId' => intval($arResult["TEMPLATE_DATA"]["TASK_ID"]),
        'role' => $role,
        'multiple' => $multiple,
	    'pathToTasks' => $arParams['PATH_TO_TASKS'],
		'useAdd' => $arResult['TEMPLATE_DATA']['CAN_ADD_MAIL_USERS'],
    );
    if(!$multiple)
    {
        $params['min'] = 1;
        $params['max'] = 1;
    }
    if(\Bitrix\Tasks\Util\Type::isIterable($arResult['TEMPLATE_DATA']['USER']))
    {
        $params['user'] = $arResult['TEMPLATE_DATA']['USER'];
    }
    ?>

    <script>
        new BX.Tasks.Component.TaskDetailPartsUserView(<?=CUtil::PhpToJSObject($params)?>);
    </script>
<?endif?>
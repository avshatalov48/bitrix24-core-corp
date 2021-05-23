<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\UI;

Loc::loadMessages(__FILE__);

$helper = $arResult['HELPER'];
$arParams =& $helper->getComponent()->arParams; // make $arParams the same variable as $this->__component->arParams, as it really should be
?>

<?//$helper->displayFatals();?>
<?if(!$helper->checkHasFatals()):?>

	<div id="<?=$helper->getScopeId()?>" class="tasks tasks-replication-view <?if($arParams['REPLICATE']):?>enabled<?endif?>">

		<?//$helper->displayWarnings();?>

		<?if(!$arParams['ENTITY_ID']):?>
			<?=Loc::getMessage("TASKS_SIDEBAR_TEMPLATE_NOT_ACCESSIBLE")?>
		<?else:?>
			<div class="js-id-replication-detail tasks-replication-detail <?=($arParams['REPLICATE'] ? '' : 'invisible')?>">
				<div class="tasks-replication-detail-inner">
					<?=Loc::getMessage("TASKS_SIDEBAR_TASK_REPEATS")?> <?=UI\Task\Template::makeReplicationPeriodString($arParams['DATA'])?>
					<?if($arParams['ENABLE_TEMPLATE_LINK']):?>
						<br />(<a href="<?=$arParams["PATH_TO_TEMPLATES_TEMPLATE"]?>" target="_top"><?=Loc::getMessage("TASKS_COMMON_TEMPLATE_LC")?></a>)
					<?endif?>
				</div>
			</div>
			<?if($arParams['ENABLE_SYNC']):?>
				<span class="js-id-replication-switch task-dashed-link tasks-replication-view-switch">
					<span class="task-dashed-link-inner tasks-replication-view-enable"><?=Loc::getMessage('TASKS_TWRV_ENABLE_REPLICATION');?></span>
					<span class="task-dashed-link-inner tasks-replication-view-disable"><?=Loc::getMessage('TASKS_TWRV_DISABLE_REPLICATION');?></span>
				</span>
			<?endif?>

			<?$helper->initializeExtension();?>

		<?endif?>

	</div>

<?endif?>
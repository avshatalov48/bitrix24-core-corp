<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$helper = $arResult['HELPER'];
$arParams =& $helper->getComponent()->arParams; // make $arParams the same variable as $this->__component->arParams, as it really should be
?>

<?//$helper->displayFatals();?>
<?if(!$helper->checkHasFatals()):?>

	<?
	$readOnly = $arResult['TEMPLATE_DATA']['READ_ONLY'];
	$multiple = $arParams['MAX'] > 1;

	if($arParams['HIDE_IF_EMPTY'] && !count($arParams['DATA']))
	{
		return;
	}
	?>

	<div id="<?=$helper->getScopeId()?>" class="tasks task-user-selector mem-sel-empty-true<?if($readOnly):?> readonly<?endif?><?if(!$multiple):?> single<?endif?>">

		<?//$helper->displayWarnings();?>
		<div class="task-detail-sidebar-info-link <?if(!$readOnly):?>js-id-mem-sel-is-open-form<?else:?>js-id-mem-sel-header-button<?endif?>">
			<?if(!$readOnly):?>
				<span class="task-user-selector-change"><?=Loc::getMessage("TASKS_COMMON_CHANGE_LCF")?></span>
				<span class="task-user-selector-add"><?=Loc::getMessage("TASKS_COMMON_ADD_LCF")?></span>
			<?elseif($arParams['HEADER_BUTTON_LABEL_IF_READ_ONLY'] != ''):?>
				<?=htmlspecialcharsbx($arParams['HEADER_BUTTON_LABEL_IF_READ_ONLY'])?>
			<?endif?>
		</div>

		<div class="task-detail-sidebar-info-title <?if($multiple):?>task-detail-sidebar-info-title-line<?endif?>">
			<?=($arParams['TITLE'] != '' ? htmlspecialcharsbx($arParams['TITLE']) : '&nbsp;')?>
		</div>

		<div class="js-id-mem-sel-is-items<?if($multiple):?> task-detail-sidebar-info-users-list<?endif?>">
			<script type="text/html" data-bx-id="mem-sel-is-item">
				<?ob_start();?>
				<div class="task-detail-sidebar-info-user-wrap js-id-mem-sel-is-i js-id-mem-sel-is-i-{{VALUE}} {{ITEM_SET_INVISIBLE}}" data-item-value="{{VALUE}}">
					<div class="task-detail-sidebar-info-user task-detail-sidebar-info-user-{{USER_TYPE}}">
						<? if ($arParams["PUBLIC_MODE"]):?>
							<span class="js-id-item-set-is-i-avatar task-detail-sidebar-info-user-photo" style="{{AVATAR_CSS}}"></span>
						<? else: ?>
							<a class="js-id-item-set-is-i-avatar task-detail-sidebar-info-user-photo" href="{{URL}}" target="_top" style="{{AVATAR_CSS}}"></a>
						<? endif ?>
						<div class="task-detail-sidebar-info-user-title">
							<? if ($arParams["PUBLIC_MODE"]):?>
								<span class="task-detail-sidebar-info-user-name">{{DISPLAY}}</span>
							<? else: ?>
								<a href="{{URL}}" class="task-detail-sidebar-info-user-name task-detail-sidebar-info-user-name-link" target="_top">{{DISPLAY}}</a>
							<? endif ?>
							<div class="task-detail-sidebar-info-user-pos">{{WORK_POSITION}}</div>

							<?if(!$readOnly):?>
								<span class="js-id-mem-sel-is-i-delete tasks-btn-delete task-detail-sidebar-info-user-del" title="<?=Loc::getMessage('TASKS_COMMON_DELETE')?>"></span>
							<?endif?>
						</div>
					</div>
				</div>
				<?$template = trim(ob_get_flush());?>
			</script>

			<?
			foreach($arParams['DATA'] as $item)
			{
				print($helper->fillTemplate($template, $item));
			}
			?>

		</div>

	</div>

	<?if(!$readOnly || ($readOnly && !$arParams['DISABLE_JS_IF_READ_ONLY'])):?>
		<?$helper->initializeExtension();?>
	<?endif?>

<?endif?>

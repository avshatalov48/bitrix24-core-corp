<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$helper = $arResult['HELPER'];

\Bitrix\Main\UI\Extension::load('ui.entity-selector');
?>

<?//$helper->displayFatals();?>
<?if(!$helper->checkHasFatals()):?>

	<?//$helper->displayWarnings();?>

	<?$group = $arResult['DATA'];?>
	<?$empty = empty($group);?>
	<?$readOnly = $arParams['READ_ONLY'];?>

	<?if(!$empty || !$readOnly):?>

		<span id="<?=$helper->getScopeId()?>">

			<span class="js-id-ms-plink-item task-group-field <?=($empty ? 'invisible' : '')?>"><?
				?><span class="task-group-field-inner"><?
					?><a href="<?= ($group['URL'] ?? null) ?>" class="js-id-ms-plink-item-link task-group-field-label" target="_top"><?=htmlspecialcharsbx($group['DISPLAY'] ?? null)?></a><?
					?><?if(!$readOnly):?><span class="js-id-ms-plink-deselect task-group-field-title-del"></span><?endif?><?
				?></span><?
			?></span>

			<?if(!$readOnly):?>
				<span class="js-id-ms-plink-control task-dashed-link task-group-select <?=($empty ? '' : 'invisible')?>">
					<span class="task-dashed-link-inner"><?=Loc::getMessage("TASKS_COMMON_ADD")?></span>
				</span>
			<?endif?>

		</span>

		<?if(!$readOnly):?>
			<?$helper->initializeExtension();?>
		<?endif?>

	<?endif?>

<?endif?>

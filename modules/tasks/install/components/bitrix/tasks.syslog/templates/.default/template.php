<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$helper = $arResult['HELPER'];
$arParams =& $helper->getComponent()->arParams; // make $arParams the same variable as $this->__component->arParams, as it really should be
?>

<?//$helper->displayFatals();?>
<?if(!$helper->checkHasFatals()):?>

	<div id="<?=$helper->getScopeId()?>" class="tasks">

		<?//$helper->displayWarnings();?>

		<table id="task-log-table" class="task-log-table">
			<col class="task-log-type-column" />
			<col class="task-log-date-column" />
			<col class="task-log-message-column" />
			<col class="task-log-errors-column" />

			<tr>
				<th class="task-log-column-header task-log-type-column"><?=Loc::getMessage('TASKS_CSL_T_COL_TYPE')?></th>
				<th class="task-log-column-header task-log-date-column"><?=Loc::getMessage('TASKS_CSL_T_COL_DATE')?></th>
				<th class="task-log-column-header task-log-message-column"><?=Loc::getMessage('TASKS_CSL_T_COL_MESSAGE')?></th>
				<th class="task-log-column-header task-log-errors-column"><?=Loc::getMessage('TASKS_CSL_T_COL_ERRORS')?></th>
			</tr>

			<?foreach($arResult['DATA']['ITEMS'] as $item):?>

				<tr>
					<td class="task-log-column-data task-log-type-column task-log-message-level-<?=intval($item['TYPE'])?>">
						<?=Loc::getMessage('TASKS_CSL_T_MESSAGE_TYPE_'.intval($item['TYPE']));?>
					</td>
					<td class="task-log-column-data task-log-date-column">
						<span class="task-log-date"><?=htmlspecialcharsbx($item['CREATED_DATE']);?></span>
					</td>
					<td class="task-log-column-data task-log-message-column">
						<?=htmlspecialcharsbx($item['MESSAGE']);?>
					</td>
					<td class="task-log-column-data task-log-errors-column">
						<?if(count($item['ERROR'])):?>
							<ul class="task-log-errors">
							<?foreach($item['ERROR'] as $error):?>
								<li><?=htmlspecialcharsbx($error->getMessage())?></li>
							<?endforeach?>
							</ul>
						<?else:?>
							<?=Loc::getMessage('TASKS_CSL_T_NO_ERROR')?>
						<?endif?>
					</td>
				</tr>

			<?endforeach?>

		</table>

	</div>

	<?//$helper->initializeExtension();?>

<?endif?>
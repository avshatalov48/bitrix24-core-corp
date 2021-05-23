<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration\Bitrix24\UI;

Loc::loadMessages(__FILE__);

$helper = $arResult['HELPER'];
?>

<?if (!$arResult['DATA']['FEATURE_ENABLED']):?>
	<div class="tasks-btn-restricted tasks-uf-panel-restricted">
		<?= Loc::getMessage("TASKS_TWR_TEMPLATE_FEATURE_ACCESS_RESTRICTED");?>
		<a href="<?= UI::getLicenseUrl();?>" target="_blank"><?=Loc::getMessage('TASKS_TWR_TEMPLATE_FEATURE_ACCESS_RESTRICTED_MORE');?></a>
	</div>
<?else:?>
	<?$helper->displayFatals();?>
	<?if (!$helper->checkHasFatals()):?>
		<div id="<?=$helper->getScopeId()?>" class="tasks">
			<?
			$helper->displayWarnings();
			$inputPrefix = $arParams['INPUT_PREFIX'];
			?>

			<?if($arParams['CAN_READ']):?>
				<table class="task-options-task-other tasks-rights-table">
					<thead>
					<tr>
						<td><?=Loc::getMessage('TASKS_TWR_TEMPLATE_USER')?></td>
						<td class="tasks-rights-table-gap-col">&nbsp;</td>
						<td class="tasks-rights-table-al-col"><?=Loc::getMessage('TASKS_TWR_TEMPLATE_ACCESS_LEVEL')?></td>
						<td class="tasks-rights-table-gap-col">&nbsp;</td>
					</tr>
					</thead>
					<tbody class="js-id-rights-is-items">
						<script data-bx-id="rights-is-item" type="text/html">
							<?ob_start();?>
							<tr data-item-value="{{VALUE}}" class="js-id-rights-is-i js-id-rights-is-i-{{VALUE}} {{ITEM_SET_INVISIBLE}}">
								<td>
									<a class="tasks-rights-user-link" href="{{URL}}" target="_blank">{{DISPLAY}}</a>
									<input type="hidden" name="<?=$inputPrefix?>[{{VALUE}}][GROUP_CODE]" value="{{MEMBER_TYPE}}{{MEMBER_ID}}" />
									<input type="hidden" name="<?=$inputPrefix?>[{{VALUE}}][ID]" value="{{ID}}" />
								</td>
								<td></td>
								<td>
									<span class="task-dashed-link">
										<span class="js-id-rights-is-i-operation-title task-dashed-link-inner">
											{{TITLE}}
										</span>
										<input class="js-id-rights-is-i-operation" type="hidden" name="<?=$inputPrefix?>[{{VALUE}}][TASK_ID]" value="{{TASK_ID}}" />
									</span>
								</td>

								<td>
									<span class="js-id-rights-is-i-delete tasks-btn-delete task-options-title-del" title="<?=Loc::getMessage('TASKS_COMMON_DELETE')?>"></span>
								</td>
							</tr>
							<?$template = trim(ob_get_flush());?>
						</script>

						<?
						foreach($arResult['JS_DATA']['data'] as $item)
						{
							print($helper->fillTemplate($template, $item));
						}
						?>
					</tbody>
				</table>
				<div class="tasks-rights-footer">
					<span class="task-dashed-link">
						<span class="js-id-rights-is-open-form task-dashed-link-inner"><?=Loc::getMessage('TASKS_COMMON_ADD')?></span>
					</span>
				</div>
				<input type="hidden" name="<?=$inputPrefix?>[]" value="" />
			<?endif?>
		</div>

		<script type="text/javascript">
			BX.message({
				'path_user': '<?= $arResult['PATHS']['USER'];?>',
				'path_group': '<?= $arResult['PATHS']['GROUP']?>',
				'path_department': '<?= $arResult['PATHS']['DEPARTMENT']?>'
			});
		</script>

		<?$helper->initializeExtension();?>
	<?endif?>
<?endif;?>
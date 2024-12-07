<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Helper\RestrictionUrl;
use Bitrix\Tasks\Integration\Bitrix24;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit;

/** @var array $arResult */
/** @global $APPLICATION */

Loc::loadMessages(__FILE__);

$helper = $arResult['HELPER'];
$arParams =& $helper->getComponent()->arParams; // make $arParams the same variable as $this->__component->arParams, as it really should be

?>

<?//$helper->displayFatals();?>
<?if(!$helper->checkHasFatals()):?>

	<div id="<?=$helper->getScopeId()?>" class="tasks">

		<?foreach($arParams['OPTIONS'] as $option):?>
			<?php
				$limitedCodes = [
					'MATCH_WORK_TIME' => [
						'limitExceeded' => $arResult['TASK_SKIP_WEEKENDS_LIMIT_EXCEEDED'],
						'clickHandler' => Limit::getLimitLockClick(
							Bitrix24\FeatureDictionary::TASK_SKIP_WEEKENDS,
							null,
						),
					],
					'TASK_CONTROL' => [
						'limitExceeded' => $arResult['TASK_CONTROL_LIMIT_EXCEEDED'],
						'clickHandler' => Limit::getLimitLockClick(
							Bitrix24\FeatureDictionary::TASK_CONTROL,
							null,
						),
					],
				];
				$limitExceeded = false;
				$onOptionLockClick = null;
				$lockClassStyle = "cursor: pointer;";
				$lockClassName = 'task-field-locked';
				if (array_key_exists($option['CODE'], $limitedCodes))
				{
					$limitData = $limitedCodes[$option['CODE']];
					if ($limitData['limitExceeded'])
					{
						$limitExceeded = true;
						$onOptionLockClick = $limitData['clickHandler'];
						$option['VALUE'] = 'N';
					}
				}
			?>
			<?$optionJs = mb_strtolower(str_replace('_', '-', $option['CODE']));?>
			<div class="task-options-field">
				<div class="task-options-field-inner">
					<label
						class="
								task-field-label
								js-id-hint-help
								js-id-wg-optbar-flag-label-<?=htmlspecialcharsbx($optionJs)?>
							"
						data-hint-enabled="<?=htmlspecialcharsbx($option['HINT_ENABLED'])?>"
						data-hint-text="<?=$option['HINT_TEXT']?>"
					>
						<?if($option['HELP_TEXT'] != ''):?>
							<span class="js-id-hint-help task-options-help tasks-icon-help tasks-help-cursor"><?=$option['HELP_TEXT']?></span>
						<?endif?>
						<input
							data-target="<?=htmlspecialcharsbx($optionJs)?>"
							data-flag-name="<?=htmlspecialcharsbx($option['CODE'])?>"
							data-yes-value="<?=htmlspecialcharsbx($option['YES_VALUE'])?>"
							data-no-value="<?=htmlspecialcharsbx($option['NO_VALUE'])?>"
							<?=($option['YES_VALUE'] == $option['VALUE'] ? 'checked' : '')?>
							<?=($option['DISABLED'] ? 'disabled' : '')?>
							class="
									js-id-wg-optbar-flag
									js-id-wg-optbar-flag-<?=htmlspecialcharsbx($optionJs)?>
									<?=htmlspecialcharsbx($option['FLAG_CLASS'])?>
									task-field-checkbox
								"
							type="checkbox"><?=htmlspecialcharsbx($option['TEXT'])?>
					</label>
					<input
						class="js-id-wg-optbar-<?=htmlspecialcharsbx($optionJs)?>"
						type="hidden"
						name="<?=htmlspecialcharsbx($arParams['INPUT_PREFIX'])?>[<?=htmlspecialcharsbx($option['CODE'])?>]"
						value="<?=htmlspecialcharsbx($option['VALUE'])?>"

						<?=($option['DISABLED'] ? 'disabled' : '')?>
					/>
					<?php if (array_key_exists($option['CODE'], $limitedCodes)): ?>
						<?php if ($onOptionLockClick): ?>
							<span
								class="<?=$lockClassName?>"
								onclick="<?=$onOptionLockClick?>"
								style="<?=$lockClassStyle?>"
							></span>
						<?php endif; ?>
					<?php endif; ?>

					<?if($option['LINK'] && !$limitExceeded):?>
                        <a href="<?=htmlspecialcharsbx($option['LINK']['URL'])?>" target="_blank"><?=htmlspecialcharsbx($option['LINK']['TEXT'])?></a>
					<?endif?>
					<?if (($option['LINKS'] ?? null) && !$limitExceeded):?>
                        <?foreach($option['LINKS'] as $link):?>
                            <a href="<?=htmlspecialcharsbx($link['URL'])?>" target="_blank"><?=htmlspecialcharsbx($link['TEXT'])?></a>
                        <?endforeach?>
					<?endif?>
					<?if ($option['FIELDS'] ?? null):?>
                    <div id="js-id-wg-optbar-fields" class="js-id-wg-optbar-fields">
                        <?foreach($option['FIELDS'] as $field):
                            if(!isset($field['ID']))
                            {
	                            $field['ID'] = 'field-'.randString(5);
                            }
                        ?>
                            <div id="<?=$field['ID']?>" class="js-id-wg-optbar-field js-id-wg-optbar-field-<?=$field['TYPE']?>">
                                <?php include(__DIR__.'/'.mb_strtolower($field['TYPE']).'-field.php')?>
                            </div>
                        <?endforeach?>
                    </div>
					<?endif?>

				</div>
			</div>
		<?endforeach?>

	</div>

	<?$helper->initializeExtension();?>

<?endif?>
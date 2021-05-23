<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\UI\Task\Tag;

Loc::loadMessages(__FILE__);

$helper = $arResult['HELPER'];
?>

<?$helper->displayFatals();?>
<?if(!$helper->checkHasFatals()):?>

	<div id="<?=$helper->getScopeId()?>" class="tasks task-form-field <?=$arParams['DISPLAY']?> <?=($arParams['READ_ONLY'] ? 'readonly' : '')?>" <?if($arParams['MAX_WIDTH'] > 0):?>style="max-width: <?=$arParams['MAX_WIDTH']?>px"<?endif?>>

		<?$helper->displayWarnings();?>

		<span class="js-id-tag-sel-items tasks-h-invisible">
		    <script type="text/html" data-bx-id="tag-sel-item">
			    <?ob_start();?>
			    <span class="js-id-tag-sel-item js-id-tag-sel-item-{{VALUE}} task-form-field-item {{ITEM_SET_INVISIBLE}}" data-item-value="{{VALUE}}">
					<span class="task-form-field-item-text" class="task-options-destination-text">
						{{DISPLAY}}
					</span>
					<span class="js-id-tag-sel-item-delete task-form-field-item-delete" title="<?=Loc::getMessage('TASKS_COMMON_CANCEL_SELECT')?>"></span>
					<input type="hidden" name="<?=htmlspecialcharsbx($arParams["INPUT_PREFIX"])?>[{{VALUE}}][NAME]" value="{{DISPLAY}}" />
			    </span>
			    <?$template = trim(ob_get_flush());?>
		    </script>
			<?
			foreach($arParams['DATA'] as $item)
			{
				print($helper->fillTemplate($template, $item));
			}
			?></span>

	    <span class="task-form-field-controls">
		    <a href="javascript:void(0);" class="js-id-tag-sel-open-form task-form-field-link add">
			    <span class="task-form-field-when-filled"><?=Loc::getMessage('TASKS_COMMON_ADD_MORE')?></span>
			    <span class="task-form-field-when-empty"><?=Loc::getMessage('TASKS_COMMON_ADD')?></span>
		    </a>
	    </span>
		<?// component tasks.tags.selector is deprecated and should be removed ASAP ?>
		<?$APPLICATION->IncludeComponent(
			"bitrix:tasks.tags.selector",
			".default",
			array(
				"NAME" => "TAGS",
				"VALUE" => Tag::formatTagString($arParams['DATA']),
				"SILENT" => 'Y'
			),
			null,
			array("HIDE_ICONS" => "Y")
		);?>

		<?// in case of all items removed, the field should be sent anyway?>
		<input type="hidden" name="<?=htmlspecialcharsbx($arParams["INPUT_PREFIX"])?>[]" value="" />
	</div>
	
	<?$helper->initializeExtension();?>

<?endif?>
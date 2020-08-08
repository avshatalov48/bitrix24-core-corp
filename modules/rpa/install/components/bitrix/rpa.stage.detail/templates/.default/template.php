<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load([
	'ui.forms',
	'ui.selector',
	'rpa.component',
	'rpa.fieldspopup',
]);

/** @var CBitrixComponentTemplate $this */
/** @var RpaStageDetailComponent $component */
$component = $this->getComponent();
/** @var \Bitrix\Rpa\Model\Stage $stage */
$stage = $arResult['stage'];
/** @var \Bitrix\Rpa\Model\Type $type*/
$type = $arResult['type'];

if($component->getErrors())
{
	foreach($component->getErrors() as $error)
	{
		?>
		<div><?=$error->getMessage();?></div>
		<?php
	}

	return;
}

function renderPermissionSelector(string $action, array $selectedCodes)
{
	global $APPLICATION;
	?>
		<div data-role="permission-setting-<?=$action;?>">
			<div class="ui-ctl ui-ctl-textbox">
				<label><?=Loc::getMessage('RPA_COMPONENT_STAGE_WHO_CAN_'.mb_strtoupper($action));?></label>
			<?php
			$APPLICATION->IncludeComponent(
				"bitrix:main.user.selector",
				"",
				[
					"ID" => 'rpa-perm-stage-'.$action,
					"LIST" => $selectedCodes,
					"INPUT_NAME" => 'permissions['.$action.'][accessCode][]',
					"USE_SYMBOLIC_ID" => true,
					"BUTTON_SELECT_CAPTION" => Loc::getMessage('MPF_DESTINATION_1'),
					"BUTTON_SELECT_CAPTION_MORE" => Loc::getMessage('MPF_DESTINATION_2'),
					"API_VERSION" => 3,
					"SELECTOR_OPTIONS" => [
						'context' => 'RPA_STAGE_'.mb_strtoupper($action),
						'contextCode' => 'U',
						'useSearch' => 'Y',
						'userNameTemplate' => \CSite::GetNameFormat(),
						'enableDepartments' => 'Y',
						'enableSonetgroups' => 'Y',
						'departmentSelectDisable' => 'N',
						'showVacations' => 'Y',
						'useClientDatabase' => 'N',
						'allowAddUser' => 'N',
						'allowAddCrmContact' => 'N',
						'allowAddSocNetGroup' => 'N',
						'allowSearchEmailUsers' => 'N',
						'allowSearchCrmEmailUsers' => 'N',
						'allowSearchNetworkUsers' => 'N',
						'enableAll' => 'Y',
					]
				]
			);
			?>
			</div>
			<div class="ui-ctl ui-ctl-textbox" style="display: none;">
				<label><?=Loc::getMessage('RPA_COMPONENT_STAGE_PERMISSION');?></label>
				<select name="permissions[<?=$action;?>][permission][]">
					<?foreach(\Bitrix\Rpa\UserPermissions::getPermissionsMap() as $id => $name)
					{
					?>
						<option value="<?=$id;?>"<?php
						if($id === \Bitrix\Rpa\UserPermissions::PERMISSION_ANY)
						{
							echo ' selected';
						}
						?>><?=$name;?></option>
					<?php
					}?>
				</select>
			</div>
		</div>
<?php
}

function renderFieldsPopup(string $visibility, array $settings)
{
?>
	<div class="ui-ctl ui-ctl-textbox">
		<label><?=Loc::getMessage('RPA_COMPONENT_STAGE_'.mb_strtoupper($visibility).'_FIELDS');?></label>
		<a id="<?=$settings['id'];?>"><?=Loc::getMessage('RPA_COMPONENT_STAGE_FIELD_CHOOSE');?></a>
	</div>
<?php
}

function renderNextStageSelector(\Bitrix\Rpa\Model\Stage $stage)
{
?>
	<div class="ui-ctl ui-ctl-textbox">
		<label><?=Loc::getMessage('RPA_COMPONENT_STAGE_NEXT_POSSIBLE_STAGES');?></label>
		<select name="possibleNextStages[]" multiple="multiple">
			<?php
			$currentNextStages = $stage->getPossibleNextStageIds();
			foreach($stage->getType()->getStages() as $nextStage)
			{
				if($nextStage->getId() === $stage->getId())
				{
					continue;
				}
				?>
				<option value="<?=$nextStage->getId();?>"
					<?php
					if(isset($currentNextStages[$nextStage->getId()]))
					{
						echo ' selected="selected"';
					}
					if($nextStage->getId() === $stage->getId())
					{
						echo ' disabled="disabled"';
					}
					?>><?=$nextStage->getName();?></option>
			<?php
			}
			?>
		</select>
	</div>
<?php
}
?>

<div class="rpa-wrapper">
	<form id="rpa-stage-form">
		<input type="hidden" name="typeId" value="<?=(int) $type->getId();?>" />
		<input type="hidden" name="id" value="<?=(int) $stage->getId();?>" />
		<div class="ui-ctl ui-ctl-textbox">
			<label><?=Loc::getMessage('RPA_COMPONENT_STAGE_FIELD_NAME');?></label>
			<input type="text" class="ui-ctl-element" name="name" value="<?=htmlspecialcharsbx($stage->getName());?>" placeholder="<?=Loc::getMessage('RPA_COMPONENT_STAGE_FIELD_NAME');?>">
		</div>
		<div class="ui-ctl ui-ctl-textbox">
			<label><?=Loc::getMessage('RPA_COMPONENT_STAGE_FIELD_CODE');?></label>
			<input type="text" class="ui-ctl-element" name="code" value="<?=htmlspecialcharsbx($stage->getCode());?>" placeholder="<?=Loc::getMessage('RPA_COMPONENT_STAGE_FIELD_CODE');?>">
		</div>
		<?php
		if($arResult['isFirstStage'])
		{
			renderPermissionSelector('create', $stage->getAccessCodesForModify());
		}
		else
		{
			//renderPermissionSelector('view', $stage->whoCanView());
			renderPermissionSelector('modify', $stage->getAccessCodesForModify());
			renderPermissionSelector('move', $stage->getAccessCodesForItemsMove());
		}
		foreach($arResult['jsParams']['fields'] as $visibility => $settings)
		{
			renderFieldsPopup($visibility, $settings);
		}
		renderNextStageSelector($stage);
		?>
		<div class="ui-ctl ui-ctl-textbox">
			<label><?=Loc::getMessage('RPA_COMPONENT_STAGE_ACTIONS');?></label>
			<span>Some automation settings here</span>
		</div>
		<div id="salescenter-cashbox-buttons">
			<?php
			$buttons = [
				[
					'TYPE' => 'save',
				],
				'cancel' => '/bitrix/components/bitrix/rpa.type.detail/slider.php?id='.$type->getId(),
			];
			$buttons[] = [
				'TYPE' => 'remove',
			];
			$APPLICATION->IncludeComponent(
				'bitrix:ui.button.panel',
				"",
				[
					'BUTTONS' => $buttons,
					'ALIGN' => 'center'
				],
				$this->getComponent()
			);
			?>
		</div>
	</form>
</div>
<script>
	BX.ready(function()
	{
		<?='BX.message('.\CUtil::PhpToJSObject($arResult['messages']).');'?>
		(new BX.Rpa.StageComponent(document.getElementById('rpa-stage-form'), <?=CUtil::PhpToJSObject($arResult['jsParams']);?>)).init();
	});
</script>
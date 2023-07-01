<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

global $APPLICATION;
$bodyClass = $APPLICATION->getPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-background");
use \Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load([
	'ui.forms',
	'rpa.component',
	'ui.selector',
	'rpa.fieldscontroller',
	'uf',
	'ui.alerts',
]);
$APPLICATION->SetAdditionalCSS("/bitrix/css/main/font-awesome.css");

/** @var CBitrixComponentTemplate $this */
/** @var RpaTypeDetailComponent $component */
$component = $this->getComponent();

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
	<div class="rpa-type-permissions" data-role="permission-setting-<?=$action;?>">
		<?php
		$APPLICATION->IncludeComponent(
			"bitrix:main.user.selector",
			"",
			[
				"ID" => 'rpa-perm-type-'.$action,
				"LIST" => $selectedCodes,
				"INPUT_NAME" => 'permissions['.$action.'][accessCode][]',
				"USE_SYMBOLIC_ID" => true,
				"BUTTON_SELECT_CAPTION" => Loc::getMessage('MPF_DESTINATION_1'),
				"BUTTON_SELECT_CAPTION_MORE" => Loc::getMessage('MPF_DESTINATION_2'),
				"API_VERSION" => 3,
				"SELECTOR_OPTIONS" => [
					'context' => 'RPA_TYPE_'.mb_strtoupper($action),
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
		<div class="ui-ctl ui-ctl-textbox" style="display: none;">
			<select name="permissions[<?=$action;?>][permission][]">
				<option value="X" selected>X</option>
			</select>
		</div>
	</div>
	<?php
}

/** @var \Bitrix\Rpa\Model\Type $type */
$type = $component->getType();
?>

<div class="rpa-type">
	<div class="ui-alert ui-alert-danger" style="display: none;">
		<span class="ui-alert-message" id="rpa-type-errors"></span>
		<span class="ui-alert-close-btn" onclick="this.parentNode.style.display = 'none';"></span>
	</div>
	<form id="rpa-type-form" class="rpa-type-form">
		<input type="hidden" data-type="field" name="id" value="<?= (int) $type->getId();?>" />
		<div class="rpa-automation-block">
			<div class="rpa-automation-title">
				<span class="rpa-automation-title-text"><?=Loc::getMessage('RPA_COMMON_PROCESS');?></span>
			</div>
			<div class="rpa-automation-section">
				<div class="rpa-automation-field">
					<label class="rpa-automation-label" for="title"><?=Loc::getMessage('RPA_COMMON_TITLE');?></label>
					<div class="ui-ctl ui-ctl-w100">
						<input data-type="field" type="text" class="ui-ctl-element" name="title" value="<?=htmlspecialcharsbx($type->getTitle());?>" placeholder="<?=Loc::getMessage('RPA_COMMON_TITLE');?>">
					</div>
				</div>
			</div>
			<?php
			if($arResult['isNew'])
			{
			?>
			<div class="rpa-automation-section">
				<div class="rpa-automation-field">
					<div class="ui-ctl ui-ctl-checkbox ui-ctl-w100">
						<input data-type="scenario" type="checkbox" class="ui-ctl-element" id="rpa-type-scenario-move-item-on-create" name="scenarios" value="moveItemOnCreate" checked="checked">
						<label class="rpa-automation-label" for="rpa-type-scenario-move-item-on-create"><?=Loc::getMessage('RPA_MOVE_TO_THE_NEXT_STAGE');?></label>
					</div>
				</div>
			</div>
			<?php
			}
			?>
		</div>
		<div class="rpa-automation-block">
			<div class="rpa-automation-title">
				<span class="rpa-automation-title-text"><?=Loc::getMessage('RPA_COMMON_LAUNCH');?></span>
			</div>
			<div class="rpa-automation-section">
				<?php
				/*
				<div class="rpa-automation-options">
					<div class="rpa-automation-options-item rpa-automation-options-item-selected rpa-automation-options-item-person">
						<span class="rpa-automation-options-subject">
							<span class="rpa-type-text"><?=Loc::getMessage('RPA_LAUNCH_MANUAL');?></span>
						</span>
					</div>
					<div class="rpa-automation-options-item rpa-automation-options-item-afterend">
						<span class="rpa-automation-options-subject">
							<span class="rpa-type-text"><?=Loc::getMessage('RPA_LAUNCH_AUTOMATION');?></span>
						</span>
					</div>
					<div class="rpa-automation-options-item rpa-automation-options-item-shedule">
						<span class="rpa-automation-options-subject">
							<span class="rpa-type-text"><?=Loc::getMessage('RPA_LAUNCH_SCHEDULE');?></span>
						</span>
					</div>
				</div>
				*/?>
				<div class="rpa-automation-field">
					<label class="rpa-automation-label" for="title"><?=Loc::getMessage('RPA_LAUNCH_MANUAL_PERMISSION');?></label>
					<?php
						renderPermissionSelector('items_create', $type->getAccessCodesForAddItems());
					?>
				</div>
			</div>
		</div>
		<div class="rpa-automation-block">
			<div class="rpa-automation-title">
				<span class="rpa-automation-title-text"><?=Loc::getMessage('RPA_FIELDS_SELECTOR_TITLE');?></span>
			</div>
			<div class="rpa-automation-section">
				<div id="rpa-fields-selector"></div>
			</div>
		</div>
		<div class="rpa-automation-block">
			<div class="rpa-automation-title">
				<span class="rpa-automation-title-text"><?=Loc::getMessage('RPA_COMMON_PERMISSIONS');?></span>
			</div>
			<?php
			/*
			<div class="rpa-automation-section">
				<div class="rpa-type-access-desc">
					<div class="rpa-type-access-desc-text"><?=Loc::getMessage('RPA_COMMON_PERMISSIONS_DESCRIPTION');?></div>
				</div>
				<div class="rpa-type-btn-block">
					<button class="ui-btn ui-btn-sm ui-btn-light-border"><?=Loc::getMessage('RPA_COMMON_PERMISSIONS_BUTTON');?></button>
				</div>
			</div>
			*/
			?>
			<div class="rpa-automation-section">
				<div class="rpa-automation-field">
					<label class="rpa-automation-label" for="title"><?=Loc::getMessage('RPA_COMPONENT_TYPE_WHO_CAN_MODIFY');?></label>
					<div class="ui-ctl ui-ctl-w100">
						<?php
						renderPermissionSelector('modify', $type->getAccessCodesForModify());
						/*?><div style="display: none;"><?php
						renderPermissionSelector('view', $type->whoCanView());
						*/?>
					</div>
				</div>
			</div>
		</div>
		<div class="rpa-automation-block">
			<div class="rpa-automation-title">
				<span class="rpa-automation-title-text"><?=Loc::getMessage('RPA_TYPE_IMAGE_SECTION');?></span>
			</div>
			<div class="rpa-automation-section rpa-automation-section-image" data-role="icon-selector">
				<?php /*
				<div class="rpa-type-image-wrap">
					<div class="rpa-type-image-block">
						<div class="rpa-type-image-inner">
							<div class="rpa-type-image-header"></div>
							<div class="rpa-type-image-content">
								<span class="rpa-type-image fa fa-plane" style="background-color: #3949a0;"></span>
								<span class="rpa-type-image" style="background-image: url(&quot;https://cdn.bitrix24.site/bitrix/images/landing/business/1920x1080/img6.jpg&quot;);">
									<span class="rpa-type-image-delete"></span>
								</span>
							</div>
							<div class="rpa-type-image-footer"></div>
						</div>
					</div>
					<div class="rpa-type-btn-block">
						<button class="ui-btn ui-btn-sm ui-btn-light">������� ������</button>
						<button class="ui-btn ui-btn-sm ui-btn-light"><?=Loc::getMessage('RPA_TYPE_IMAGE_UPLOAD');?></button>
						<button class="ui-btn ui-btn-sm ui-btn-light ui-btn-disabled">�������� ��������</button>
					</div>
				</div>
				<div class="rpa-type-image-palette">
					<div class="rpa-type-image-palette-list">
						<div class="rpa-type-image-palette-item rpa-type-image-palette-item-selected" style="background-color: #3949a0;"></div>
						<div class="rpa-type-image-palette-item" style="background-color: #6ab8ee;"></div>
						<div class="rpa-type-image-palette-item" style="background-color: #4fd2c2;"></div>
						<div class="rpa-type-image-palette-item" style="background-color: #a5c33c;"></div>
						<div class="rpa-type-image-palette-item" style="background-color: #f7b70a;"></div>
						<div class="rpa-type-image-palette-item" style="background-color: #E74C3c;"></div>
						<div class="rpa-type-image-palette-item" style="background-color: #333;"></div>
					</div>
				</div>*/?>

				<?php
				$image = $type->getImage();
				$icons = $arResult['icons'];
				?>
				<div class="rpa-automation-options">
					<?php
					foreach($icons as $icon)
					{
						echo '<div data-icon="'.htmlspecialcharsbx($icon).'" class="rpa-automation-options-item rpa-tile-item-icon-'.htmlspecialcharsbx($icon).($icon === $image ? ' rpa-automation-options-item-selected' : '').'" onclick="BX.Rpa.TypeComponent.onIconClick(this);"></div>';
					}
					?>
				</div>
			</div>
		</div>

		<div id="salescenter-cashbox-buttons">
			<?php
			$buttons = [
				[
					'TYPE' => 'save',
				],
				'cancel'
			];
			if(isset($arResult['id']) && $arResult['id'] > 0)
			{
				$buttons[] = [
					'TYPE' => 'remove',
				];
			}
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
	var params = <?=CUtil::PhpToJSObject($arResult['jsParams']);?>;
	params.errorsContainer = document.getElementById('rpa-type-errors');
	var factory = null;
	if(params.isCreationEnabled === true)
	{
		factory = new BX.UI.UserFieldFactory.Factory(params.entityId, {
			moduleId: 'rpa',
		});
	}
	var fieldsController = new BX.Rpa.FieldsController({
		fields: params.fields,
		hiddenFields: params.hiddenFields,
		factory: factory,
		errorContainer: params.errorsContainer,
		typeId: params.type.typeId,
		languageId: params.languageId,
	});
	document.getElementById('rpa-fields-selector').appendChild(fieldsController.render());
	var component = new BX.Rpa.TypeComponent(document.getElementById('rpa-type-form'), params);
	component.setFieldsController(fieldsController).init();
});
</script>
<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var CAllMain $APPLICATION */
/** @var array $arResult */

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\TypePreset;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.common',
	'ui.forms',
	'ui.entity-selector',
	'ui.switcher',
	'ui.alerts',
	'ui.hint',
	'ui.dialogs.messagebox',
	'crm.type-model',
	'main.loader',
	'ui.layout-form',
]);

/** @var CBitrixComponentTemplate $this */
/** @var CrmTypeDetailComponent $component */
$component = $this->getComponent();

if($component->getErrors()):?>
	<div class="ui-alert ui-alert-danger">
		<?php foreach($component->getErrors() as $error):?>
			<span class="ui-alert-message"><?= htmlspecialcharsbx($error->getMessage()) ?></span>
		<?php endforeach;?>
	</div>
	<?php
	return;
endif;

$component->addJsRouter($this);

$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'crm-type-head--modifier crm-type-hidden');
$this->SetViewTarget('below_pagetitle');
?>
<div class="crm-type-head-control" data-role="preset-selector-container">
	<div class="crm-type-head-control-logo"></div>
	<div class="crm-type-head-control-content">
		<div class="crm-type-head-control-caption"><?=Loc::getMessage('CRM_TYPE_DETAIL_TITLE_PRESET')?></div>
		<a
			data-role="crm-type-preset-selector"
			class="crm-type-head-control-link"
			onclick="BX.Crm.Component.TypeDetail.handlePresetSelectorClick();"
		></a>
	</div>
</div>
<?php
$this->EndViewTarget();
$component->addToolbar($this);
$type = $component->getType();
$menuItems = [];
$menuItems[] = [
	'NAME' => Loc::getMessage('CRM_TYPE_DETAIL_TAB_COMMON'),
	'ATTRIBUTES' => [
		'onclick' => "BX.Crm.Component.TypeDetail.handleLeftMenuClick('common');",
		'data-role' => 'tab-common',
	],
	'ACTIVE' => ($type->getId() > 0),
];
$menuItems[] = [
	'NAME' => Loc::getMessage('CRM_TYPE_DETAIL_TAB_FIELDS'),
	'ATTRIBUTES' => [
		'onclick' => "BX.Crm.Component.TypeDetail.handleLeftMenuClick('fields');",
		'data-role' => 'tab-fields',
	],
	'ACTIVE' => ($type->getId() > 0),
];
$menuItems[] = [
	'NAME' => Loc::getMessage('CRM_TYPE_DETAIL_TAB_RELATIONS'),
	'ATTRIBUTES' => [
		'onclick' => "BX.Crm.Component.TypeDetail.handleLeftMenuClick('relation');",
		'data-role' => 'tab-relation',
	],
];
$menuItems[] = [
	'NAME' => Loc::getMessage('CRM_TYPE_DETAIL_TAB_USER_FIELDS'),
	'ATTRIBUTES' => [
		'onclick' => "BX.Crm.Component.TypeDetail.handleLeftMenuClick('user-fields');",
		'data-role' => 'tab-user-fields',
	],
];
//$menuItems[] = [
//	'NAME' => Loc::getMessage('CRM_TYPE_DETAIL_TAB_CONVERSION'),
//	'ATTRIBUTES' => [
//		'onclick' => "BX.Crm.Component.TypeDetail.handleLeftMenuClick('conversion');",
//		'data-role' => 'tab-conversion',
//	],
//];

if ($arResult['isCustomSectionsAvailable'])
{
	$menuItems[] = [
		'NAME' => Loc::getMessage('CRM_TYPE_DETAIL_TAB_CUSTOM_SECTION'),
		'ATTRIBUTES' => [
			'onclick' => "BX.Crm.Component.TypeDetail.handleLeftMenuClick('custom-section');",
			'data-role' => 'tab-custom-section',
		],
	];
}

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrappermenu',
	'',
	[
		'TITLE' => Loc::getMessage('CRM_COMMON_SETTINGS'),
		'ITEMS' => $menuItems,
	],
	$this->getComponent()
);

$renderCheckbox = static function (
		?string $title,
		?string $name,
		bool $isChecked,
		bool $isDisabled = false,
		?string $hint = null
	): string {
	return '
	<div class="ui-form-row">
		<label class="ui-ctl ui-ctl-checkbox ui-ctl-w100">
			<input
				type="checkbox"
				class="ui-ctl-element"
				name="' . htmlspecialcharsbx($name) . '" '
				. ($isChecked ? 'checked="checked"' : '')
				. ($isDisabled ? 'disabled="disabled"' : '')
				. ' value="Y"
				data-name="' . htmlspecialcharsbx($name) . '"
			 />
			<div class="ui-ctl-label-text">'
				. htmlspecialcharsbx($title)
				. ($hint ? '<span data-hint="' . htmlspecialcharsbx($hint) . '" class="ui-hint"></span>' : '') .
			'</div>
		</label>
	</div>';
};

//$renderConversionSection = static function(string $title, array $typesInfo, string $role): string
//{
//	$types = '';
//	foreach ($typesInfo as $item)
//	{
//		$types .= '
//			<div class="ui-form-row">
//				<label class="ui-ctl ui-ctl-checkbox ui-ctl-w100">
//					<input
//						type="checkbox"
//						class="ui-ctl-element"
//						data-entity-type-id="'.(int)$item['entityTypeId'].'"
//						data-role="'.htmlspecialcharsbx($role).'" '
//						. ($item['isChecked'] ? 'checked="checked"' : '')
//						. ' value="Y"
//					/>
//					<div class="ui-ctl-label-text">'.htmlspecialcharsbx($item['title']).'</div>
//				</label>
//			</div>
//		';
//	}
//
//	return '
//		<div class="ui-form-row">
//			<div class="ui-form-label">
//				<div class="ui-ctl-label-text">'.htmlspecialcharsbx($title).'</div>
//			</div>
//			<div class="ui-form-content ui-form-row-inline">'
//				. $types
//			. '</div>
//		</div>
//	';
//};

$renderCardMessage = static function(?string $title, ?string $description, string $icon = null): string
{
	return '
		<div class="crm-type-ui-card crm-type-ui-card-message">
			<div class="crm-type-ui-card-header">
				<div class="crm-type-ui-card-message-icon crm-type-ui-card-message-icon--custom-fields" style="' . ($icon ? 'background-image: url(' . htmlspecialcharsbx($icon) . ')' : '') . '"></div>
				<div class="crm-type-ui-card-message-title">' . htmlspecialcharsbx($title) . '</div>
			</div>
			<div class="crm-type-ui-card-body">
				<div class="crm-type-ui-card-message-description">' . htmlspecialcharsbx($description) . '</div>
			</div>
			<div 
				class="crm-type-ui-card-message-close-button" 
				title="' . Loc::getMessage('CRM_TYPE_DETAIL_HIDE_DESCRIPTION') . '"
				onclick="BX.Crm.Component.TypeDetail.handleHideDescriptionClick(this);"
			></div>
		</div>
	';
};

$renderFieldSelector = static function (?string $title, bool $isActive, string $code, string $icon = null): string
{
	return '
		<div 
			class="crm-type-field-button-item '.($isActive ? 'crm-type-field-button-item-active' : '').'"  
			data-name="' . htmlspecialcharsbx($code) . '"
			onclick="BX.Crm.Component.TypeDetail.handleBooleanFieldClick(\'' . htmlspecialcharsbx(CUtil::JSEscape($code)) . '\');"
		>
			<div class="crm-type-field-button-item-icon' . ($icon ? ' ' . $icon : '') . '"></div>
			<div class="crm-type-field-button-item-text">
				<span>' . htmlspecialcharsbx($title) . '</span>
			</div>
		</div>
	';
}
?>
<div class="crm-type">
	<div class="ui-alert ui-alert-danger" style="display: none;">
		<span class="ui-alert-message" id="crm-type-errors"></span>
		<span class="ui-alert-close-btn" onclick="this.parentNode.style.display = 'none';"></span>
	</div>
	<div class="crm-type-tab<?php ($type->getId() <= 0 ? ' crm-type-tab-current' : '')?>" data-tab="presets">
		<div class="crm-type-presets">
			<?php foreach ($arResult['presetCategories'] as $code => $category):?>
				<div class="crm-type-presets-category">
					<div class="crm-type-presets-category-title"><?= htmlspecialcharsbx($category['title']) ?></div>
					<div class="crm-type-presets-category-list">
						<?php /** @var TypePreset $preset */
						$categoryPresets = array_filter(
								$arResult['presets'],
								static function(TypePreset $preset) use ($code) {
									return $preset->getCategory() === $code;
							}
						);
						foreach ($categoryPresets as $preset):
							$presetClassNames = ['crm-type-preset'];
							if ($preset->isDisabled())
							{
								$presetClassNames[] = 'crm-type-preset-disabled';
							}
							?>
							<div
								class="<?= implode(' ', $presetClassNames) ?>"
								data-role="preset"
								data-preset-id="<?= $preset->getId() ?>"
								onclick="BX.Crm.Component.TypeDetail.handlePresetClick('<?= $preset->getId() ?>');"
							>
								<div class="crm-type-preset-icon" style="background-image: <?=($preset->getIcon()
									? 'url(' . $preset->getIcon() . ')'
									: 'none'
								)?>"></div>
								<div class="crm-type-preset-text">
									<div class="crm-type-preset-text-title"><?= htmlspecialcharsbx($preset->getTitle()) ?></div>
									<div class="crm-type-preset-text-description"><?= htmlspecialcharsbx($preset->getDescription()) ?></div>
								</div>
							</div>
						<?php endforeach;?>
					</div>
				</div>
			<?php endforeach;?>
		</div>
	</div>
	<form id="crm-type-form" class="ui-form">
		<div class="crm-type-tab" data-tab="common">
			<div class="ui-title-3"><?= Loc::getMessage('CRM_TYPE_DETAIL_TAB_COMMON') ?></div>
			<div class="ui-form-row crm-type-form-label-xs">
				<div class="ui-form-label">
					<div class="ui-ctl-label-text"><?= Loc::getMessage('CRM_TYPE_DETAIL_FIELD_TITLE') ?></div>
				</div>
				<div class="ui-form-content">
					<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
						<input
							type="text"
							name="title"
							class="ui-ctl-element"
							placeholder="<?= Loc::getMessage('CRM_COMMON_TITLE') ?>"
							value="<?= htmlspecialcharsbx($type->getTitle()) ?>"
						/>
					</div>
				</div>
			</div>
			<?php
			echo $renderCheckbox(
				Loc::getMessage('CRM_TYPE_TYPE_IS_CATEGORIES_ENABLED_TITLE'),
				'isCategoriesEnabled',
				$type->getIsCategoriesEnabled(),
				(bool) $arResult['isCategoriesControlDisabled'],
				$arResult['isCategoriesControlDisabled']
					? Loc::getMessage('CRM_TYPE_DETAIL_CATEGORIES_DISABLED_HINT')
					: null
			);

			echo $renderCardMessage(
				Loc::getMessage('CRM_TYPE_DETAIL_CATEGORIES_CARD_TITLE'),
				Loc::getMessage('CRM_TYPE_DETAIL_CATEGORIES_CARD_DESCRIPTION')
			);

			echo $renderCheckbox(
				Loc::getMessage('CRM_TYPE_TYPE_IS_STAGES_ENABLED_TITLE'),
				'isStagesEnabled',
				$type->getIsStagesEnabled(),
				false,
				$type->getIsStagesEnabled()
					? Loc::getMessage('CRM_TYPE_DETAIL_STAGES_DISABLED_HINT')
					: null
			);

			echo $renderCardMessage(
				Loc::getMessage('CRM_TYPE_DETAIL_STAGES_CARD_TITLE'),
				Loc::getMessage('CRM_TYPE_DETAIL_STAGES_CARD_DESCRIPTION')
			);

			echo $renderCheckbox(
				Loc::getMessage('CRM_TYPE_TYPE_IS_AUTOMATION_ENABLED_TITLE'),
				'isAutomationEnabled',
				$type->getIsAutomationEnabled()
			);

			echo $renderCardMessage(
				Loc::getMessage('CRM_TYPE_DETAIL_AUTOMATION_CARD_TITLE'),
				Loc::getMessage('CRM_TYPE_DETAIL_AUTOMATION_CARD_DESCRIPTION')
			);

			echo $renderCheckbox(
				Loc::getMessage('CRM_TYPE_TYPE_IS_BIZ_PROC_ENABLED_TITLE'),
				'isBizProcEnabled',
				$type->getIsBizProcEnabled()
			);

			echo $renderCardMessage(
				Loc::getMessage('CRM_TYPE_DETAIL_BIZ_PROC_CARD_TITLE'),
				Loc::getMessage('CRM_TYPE_DETAIL_BIZ_PROC_CARD_DESCRIPTION')
			);

			echo $renderCheckbox(
				Loc::getMessage('CRM_TYPE_TYPE_IS_SET_OPEN_PERMISSIONS_TITLE'),
				'isSetOpenPermissions',
				$type->getIsSetOpenPermissions(),
				false,
				Loc::getMessage('CRM_TYPE_DETAIL_IS_SET_OPEN_PERMISSIONS_HINT')
			);

			echo $renderCardMessage(
				Loc::getMessage('CRM_TYPE_DETAIL_IS_SET_OPEN_PERMISSIONS_TITLE'),
				Loc::getMessage('CRM_TYPE_DETAIL_IS_SET_OPEN_PERMISSIONS_DESCRIPTION')
			);
			?>
		</div>
		<div class="crm-type-tab" data-tab="fields">
			<div class="ui-title-3"><?= Loc::getMessage('CRM_TYPE_DETAIL_FIELDS_SECTION_TITLE') ?></div>
			<div class="crm-type-field-button-layout">
				<?php
				echo $renderFieldSelector(
					Loc::getMessage('CRM_TYPE_DETAIL_FIELD_MY_COMPANY'),
					$type->getIsMycompanyEnabled(),
					'isMycompanyEnabled',
					'crm-type-field-icon-type1'
				);
				echo $renderFieldSelector(
					Loc::getMessage('CRM_TYPE_DETAIL_FIELD_SOURCE'),
					$type->getIsSourceEnabled(),
					'isSourceEnabled',
					'crm-type-field-icon-type2'
				);
				echo $renderFieldSelector(
					Loc::getMessage('CRM_TYPE_DETAIL_FIELD_CLIENT'),
					$type->getIsClientEnabled(),
					'isClientEnabled',
					'crm-type-field-icon-type3'
				);
				echo $renderFieldSelector(
					Loc::getMessage('CRM_TYPE_DETAIL_FIELD_DATES'),
					$type->getIsBeginCloseDatesEnabled(),
					'isBeginCloseDatesEnabled',
					'crm-type-field-icon-type4'
				);
				echo $renderFieldSelector(
					Loc::getMessage('CRM_TYPE_DETAIL_FIELD_OBSERVERS'),
					$type->getIsObserversEnabled(),
					'isObserversEnabled',
					'crm-type-field-icon-type1'
				);
				?>
			</div>
			<div class="ui-title-3"><?= Loc::getMessage('CRM_TYPE_DETAIL_ADDITIONAL_SECTION_TITLE') ?></div>
			<?php
			/*
			echo $renderCheckbox(
				Loc::getMessage('CRM_TYPE_TYPE_IS_CRM_TRACKING_ENABLED_TITLE'),
				'isCrmTrackingEnabled',
				$type->getIsCrmTrackingEnabled()
			);
			*/

			echo $renderCheckbox(
				Loc::getMessage('CRM_TYPE_TYPE_IS_DOCUMENTS_ENABLED_TITLE'),
				'isDocumentsEnabled',
				$type->getIsDocumentsEnabled()
			);
			echo $renderCheckbox(
				Loc::getMessage('CRM_TYPE_TYPE_IS_LINK_WITH_PRODUCTS_ENABLED_TITLE'),
				'isLinkWithProductsEnabled',
				$type->getIsLinkWithProductsEnabled()
			);
			echo $renderCheckbox(
				Loc::getMessage('CRM_TYPE_TYPE_IS_RECYCLEBIN_ENABLED_TITLE'),
				'isRecyclebinEnabled',
				$type->getIsRecyclebinEnabled(),
				$arResult['isRecyclebinControlDisabled'],
				$arResult['isRecyclebinControlDisabled']
					? Loc::getMessage('CRM_TYPE_DETAIL_RECYCLE_DISABLED_HINT')
					: null
			);
			echo $renderCheckbox(
				Loc::getMessage('CRM_TYPE_TYPE_IS_COUNTERS_ENABLED_TITLE'),
				'isCountersEnabled',
				$type->getIsCountersEnabled(),
			);
			?>
		</div>
		<div class="crm-type-tab" data-tab="relation">
			<div class="ui-title-3"><?= Loc::getMessage('CRM_TYPE_DETAIL_TAB_RELATIONS') ?></div>
			<?php
			echo $renderCardMessage(
				Loc::getMessage('CRM_TYPE_DETAIL_RELATION_CARD_TITLE'),
				Loc::getMessage('CRM_TYPE_DETAIL_RELATION_CARD_DESCRIPTION')
			);
			?>
			<div class="crm-type-relation-switch-btn">
				<span data-switcher="<?= htmlspecialcharsbx(Json::encode([
					'id' => 'crm-type-relation-parent-switcher',
				])) ?>" class="ui-switcher"></span>
				<?= Loc::getMessage('CRM_TYPE_DETAIL_RELATION_PARENT') ?>
			</div>
			<div
				data-role="crm-type-relation-parent-items"
				class="crm-type-relation-items"
			>
				<div class="crm-type-relation-items-section">
					<div class="crm-type-relation-subtitle"><?= Loc::getMessage('CRM_TYPE_DETAIL_RELATION_PARENT_ITEMS') ?></div>
					<div data-role="crm-type-relation-parent-items-selector"></div>
					<div data-role="crm-type-relation-parent-items-tabs">
						<?= $renderCheckbox(
							Loc::getMessage('CRM_TYPE_DETAIL_RELATION_CHILDREN_LIST'),
							'isRelationParentShowChildrenEnabled',
							false,
							false
						);
						?>
						<div data-role="crm-type-relation-parent-items-tabs-selector"></div>
					</div>
				</div>
			</div>
			<div class="crm-type-relation-switch-btn">
				<span data-switcher="<?= htmlspecialcharsbx(Json::encode([
					'id' => 'crm-type-relation-child-switcher',
				])) ?>" class="ui-switcher"></span>
				<?= Loc::getMessage('CRM_TYPE_DETAIL_RELATION_CHILD') ?>
			</div>
			<div
					data-role="crm-type-relation-child-items"
					class="crm-type-relation-items"
			>
				<div class="crm-type-relation-items-section">
					<div class="crm-type-relation-subtitle"><?= Loc::getMessage('CRM_TYPE_DETAIL_RELATION_CHILD_ITEMS') ?></div>
					<div data-role="crm-type-relation-child-items-selector"></div>
					<div data-role="crm-type-relation-child-items-tabs">
						<?= $renderCheckbox(
							Loc::getMessage('CRM_TYPE_DETAIL_RELATION_CHILDREN_LIST'),
							'isRelationChildShowChildrenEnabled',
							false,
							false
						);
						?>
						<div data-role="crm-type-relation-child-items-tabs-selector"></div>
					</div>
				</div>
			</div>
		</div>
		<div class="crm-type-tab" data-tab="user-fields">
			<div class="ui-title-3"><?= Loc::getMessage('CRM_TYPE_DETAIL_TAB_USER_FIELDS_TITLE') ?></div>
			<?php
			echo $renderCardMessage(
				Loc::getMessage('CRM_TYPE_DETAIL_TAB_USER_FIELDS_CARD_TITLE'),
				Loc::getMessage('CRM_TYPE_DETAIL_TAB_USER_FIELDS_CARD_DESCRIPTION')
			);
			echo $renderFieldSelector(
				Loc::getMessage('CRM_TYPE_DETAIL_IS_USE_IN_USERFIELD_ENABLED_TITLE'),
				$type->getIsUseInUserfieldEnabled(),
				'isUseInUserfieldEnabled',
				'crm-type-field-icon-type3'
			);
			?>
			<?php foreach ($arResult['linkedUserFields'] as $name => $userField)
			{
				$icon = mb_strpos($name, 'CALENDAR') !== false
					? 'crm-type-field-icon-type3'
					: 'crm-type-field-icon-type2';
				echo $renderFieldSelector(
					$userField['title'],
					$userField['isEnabled'],
					'linkedUserFields[' . $name . ']',
					$icon
				);
			}?>
		</div>
		<?php if ($arResult['isCustomSectionsAvailable']): ?>
			<div class="crm-type-tab" data-tab="custom-section">
				<div class="ui-title-3"><?= Loc::getMessage('CRM_TYPE_DETAIL_TAB_CUSTOM_SECTION') ?></div>
				<?php echo $renderCardMessage(
					Loc::getMessage('CRM_TYPE_DETAIL_TAB_CUSTOM_SECTION_CARD_TITLE'),
					Loc::getMessage('CRM_TYPE_DETAIL_TAB_CUSTOM_SECTION_CARD_DESCRIPTION')
				);?>
				<div class="crm-type-relation-switch-btn">
				<span data-switcher="<?= htmlspecialcharsbx(Json::encode([
					'id' => 'crm-type-custom-section-switcher',
				])) ?>" class="ui-switcher"></span>
					<?= Loc::getMessage('CRM_TYPE_DETAIL_CUSTOM_SECTION_SWITCHER') ?>
				</div>
				<div
					data-role="crm-type-custom-section-container"
					class="crm-type-custom-section-container"
				>
					<div class="crm-type-relation-subtitle"><?= Loc::getMessage('CRM_TYPE_DETAIL_CUSTOM_SECTION_LABEL') ?></div>
					<div data-role="crm-type-custom-section-selector"></div>
				</div>
			</div>
		<?php endif;?>
		<?php /*
		<div class="crm-type-tab" data-tab="conversion">
			<?php
			echo $renderConversionSection(
				Loc::getMessage('CRM_TYPE_DETAIL_CONVERSION_SOURCE'),
				$arResult['conversionParams']['source'],
				'conversion-source'
			);

			echo $renderConversionSection(
				Loc::getMessage('CRM_TYPE_DETAIL_CONVERSION_DESTINATION'),
				$arResult['conversionParams']['destination'],
				'conversion-destination'
			)
			?>
		</div> */?>
	</form>
	<div id="crm-type-buttons">
		<?php
		$buttons = [
			[
				'TYPE' => 'save',
			],
			'cancel' => $arResult['listUrl'],
		];
		if($type->getId() > 0)
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
				'ALIGN' => 'center',
			],
			$this->getComponent()
		);
		?>
	</div>
</div>
<script>
BX.ready(function()
{
	<?= 'BX.message('.\CUtil::PhpToJSObject(Loc::loadLanguageFile(__FILE__)).');' ?>
	<?= 'BX.message('.\CUtil::PhpToJSObject(Container::getInstance()->getLocalization()->loadMessages()).');' ?>
	var type = new BX.Crm.Models.TypeModel(<?= CUtil::PhpToJSObject($arResult['type']) ?>);
	var form = document.getElementById('crm-type-form');
	var component = new BX.Crm.Component.TypeDetail({
		type: type,
		form: form,
		container: document.querySelector('.crm-type'),
		errorsContainer: document.getElementById('crm-type-errors'),
		presets: <?= CUtil::PhpToJSObject(array_map(
				static function (TypePreset $preset) {
					return $preset->jsonSerialize();
				},
				$arResult['presets']
		)) ?>,
		relations: <?= CUtil::PhpToJSObject($arResult['relations']) ?>,
		isRestricted: <?=$arResult['isRestricted'] ? 'true' : 'false'?>
	});
	component.init();
	BX.UI.Hint.init(form);
	BX.UI.Switcher.initByClassName();
});
</script>

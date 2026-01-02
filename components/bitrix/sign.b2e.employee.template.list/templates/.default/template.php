<?php

use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Bitrix\Sign\Config\Feature;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\Template\EntityType;
use Bitrix\Sign\Ui\TemplateGrid\ActionMenu;
use Bitrix\UI\Buttons\BaseButton;
use Bitrix\UI\Buttons\Button;
use Bitrix\UI\Buttons\Color;
use Bitrix\UI\Toolbar\ButtonLocation;
use Bitrix\Sign\Type\Document\InitiatedByType;
use Bitrix\UI\Toolbar\Facade\Toolbar;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
/** @var array $arParams */
/** @var array $arResult */

/** @var $APPLICATION */

\CJSCore::Init("loader");
\Bitrix\Main\Loader::includeModule('crm');
$extensions = [
	'sign.v2.grid.b2e.templates',
	'ui.switcher',
	'crm_common',
	'sign.v2.ui.tokens',
	'sign.v2.b2e.blank-importer',
];

if (($arResult['CAN_ADD_TEMPLATE'] ?? false) && ($arResult['CAN_EXPORT_BLANK'] ?? false))
{
	$extensions[] = 'sign.v2.b2e.blank-importer';
}

\Bitrix\Main\UI\Extension::load($extensions);

$rootGridTitle = (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_TITLE') ?? '';
$gridTitle = htmlspecialcharsbx($arParams['CURRENT_FOLDER_TITLE']) ? : $rootGridTitle;
$APPLICATION->SetTitle($gridTitle);

Toolbar::addFilter([
	'GRID_ID' => $arParams['GRID_ID'] ?? '',
	'FILTER_ID' => $arParams['FILTER_ID'] ?? '',
	'FILTER' => $arParams['FILTER_FIELDS'] ?? [],
	'FILTER_PRESETS' => $arParams['FILTER_PRESETS'] ?? [],
	'DISABLE_SEARCH' => false,
	'ENABLE_LIVE_SEARCH' => true,
	'ENABLE_LABEL' => true,
	'THEME' => Bitrix\Main\UI\Filter\Theme::MUTED,
]);

$createTemplateEntityButton = $arResult['CREATE_TEMPLATE_ENTITY_BUTTON'] ?? false;
$showTariffSlider = $arResult['SHOW_TARIFF_SLIDER'] ?? false;

if ($createTemplateEntityButton instanceof BaseButton  && $showTariffSlider)
{
	$createTemplateEntityButton
		->setIcon(\Bitrix\UI\Buttons\Icon::LOCK)
		->addClass('sign-b2e-js-tarriff-slider-trigger')
		->setTag('button')
	;
}

if ($createTemplateEntityButton instanceof BaseButton  && $arResult['CAN_ADD_TEMPLATE'])
{
	Toolbar::addButton($createTemplateEntityButton, ButtonLocation::AFTER_TITLE);
}

if (($arResult['CAN_ADD_TEMPLATE'] ?? false) && ($arResult['CAN_EXPORT_BLANK'] ?? false) && !$arResult['IS_FOLDER_CONTENT_MODE'])
{
	Toolbar::addButton(
		(new Button([]))
			->setColor(Color::PRIMARY)
			->setText(Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ACTION_IMPORT'))
			->addClass('sign-b2e-js-import-blank'),
		ButtonLocation::AFTER_FILTER,
	);
}

$getInitiatedByTypeForView = static fn(
	?InitiatedByType $initiatedByType,
	EntityType $entityType,
): ?InitiatedByType => match ($entityType)
	{
		EntityType::FOLDER => InitiatedByType::COMPANY,
		EntityType::TEMPLATE => $initiatedByType,
		default => throw new \Bitrix\Main\ArgumentTypeException('entityType', 'EntityType::FOLDER or EntityType::TEMPLATE'),
	}
;

$buildDataAttributes = static function(array $attributes): string {
	$attributes = array_map(
		static function($key, $value) {
			$key = htmlspecialcharsbx($key);
			$value = htmlspecialcharsbx($value);

			return "data-{$key}=\"{$value}\"";
		},
		array_keys($attributes),
		$attributes,
	);

	return implode(' ', $attributes);
};

$getAddMetadataForFrontendFunction = static function(array $templateData) use (
	$getInitiatedByTypeForView,
	$buildDataAttributes,
) {
	return static function(mixed $wrappedLayout) use ($getInitiatedByTypeForView, $templateData, $buildDataAttributes) {
		$id = (int)$templateData['columns']['ID'];
		$entityType = $templateData['entityType'];
		$initiatedByType = $templateData['columns']['TYPE'] ?? null;

		$initiatedByTypeValue = $getInitiatedByTypeForView($initiatedByType, $entityType);
		$dataAttributes = $buildDataAttributes(
			[
				'id' => $id,
				'entity-type' => $entityType->value,
				'initiated-by-type' => $initiatedByTypeValue?->value ?? '',
				'can-edit' => $templateData['access']['canEdit'] ?? '',
				'can-delete' => $templateData['access']['canDelete'] ?? '',
			]
		);

		return "<div class='sign-grid-template__cell-metadata' {$dataAttributes}>{$wrappedLayout}</div>";
	};
};

$getInitiatedByTypeTemplate = static function (?InitiatedByType $initiatedByType, EntityType $entityType) use (
	$getInitiatedByTypeForView
): ?string
{
	return match ($getInitiatedByTypeForView($initiatedByType, $entityType))
	{
		InitiatedByType::EMPLOYEE => Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_COLUMN_TYPE_FROM_EMPLOYEE'),
		InitiatedByType::COMPANY => Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_COLUMN_TYPE_FROM_COMPANY'),
		default => null,
	};
};

$getUserInfoTemplate = static function (array $responsible): string
{
	$userId = (int)$responsible['ID'] ?? 0;
	$fullName = $responsible['FULL_NAME'] ?? '';
	$imagePath = $responsible['AVATAR_PATH'] ?? '';

	ob_start();
	?>
	<div style="display: flex; align-items: center">
		<a
			class="sign-personal-grid-user"
			target="_top"
			onclick="event.stopPropagation();"
			href="/company/personal/user/<?= $userId ?>/">
		<span class="ui-icon ui-icon-common-user">
			<i style=" <?= $imagePath !== '' ? "background-image: url('" . Uri::urnEncode($imagePath) . "');" : '' ?>">
			</i>
		</span>
		<span class="sign-personal-grid-user-name">
				<?= $fullName ?>
		</span>
		</a>
	</div>
	<?php
	return (string)ob_get_clean();
};

$getDateTemplate = static function (
	?\Bitrix\Sign\Type\DateTime $dateModify,
): string {

	ob_start();?>
	<div title="<?= $dateModify->toString() ?>"><?= (new \Bitrix\Main\Type\Date($dateModify))->toString() ?></div>
	<?php

	return (string)ob_get_clean();
};

$getSwitcherTemplate = static function (array $templatesData): string
{
	$visibility = $templatesData['columns']['VISIBILITY']->value;
	$status = $templatesData['columns']['STATUS']?->value;
	$entityId = (int)$templatesData['id'];
	$company = $templatesData['columns']['COMPANY']['TITLE'];
	$entityType = $templatesData['entityType'];
	$canEdit = $templatesData['access']['canEdit'] ?? false;
	$isDisabled = !$canEdit || ($entityType->isTemplate() && $visibility === 'invisible' && $status === 'new');
	$isChecked = $visibility === 'visible' ? 'true' : 'false';

	if ($company === null)
	{
		$isDisabled = 'true';
		$isChecked = 'false';
	}

	ob_start();?>
	<div id="switcher_b2e_template_grid_<?= $entityId ?>_<?= $entityType->value ?>" class="ui-switcher-container ui-switcher-color-green"></div>
	<script>
		BX.ready(() => {
			templateGrid.renderSwitcher(
				<?= $entityId ?>,
				'<?= $entityType->value ?>',
				<?= $isChecked ?>,
				<?= $isDisabled ? 'true' : 'false' ?>,
				<?= $canEdit ? 'true' : 'false' ?>
			);
		});
	</script>
	<?php

	return (string)ob_get_clean();
};

$getLinkTemplate = static function (array $templatesData, bool $showTariffSlider = false) use ($arResult): string
{

	$entityId = (int)$templatesData['columns']['ID'];
	$folderId = $arResult['FOLDER_ID'] ?? 0;
	$editTemplateLink = Container::instance()->getUrlGeneratorService()->makeEditTemplateLink($entityId, $folderId);
	$title = $templatesData['columns']['TITLE'];
	$id = $templatesData['columns']['ID'];
	$entityType = $templatesData['entityType'];
	$canEdit = $templatesData['access']['canEdit'] ?? false;
	$editTemplateLinkObject = new Uri($editTemplateLink);
	$isValidEditTemplateLink = str_starts_with($editTemplateLinkObject->getPath(), '/sign/b2e/doc/0/');

	$editTemplateHref = $showTariffSlider ? '#' : (string)$editTemplateLinkObject;
	ob_start();?>

	<?php if ($entityType->isFolder()): ?>
		<a
			href="#"
			onclick="templateGrid.openSliderTemplateFolderContent(<?= $id ?>); return false;"
			class="sign-template-title"
			data-entity-type="<?= htmlspecialcharsbx($entityType->value) ?>"
		>
			<span class="sign-template-folder-icon"></span>
			<span class="sign-template-title-text"><?= htmlspecialcharsbx($title) ?></span>
		</a>
	<?php elseif ($isValidEditTemplateLink && $canEdit): ?>
		<a
			<?php if ($showTariffSlider): ?>
				onclick="top.BX.UI.InfoHelper.show('limit_office_e_signature')"
			<?php else: ?>
				onclick="event.stopPropagation()"
			<?php endif; ?>
			href="<?= $editTemplateHref ?>"
			class="sign-template-title"
			data-entity-type="<?= htmlspecialcharsbx($entityType->value) ?>"
		>
			<span class="sign-template-icon"></span>
			<span class="sign-template-title-text"><?= htmlspecialcharsbx($title) ?></span>
		</a>
	<?php else: ?>
		<p
			class="sign-template-title-without-link"
			data-entity-type="<?= htmlspecialcharsbx($entityType->value) ?>"
			title="<?= Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_NO_ACCESS_EDIT_TEMPLATE') ?>">
			<span class="sign-template-icon"></span>
			<span><?= htmlspecialcharsbx($title) ?></span>
		</p>
	<?php endif; ?>

	<?php

	return (string)ob_get_clean();
};

$getCompanyTemplate = static function (array $company): string
{
	ob_start();?>

	<?php if ($company['COUNT'] > 0): ?>
		<span>
			<?= Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_COLUMN_COMPANY_SEVERAL_COMPANIES',
				[
					'#COMPANY#' => htmlspecialcharsbx($company['TITLE']),
					'#COUNT#' => (int)$company['COUNT'],
				]);
			?>
		</span>
	<?php else: ?>
		<span> <?= htmlspecialcharsbx($company['TITLE']) ?> </span>
	<?php endif; ?>

	<?php

	return (string)ob_get_clean();
};

$getActionButton = static function (array $templatesData): string
{
	$entityId = (int)$templatesData['id'] ?? 0;
	$entityType = $templatesData['entityType'] ?? '';
	$templateIds = is_array($templatesData['templateIds'] ?? null) ? $templatesData['templateIds'] : [];
	$sendBlockedParams = $templatesData['sendBlockedParams'] ?? [];
	$containerId = "button_container_{$entityId}_{$entityType->value}";

	ob_start(); ?>
	<div id="<?= htmlspecialcharsbx($containerId) ?>"></div>
	<script>
		BX.ready(() => {
			const container = document.getElementById('<?= CUtil::JSEscape($containerId) ?>');
			if (container)
			{
				container.innerHTML = '';

				const buttonElement = templateGrid.renderSendButton(
					<?= $entityId ?>,
					'<?= CUtil::JSEscape($entityType->value) ?>',
					<?= Json::encode($templateIds) ?>,
					<?= Json::encode($sendBlockedParams) ?>,
				);
				container.appendChild(buttonElement);
			}
		});
	</script>
	<?php

	return (string)ob_get_clean();
};

$gridRows = [];
foreach ($arResult["DOCUMENT_TEMPLATES"] as $templatesData)
{
	$addMetadataToLayout = $getAddMetadataForFrontendFunction($templatesData);
	$dateModify = $templatesData['columns']['DATE_MODIFY'] ?? null;
	$entityId = (int)$templatesData['columns']['ID'];
	$folderId = (int)$arResult['FOLDER_ID'] ?? 0;
	$editTemplateLink = Container::instance()->getUrlGeneratorService()->makeEditTemplateLink($entityId, $folderId);

	$gridRow = [
		'data' => [
			'ID' => $entityId,
			'TITLE' => $addMetadataToLayout($getLinkTemplate($templatesData, $showTariffSlider)),
			'DATE_MODIFY' => $addMetadataToLayout($dateModify !== null ? $getDateTemplate($dateModify) : null),
			'RESPONSIBLE' => $addMetadataToLayout($getUserInfoTemplate($templatesData['columns']['RESPONSIBLE'])),
			'VISIBILITY' => $addMetadataToLayout($getSwitcherTemplate($templatesData)),
			'COMPANY' => $addMetadataToLayout($getCompanyTemplate($templatesData['columns']['COMPANY'])),
			'ACTION' => $addMetadataToLayout($getActionButton($templatesData))
		],
	];

	if (($templatesData['access']['canEdit'] ?? false) && $templatesData['entityType']->isFolder())
	{
		$gridRow['actions'][] = [
			'text' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ACTION_RENAME_FOLDER'),
			'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_rename.svg',
			'onclick' => "templateGrid.renameFolder({$entityId}, '{$templatesData['columns']['TITLE']}')",
		];
	}

	if (($templatesData['access']['canEdit'] ?? false) && $templatesData['entityType']->isTemplate())
	{
		$gridRow['actions'][] = [
			'text' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ACTION_EDIT'),
			'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_edit.svg',
			'onclick' => $showTariffSlider
				? "top.BX.UI.InfoHelper.show('limit_office_e_signature')"
				: "BX.SidePanel.Instance.open('$editTemplateLink')"
			,
		];
	}

	if (($templatesData['access']['canCreate'] ?? false) && $templatesData['entityType']->isTemplate())
	{
		$gridRow['actions'][] = [
			'text' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ACTION_COPY'),
			'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_copy.svg',
			'onclick' => $showTariffSlider
				? "top.BX.UI.InfoHelper.show('limit_office_e_signature')"
				: "templateGrid.copyTemplate({$entityId}, {$folderId})",
		];
	}

	if ($templatesData['access']['canDelete'] ?? false)
	{
		$gridRow['actions'][] = [
			'text' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ACTION_DELETE'),
			'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_remove.svg',
			'onclick' => "templateGrid.delete({$entityId}, '{$templatesData['entityType']->value}')",
		];
	}

	if (($arResult['CAN_EXPORT_BLANK'] ?? false) && $templatesData['entityType']->isTemplate())
	{
		$gridRow['actions'][] = [
			'text' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ACTION_EXPORT'),
			'onclick' => "templateGrid.exportBlank({$entityId})",
		];
	}

	if ($templatesData['access']['canMoveToFolder'] ?? false)
	{
		$gridRow['actions'][] = [
			'text' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ACTION_MOVE_TO_FOLDER'),
			'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_move.svg',
			'onclick' => "templateGrid.moveTemplatesToFolder({$entityId})",
		];
	}

	if (Feature::instance()->isSenderTypeAvailable())
	{
		$gridRow['data']['TYPE'] = $addMetadataToLayout($getInitiatedByTypeTemplate($templatesData['columns']['TYPE'], $templatesData['entityType']));
	}

	$gridRows[] = $gridRow;
}

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	[
		'GRID_ID' => $arParams['GRID_ID'] ?? '',
		'COLUMNS' => $arParams['COLUMNS'] ?? '',
		'ROWS' => $gridRows,
		'NAV_OBJECT' => $arResult['PAGE_NAVIGATION'] ?? null,
		'SHOW_TOTAL_COUNTER' => true,
		'TOTAL_ROWS_COUNT' => $arResult['TOTAL_COUNT'] ?? 0,
		'ALLOW_COLUMNS_SORT' => true,
		'ALLOW_SORT' => true,
		'ALLOW_COLUMNS_RESIZE' => true,
		'AJAX_MODE' => 'Y',
		'AJAX_OPTION_HISTORY' => 'N',
		'AJAX_OPTION_JUMP' => 'N',
		'SHOW_ACTION_PANEL' => false,
		'TOP_ACTION_PANEL_RENDER_TO' => '.main-grid-container',
		'SHOW_ROW_CHECKBOXES' => true,
		'ACTION_PANEL' => [
			'GROUPS' => [
				[
					'ITEMS' => ActionMenu::getButtons()
				]
			],
		],
	]);
?>

<script>
	const templateGrid = new BX.Sign.V2.Grid.B2e.Templates(
		'<?= CUtil::JSEscape($arParams['GRID_ID'] ?? '') ?>',
		'<?= CUtil::JSEscape($arParams['ADD_NEW_TEMPLATE_LINK'] ?? '') ?>',
		<?= Json::encode($arParams['URL_LIST_FOR_RELOAD'] ?? []) ?>,
	);

	const toolbarElement = document.querySelector('.page-toolbar');
	if (toolbarElement)
	{
		toolbarElement.id = 'signTemplateListToolbarContainer';
		toolbarElement.style.marginLeft = '0';
	}
	templateGrid.subscribeOnGridEvents();
	templateGrid.reloadAfterSliderClose();

	<?php if (($arResult['CAN_ADD_TEMPLATE'] ?? false) && ($arResult['CAN_EXPORT_BLANK'] ?? false)): ?>
		const el = document.getElementsByClassName('sign-b2e-js-import-blank');
		if (el && el[0])
		{
			const templateGridBlankImporter = new BX.Sign.V2.B2e.BlankImporter(el[0]);
			templateGridBlankImporter.subscribe('onSuccessImport', () => templateGrid.reload());
		}
	<?php endif; ?>
</script>

<?php if ($showTariffSlider): ?>
	<script>
		BX.ready(function()
		{
			const el = document.getElementsByClassName('sign-b2e-js-tarriff-slider-trigger');
			if (el && el[0])
			{
				BX.bind(el[0], 'click', function()
				{
					top.BX.UI.InfoHelper.show('limit_office_e_signature');
				});
			}
		});
	</script>
<?php endif; ?>
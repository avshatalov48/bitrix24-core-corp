<?php

use Bitrix\Main\Web\Uri;
use Bitrix\Sign\Config\Feature;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Service\Container;
use Bitrix\UI\Toolbar\ButtonLocation;
use Bitrix\Sign\Type\Document\InitiatedByType;

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

$APPLICATION->SetTitle((string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_TITLE') ?? '');

\Bitrix\UI\Toolbar\Facade\Toolbar::addFilter([
	'GRID_ID' => $arParams['GRID_ID'] ?? '',
	'FILTER_ID' => $arParams['FILTER_ID'] ?? '',
	'FILTER' => $arParams['FILTER_FIELDS'] ?? [],
	'FILTER_PRESETS' => $arParams['FILTER_PRESETS'] ?? [],
	'DISABLE_SEARCH' => false,
	'ENABLE_LIVE_SEARCH' => true,
	'ENABLE_LABEL' => true,
	'THEME' => Bitrix\Main\UI\Filter\Theme::MUTED,
]);

$createButton = (new \Bitrix\UI\Buttons\CreateButton([]))
	->setText(Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ADD_NEW_TITLE') ?? '')
	->setLink($arParams['ADD_NEW_TEMPLATE_LINK'] ?? '#')
;
$showTariffSlider = $arResult['SHOW_TARIFF_SLIDER'] ?? false;

if ($showTariffSlider)
{
	$createButton
		->addClass('ui-btn-icon-lock')
		->addClass('sign-b2e-js-tarriff-slider-trigger')
		->setTag('button')
	;
}

if ($arResult['CAN_ADD_TEMPLATE'] ?? false)
{
	\Bitrix\UI\Toolbar\Facade\Toolbar::addButton($createButton, ButtonLocation::AFTER_TITLE);
}

if (($arResult['CAN_ADD_TEMPLATE'] ?? false) && ($arResult['CAN_EXPORT_BLANK'] ?? false))
{
	\Bitrix\UI\Toolbar\Facade\Toolbar::addButton(
		(new \Bitrix\UI\Buttons\Button([]))
			->setColor(\Bitrix\UI\Buttons\Color::PRIMARY)
			->setText(Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ACTION_IMPORT'))
			->addClass('sign-b2e-js-import-blank'),
		ButtonLocation::AFTER_FILTER,
	);
}

$getInitiatedByTypeTemplate = static function (?InitiatedByType $InitiatedByType): ?string
{
	return match ($InitiatedByType)
	{
		InitiatedByType::EMPLOYEE => Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_COLUMN_TYPE_FROM_EMPLOYEE'),
		InitiatedByType::COMPANY => Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_COLUMN_TYPE_FROM_COMPANY'),
		default => null,
	};
};

$getUserInfoTemplate = static function (
	?int $userId,
	?string $fullName,
	string $imagePath,
): string {

	ob_start();
	?>
	<div style="display: flex; align-items: center">
		<a
			class="sign-personal-grid-user"
			target="_top"
			onclick="event.stopPropagation();"
			href="/company/personal/user/<?= $userId ?>/">
		<span class="ui-icon ui-icon-common-user">
			<i style=" <?= ($imagePath !== '' ? "background-image: url('". Uri::urnEncode($imagePath) . "');" : '') ?>">
			</i>
		</span>
		<span class="sign-personal-grid-user-name">
				<?= htmlspecialcharsbx($fullName) ?>
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

$getSwitcherTemplate = static function (
	string $visibility,
	string $status,
	int $templateId,
	?string $company,
	bool $isDisabled = false,
	bool $canEdit = false,
): string {
	$isChecked = $visibility === 'visible' ? 'true' : 'false';

	if ($company === null)
	{
		$isDisabled = 'true';
		$isChecked = 'false';
	}

	ob_start();?>
	<div id="switcher_b2e_template_grid_<?= $templateId ?>" class="ui-switcher-container ui-switcher-color-green"></div>
	<script>
		BX.ready(() => {
			templateGrid.renderSwitcher(<?= $templateId ?>, <?= $isChecked ?>, <?= $isDisabled ? 'true' : 'false' ?>, <?= $canEdit ? 'true' : 'false' ?>);
		});
	</script>
	<?php

	return (string)ob_get_clean();
};

$getLinkTemplate = static function (
	string $title,
	string $editTemplateLink,
	bool $canEdit = false,
	bool $showTariffSlider = false,
): string {
	$editTemplateLinkObject = new \Bitrix\Main\Web\Uri($editTemplateLink);
	$isValidEditTemplateLink = str_starts_with($editTemplateLinkObject->getPath(), '/sign/b2e/doc/0/');

	$editTemplateHref = $showTariffSlider ? '#' : (string)$editTemplateLinkObject;
	ob_start();?>

	<?php if ($isValidEditTemplateLink && $canEdit): ?>
		<a <?php if ($showTariffSlider): ?>onclick="top.BX.UI.InfoHelper.show('limit_office_e_signature')"<?php endif; ?> href="<?= $editTemplateHref ?>" class="sign-template-title">
			<?= htmlspecialcharsbx($title) ?>
		</a>
	<?php else: ?>
		<p class="sign-template-title-without-link">
			<?= htmlspecialcharsbx($title) ?>
		</p>
	<?php endif; ?>

	<?php

	return (string)ob_get_clean();
};

$gridRows = [];
$editTemplateLinks = [];
foreach ($arResult["DOCUMENT_TEMPLATES"] as $templatesData)
{
	$dateModify = $templatesData['columns']['DATE_MODIFY'] ?? null;
	$templateId = (int)$templatesData['columns']['ID'];
	$editTemplateLink = Container::instance()
		->getUrlGeneratorService()
		->makeEditTemplateLink($templateId)
	;
	$editTemplateLinks[] = $editTemplateLink;
	$isVisibilitySwitcherDisabled = !($templatesData['access']['canEdit'] ?? false) ||
		($templatesData['columns']['VISIBILITY']->value === 'invisible' && $templatesData['columns']['STATUS']->value === 'new')
	;
	$gridRow = [
		'data' => [
			'ID' => $templateId,
			'TITLE' => $getLinkTemplate(
				$templatesData['columns']['TITLE'],
				$editTemplateLink,
				$templatesData['access']['canEdit'],
				$showTariffSlider,
			),
			'DATE_MODIFY' => $dateModify !== null ? $getDateTemplate($dateModify) : null,
			'RESPONSIBLE' => $getUserInfoTemplate(
				(int)$templatesData['columns']['RESPONSIBLE']['ID'],
				$templatesData['columns']['RESPONSIBLE']['FULL_NAME'],
				$templatesData['columns']['RESPONSIBLE']['AVATAR_PATH'],
			),
			'VISIBILITY' => $getSwitcherTemplate(
				$templatesData['columns']['VISIBILITY']->value,
				$templatesData['columns']['STATUS']->value,
				(int)$templatesData['id'],
				$templatesData['columns']['COMPANY'],
				$isVisibilitySwitcherDisabled,
				$templatesData['access']['canEdit'] ?? false
			),
			'COMPANY' => htmlspecialcharsbx($templatesData['columns']['COMPANY']),
		],
	];
	if ($templatesData['access']['canEdit'] ?? false)
	{
		$gridRow['actions'][] = [
			'text' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ACTION_EDIT'),
			'onclick' => $showTariffSlider
				? "top.BX.UI.InfoHelper.show('limit_office_e_signature')"
				: "BX.SidePanel.Instance.open('$editTemplateLink')"
			,
		];
	}

	if ($templatesData['access']['canCreate'] ?? false)
	{
		$gridRow['actions'][] = [
			'text' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ACTION_COPY'),
			'onclick' => "templateGrid.copyTemplate({$templateId})",
		];
	}

	if ($templatesData['access']['canDelete'] ?? false)
	{
		$gridRow['actions'][] = [
			'text' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ACTION_DELETE'),
			'onclick' => "templateGrid.deleteTemplate({$templateId})",
		];
	}

	if ($arResult['CAN_EXPORT_BLANK'] ?? false)
	{
		$gridRow['actions'][] = [
			'text' => (string)Loc::getMessage('SIGN_B2E_EMPLOYEE_TEMPLATE_LIST_ACTION_EXPORT'),
			'onclick' => "templateGrid.exportBlank({$templateId})",
		];
	}

	if (Feature::instance()->isSenderTypeAvailable())
	{
		$gridRow['data']['TYPE'] = $getInitiatedByTypeTemplate($templatesData['columns']['TYPE']);
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
		'SHOW_ROW_CHECKBOXES' => false,
		'SHOW_TOTAL_COUNTER' => true,
		'TOTAL_ROWS_COUNT' => $arResult['TOTAL_COUNT'] ?? 0,
		'ALLOW_COLUMNS_SORT' => true,
		'ALLOW_SORT' => true,
		'ALLOW_COLUMNS_RESIZE' => true,
		'AJAX_MODE' => 'Y',
		'AJAX_OPTION_HISTORY' => 'N',
		'AJAX_OPTION_JUMP' => 'N',
		'SHOW_ACTION_PANEL' => false,
		'ACTION_PANEL' => [],
	]);
?>

<script>
	const templateGrid = new BX.Sign.V2.Grid.B2e.Templates();
	const addNewTemplateLink = '<?= CUtil::JSEscape($arParams['ADD_NEW_TEMPLATE_LINK']) ?>';

	templateGrid.reloadAfterSliderClose(addNewTemplateLink);

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
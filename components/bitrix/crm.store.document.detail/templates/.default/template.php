<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Application;
use Bitrix\Main\IO\File;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Loader;

global $APPLICATION;

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'no-background');

Extension::load([
	'ui.alerts',
	'ui.tooltip',
	'ui.icons',
	'ui.design-tokens',
	'ui.fonts.opensans',
	'catalog.entity-card',
	'catalog.document-card',
	'crm.entity-selector',
	'spotlight',
]);

if (!empty($arResult['ERROR_MESSAGES']) && is_array($arResult['ERROR_MESSAGES']))
{
	if (count($arResult['ERROR_MESSAGES']) === 1)
	{
		$APPLICATION->IncludeComponent(
			'bitrix:ui.info.error',
			'',
			[
				'DESCRIPTION' => $arResult['ERROR_MESSAGES'][0],
				'IS_HTML' => 'Y',
			]
		);
	}
	else
	{
		foreach ($arResult['ERROR_MESSAGES'] as $error)
		{

			?>
			<div class="ui-alert ui-alert-danger catalog-store-document-list--alert" style="margin-bottom: 0;">
				<span class="ui-alert-message"><?= $error ?></span>
			</div>
			<?php
		}
	}

	return;
}

if (
	Loader::includeModule('location') // TODO: remove this hack after refactoring location's libraries (exception)
	&& Loader::includeModule('sale')
)
{
	Extension::load('sale.address');
}

if (isset($arResult['TOOLBAR_ID']))
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
		(SITE_TEMPLATE_ID === 'bitrix24' ? 'slider' : 'type2'),
		[
			'TOOLBAR_ID' => $arResult['TOOLBAR_ID'],
			'BUTTONS' => $arResult['BUTTONS'] ?? []
		],
		$component,
		['HIDE_ICONS' => 'Y']
	);
}

if ((int)$arResult['ENTITY_DATA']['ID'] > 0)
{
	$labelColorClass = 'ui-label-light';
	$isDocumentCancelled = $arResult['ENTITY_DATA']['DEDUCTED'] === 'N' && !empty($arResult['ENTITY_DATA']['EMP_DEDUCTED_ID']);
	if ($isDocumentCancelled)
	{
		$labelColorClass = 'ui-label-lightorange';
	}
	elseif ($arResult['ENTITY_DATA']['DEDUCTED'] === 'Y')
	{
		$labelColorClass = 'ui-label-lightgreen';
	}

	if ($isDocumentCancelled)
	{
		$labelText = Loc::getMessage('CRM_STORE_DOCUMENT_DETAIL_DOCUMENT_STATUS_CANCELLED');
	}
	else
	{
		$labelText = Loc::getMessage('CRM_STORE_DOCUMENT_DETAIL_DOCUMENT_STATUS_' . $arResult['ENTITY_DATA']['DEDUCTED']);
	}

	$this->SetViewTarget('in_pagetitle');
	?>
	<div class="catalog-title-buttons-wrapper">
	<span id="pagetitle_btn_wrapper" class="pagetitile-button-container">
		<span id="page_url_copy_btn" class="page-link-btn"></span>
	</span>
		<span class="ui-label ui-label-lg document-status-label ui-label-fill <?= $labelColorClass ?>">
		<span class="ui-label-inner">
			<?= $labelText ?>
		</span>
	</span>
	</div>
	<div class="catalog-title-document-type">
		<?= Loc::getMessage('CRM_STORE_DOCUMENT_DETAIL_DOC_TYPE_SHORT_SHIPMENT') ?>
	</div>
	<?php
	$this->EndViewTarget();
}

$tabs = [
	[
		'id' => 'main',
		'name' => Loc::getMessage('CRM_STORE_DOCUMENT_DETAIL_TAB_GENERAL_TITLE'),
		'enabled' => true,
		'active' => true,
	],
	[
		'id' => 'tab_products',
		'name' => Loc::getMessage('CRM_STORE_DOCUMENT_DETAIL_TAB_PRODUCT_TITLE'),
		'enabled' => true,
		'active' => false,
	],
];

$guid = $arResult['GUID'];
$containerId = "{$guid}_CONTAINER";
$tabMenuContainerId = "{$guid}_TABS_MENU";
$tabContainerId = "{$guid}_TABS";

$tabContainerClassName = 'catalog-entity-section catalog-entity-section-tabs';
$tabContainerClassName .= ' ui-entity-stream-section-planned-above-overlay';
?>

<script>
	BX.ready(function() {
		BX.message({
			'CRM_TIMELINE_HISTORY_STUB': '<?= Loc::getMessage('CRM_STORE_DOCUMENT_DETAIL_TIMELINE_STUB_MESSAGE') ?>',
		});
	});
</script>

<div id="<?= htmlspecialcharsbx($containerId) ?>" class="catalog-entity-wrap catalog-wrapper">
	<div class="<?= $tabContainerClassName ?>">
		<ul id="<?= htmlspecialcharsbx($tabMenuContainerId) ?>" class="catalog-entity-section-tabs-container">
			<?php
			foreach ($tabs as $tab)
			{
				$classNames = ['catalog-entity-section-tab'];

				if (isset($tab['active']) && $tab['active'])
				{
					$classNames[] = 'catalog-entity-section-tab-current';
				}
				elseif (isset($tab['enabled']) && !$tab['enabled'])
				{
					$classNames[] = 'catalog-entity-section-tab-disabled';
				}
				?>
				<li data-tab-id="<?= htmlspecialcharsbx($tab['id']) ?>" class="<?= implode(' ', $classNames) ?>">
					<a class="catalog-entity-section-tab-link" href="#"><?= htmlspecialcharsbx($tab['name']) ?></a>
				</li>
				<?php
			}
			?>
		</ul>
	</div>
	<div id="<?= htmlspecialcharsbx($tabContainerId) ?>" style="position: relative;">
		<?php
		foreach ($tabs as $tab)
		{
			$tabId = $tab['id'];
			$className = 'catalog-entity-section catalog-entity-section-info';
			$style = '';

			if ($tab['active'] !== true)
			{
				$className .= ' catalog-entity-section-tab-content-hide catalog-entity-section-above-overlay';
				$style = 'style="display: none;"';
			}
			?>
			<div data-tab-id="<?= htmlspecialcharsbx($tabId) ?>" class="<?= $className ?>" <?= $style ?>>
				<?php
				$tabFolderPath = Application::getDocumentRoot() . $templateFolder . '/tabs/';
				$file = new File($tabFolderPath . $tabId . '.php');

				if ($file->isExists())
				{
					include $file->getPath();
				}
				else
				{
					echo "Unknown tab {{$tabId}}.";
				}
				?>
			</div>
			<?php
		}
		?>
	</div>
</div>

<script>
	BX.message(<?=Json::encode(Loc::loadLanguageFile(__FILE__))?>);

	if (!BX.Reflection.getClass('BX.Crm.Store.DocumentCard.Document.Instance'))
	{
		BX.Crm.Store.DocumentCard.Document.Instance = new BX.Crm.Store.DocumentCard.Document(
			'<?=CUtil::JSEscape($guid)?>',
			{
				entityId: '<?=CUtil::JSEscape($arResult['DOCUMENT_ID'])?>',
				documentStatus: '<?= CUtil::JSEscape($arResult['ENTITY_DATA']['DEDUCTED'] ?? 'N') ?>',
				tabs: <?=CUtil::PhpToJSObject($tabs)?>,
				containerId: '<?=CUtil::JSEscape($containerId)?>',
				tabContainerId: '<?=CUtil::JSEscape($tabContainerId)?>',
				tabMenuContainerId: '<?=CUtil::JSEscape($tabMenuContainerId)?>',
				isDeductLocked: <?= CUtil::PhpToJSObject($arResult['IS_DEDUCT_LOCKED']) ?>,
				permissions: <?= CUtil::PhpToJSObject($arResult['DOCUMENT_PERMISSIONS']) ?>,
				masterSliderUrl: <?= CUtil::PhpToJSObject($arResult['MASTER_SLIDER_URL']) ?>,
				copyLinkButtonId: 'page_url_copy_btn',
				inventoryManagementSource: <?= CUtil::PhpToJSObject($arResult['INVENTORY_MANAGEMENT_SOURCE']) ?>,
			}
		);
	}

	BX.ready(function () {
		BX.Crm.Store.DocumentCard.Document.Instance.adjustToolPanel();
		<?php if (isset($arResult['TOOLBAR_ID'])):?>
			BX.Catalog.DocumentCard.FeedbackButton.render(
				document.getElementById('<?=CUtil::JSEscape($arResult['TOOLBAR_ID'])?>'),
				<?=CUtil::JSEscape(((int)$arResult['DOCUMENT_ID'] <= 0))?>
			);
		<?php endif; ?>

		<?php if (isset($arResult['FOCUSED_TAB'])): ?>
			const tabId = '<?=CUtil::JSEscape($arResult['FOCUSED_TAB'])?>';
			BX.Crm.Store.DocumentCard.Document.Instance.focusOnTab(tabId);
		<?php endif; ?>

		<?if ($arResult['WAREHOUSE_CRM_TOUR_DATA']['IS_TOUR_AVAILABLE']):?>
		const onboardingData = {
			chain:  Number(<?=CUtil::PhpToJSObject($arResult['WAREHOUSE_CRM_TOUR_DATA']['CHAIN_DATA']['CHAIN'])?>),
			chainStep: Number(<?=CUtil::PhpToJSObject($arResult['WAREHOUSE_CRM_TOUR_DATA']['CHAIN_DATA']['STAGE'])?>),
		};
		BX.Crm.Store.DocumentCard.Document.Instance.enableOnboardingChain(onboardingData);
		<?endif;?>
	});
</script>

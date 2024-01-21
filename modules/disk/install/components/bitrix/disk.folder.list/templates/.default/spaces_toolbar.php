<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var $APPLICATION \CMain */
/** @var array $arResult */
/** @var array $arParams */
/** @var \Bitrix\Main\Web\Uri $uriToGrid */
/** @var \Bitrix\Main\Web\Uri $uriToTileM */
/** @var \Bitrix\Main\Web\Uri $uriToTileXL */
/** @var \CBitrixComponent $component */
/** @var string $sortLabel */

use Bitrix\Main\Localization\Loc;
use Bitrix\Disk\Internals\Grid\FolderListOptions;

$uriToGrid->deleteParams(['IFRAME']);
$uriToTileM->deleteParams(['IFRAME']);
$uriToTileXL->deleteParams(['IFRAME']);
?>

<div id="disk-folder-list-toolbar" class="disk__sn-spaces_subtoolbar">
	<?php
		$APPLICATION->IncludeComponent(
			'bitrix:disk.breadcrumbs',
			'',
			[
				'STORAGE_ID' => $arResult['STORAGE']['ID'],
				'BREADCRUMBS_ROOT' => $arResult['BREADCRUMBS_ROOT'],
				'BREADCRUMBS' => $arResult['BREADCRUMBS'],
				'ENABLE_DROPDOWN' => !$arResult['IS_TRASH_MODE'],
			]
		);
	?>

	<div class="disk__sn-spaces_config">
		<?php if (!empty($arResult['ENABLED_TRASHCAN_TTL']) && $arResult['IS_TRASH_MODE']): ?>
			<div class="disk-folder-list-trashcan-info">
				<span class="disk-folder-list-trashcan-info-text">
					<?= Loc::getMessage(
						'DISK_FOLDER_LIST_TRASHCAN_TTL_NOTICE',
						['#TTL_DAY#' => $arResult['TRASHCAN_TTL']]
					) ?>
				</span>
			</div>
		<?php endif; ?>
		<div class="disk-folder-list-sorting">
			<span
				class="disk-folder-list-sorting-text"
				data-role="disk-folder-list-sorting"
			>
				<?= $sortLabel ?>
			</span>
		</div>

		<div class="disk__sn-spaces_subtoolbar-view">
			<a
				href="?<?= $uriToGrid->getQuery() ?>"
				class="disk__sn-spaces_subtoolbar-view-btn <?=
				(
					$arResult['GRID']['MODE'] === FolderListOptions::VIEW_MODE_GRID
					? 'disk-folder-list-view-item-active'
					: ''
				) ?>"
			><div class="ui-icon-set --lines"></div></a>
			<a
				href="?<?= $uriToTileM->getQuery() ?>"
				class="disk__sn-spaces_subtoolbar-view-btn js-disk-change-view <?=
				(
					$arResult['GRID']['MODE'] === FolderListOptions::VIEW_MODE_TILE
					&& $arResult['GRID']['VIEW_SIZE'] === FolderListOptions::VIEW_TILE_SIZE_M
						? 'disk-folder-list-view-item-active'
						: ''
				) ?>"
				data-view-tile-size="<?= FolderListOptions::VIEW_TILE_SIZE_M ?>"
			><div class="ui-icon-set --more-9-cubes-2"></div></a>
			<a
				href="?<?= $uriToTileXL->getQuery() ?>"
				class="disk__sn-spaces_subtoolbar-view-btn js-disk-change-view <?=
				(
					$arResult['GRID']['MODE'] === FolderListOptions::VIEW_MODE_TILE
					&& $arResult['GRID']['VIEW_SIZE'] === FolderListOptions::VIEW_TILE_SIZE_XL
						? 'disk-folder-list-view-item-active'
						: ''
				) ?>"
				data-view-tile-size="<?= FolderListOptions::VIEW_TILE_SIZE_XL ?>"
			><div class="ui-icon-set --4-cubes-1"></div></a>
		</div>
	</div>
</div>

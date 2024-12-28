<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;

/**
 * @var CMain $APPLICATION
 * @var array $arResult
 */

$APPLICATION->SetTitle(Loc::getMessage('BICONNECTOR_SUPERSET_SOURCE_CONNECT_LIST_TITLE_MSGVER_1'));
\Bitrix\UI\Toolbar\Facade\Toolbar::deleteFavoriteStar();

\Bitrix\Main\UI\Extension::load([
	'ui.icons',
]);

/** @var \Bitrix\BIConnector\Superset\ExternalSource\Source[] $sourceList */
$sourceList = $arResult['SOURCE_LIST'];

?>

<div class="biconnector-superset-source-list">
	<?php
		foreach ($sourceList as $source):
	?>
	<div class="biconnector-superset-source-list-card">
		<div class="biconnector-superset-source-list-card__content">
			<div class="biconnector-superset-source-list-card__icon">
				<div style="background: url(images/<?= mb_strtolower($source->getCode()) ?>.svg) no-repeat center; width: 120px; height: 120px"></div>
			</div>
			<div class="biconnector-superset-source-list-card__desc">
				<div class="biconnector-superset-source-list-card__desc-title"><?= htmlspecialcharsbx($source->getTitle()) ?></div>
				<div class="biconnector-superset-source-list-card__desc-text"><?= htmlspecialcharsbx($source->getDescription()) ?></div>
			</div>
		</div>

		<?php
			if ($source->isConnected()):
		?>
		<div class="biconnector-superset-source-list-card__second-connect">
			<div class="biconnector-superset-source-list-card__second-connect-check-wrapper">
				<i class="biconnector-superset-source-list-card__second-connect-check ui-icon-set --circle-check"></i>
			</div>
			<button
				onclick="<?= $source->getOnClickConnectButtonScript() ?>"
				class="biconnector-superset-source-list-card__second-connect-button ui-btn-icon-add ui-btn ui-btn-light-border ui-btn-no-caps ui-btn-round ui-btn-sm"
			>
				<span class="biconnector-superset-source-list-card__second-connect-button-text">
					<?= Loc::getMessage('BICONNECTOR_SUPERSET_SOURCE_CONNECT_LIST_ADD_CONNECTION_BUTTON') ?>
				</span>
			</button>
		</div>
		<?php
			else:
		?>
			<div class="biconnector-superset-source-list-card__first-connect">
				<button
					onclick="<?= $source->getOnClickConnectButtonScript() ?>"
					class="biconnector-superset-source-list-card__first-connect-button ui-btn ui-btn-sm ui-btn-success ui-btn-round ui-btn-no-caps"
				>
					<?= Loc::getMessage('BICONNECTOR_SUPERSET_SOURCE_CONNECT_LIST_ADD_FIRST_CONNECTION_BUTTON') ?>
				</button>
			</div>
		<?php
			endif;
		?>
	</div>
	<?php
		endforeach;
	?>
</div>

<script>
	BX.ready(() => {
		BX.BIConnector.SourceConnectList.Instance = new BX.BIConnector.SourceConnectList();
	});
</script>

<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var \Bitrix\Disk\Internals\BaseComponent $component */
?>
<?php
array_unshift($arResult['BREADCRUMBS'], $arResult['BREADCRUMBS_ROOT']);
$crumb = array_pop($arResult['BREADCRUMBS']);
$prevCrumb = end($arResult['BREADCRUMBS']);
$preparedToJs = array();
foreach($arResult['BREADCRUMBS'] as $crumb)
{
	$preparedToJs[] = array(
		'title' => $crumb['NAME'],
		'text' => $crumb['NAME'],
		'href' => $crumb['ENCODED_LINK'],
	);
}
?>
<div class="disk-breadcrumbs js-disk-breadcrumbs <?= $arParams['CLASS_NAME'] ?>" id="<?= $arResult['BREADCRUMBS_ID'] ?>">
	<div class="disk-breadcrumbs-item js-disk-breadcrumbs-folder" data-object-id="<?= $crumb['ID'] ?>" data-object-name="<?= $crumb['NAME'] ?>" data-is-root="<?= $crumb['ID'] == $arResult['BREADCRUMBS_ROOT']['ID']? 1 : '' ?>" data-object-parent-path="<?= $component->encodeUrn($crumb['LINK']) ?>">
		<a class="disk-breadcrumbs-item-back" <?= !$prevCrumb? 'style="display:none;"' : "href=\"{$prevCrumb['ENCODED_LINK']}\" data-object-id=\"{$prevCrumb['ID']}\" title=\"{$prevCrumb['NAME']}\"" ?> ></a>
		<a class="disk-breadcrumbs-item-title js-disk-breadcrumbs-folder-link" href="<?= $crumb['ENCODED_LINK'] ?>" data-object-id="<?= $crumb['ID'] ?>"><?= $crumb['NAME'] ?></a>
		<span class="disk-breadcrumbs-item-arrow js-disk-breadcrumbs-arrow"></span>
	</div>
</div>

<script type="text/javascript">
	BX.ready(function () {
		BX.Disk['BreadcrumbsClass_<?= $component->getComponentId() ?>'] = new BX.Disk.BreadcrumbsClass({
			storageId: <?= $arResult['STORAGE_ID'] ?>,
			containerId: '<?= $arResult['BREADCRUMBS_ID'] ?>',
			collapsedCrumbs: <?= \Bitrix\Main\Web\Json::encode($preparedToJs) ?>,
			showOnlyDeleted: <?= (int)$arResult['SHOW_ONLY_DELETED'] ?>,
			enableDropdown: <?= (int)$arParams['ENABLE_DROPDOWN'] ?>
		});
	});
</script>
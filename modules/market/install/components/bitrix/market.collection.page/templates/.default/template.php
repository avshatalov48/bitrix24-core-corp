<?

use Bitrix\Main\Localization\Loc;
use Bitrix\Market\AppFavoritesTable;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var string $templateName
 * @var string $templateFile
 * @var string $templateFolder
 * @var string $componentPath
 * @var CBitrixComponent $component
 * @var CBitrixComponentTemplate $this
 * @global CMain $APPLICATION
 * @global CUser $USER
 */
?>

<div class="market-wrapper-page">
	<div class="market-page-header-container">
		<div class="market-page-header-container-blur-bg" style="background-image: url(<?= htmlspecialcharsbx($arResult['IMAGE']); ?>);"></div>
		<div class="market-page-header-container-inner">
			<div class="market-page-images-block" style="background-image: url(<?= htmlspecialcharsbx($arResult['IMAGE']); ?>);">
				<div class="market-page-title">
					<h1 class="market-page-title-text"><?= htmlspecialcharsbx($arResult['NAME']); ?></h1>
					<?/*
					<div class="market-page-title-description"></div>
					<span class="market-page-description"></span>
					*/?>
				</div>
			</div>
			<div class="market-page-info-block">
				<?php if (!empty($arResult['PAGE_INFO']['AUTHOR'])) : ?>
					<div class="market-page-header-author-container">
						<div class="market-page-header-author-avatar-block" style="background-image: url(<?= htmlspecialcharsbx($arResult['PAGE_INFO']['AUTHOR']['IMG']); ?>);"></div>
						<div class="market-page-header-author-info-block">
							<div class="market-page-header-author-info-title"><?= Loc::getMessage('MARKET_COLLECTIONS_AUTHOR')?></div>
							<div class="market-page-header-author-info-name"><?= htmlspecialcharsbx($arResult['PAGE_INFO']['AUTHOR']['NAME']); ?></div>
							<div class="market-page-header-author-info-position"><?= htmlspecialcharsbx($arResult['PAGE_INFO']['AUTHOR']['POSITION']); ?></div>
						</div>
					</div>
					<div>
						<?/*
 							<button class="ui-btn market-btn-light-border ui-btn-xl ui-btn-round">feedback</button>
 						*/?>
					</div>
					<?/*
 						<div class="market-page-info-block-after"></div>
 					*/?>
				<? endif; ?>
			</div>
			<div class="market-page-images-block-after"></div>
		</div>
	</div>
	<div class="market-page-content-container">
		<article class="market-page-article-container">
			<?php foreach ($arResult['PAGE_INFO']['BLOCKS'] as $block) : ?>
				<div class="market-page-article-block">
					<div class="market-page-article-block-title"><?= htmlspecialcharsbx($block['TITLE']); ?></div>
					<div class="market-page-article-block-content"><?= htmlspecialcharsbx($block['TEXT']); ?></div>
				</div>
				<?php if (!empty($block['APPS'])) :?>
					<?php
					$APPLICATION->includeComponent(
						'bitrix:market.collection.toplist',
						'',
						[
							'SHOW_LIST_BUTTON' => 'N',
							'IS_COLLECTION_LANDING' => 'Y',
							'ITEM' => $block,
							'FAVORITE_APPS' => AppFavoritesTable::getUserFavorites(),
						]
					);?>
					<br><br>
				<?endif;?>
			<? endforeach; ?>
		</article>

		<?php
		$APPLICATION->includeComponent(
			'bitrix:market.collection.toplist',
			'',
			[
				'SHOW_LIST_BUTTON' => 'N',
				'IS_COLLECTION_LANDING' => 'Y',
				'ITEM' => $arResult,
				'FAVORITE_APPS' => AppFavoritesTable::getUserFavorites(),
			]
		);?>
	</div>
</div>
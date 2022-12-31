<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

/** @var CBitrixComponentTemplate $this */
/** @var string $templateFolder */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

if (
	(
		in_array($arResult['PAGE_MODE'], ['first', 'refresh'])
		&& $arResult['SHOW_PINNED_PANEL'] === 'Y'
	)
	|| $arResult['TARGET'] === 'ENTRIES_ONLY_PINNED'
)
{
	$blogPostLivefeedProvider = new \Bitrix\Socialnetwork\Livefeed\BlogPost;
	$blogPostEventIdList = $blogPostLivefeedProvider->getEventId();

	$classList = [ 'lenta-pinned-panel' ];
	if (count($arResult['pinnedEvents']) > 0)
	{
		$classList[] = 'lenta-pinned-panel-active';
	}

	if (count($arResult['pinnedEvents']) >= \Bitrix\Mobile\Component\LogList\Util::getCollapsedPinnedPanelItemsLimit())
	{
		$classList[] = 'lenta-pinned-panel-collapsed';
	}

	?><div data-livefeed-pinned-panel class="<?=implode(' ', $classList)?>"><?php

		?><div class="lenta-pinned-collapsed-posts">
			<div class="lenta-pinned-collapsed-posts-content">
				<div class="lenta-pinned-collapsed-posts-title">
					<?=Loc::getMessage('MOBILE_LOG_PINNED_COLLAPSED_COUNTER_POSTS')?>
					<span class="lenta-pinned-collapsed-count lenta-pinned-collapsed-count-posts"><?=count($arResult['pinnedEvents'])?></span>
				</div>
				<div class="lenta-pinned-collapsed-posts-comments">
					<?=Loc::getMessage('MOBILE_LOG_PINNED_COLLAPSED_COUNTER_COMMENTS')?>
					<span class="lenta-pinned-collapsed-count lenta-pinned-collapsed-count-comments">0</span>
				</div>
			</div>
			<div class="lenta-pinned-collapsed-posts-control">
				<div class="lenta-pinned-collapsed-posts-btn"><?=Loc::getMessage('MOBILE_LOG_PINNED_COLLAPSED_COUNTER_BUTTON')?></div>
			</div>
		</div><?php

		if (!empty($arResult['pinnedEvents']))
		{
			if ($arResult['TARGET'] === 'ENTRIES_ONLY_PINNED')
			{
				ob_start();
			}

			foreach($arResult['pinnedEvents'] as $pinnedEvent)
			{
				$arEvent = $pinnedEvent;
				if(in_array($pinnedEvent['EVENT_ID'], $blogPostEventIdList))
				{
					require($_SERVER["DOCUMENT_ROOT"].$templateFolder."/include/blog_post.php");
				}
				else
				{
					require($_SERVER["DOCUMENT_ROOT"].$templateFolder."/include/log_entry.php");
				}
			}

			if ($arResult['TARGET'] === 'ENTRIES_ONLY_PINNED')
			{
				$targetHtml = ob_get_contents();
			}
		}
	?></div><?php
}

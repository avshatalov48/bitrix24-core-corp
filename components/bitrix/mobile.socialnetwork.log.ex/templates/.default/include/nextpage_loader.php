<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

?><div id="next_post_more" class="lenta-item">
	<div class="bx-placeholder-wrap">
		<div class="bx-placeholder">
			<table class="bx-feed-curtain">
				<tr class="bx-curtain-row-0"><td class="bx-curtain-cell-1"></td><td class="bx-curtain-cell-2 transparent"></td><td class="bx-curtain-cell-3"></td><td class="bx-curtain-cell-4"></td><td class="bx-curtain-cell-5"></td><td class="bx-curtain-cell-6"></td><td class="bx-curtain-cell-7"></td></tr>
				<tr class="bx-curtain-row-1 2"><td class="bx-curtain-cell-1"></td><td class="bx-curtain-cell-2 transparent"></td><td class="bx-curtain-cell-3"></td><td class="bx-curtain-cell-4 transparent"></td><td class="bx-curtain-cell-5" colspan="3"></td></tr>
				<tr class="bx-curtain-row-2 3"><td class="bx-curtain-cell-1"></td><td class="bx-curtain-cell-2 transparent" rowspan="2"><div class="bx-bx-curtain-avatar"></div></td><td class="bx-curtain-cell-3" colspan="5"></td></tr>
				<tr class="bx-curtain-row-1"><td class="bx-curtain-cell-1"></td><td class="bx-curtain-cell-3"></td><td class="bx-curtain-cell-4 transparent" colspan="3"></td><td class="bx-curtain-cell-7"></td></tr>
				<tr class="bx-curtain-row-2"><td class="bx-curtain-cell-1" colspan="7"></td></tr>
				<tr class="bx-curtain-row-1"><td class="bx-curtain-cell-1" colspan="3"></td><td class="bx-curtain-cell-4 transparent" colspan="3"></td><td class="bx-curtain-cell-7"></td></tr>
				<tr class="bx-curtain-row-2"><td class="bx-curtain-cell-1" colspan="7"></td></tr>
			</table>
		</div>
	</div>
</div><?php

?><div id="next_page_refresh_needed" style="display: none;">
	<div class="feed-nextpage-locked--container">
		<div class="feed-nextpage-locked--title"><?= Loc::getMessage('MOBILE_LOG_REFRESH_NEEDED_TITLE3')?></div>
		<div class="feed-nextpage-locked--content">
			<span class="feed-nextpage-locked--btn" id="next_page_refresh_needed_button"><?= Loc::getMessage('MOBILE_LOG_REFRESH_NEEDED_BUTTON')?></span>
			<div class="feed-nextpage-locked--prompt"><?= Loc::getMessage('MOBILE_LOG_REFRESH_NEEDED_SUBTITLE')?></div>
		</div>
	</div>
</div>
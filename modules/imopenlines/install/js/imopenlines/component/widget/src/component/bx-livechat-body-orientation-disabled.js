/**
 * Bitrix OpenLines widget
 * Body orientation disabled component (Vue component)
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

import {BitrixVue} from "ui.vue";

BitrixVue.component('bx-livechat-body-orientation-disabled',
{
	template: `
		<div class="bx-livechat-body" key="orientation-head">
			<div class="bx-livechat-mobile-orientation-box">
				<div class="bx-livechat-mobile-orientation-icon"></div>
				<div class="bx-livechat-mobile-orientation-text">{{$Bitrix.Loc.getMessage('BX_LIVECHAT_MOBILE_ROTATE')}}</div>
			</div>
		</div>
	`
});
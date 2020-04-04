/**
 * Bitrix OpenLines widget
 * Body orientation disabled component (Vue component)
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

import {Vue} from "ui.vue";

Vue.component('bx-livechat-body-orientation-disabled',
{
	computed:
	{
		localize()
		{
			return  Object.freeze({
				BX_LIVECHAT_MOBILE_ROTATE: this.$root.$bitrixMessages.BX_LIVECHAT_MOBILE_ROTATE
			});
		}
	},
	template: `
		<div class="bx-livechat-body" key="orientation-head">
			<div class="bx-livechat-mobile-orientation-box">
				<div class="bx-livechat-mobile-orientation-icon"></div>
				<div class="bx-livechat-mobile-orientation-text">{{localize.BX_LIVECHAT_MOBILE_ROTATE}}</div>
			</div>
		</div>
	`
});
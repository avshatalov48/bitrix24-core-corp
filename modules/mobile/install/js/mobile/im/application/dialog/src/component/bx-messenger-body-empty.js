/**
 * Bitrix OpenLines widget
 * Body loading component (Vue component)
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

import {Vue} from "ui.vue";

Vue.component('bx-im-view-body-empty',
{
	computed:
	{
		localize()
		{
			return  Object.freeze({
				MOBILE_CHAT_EMPTY: this.$root.$bitrixMessages.MOBILE_CHAT_EMPTY
			});
		}
	},
	template: `
		<div class="bx-mobilechat-loading-window">
			<h3 class="bx-mobilechat-help-title bx-mobilechat-help-title-md bx-mobilechat-loading-msg">{{localize.MOBILE_CHAT_EMPTY}}</h3>
		</div>
	`
});
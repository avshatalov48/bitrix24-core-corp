/**
 * Bitrix OpenLines widget
 * Body loading component (Vue component)
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

import {Vue} from "ui.vue";

Vue.component('bx-im-view-body-loading',
{
	computed:
	{
		localize()
		{
			return  Object.freeze({
				MOBILE_CHAT_LOADING: this.$root.$bitrixMessages.MOBILE_CHAT_LOADING
			});
		}
	},
	template: `
		<div class="bx-mobilechat-loading-window">
			<svg class="bx-mobilechat-loading-circular" viewBox="25 25 50 50">
				<circle class="bx-mobilechat-loading-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/>
				<circle class="bx-mobilechat-loading-inner-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/>
			</svg>
			<h3 class="bx-mobilechat-help-title bx-mobilechat-help-title-md bx-mobilechat-loading-msg">{{localize.MOBILE_CHAT_LOADING}}</h3>
		</div>
	`
});
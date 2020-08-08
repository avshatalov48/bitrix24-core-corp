/**
 * Bitrix OpenLines widget
 * Body error component (Vue component)
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

import {Vue} from "ui.vue";
import {Vuex} from "ui.vue.vuex";

Vue.component('bx-im-view-body-error',
{
	computed:
	{
		localize()
		{
			return  Object.freeze({
				MOBILE_CHAT_ERROR_TITLE: this.$root.$bitrixMessages.MOBILE_CHAT_ERROR_TITLE,
				MOBILE_CHAT_ERROR_DESC: this.$root.$bitrixMessages.MOBILE_CHAT_ERROR_DESC
			});
		},
		...Vuex.mapState({
			application: state => state.application
		}),
	},
	template: `
		<div class="bx-mobilechat-body" key="error-body">
			<div class="bx-mobilechat-warning-window">
				<div class="bx-mobilechat-warning-icon"></div>
				<template v-if="application.error.description"> 
					<div class="bx-mobilechat-help-title bx-mobilechat-help-title-sm bx-mobilechat-warning-msg" v-html="application.error.description"></div>
				</template> 
				<template v-else>
					<div class="bx-mobilechat-help-title bx-mobilechat-help-title-md bx-mobilechat-warning-msg">{{localize.MOBILE_CHAT_ERROR_TITLE}}</div>
					<div class="bx-mobilechat-help-title bx-mobilechat-help-title-sm bx-mobilechat-warning-msg">{{localize.MOBILE_CHAT_ERROR_DESC}}</div>
				</template> 
			</div>
		</div>
	`
});

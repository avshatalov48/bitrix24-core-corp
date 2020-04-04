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

Vue.component('bx-livechat-body-error',
{
	computed:
	{
		localize()
		{
			return  Object.freeze({
				BX_LIVECHAT_ERROR_TITLE: this.$root.$bitrixMessages.BX_LIVECHAT_ERROR_TITLE,
				BX_LIVECHAT_ERROR_DESC: this.$root.$bitrixMessages.BX_LIVECHAT_ERROR_DESC
			});
		},
		...Vuex.mapState({
			application: state => state.application
		}),
	},
	template: `
		<div class="bx-livechat-body" key="error-body">
			<div class="bx-livechat-warning-window">
				<div class="bx-livechat-warning-icon"></div>
				<template v-if="application.error.description"> 
					<div class="bx-livechat-help-title bx-livechat-help-title-sm bx-livechat-warning-msg" v-html="application.error.description"></div>
				</template> 
				<template v-else>
					<div class="bx-livechat-help-title bx-livechat-help-title-md bx-livechat-warning-msg">{{localize.BX_LIVECHAT_ERROR_TITLE}}</div>
					<div class="bx-livechat-help-title bx-livechat-help-title-sm bx-livechat-warning-msg">{{localize.BX_LIVECHAT_ERROR_DESC}}</div>
				</template> 
			</div>
		</div>
	`
});

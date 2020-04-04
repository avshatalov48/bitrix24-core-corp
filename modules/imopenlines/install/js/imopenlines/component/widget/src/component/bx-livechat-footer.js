/**
 * Bitrix OpenLines widget
 * Footer component (Vue component)
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

import {Vue} from "ui.vue";
import {Vuex} from "ui.vue.vuex";

Vue.component('bx-livechat-footer',
{
	computed:
	{
		localize()
		{
			return Vue.getFilteredPhrases('BX_LIVECHAT_COPYRIGHT_', this.$root.$bitrixMessages);
		},
		...Vuex.mapState({
			widget: state => state.widget
		})
	},
	template: `
		<div class="bx-livechat-copyright">	
			<template v-if="widget.common.copyrightUrl">
				<a :href="widget.common.copyrightUrl" target="_blank">
					<span class="bx-livechat-logo-name">{{localize.BX_LIVECHAT_COPYRIGHT_TEXT}}</span>
					<span class="bx-livechat-logo-icon"></span>
				</a>
			</template>
			<template v-else>
				<span class="bx-livechat-logo-name">{{localize.BX_LIVECHAT_COPYRIGHT_TEXT}}</span>
				<span class="bx-livechat-logo-icon"></span>
			</template>
		</div>
	`
});

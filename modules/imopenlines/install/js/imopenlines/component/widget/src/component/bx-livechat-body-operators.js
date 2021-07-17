/**
 * Bitrix OpenLines widget
 * Body operators component (Vue component)
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

import {BitrixVue} from "ui.vue";
import {Vuex} from "ui.vue.vuex";

BitrixVue.component('bx-livechat-body-operators',
{
	computed:
	{
		...Vuex.mapState({
			widget: state => state.widget
		})
	},
	template: `
		<div class="bx-livechat-help-container">
			<transition name="bx-livechat-animation-fade">
				<h2 v-if="widget.common.online" key="online" class="bx-livechat-help-title bx-livechat-help-title-lg">{{widget.common.textMessages.bxLivechatOnlineLine1}}<div class="bx-livechat-help-subtitle">{{widget.common.textMessages.bxLivechatOnlineLine2}}</div></h2>
				<h2 v-else key="offline" class="bx-livechat-help-title bx-livechat-help-title-sm">{{widget.common.textMessages.bxLivechatOffline}}</h2>
			</transition>	
			<div class="bx-livechat-help-user">
				<template v-for="operator in widget.common.operators">
					<div class="bx-livechat-user" :key="operator.id">
						<template v-if="operator.avatar">
							<div class="bx-livechat-user-icon" :style="'background-image: url('+encodeURI(operator.avatar)+')'"></div>
						</template>
						<template v-else>
							<div class="bx-livechat-user-icon"></div>
						</template>	
						<div class="bx-livechat-user-info">
							<div class="bx-livechat-user-name">{{operator.firstName? operator.firstName: operator.name}}</div>
						</div>
					</div>
				</template>	
			</div>
		</div>
	`
});
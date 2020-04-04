/**
 * Bitrix OpenLines widget
 * Head component (Vue component)
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

import {Vue} from "ui.vue";
import {Vuex} from "ui.vue.vuex";
import {VoteType} from "../const";
import {EventType} from "im.const";

Vue.component('bx-livechat-head',
{
	/**
	 * @emits 'close'
	 * @emits 'like'
	 * @emits 'history'
	 */
	props:
	{
		isWidgetDisabled: { default: false },
	},
	methods:
	{
		close(event)
		{
			this.$emit('close');
		},
		like(event)
		{
			this.$emit('like');
		},
		history(event)
		{
			this.$emit('history');
		},
	},
	computed:
	{
		VoteType: () => VoteType,

		customBackgroundStyle(state)
		{
			return state.widget.common.styles.backgroundColor? 'background-color: '+state.widget.common.styles.backgroundColor+'!important;': '';
		},
		customBackgroundOnlineStyle(state)
		{
			return state.widget.common.styles.backgroundColor? 'border-color: '+state.widget.common.styles.backgroundColor+'!important;': '';
		},
		showName()
		{
			return this.widget.dialog.operator.firstName || this.widget.dialog.operator.lastName;
		},
		localize()
		{
			return Vue.getFilteredPhrases('BX_LIVECHAT_', this.$root.$bitrixMessages);
		},
		...Vuex.mapState({
			widget: state => state.widget,
			application: state => state.application,
		})
	},
	watch:
	{
		showName(value)
		{
			if (value)
			{
				setTimeout(() => {
					this.$root.$emit(EventType.dialog.scrollToBottom);
				}, 300);
			}
		},
	},
	template: `
		<div class="bx-livechat-head-wrap">
			<template v-if="isWidgetDisabled">
				<div class="bx-livechat-head" :style="customBackgroundStyle">
					<div class="bx-livechat-title">{{widget.common.configName || localize.BX_LIVECHAT_TITLE}}</div>
					<div class="bx-livechat-control-box">
						<button v-if="!widget.common.pageMode" class="bx-livechat-control-btn bx-livechat-control-btn-close" :title="localize.BX_LIVECHAT_CLOSE_BUTTON" @click="close"></button>
					</div>
				</div>	
			</template>
			<template v-else-if="application.error.active">
				<div class="bx-livechat-head" :style="customBackgroundStyle">
					<div class="bx-livechat-title">{{widget.common.configName || localize.BX_LIVECHAT_TITLE}}</div>
					<div class="bx-livechat-control-box">
						<button v-if="!widget.common.pageMode" class="bx-livechat-control-btn bx-livechat-control-btn-close" :title="localize.BX_LIVECHAT_CLOSE_BUTTON" @click="close"></button>
					</div>
				</div>
			</template>
			<template v-else-if="!widget.common.configId">
				<div class="bx-livechat-head" :style="customBackgroundStyle">
					<div class="bx-livechat-title">{{widget.common.configName || localize.BX_LIVECHAT_TITLE}}</div>
					<div class="bx-livechat-control-box">
						<button v-if="!widget.common.pageMode" class="bx-livechat-control-btn bx-livechat-control-btn-close" :title="localize.BX_LIVECHAT_CLOSE_BUTTON" @click="close"></button>
					</div>
				</div>
			</template>			
			<template v-else>
				<div class="bx-livechat-head" :style="customBackgroundStyle">
					<template v-if="!showName">
						<div class="bx-livechat-title">{{widget.common.configName || localize.BX_LIVECHAT_TITLE}}</div>
					</template>
					<template v-else>
						<div class="bx-livechat-user bx-livechat-status-online">
							<template v-if="widget.dialog.operator.avatar">
								<div class="bx-livechat-user-icon" :style="'background-image: url('+encodeURI(widget.dialog.operator.avatar)+')'">
									<div v-if="widget.dialog.operator.online" class="bx-livechat-user-status" :style="customBackgroundOnlineStyle"></div>
								</div>
							</template>
							<template v-else>
								<div class="bx-livechat-user-icon">
									<div v-if="widget.dialog.operator.online" class="bx-livechat-user-status" :style="customBackgroundOnlineStyle"></div>
								</div>
							</template>
						</div>
						<div class="bx-livechat-user-info">
							<div class="bx-livechat-user-name">{{widget.dialog.operator.firstName? widget.dialog.operator.firstName: widget.dialog.operator.name}}</div>
							<div class="bx-livechat-user-position">{{widget.dialog.operator.workPosition? widget.dialog.operator.workPosition: localize.BX_LIVECHAT_USER}}</div>
						</div>
					</template>
					<div class="bx-livechat-control-box">
						<span class="bx-livechat-control-box-active" v-if="widget.common.dialogStart && widget.dialog.sessionId">
							<button v-if="widget.common.vote.enable && (!widget.dialog.sessionClose || widget.dialog.sessionClose && widget.dialog.userVote == VoteType.none)" :class="'bx-livechat-control-btn bx-livechat-control-btn-like bx-livechat-dialog-vote-'+(widget.dialog.userVote)" :title="localize.BX_LIVECHAT_VOTE_BUTTON" @click="like"></button>
							<button v-if="widget.common.vote.enable && widget.dialog.sessionClose && widget.dialog.userVote != VoteType.none" :class="'bx-livechat-control-btn bx-livechat-control-btn-disabled bx-livechat-control-btn-like bx-livechat-dialog-vote-'+(widget.dialog.userVote)"></button>
							<button class="bx-livechat-control-btn bx-livechat-control-btn-mail" :title="localize.BX_LIVECHAT_MAIL_BUTTON_NEW" @click="history"></button>
						</span>	
						<button v-if="!widget.common.pageMode" class="bx-livechat-control-btn bx-livechat-control-btn-close" :title="localize.BX_LIVECHAT_CLOSE_BUTTON" @click="close"></button>
					</div>
				</div>
			</template>
		</div>
	`
});
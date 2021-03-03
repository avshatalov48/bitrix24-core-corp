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
import { SessionStatus, VoteType } from "../const";
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
		showName(state)
		{
			return state.widget.dialog.operator.firstName || state.widget.dialog.operator.lastName;
		},
		voteActive(state)
		{
			if (
				!state.widget.common.vote.beforeFinish
				&& state.widget.dialog.sessionStatus < SessionStatus.waitClient
			)
			{
				return false;
			}

			if (!state.widget.dialog.sessionClose || state.widget.dialog.sessionClose && state.widget.dialog.userVote === VoteType.none)
			{
				return true;
			}

			if (state.widget.dialog.sessionClose && state.widget.dialog.userVote !== VoteType.none)
			{
				return true;
			}

			return false;
		},
		chatTitle(state)
		{
			return state.widget.common.textMessages.bxLivechatTitle
				|| state.widget.common.configName
				|| this.localize.BX_LIVECHAT_TITLE
		},
		operatorName(state)
		{
			if (!this.showName)
				return '';

			return state.widget.dialog.operator.firstName? state.widget.dialog.operator.firstName: state.widget.dialog.operator.name
		},
		operatorDescription(state)
		{
			if (!this.showName)
			{
				return '';
			}

			const operatorPosition = state.widget.dialog.operator.workPosition? state.widget.dialog.operator.workPosition: this.localize.BX_LIVECHAT_USER;

			if (state.widget.common.showSessionId && state.widget.dialog.sessionId >= 0)
			{
				return this.localize.BX_LIVECHAT_OPERATOR_POSITION_AND_SESSION_ID
					.replace("#POSITION#", operatorPosition)
					.replace("#ID#", state.widget.dialog.sessionId);
			}

			return this.localize.BX_LIVECHAT_OPERATOR_POSITION_ONLY.replace("#POSITION#", operatorPosition);
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
					<div class="bx-livechat-title">{{chatTitle}}</div>
					<div class="bx-livechat-control-box">
						<button v-if="!widget.common.pageMode" class="bx-livechat-control-btn bx-livechat-control-btn-close" :title="localize.BX_LIVECHAT_CLOSE_BUTTON" @click="close"></button>
					</div>
				</div>	
			</template>
			<template v-else-if="application.error.active">
				<div class="bx-livechat-head" :style="customBackgroundStyle">
					<div class="bx-livechat-title">{{chatTitle}}</div>
					<div class="bx-livechat-control-box">
						<button v-if="!widget.common.pageMode" class="bx-livechat-control-btn bx-livechat-control-btn-close" :title="localize.BX_LIVECHAT_CLOSE_BUTTON" @click="close"></button>
					</div>
				</div>
			</template>
			<template v-else-if="!widget.common.configId">
				<div class="bx-livechat-head" :style="customBackgroundStyle">
					<div class="bx-livechat-title">{{chatTitle}}</div>
					<div class="bx-livechat-control-box">
						<button v-if="!widget.common.pageMode" class="bx-livechat-control-btn bx-livechat-control-btn-close" :title="localize.BX_LIVECHAT_CLOSE_BUTTON" @click="close"></button>
					</div>
				</div>
			</template>			
			<template v-else>
				<div class="bx-livechat-head" :style="customBackgroundStyle">
					<template v-if="!showName">
						<div class="bx-livechat-title">{{chatTitle}}</div>
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
							<div class="bx-livechat-user-name">{{operatorName}}</div>
							<div class="bx-livechat-user-position">{{operatorDescription}}</div>							
						</div>
					</template>
					<div class="bx-livechat-control-box">
						<span class="bx-livechat-control-box-active" v-if="widget.common.dialogStart && widget.dialog.sessionId">
							<button v-if="widget.common.vote.enable && voteActive" :class="'bx-livechat-control-btn bx-livechat-control-btn-like bx-livechat-dialog-vote-'+(widget.dialog.userVote)" :title="localize.BX_LIVECHAT_VOTE_BUTTON" @click="like"></button>
							<button v-if="false" class="bx-livechat-control-btn bx-livechat-control-btn-mail" :title="localize.BX_LIVECHAT_MAIL_BUTTON_NEW" @click="history"></button>
						</span>	
						<button v-if="!widget.common.pageMode" class="bx-livechat-control-btn bx-livechat-control-btn-close" :title="localize.BX_LIVECHAT_CLOSE_BUTTON" @click="close"></button>
					</div>
				</div>
			</template>
		</div>
	`
});
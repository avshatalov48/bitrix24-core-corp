/**
 * Bitrix OpenLines widget
 * Head component (Vue component)
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

import {BitrixVue} from 'ui.vue';
import {Vuex} from 'ui.vue.vuex';
import {WidgetEventType, FormType, SessionStatus, VoteType} from '../const';
import {EventType} from 'im.const';
import {Browser} from 'main.core.minimal';
import {EventEmitter} from 'main.core.events';

BitrixVue.component('bx-livechat-head',
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
	data()
	{
		return {
			multiDialog: false // disabled because of beta status
		};
	},
	methods:
	{
		openDialogList()
		{
			EventEmitter.emit(WidgetEventType.hideForm);
			this.$emit('openDialogList');
		},
		close(event)
		{
			this.$emit('close');
		},
		like()
		{
			EventEmitter.emit(WidgetEventType.showForm, {
				type: FormType.like
			});
		},
		openMenu(event)
		{
			this.$emit('openMenu', event);
		},
	},
	computed:
	{
		VoteType: () => VoteType,

		chatId()
		{
			if (this.application)
			{
				return this.application.dialog.chatId;
			}
		},

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
			if (!!state.widget.dialog.closeVote)
			{
				return false;
			}

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
			return BitrixVue.getFilteredPhrases('BX_LIVECHAT_', this);
		},

		ie11()
		{
			return Browser.isIE11();
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
					this.$root.$emit(EventType.dialog.scrollToBottom, {chatId: this.chatId});
				}, 300);
			}
		},
	},
	//language=Vue
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
							<button
								v-if="!ie11 && application.dialog.chatId > 0"
								class="bx-livechat-control-btn bx-livechat-control-btn-menu"
								@click="openMenu"
								:title="localize.BX_LIVECHAT_DOWNLOAD_HISTORY"
							></button>
							<button
								v-if="multiDialog && application.dialog.chatId > 0"
								class="bx-livechat-control-btn bx-livechat-control-btn-list"
								@click="openDialogList"
							></button>
						</span>	
						<button v-if="!widget.common.pageMode" class="bx-livechat-control-btn bx-livechat-control-btn-close" :title="localize.BX_LIVECHAT_CLOSE_BUTTON" @click="close"></button>
					</div>
				</div>
			</template>
		</div>
	`
});
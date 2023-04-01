/**
 * Bitrix OpenLines widget
 * Form vote component (Vue component)
 *
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

import {BitrixVue} from "ui.vue";
import {Vuex} from "ui.vue.vuex";
import { VoteType, WidgetEventType } from "../const";
import { EventEmitter } from "main.core.events";

BitrixVue.component('bx-livechat-form-vote',
{
	computed:
	{
		VoteType: () => VoteType,

		...Vuex.mapState({
			widget: state => state.widget,
		})
	},
	methods: {
		userVote(vote)
		{
			EventEmitter.emit(WidgetEventType.hideForm);
			this.$store.commit('widget/dialog', {userVote: vote});

			EventEmitter.emit(WidgetEventType.sendDialogVote, {vote});
		},
		hideForm()
		{
			EventEmitter.emit(WidgetEventType.hideForm);
		},
	},
	template: `
		<transition enter-active-class="bx-livechat-consent-window-show" leave-active-class="bx-livechat-form-close">
			<div class="bx-livechat-alert-box bx-livechat-form-rate-show" key="vote">
				<div class="bx-livechat-alert-close" :title="$Bitrix.Loc.getMessage('BX_LIVECHAT_VOTE_LATER')" @click="hideForm"></div>
				<div class="bx-livechat-alert-rate-box">
					<h4 class="bx-livechat-alert-title bx-livechat-alert-title-mdl">{{widget.common.vote.messageText}}</h4>
					<div class="bx-livechat-btn-box">
						<button class="bx-livechat-btn bx-livechat-btn-like" @click="userVote(VoteType.like)" :title="widget.common.vote.messageLike"></button>
						<button class="bx-livechat-btn bx-livechat-btn-dislike" @click="userVote(VoteType.dislike)" :title="widget.common.vote.messageDislike"></button>
					</div>
					<div class="bx-livechat-alert-later"><span class="bx-livechat-alert-later-btn" @click="hideForm">{{$Bitrix.Loc.getMessage('BX_LIVECHAT_VOTE_LATER')}}</span></div>
				</div>
			</div>
		</transition>	
	`
});
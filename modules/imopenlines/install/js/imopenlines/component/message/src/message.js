/**
 * Bitrix Messenger
 * Message Vue component
 *
 * @package bitrix
 * @subpackage im
 * @copyright 2001-2019 Bitrix
 */

import { BitrixVue } from "ui.vue";
import { Vuex } from "ui.vue.vuex";
import './message.css';
import 'im.view.message';
import 'ui.fonts.opensans';
import { EventEmitter } from "main.core.events";
import { WidgetEventType } from "../../widget/src/const";

const FormType = Object.freeze({
	none: 'none',
	like: 'like',
	welcome: 'welcome',
	offline: 'offline',
	history: 'history',
});

const VoteType = Object.freeze({
	none: 'none',
	like: 'like',
	dislike: 'dislike',
});

BitrixVue.cloneComponent('bx-imopenlines-message', 'bx-im-view-message',
{
	methods:
	{
		checkMessageParamsForForm()
		{
			if (!this.message.params || !this.message.params.IMOL_FORM)
			{
				return true;
			}

			if (this.message.params.IMOL_FORM === FormType.like)
			{
				if (
					parseInt(this.message.params.IMOL_VOTE_SID) === this.widget.dialog.sessionId
					&& this.widget.dialog.userVote === VoteType.none
				)
				{
					EventEmitter.emit(WidgetEventType.showForm, {
						type: FormType.like,
						delayed: true
					});
				}
			}
		}
	},
	created()
	{
		this.checkMessageParamsForForm();
	},
	computed:
	{
		dialogNumber()
		{
			if (!this.message.params)
			{
				return false;
			}

			if (!this.message.params.IMOL_SID)
			{
				return false;
			}

			return this.$Bitrix.Loc.getMessage('IMOL_MESSAGE_DIALOG_ID').replace('#ID#', this.message.params.IMOL_SID);
		},
		showMessage()
		{
			if (!this.message.params)
			{
				return true;
			}

			if (
				this.message.params.IMOL_FORM &&
				this.message.params.IMOL_FORM === 'like'
			)
			{
				return false;
			}

			return true;
		},
		...Vuex.mapState({
			widget: state => state.widget,
		})
	},
	template: `
		<div v-if="showMessage" class="bx-imopenlines-message">
			<div v-if="dialogNumber" class="bx-imopenlines-message-dialog-number">{{dialogNumber}}</div>
			#PARENT_TEMPLATE#
		</div>
	`
});
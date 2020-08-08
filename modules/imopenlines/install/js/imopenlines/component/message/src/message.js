/**
 * Bitrix Messenger
 * Message Vue component
 *
 * @package bitrix
 * @subpackage im
 * @copyright 2001-2019 Bitrix
 */

import './message.css';
import 'im.view.message';

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

BX.Vue.cloneComponent('bx-imopenlines-message', 'bx-im-view-message',
{
	methods:
	{
		checkFormShow()
		{
			if (
				!this.message.params
				|| !this.message.params.IMOL_FORM
			)
			{
				return true;
			}

			if (this.message.params.IMOL_FORM === 'welcome')
			{
				if (
					!this.widget.dialog.sessionClose
					&& (
						!this.widget.user.name
						&& !this.widget.user.lastName
						&& !this.widget.user.email
						&& !this.widget.user.phone
					)
				)
				{
					this.$root.$emit('requestShowForm', {
						type: FormType.welcome,
						delayed: true
					})
				}
			}
			else if (this.message.params.IMOL_FORM === 'offline')
			{
				if (
					!this.widget.dialog.sessionClose
					&& (!this.widget.user.email)
				)
				{
					this.$root.$emit('requestShowForm', {
						type: FormType.offline,
						delayed: true
					})
				}
			}
			else if (this.message.params.IMOL_FORM === 'history-delay')
			{
				if (
					parseInt(this.message.params.IMOL_VOTE) === this.widget.dialog.sessionId
					&& this.widget.dialog.userVote === VoteType.none
				)
				{
					this.$root.$emit('requestShowForm', {
						type: FormType.like,
						delayed: true
					})
				}
			}
		}
	},
	created()
	{
		this.checkFormShow();
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

			return this.localize.IMOL_MESSAGE_DIALOG_ID.replace('#ID#', this.message.params.IMOL_SID);
		},
		localize()
		{
			return Object.freeze(
				Object.assign({},
					this.parentLocalize,
					BX.Vue.getFilteredPhrases('IMOL_MESSAGE_', this.$root.$bitrixMessages)
				)
			);
		},
		showMessage()
		{
			if (!this.message.params)
			{
				return true;
			}

			if (
				this.message.params.IMOL_FORM &&
				this.message.params.IMOL_FORM === 'history-delay' // TODO change after release to vote
			)
			{
				return false;
			}

			return true;
		},

		...BX.Vuex.mapState({
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
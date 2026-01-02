/**
 * @module im/messenger/provider/services/analytics/vote
 */
jn.define('im/messenger/provider/services/analytics/vote', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');
	const { Analytics } = require('im/messenger/const');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { AnalyticsHelper } = require('im/messenger/provider/services/analytics/helper');

	/**
	 * @class VoteAnalytics
	 */
	class VoteAnalytics
	{
		constructor()
		{
			this.store = serviceLocator.get('core').getStore();
		}

		/**
		 * @param {DialogId} dialogId
		 */
		sendClickToOpenCreateVote(dialogId)
		{
			/** @type {DialoguesModelState} */
			const dialog = this.store.getters['dialoguesModel/getById'](dialogId);

			new AnalyticsEvent()
				.setTool(Analytics.Tool.im)
				.setCategory(AnalyticsHelper.getCategoryByChatType(dialog.type))
				.setEvent(Analytics.Event.clickCreatePoll)
				.setSection()
				.setP1(AnalyticsHelper.getP1ByChatType(dialog.type))
				.setP2(AnalyticsHelper.getP2ByUserType())
				.setP4(
					DialogHelper.createByDialogId(dialogId)?.isCollab
						? AnalyticsHelper.getFormattedCollabIdByDialogId(dialog.dialogId)
						: AnalyticsHelper.getFormattedParentChatId(dialog.parentChatId)
					,
				)
				.setP5(AnalyticsHelper.getFormattedChatId(dialog.chatId))
				.send()
			;
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {number} voteId
		 * @param {Object} voteData
		 */
		sendOnVotePublished(dialogId, voteId, voteData)
		{
			this.#sendVotePublished(dialogId, voteId, voteData.settings.isAnonymous);
			this.#sendAnswersCountSetting(voteId, Object.keys(voteData.questions[0].answers).length);
			this.#sendMultipleSetting(voteId, voteData.questions[0].isMultipleChoice);
			this.#sendCancelingSetting(voteId, voteData.settings.isRevoteEnabled);
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {number} voteId
		 * @param {boolean} isAnonymous
		 */
		#sendVotePublished(dialogId, voteId, isAnonymous)
		{
			/** @type {DialoguesModelState} */
			const dialog = this.store.getters['dialoguesModel/getById'](dialogId);

			new AnalyticsEvent()
				.setTool(Analytics.Tool.im)
				.setCategory(AnalyticsHelper.getCategoryByChatType(dialog.type))
				.setEvent(Analytics.Event.publishPoll)
				.setType(isAnonymous ? Analytics.Type.voteAnonymous : Analytics.Type.votePublic)
				.setP1(AnalyticsHelper.getP1ByChatType(dialog.type))
				.setP2(AnalyticsHelper.getP2ByUserType())
				.setP3(this.#getP3ByVoteId(voteId))
				.setP4(
					DialogHelper.createByDialogId(dialogId)?.isCollab
						? AnalyticsHelper.getFormattedCollabIdByDialogId(dialog.dialogId)
						: AnalyticsHelper.getFormattedParentChatId(dialog.parentChatId)
					,
				)
				.setP5(AnalyticsHelper.getFormattedChatId(dialog.chatId))
				.send()
			;
		}

		/**
		 * @param {number} voteId
		 * @param {number} answersCount
		 */
		#sendAnswersCountSetting(voteId, answersCount)
		{
			this.#getBaseSettingEvent(voteId)
				.setEvent(Analytics.Event.setOptions)
				.setType(answersCount === 2 ? Analytics.Type.voteAnswersTwo : Analytics.Type.voteAnswersMultiple)
				.send()
			;
		}

		/**
		 * @param {number} voteId
		 * @param {boolean} isMultipleChoice
		 */
		#sendMultipleSetting(voteId, isMultipleChoice)
		{
			this.#getBaseSettingEvent(voteId)
				.setEvent(Analytics.Event.isMultipleChoice)
				.setType(isMultipleChoice ? 'Y' : 'N')
				.send()
			;
		}

		/**
		 * @param {number} voteId
		 * @param {boolean} isRevoteEnabled
		 */
		#sendCancelingSetting(voteId, isRevoteEnabled)
		{
			this.#getBaseSettingEvent(voteId)
				.setEvent(Analytics.Event.setCancelVote)
				.setType(isRevoteEnabled ? 'Y' : 'N')
				.send()
			;
		}

		/**
		 * @param {number} voteId
		 * @return {AnalyticsEvent}
		 */
		#getBaseSettingEvent(voteId)
		{
			return (
				new AnalyticsEvent()
					.setTool(Analytics.Tool.im)
					.setCategory('poll_settings')
					.setP3(this.#getP3ByVoteId(voteId))
			);
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {number} voteId
		 */
		sendVoteVoted(dialogId, voteId)
		{
			/** @type {DialoguesModelState} */
			const dialog = this.store.getters['dialoguesModel/getById'](dialogId);

			new AnalyticsEvent()
				.setTool(Analytics.Tool.im)
				.setCategory(AnalyticsHelper.getCategoryByChatType(dialog.type))
				.setEvent(Analytics.Event.vote)
				.setP1(AnalyticsHelper.getP1ByChatType(dialog.type))
				.setP2(AnalyticsHelper.getP2ByUserType())
				.setP3(this.#getP3ByVoteId(voteId))
				.send()
			;
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {number} voteId
		 */
		sendVoteFinished(dialogId, voteId)
		{
			/** @type {DialoguesModelState} */
			const dialog = this.store.getters['dialoguesModel/getById'](dialogId);

			new AnalyticsEvent()
				.setTool(Analytics.Tool.im)
				.setCategory(AnalyticsHelper.getCategoryByChatType(dialog.type))
				.setEvent(Analytics.Event.finishPoll)
				.setType(Analytics.Type.voteFinisherUser)
				.setP1(AnalyticsHelper.getP1ByChatType(dialog.type))
				.setP2(AnalyticsHelper.getP2ByUserType())
				.setP3(this.#getP3ByVoteId(voteId))
				.setP4(
					DialogHelper.createByDialogId(dialogId)?.isCollab
						? AnalyticsHelper.getFormattedCollabIdByDialogId(dialog.dialogId)
						: AnalyticsHelper.getFormattedParentChatId(dialog.parentChatId)
					,
				)
				.setP5(AnalyticsHelper.getFormattedChatId(dialog.chatId))
				.send()
			;
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {number} voteId
		 */
		sendVoteCancelled(dialogId, voteId)
		{
			/** @type {DialoguesModelState} */
			const dialog = this.store.getters['dialoguesModel/getById'](dialogId);

			new AnalyticsEvent()
				.setTool(Analytics.Tool.im)
				.setCategory(AnalyticsHelper.getCategoryByChatType(dialog.type))
				.setEvent(Analytics.Event.cancelVote)
				.setP1(AnalyticsHelper.getP1ByChatType(dialog.type))
				.setP2(AnalyticsHelper.getP2ByUserType())
				.setP3(this.#getP3ByVoteId(voteId))
				.send()
			;
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {number} voteId
		 */
		sendVoteResultLinkCopied(dialogId, voteId)
		{
			this.#getVoteLinkCopiedEvent(dialogId, voteId)
				.setType(Analytics.Type.voteLinkCopySourceResult)
				.send()
			;
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {number} voteId
		 */
		sendVoteMessageLinkCopied(dialogId, voteId)
		{
			this.#getVoteLinkCopiedEvent(dialogId, voteId)
				.setType(Analytics.Type.voteLinkCopySourceMessage)
				.send()
			;
		}

		/**
		 * @return {AnalyticsEvent}
		 */
		#getVoteLinkCopiedEvent(dialogId, voteId)
		{
			const analyticsEvent = (
				new AnalyticsEvent()
					.setTool(Analytics.Tool.im)
					.setCategory(Analytics.Category.chat)
					.setEvent(Analytics.Event.copyPollLink)
					.setP2(AnalyticsHelper.getP2ByUserType())
					.setP3(this.#getP3ByVoteId(voteId))
			);

			if (dialogId)
			{
				/** @type {DialoguesModelState} */
				const dialog = this.store.getters['dialoguesModel/getById'](dialogId);

				analyticsEvent
					.setCategory(AnalyticsHelper.getCategoryByChatType(dialog.type))
					.setP1(AnalyticsHelper.getP1ByChatType(dialog.type))
				;
			}

			return analyticsEvent;
		}

		#getP3ByVoteId(voteId)
		{
			return `pollId_${voteId}`;
		}
	}

	module.exports = { VoteAnalytics };
});

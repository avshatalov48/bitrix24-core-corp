/**
 * @module im/messenger/provider/service/reaction
 */
jn.define('im/messenger/provider/service/reaction', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { RestMethod } = require('im/messenger/const');
	const { Logger } = require('im/messenger/lib/logger');
	const { runAction } = require('im/messenger/lib/rest');

	class ReactionService
	{
		static getReactionRequestLimit()
		{
			return 50;
		}

		constructor(messageId)
		{
			this.messageId = messageId;
		}

		/**
		 * @param {AllReactions} reactionType
		 * @param {number || null} [lastId = null]
		 * @return Promise<ReactionServiceGetData>
		 */
		async getReactions(reactionType, lastId = null)
		{
			const data = await this.loadReactions(reactionType, lastId);

			void serviceLocator.get('core').getStore().dispatch('usersModel/set', data.users);

			const reactionViewerUsers = data.reactions.map((reactionData) => {
				const user = data.users.find((userData) => userData.id === reactionData.userId);

				/** @type {ReactionViewerUser} */
				return {
					id: user.id,
					reactionId: reactionData.id,
					avatar: user.avatar,
					name: user.name,
					color: user.color,
					reaction: reactionData.reaction.toLowerCase(),
					dateCreate: reactionData.dateCreate,
				};
			}).sort((a, b) => {
				return a.reactionId - b.reactionId;
			});

			/** @type {ReactionServiceGetData} */
			return {
				reactionViewerUsers,
				hasNextPage: reactionViewerUsers.length === ReactionService.getReactionRequestLimit(),
			};
		}

		/**
		 * @private
		 * @param {AllReactions} reactionType
		 * @param {number} lastId
		 * @return {Promise<ReactionServiceLoadData>}
		 */
		async loadReactions(reactionType, lastId)
		{
			const reactionTailData = {
				messageId: this.messageId,
				filter: {
					reaction: reactionType === 'all' ? null : reactionType,
					lastId,
				},
				limit: ReactionService.getReactionRequestLimit(),
				order: {
					id: 'ASC',
				},
			};

			return runAction(RestMethod.imV2ChatMessageReactionTail, { data: reactionTailData })
				.catch((errors) => {
					Logger.error('ReactionService.loadReactions error: ', errors);
				})
			;
		}
	}

	module.exports = { ReactionService };
});

/**
 * @module im/messenger/provider/service/reaction
 */
jn.define('im/messenger/provider/service/reaction', (require, exports, module) => {
	const { core } = require('im/messenger/core');
	const { RestMethod } = require('im/messenger/const');
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
		 * @param {ReactionType} reactionType
		 * @param {number || null} [lastId = null]
		 * @return Promise<ReactionServiceGetData>
		 */
		async getReactions(reactionType, lastId = null)
		{
			const data = await this.loadReactions(reactionType, lastId);

			void core.getStore().dispatch('usersModel/set', data.users);

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
		 * @param {ReactionType} reactionType
		 * @param {number} lastId
		 * @return {Promise<ReactionServiceLoadData>}
		 */
		async loadReactions(reactionType, lastId)
		{
			return new Promise((resolve, reject) => {
				BX.rest.callMethod(
					RestMethod.imV2ChatMessageReactionTail,
					{
						messageId: this.messageId,
						filter: {
							reaction: reactionType,
							lastId,
						},
						limit: ReactionService.getReactionRequestLimit(),
						order: {
							id: 'ASC',
						},
					},
					(result) => {
						if (result.error())
						{
							reject(result.error());

							return;
						}

						resolve(result.data());
					},
				);
			});
		}
	}

	module.exports = { ReactionService };
});

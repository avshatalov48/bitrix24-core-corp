/**
 * @module assets/icons/src/reaction
 */
jn.define('assets/icons/src/reaction', (require, exports, module) => {
	const { BaseIcon } = require('assets/icons/src/base');
	const { withCurrentDomain } = require('utils/url');

	/**
	 * @class ReactionIcon
	 * @extends {BaseIcon}
	 */
	class ReactionIcon extends BaseIcon
	{
		static LIKE = new ReactionIcon('LIKE', {
			name: 'like',
			testId: 'MESSAGE_MENU_REACTION_LIKE',
			path: '/bitrix/mobileapp/mobile/extensions/bitrix/assets/reactions/icons/like.svg',
			imageUrl: '/bitrix/mobileapp/mobile/extensions/bitrix/assets/reactions/images/like.png',
			lottieUrl: '/bitrix/mobileapp/mobile/extensions/bitrix/assets/reactions/lotties/like.json',
			content: '',
		});

		static KISS = new ReactionIcon('KISS', {
			name: 'kiss',
			testId: 'MESSAGE_MENU_REACTION_KISS',
			path: '/bitrix/mobileapp/mobile/extensions/bitrix/assets/reactions/icons/kiss.svg',
			imageUrl: '/bitrix/mobileapp/mobile/extensions/bitrix/assets/reactions/images/kiss.png',
			lottieUrl: '/bitrix/mobileapp/mobile/extensions/bitrix/assets/reactions/lotties/kiss.json',
			content: '',
		});

		static LAUGH = new ReactionIcon('LAUGH', {
			name: 'laugh',
			testId: 'MESSAGE_MENU_REACTION_LAUGH',
			path: '/bitrix/mobileapp/mobile/extensions/bitrix/assets/reactions/icons/laugh.svg',
			imageUrl: '/bitrix/mobileapp/mobile/extensions/bitrix/assets/reactions/images/laugh.png',
			lottieUrl: '/bitrix/mobileapp/mobile/extensions/bitrix/assets/reactions/lotties/laugh.json',
			content: '',
		});

		static WONDER = new ReactionIcon('WONDER', {
			name: 'wonder',
			testId: 'MESSAGE_MENU_REACTION_WONDER',
			path: '/bitrix/mobileapp/mobile/extensions/bitrix/assets/reactions/icons/wonder.svg',
			imageUrl: '/bitrix/mobileapp/mobile/extensions/bitrix/assets/reactions/images/wonder.png',
			lottieUrl: '/bitrix/mobileapp/mobile/extensions/bitrix/assets/reactions/lotties/wonder.json',
			content: '',
		});

		static CRY = new ReactionIcon('CRY', {
			name: 'cry',
			testId: 'MESSAGE_MENU_REACTION_CRY',
			path: '/bitrix/mobileapp/mobile/extensions/bitrix/assets/reactions/icons/cry.svg',
			imageUrl: '/bitrix/mobileapp/mobile/extensions/bitrix/assets/reactions/images/cry.png',
			lottieUrl: '/bitrix/mobileapp/mobile/extensions/bitrix/assets/reactions/lotties/cry.json',
			content: '',
		});

		static ANGRY = new ReactionIcon('ANGRY', {
			name: 'angry',
			testId: 'MESSAGE_MENU_REACTION_ANGRY',
			path: '/bitrix/mobileapp/mobile/extensions/bitrix/assets/reactions/icons/angry.svg',
			imageUrl: '/bitrix/mobileapp/mobile/extensions/bitrix/assets/reactions/images/angry.png',
			lottieUrl: '/bitrix/mobileapp/mobile/extensions/bitrix/assets/reactions/lotties/angry.json',
			content: '',
		});

		static FACEPALM = new ReactionIcon('FACEPALM', {
			name: 'facepalm',
			testId: 'MESSAGE_MENU_REACTION_FACEPALM',
			path: '/bitrix/mobileapp/mobile/extensions/bitrix/assets/reactions/icons/facepalm.svg',
			imageUrl: '/bitrix/mobileapp/mobile/extensions/bitrix/assets/reactions/images/facepalm.png',
			lottieUrl: '/bitrix/mobileapp/mobile/extensions/bitrix/assets/reactions/lotties/facepalm.json',
			content: '',
		});

		static WHATS_NEW_FIRE = new ReactionIcon('WHATS_NEW_FIRE', {
			name: 'fire',
			testId: 'MESSAGE_MENU_REACTION_FIRE',
			path: '/bitrix/mobileapp/mobile/extensions/bitrix/assets/reactions/icons/whats-new-fire.svg',
			imageUrl: '/bitrix/mobileapp/mobile/extensions/bitrix/assets/reactions/images/whats-new-fire.png',
			lottieUrl: null,
			content: '',
		});

		static WHATS_NEW_LIKE = new ReactionIcon('WHATS_NEW_LIKE', {
			name: 'sad',
			testId: 'MESSAGE_MENU_REACTION_SAD',
			path: '/bitrix/mobileapp/mobile/extensions/bitrix/assets/reactions/icons/whats-new-like.svg',
			imageUrl: '/bitrix/mobileapp/mobile/extensions/bitrix/assets/reactions/images/whats-new-like.png',
			lottieUrl: null,
			content: '',
		});

		static WHATS_NEW_DISLIKE = new ReactionIcon('WHATS_NEW_DISLIKE', {
			name: 'heart-eyes',
			testId: 'MESSAGE_MENU_REACTION_HEART_EYES',
			path: '/bitrix/mobileapp/mobile/extensions/bitrix/assets/reactions/icons/whats-new-dislike.svg',
			imageUrl: '/bitrix/mobileapp/mobile/extensions/bitrix/assets/reactions/images/whats-new-dislike.png',
			lottieUrl: null,
			content: '',
		});

		/**
		 * @public
		 * @param {string} reactionId
		 * @return {ReactionIcon|null}
		 */
		static getIconByReactionId(reactionId)
		{
			return Object.values(ReactionIcon).find((reaction) => {
				return reaction.getIconName() === reactionId;
			}) ?? null;
		}

		/**
		 * @public
		 * @param {string} reactionId
		 * @return {string|null}
		 */
		static getPathByReactionId(reactionId)
		{
			const icon = ReactionIcon.getIconByReactionId(reactionId);

			return icon?.value?.path ?? null;
		}

		static #getReactionList()
		{
			return [
				this.LIKE.getValue(),
				this.KISS.getValue(),
				this.LAUGH.getValue(),
				this.WONDER.getValue(),
				this.CRY.getValue(),
				this.ANGRY.getValue(),
				this.FACEPALM.getValue(),
			];
		}

		/**
		 * Returns a mapping of reaction IDs to their corresponding Lottie animation URLs
		 * @public
		 * @returns {Object}
		 */
		static getLottieAnimationList()
		{
			return this.#getReactionList().reduce((animations, reaction) => {
				return {
					...animations,
					[reaction.name]: withCurrentDomain(reaction.lottieUrl),
				};
			}, {});
		}

		/**
		 * Returns the animation URL for chosen reactionId
		 * @public
		 * @param {string} reactionId
		 * @returns {string|null}
		 */
		static getLottieAnimationById(reactionId)
		{
			const animation = this.#getReactionList().find((reaction) => reaction.name === reactionId);

			return animation ? withCurrentDomain(animation.lottieUrl) : null;
		}

		/**
		 * Returns an array of reaction formatted for use in native contextMenuModule
		 * @public
		 * @returns {Array<Object>}
		 */
		static getDataForContextMenu()
		{
			return this.#getReactionList().map((reaction) => ({
				id: reaction.name,
				testId: reaction.testId,
				imageUrl: withCurrentDomain(reaction.imageUrl),
				lottieUrl: withCurrentDomain(reaction.lottieUrl),
				svgUrl: withCurrentDomain(reaction.path),
			}));
		}
	}

	module.exports = { ReactionIcon };
});

/**
 * @module im/messenger/lib/element/dialog/message-menu/reaction
 */
jn.define('im/messenger/lib/element/dialog/message-menu/reaction', (require, exports, module) => {
	const { ReactionAssets } = require('im/messenger/assets/common');
	const { ReactionType } = require('im/messenger/const');

	const LikeReaction = {
		id: ReactionType.like,
		testId: 'MESSAGE_MENU_REACTION_LIKE',
		imageUrl: ReactionAssets.getImageUrl(ReactionType.like),
		lottieUrl: ReactionAssets.getLottieUrl(ReactionType.like),
	};

	const KissReaction = {
		id: ReactionType.kiss,
		testId: 'MESSAGE_MENU_REACTION_KISS',
		imageUrl: ReactionAssets.getImageUrl(ReactionType.kiss),
		lottieUrl: ReactionAssets.getLottieUrl(ReactionType.kiss),
	};

	const LaughReaction = {
		id: ReactionType.laugh,
		testId: 'MESSAGE_MENU_REACTION_LAUGH',
		imageUrl: ReactionAssets.getImageUrl(ReactionType.laugh),
		lottieUrl: ReactionAssets.getLottieUrl(ReactionType.laugh),
	};

	const WonderReaction = {
		id: ReactionType.wonder,
		testId: 'MESSAGE_MENU_REACTION_WONDER',
		imageUrl: ReactionAssets.getImageUrl(ReactionType.wonder),
		lottieUrl: ReactionAssets.getLottieUrl(ReactionType.wonder),
	};

	const CryReaction = {
		id: ReactionType.cry,
		testId: 'MESSAGE_MENU_REACTION_CRY',
		imageUrl: ReactionAssets.getImageUrl(ReactionType.cry),
		lottieUrl: ReactionAssets.getLottieUrl(ReactionType.cry),
	};

	const AngryReaction = {
		id: ReactionType.angry,
		testId: 'MESSAGE_MENU_REACTION_ANGRY',
		imageUrl: ReactionAssets.getImageUrl(ReactionType.angry),
		lottieUrl: ReactionAssets.getLottieUrl(ReactionType.angry),
	};

	const FacepalmReaction = {
		id: ReactionType.facepalm,
		testId: 'MESSAGE_MENU_REACTION_FACEPALM',
		imageUrl: ReactionAssets.getImageUrl(ReactionType.facepalm),
		lottieUrl: ReactionAssets.getLottieUrl(ReactionType.facepalm),
	};

	module.exports = {
		LikeReaction,
		KissReaction,
		LaughReaction,
		WonderReaction,
		CryReaction,
		AngryReaction,
		FacepalmReaction,
	};
});

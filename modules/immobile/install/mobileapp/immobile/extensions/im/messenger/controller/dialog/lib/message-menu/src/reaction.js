/**
 * @module im/messenger/controller/dialog/lib/message-menu/reaction
 */
jn.define('im/messenger/controller/dialog/lib/message-menu/reaction', (require, exports, module) => {
	const { ReactionAssets } = require('im/messenger/assets/common');
	const { ReactionType } = require('im/messenger/const');

	const LikeReaction = {
		id: ReactionType.like,
		testId: 'MESSAGE_MENU_REACTION_LIKE',
		imageUrl: ReactionAssets.getImageUrl(ReactionType.like),
		lottieUrl: ReactionAssets.getLottieUrl(ReactionType.like),
		svgUrl: ReactionAssets.getSvgUrl(ReactionType.like),
	};

	const KissReaction = {
		id: ReactionType.kiss,
		testId: 'MESSAGE_MENU_REACTION_KISS',
		imageUrl: ReactionAssets.getImageUrl(ReactionType.kiss),
		lottieUrl: ReactionAssets.getLottieUrl(ReactionType.kiss),
		svgUrl: ReactionAssets.getSvgUrl(ReactionType.kiss),
	};

	const LaughReaction = {
		id: ReactionType.laugh,
		testId: 'MESSAGE_MENU_REACTION_LAUGH',
		imageUrl: ReactionAssets.getImageUrl(ReactionType.laugh),
		lottieUrl: ReactionAssets.getLottieUrl(ReactionType.laugh),
		svgUrl: ReactionAssets.getSvgUrl(ReactionType.laugh),
	};

	const WonderReaction = {
		id: ReactionType.wonder,
		testId: 'MESSAGE_MENU_REACTION_WONDER',
		imageUrl: ReactionAssets.getImageUrl(ReactionType.wonder),
		lottieUrl: ReactionAssets.getLottieUrl(ReactionType.wonder),
		svgUrl: ReactionAssets.getSvgUrl(ReactionType.wonder),
	};

	const CryReaction = {
		id: ReactionType.cry,
		testId: 'MESSAGE_MENU_REACTION_CRY',
		imageUrl: ReactionAssets.getImageUrl(ReactionType.cry),
		lottieUrl: ReactionAssets.getLottieUrl(ReactionType.cry),
		svgUrl: ReactionAssets.getSvgUrl(ReactionType.cry),
	};

	const AngryReaction = {
		id: ReactionType.angry,
		testId: 'MESSAGE_MENU_REACTION_ANGRY',
		imageUrl: ReactionAssets.getImageUrl(ReactionType.angry),
		lottieUrl: ReactionAssets.getLottieUrl(ReactionType.angry),
		svgUrl: ReactionAssets.getSvgUrl(ReactionType.angry),
	};

	const FacepalmReaction = {
		id: ReactionType.facepalm,
		testId: 'MESSAGE_MENU_REACTION_FACEPALM',
		imageUrl: ReactionAssets.getImageUrl(ReactionType.facepalm),
		lottieUrl: ReactionAssets.getLottieUrl(ReactionType.facepalm),
		svgUrl: ReactionAssets.getSvgUrl(ReactionType.facepalm),
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

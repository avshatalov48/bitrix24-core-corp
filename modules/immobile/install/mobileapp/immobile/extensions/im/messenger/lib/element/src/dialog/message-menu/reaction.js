/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */

/**
 * @module im/messenger/lib/element/dialog/message-menu/reaction
 */
jn.define('im/messenger/lib/element/dialog/message-menu/reaction', (require, exports, module) => {

	const { ReactionType } = require('im/messenger/const');
	const messageMenuPath = currentDomain + '/bitrix/mobileapp/immobile/extensions/im/messenger/lib/element/src/dialog/message-menu/';
	const imagePath = messageMenuPath + 'images/';
	const lottiePath = messageMenuPath + 'lottie/';

	const LikeReaction = {
		id: ReactionType.like,
		testId: 'MESSAGE_MENU_REACTION_LIKE',
		imageUrl: imagePath + 'reaction/like.png',
		lottieUrl: lottiePath + 'reaction/like.json',
	}

	const KissReaction = {
		id: ReactionType.kiss,
		testId: 'MESSAGE_MENU_REACTION_KISS',
		imageUrl: imagePath + 'reaction/kiss.png',
		lottieUrl: lottiePath + 'reaction/kiss.json',
	}

	const LaughReaction = {
		id: ReactionType.laugh,
		testId: 'MESSAGE_MENU_REACTION_LAUGH',
		imageUrl: imagePath + 'reaction/laugh.png',
		lottieUrl: lottiePath + 'reaction/laugh.json',
	}

	const WonderReaction = {
		id: ReactionType.wonder,
		testId: 'MESSAGE_MENU_REACTION_WONDER',
		imageUrl: imagePath + 'reaction/wonder.png',
		lottieUrl: lottiePath + 'reaction/wonder.json',
	}

	const CryReaction = {
		id: ReactionType.cry,
		testId: 'MESSAGE_MENU_REACTION_CRY',
		imageUrl: imagePath + 'reaction/cry.png',
		lottieUrl: lottiePath + 'reaction/cry.json',
	}

	const AngryReaction = {
		id: ReactionType.angry,
		testId: 'MESSAGE_MENU_REACTION_ANGRY',
		imageUrl: imagePath + 'reaction/angry.png',
		lottieUrl: lottiePath + 'reaction/angry.json',
	}

	const FacepalmReaction = {
		id: ReactionType.facepalm,
		testId: 'MESSAGE_MENU_REACTION_FACEPALM',
		imageUrl: imagePath + 'reaction/facepalm.png',
		lottieUrl: lottiePath + 'reaction/facepalm.json',
	}

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

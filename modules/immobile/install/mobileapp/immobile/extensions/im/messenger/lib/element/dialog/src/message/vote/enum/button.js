/**
 * @module im/messenger/lib/element/dialog/message/vote/enum/button
 */
jn.define('im/messenger/lib/element/dialog/message/vote/enum/button', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');
	const { ButtonId } = require('im/messenger/lib/element/dialog/message/vote/const/button-id');
	const { ButtonSize } = require('ui-system/form/buttons');
	const { Icon } = require('ui-system/blocks/icon');

	/**
	 * @class VoteButton
	 * @extends {BaseEnum<VoteButton>}
	 */
	class VoteButton extends BaseEnum
	{
		static LOADER = new VoteButton(
			'LOADER',
			{
				id: ButtonId.NONE,
				height: ButtonSize.XS.getName(),
				disabled: false,
				design: 'plain',
				type: 'fill',
				rightIconName: undefined,
				hasLoader: true,
			},
		);

		static NO_VOTES = new VoteButton(
			'NO_VOTES',
			{
				id: ButtonId.NONE,
				height: ButtonSize.XS.getName(),
				disabled: true,
				design: 'plain',
				type: 'fill',
				rightIconName: undefined,
				hasLoader: false,
			},
		);

		static WITH_VOTES_DISABLED = new VoteButton(
			'WITH_VOTES_DISABLED',
			{
				id: ButtonId.SHOW_RESULT,
				height: ButtonSize.XS.getName(),
				disabled: true,
				design: 'plain',
				type: 'fill',
				rightIconName: undefined,
				hasLoader: false,
			},
		);

		static WITH_VOTES = new VoteButton(
			'WITH_VOTES',
			{
				id: ButtonId.SHOW_RESULT,
				height: ButtonSize.XS.getName(),
				disabled: false,
				design: 'plain-accent',
				type: 'fill',
				rightIconName: Icon.CHEVRON_TO_THE_RIGHT.getIconName(),
				hasLoader: false,
			},
		);

		static VOTE_ACTIVE = new VoteButton(
			'VOTE_ACTIVE',
			{
				id: ButtonId.VOTE,
				height: ButtonSize.S.getName(),
				disabled: false,
				design: 'filled',
				type: 'fill',
				rightIconName: undefined,
				hasLoader: false,
			},
		);

		static VOTE_INACTIVE = new VoteButton(
			'VOTE_INACTIVE',
			{
				id: ButtonId.VOTE,
				height: ButtonSize.S.getName(),
				disabled: true,
				design: 'filled',
				type: 'fill',
				rightIconName: undefined,
				hasLoader: false,
			},
		);
	}

	module.exports = { VoteButton };
});

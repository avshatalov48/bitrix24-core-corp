/**
 * @module im/messenger/controller/chat-composer/lib/element/participant
 */
jn.define('im/messenger/controller/chat-composer/lib/element/participant', (require, exports, module) => {
	const { Type } = require('type');
	const { Indent, Color } = require('tokens');
	const { Icon, IconView } = require('ui-system/blocks/icon');
	const { Avatar, AvatarShape } = require('ui-system/blocks/avatar');
	const { Text2, Text5 } = require('ui-system/typography');

	const { EntitySelectorElementType } = require('im/messenger/const');

	const AVATAR_SIZE = 40;

	/**
	 * @param {ParticipantProps} props
	 */
	function Participant(props)
	{
		return View(
			{
				style: {
					flexDirection: 'row',
					maxHeight: 70,
				},
			},
			View(
				{
					style: {
						paddingTop: 14,
						paddingBottom: 15,
						marginLeft: Indent.XL.toNumber(),
					},
				},
				props.type === EntitySelectorElementType.department
					? DepartmentAvatar()
					: UserAvatar(props)
				,
			),
			View(
				{
					style: {
						marginLeft: Indent.XL.toNumber(),
						borderBottomWidth: 1,
						borderBottomColor: Color.bgSeparatorSecondary.toHex(),
						flexDirection: 'column',
						paddingRight: Indent.XL.toNumber(),
						flexGrow: 2,
						paddingTop: 14,
						paddingBottom: 15,
						justifyContent: 'center',
					},
				},
				Text2({
					text: props.title,
					accent: false,
				}),
				Type.isStringFilled(props.subtitle)
					? Text5({
						text: props.subtitle,
						color: Color.base3,
						numberOfLines: 1,
					})
					: null
				,
			),
		);
	}

	function DepartmentAvatar()
	{
		return Avatar({
			backgroundColor: Color.accentExtraAqua,
			icon: IconView({
				color: Color.baseWhiteFixed,
				icon: Icon.GROUP,
				size: 32,
			}),
			size: AVATAR_SIZE,
			shape: AvatarShape.SQUARE,
		});
	}

	function UserAvatar(props)
	{
		return Avatar({
			size: AVATAR_SIZE,
			shape: AvatarShape.CIRCLE,
			uri: props.uri,
			name: props.title,
			useLetterImage: true,
			id: props.id,
		});
	}

	module.exports = { Participant };
});

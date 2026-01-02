/**
 * @module more-menu/ui/profile-button
 */
jn.define('more-menu/ui/profile-button', (require, exports, module) => {
	const { Color, Indent, Corner } = require('tokens');
	const { Text4 } = require('ui-system/typography/text');
	const { BadgeCounter, BadgeCounterDesign, BadgeCounterSize } = require('ui-system/blocks/badges/counter');
	const { withPressed } = require('utils/color');
	const { IconView, Icon } = require('ui-system/blocks/icon');

	/**
	 * @param props
	 * @param {string} props.name
	 * @param {number} props.badge
	 * @param {function} props.onClick
	 * @param {Icon} props.leftIcon
	 * @param {boold} props.shouldShowChevron
	 * @param {string} props.testId
	 * @param {Object} props.style
	 */
	const ProfileButton = (props) => {
		const {
			text,
			badge,
			onClick,
			leftIcon,
			shouldShowChevron,
			testId = 'profile_button',
			style = {},
		} = props;

		return View(
			{
				style: {
					paddingHorizontal: Indent.XL.toNumber(),
					borderRadius: Corner.L.toNumber(),
					borderWidth: 1,
					borderColor: Color.bgSeparatorSecondary.toHex(),
					height: 45,
					alignItems: 'center',
					backgroundColor: withPressed(Color.bgContentPrimary.toHex()),
					justifyContent: 'space-between',
					flexDirection: 'row',
					flex: 2,
					...style,
				},
				onClick,
			},
			leftIcon && IconView({
				icon: leftIcon,
				size: 26,
				color: Color.accentMainPrimary,
				testId: `${testId}-left-icon`,
				style: {
					marginRight: Indent.XS.toNumber(),
				},
			}),
			Text4({
				text,
				color: Color.base1.toHex(),
				style: {
					flex: 2,
					marginRight: Indent.XS.toNumber(),
				},
				numberOfLines: 1,
				ellipsize: 'end',
			}),
			Number(badge) && BadgeCounter({
				value: badge,
				design: BadgeCounterDesign.ALERT,
				size: BadgeCounterSize.M,
				color: Color.baseWhiteFixed,
				testId: `${testId}_counter`,
				style: {
					marginLeft: Indent.XS.toNumber(),
				},
			}),
			shouldShowChevron && IconView({
				icon: Icon.CHEVRON_TO_THE_RIGHT,
				size: 22,
				color: Color.base4,
				testId: `${testId}-chevron`,
				style: {
					marginLeft: Indent.XS.toNumber(),
				},
			}),
		);
	};

	module.exports = {
		ProfileButton,
	};
});

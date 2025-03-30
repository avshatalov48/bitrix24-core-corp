/**
 * @module stafftrack/check-in/cancel-reason-view
 */
jn.define('stafftrack/check-in/cancel-reason-view', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Indent, Color, Corner } = require('tokens');
	const { Avatar } = require('ui-system/blocks/avatar');

	const { Text3 } = require('ui-system/typography/text');

	const { ScrollViewWithMaxHeight } = require('stafftrack/ui');

	const CancelReasonView = (props) => View(
		{
			style: {
				paddingVertical: Indent.L.toNumber(),
			},
		},
		ReasonTitle(),
		ReasonDescriptionContainer(props),
	);

	const ReasonTitle = () => View(
		{
			style: {
				alignItems: 'flex-start',
			},
		},
		Text3({
			text: `${Loc.getMessage('M_STAFFTRACK_CHECK_IN_REASON')}:`,
			color: Color.base3,
		}),
	);

	const ReasonDescriptionContainer = (props) => View(
		{
			style: {
				paddingTop: Indent.XL2.toNumber(),
				flexDirection: 'row',
			},
		},
		AvatarBlock(props.userInfo),
		View(
			{
				style: {
					flex: 1,
					borderColor: Color.bgSeparatorPrimary.toHex(),
					borderRadius: Corner.M.toNumber(),
					borderWidth: 1,
					maxHeight: 76,
					paddingHorizontal: Indent.L.toNumber(),
					paddingVertical: Indent.M.toNumber(),
				},
			},
			ReasonDescription(props.cancelReason),
		),
	);

	const AvatarBlock = (userInfo) => View(
		{
			style: {
				width: 36,
				height: 36,
				marginRight: Indent.M.toNumber(),
			},
		},
		Avatar({
			testId: 'stafftrack-shift-cancel-description-avatar',
			id: userInfo.id,
			size: 36,
			uri: userInfo.avatar,
			name: userInfo.name,
		}),
	);

	const ReasonDescription = (reason) => {
		return new ScrollViewWithMaxHeight({
			testId: 'stafftrack-shift-cancel-description',
			style: {
				minHeight: 20,
				maxHeight: 60,
			},
			renderContent: () => Text3({
				text: reason,
				color: Color.base2,
			}),
		});
	};

	module.exports = { CancelReasonView };
});

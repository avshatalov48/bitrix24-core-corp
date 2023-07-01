/**
 * @module im/calls-card/card-content/elements/collapse-button
 */
jn.define('im/calls-card/card-content/elements/collapse-button', (require, exports, module) => {
	const collapseIcon = `<svg width="15" height="14" viewBox="0 0 15 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.15167 13.0892C4.76114 13.4798 4.12798 13.4798 3.73746 13.0892L3.20713 12.5589C2.8166 12.1684 2.8166 11.5352 3.20713 11.1447L7.43048 6.92136L4.41667 6.92136C3.86439 6.92136 3.41667 6.47364 3.41667 5.92135L3.41667 5.62969C3.41667 5.0774 3.86439 4.62969 4.41667 4.62969L9.375 4.62969L11.6667 4.62969L11.6667 6.92136L11.6667 11.8797C11.6667 12.432 11.219 12.8797 10.6667 12.8797L10.375 12.8797C9.82272 12.8797 9.375 12.432 9.375 11.8797L9.375 8.86592L5.15167 13.0892ZM14.4167 2.41666L0.666657 2.41666L0.666658 0.583325L14.4167 0.583326L14.4167 2.41666Z" fill="white" fill-opacity="0.4"/></svg>`;

	const CollapseButton = ({onRollUp}) => {
		return View(
			{
				style: {
					flexDirection: 'row',
					position: 'absolute',
					top: 10,
					right: 27,
				},
				testId: 'calls-card-collapse-button',
				onClick: () => {
					onRollUp();
				},
			},
			Text({
				text: BX.message('IMMOBILE_CALLS_CARD_COLLAPSE_BUTTON_TEXT'),
				style: {
					fontSize: 14,
					color: '#787878',
					marginRight: 11,
				},
			}),
			Image({
				style: {
					width: 15,
					height: 15,
				},
				svg: {
					content: collapseIcon,
				},
			}),
		);
	}

	module.exports = { CollapseButton };
});
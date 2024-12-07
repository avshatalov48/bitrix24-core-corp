/**
 * @module layout/ui/fields/theme/air/elements/more-button
 */
jn.define('layout/ui/fields/theme/air/elements/more-button', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { Button, ButtonSize, ButtonDesign } = require('ui-system/form/buttons/button');

	const MoreButton = ({ testId, text, onClick }) => View(
		{
			style: {
				position: 'absolute',
				bottom: 0,
				width: '100%',
				height: 90,
				flexDirection: 'row',
				alignItems: 'flex-end',
				borderRadius: 0,
				backgroundColorGradient: {
					start: Color.bgContentPrimary.toHex(0),
					middle: Color.bgContentPrimary.toHex(),
					end: Color.bgContentPrimary.toHex(),
					angle: 90,
				},
			},
			onClick,
		},
		View(
			{
				style: {
					paddingVertical: Indent.XL.toNumber(),
					paddingHorizontal: Indent.XL2.toNumber(),
					flex: 2,
				},
			},
			Button({
				testId,
				text,
				onClick,
				size: ButtonSize.XS,
				design: ButtonDesign.OUTLINE_ACCENT_2,
			}),
		),
	);

	module.exports = { MoreButton };
});

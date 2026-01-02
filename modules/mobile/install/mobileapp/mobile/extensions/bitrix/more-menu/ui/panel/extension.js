/**
 * @module more-menu/ui/panel
 */
jn.define('more-menu/ui/panel', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { Card, CardDesign } = require('ui-system/layout/card');
	const { Text2 } = require('ui-system/typography/text');
	const { PropTypes } = require('utils/validation');

	/**
	 * @param props
	 * @param {string} props.testId
	 * @param {string} [props.title]
	 * @param {Array} [props.children]
	 * @returns {LayoutComponent}
	 */
	const MoreMenuPanel = (props) => {
		const { testId, title, children, style = {} } = props;

		return Card(
			{
				testId,
				style: {
					margin: Indent.XL3.toNumber(),
					marginBottom: 0,
					borderColor: Color.bgSeparatorSecondary.toHex(),
				},
				design: CardDesign.PRIMARY,
				border: true,
				excludePaddingSide: {
					left: true,
					right: true,
					top: true,
					bottom: true,
				},
			},
			View(
				{
					style: {
						paddingHorizontal: Indent.M.toNumber(),
						paddingVertical: Indent.XL2.toNumber(),
						...style,
					},
				},
				title && Text2({
					testId: `${testId}_panel_title`,
					text: title,
					color: Color.base1.toHex(),
					style: {
						marginHorizontal: Indent.S.toNumber(),
					},
					numberOfLines: 1,
					ellipsize: 'end',
				}),
				...children,
			),
		);
	};

	MoreMenuPanel.propTypes = {
		testId: PropTypes.string.isRequired,
		title: PropTypes.string.isRequired,
		style: PropTypes.object,
		children: PropTypes.array.isRequired,
	};

	module.exports = {
		MoreMenuPanel,
	};
});

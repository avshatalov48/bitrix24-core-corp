/**
 * @module user-profile/common-tab/src/block/base-edit
 */
jn.define('user-profile/common-tab/src/block/base-edit', (require, exports, module) => {
	const { Indent, Color, Component } = require('tokens');
	const { Capital } = require('ui-system/typography/text');
	const { PropTypes } = require('utils/validation');

	/**
	 * @param {Object} props
	 * @param {string} props.testId
	 * @param {string} [props.title='']
	 * @param {Object} [props.content=null]
	 * @param {Object} [props.style]
	 * @return BaseViewWrapper
	 */
	const BaseEditWrapper = (props) => {
		const { title, content, testId, style } = props;

		return View(
			{
				style: {
					marginBottom: Indent.XL2.toNumber(),
					paddingHorizontal: Component.areaPaddingLr.toNumber(),
					borderRadius: 12,
					backgroundColor: Color.bgContentPrimary.toHex(),
					paddingBottom: Indent.L.toNumber(),
					...style,
				},
				testId,
			},
			title && View(
				{
					style: {
						paddingVertical: Indent.M.toNumber(),
					},
				},
				Capital({
					style: {
						paddingVertical: Indent.L.toNumber(),
						borderBottomWidth: 0.5,
						borderBottomColor: Color.bgSeparatorSecondary.toHex(),
					},
					accent: true,
					text: title,
					color: Color.base2,
				}),
			),
			content,
		);
	};

	BaseEditWrapper.defaultProps = {
		title: '',
		content: null,
	};

	BaseEditWrapper.propTypes = {
		testId: PropTypes.string.isRequired,
		title: PropTypes.string,
		content: PropTypes.object,
		style: PropTypes.object,
	};

	module.exports = { BaseEditWrapper };
});

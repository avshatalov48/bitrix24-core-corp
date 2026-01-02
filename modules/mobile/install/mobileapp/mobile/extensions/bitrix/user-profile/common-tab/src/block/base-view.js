/**
 * @module user-profile/common-tab/src/block/base-view
 */
jn.define('user-profile/common-tab/src/block/base-view', (require, exports, module) => {
	const { Indent, Color, Component } = require('tokens');
	const { Text4 } = require('ui-system/typography/text');
	const { Card } = require('ui-system/layout/card');

	const ViewMode = {
		FULL_WIDTH: 'full',
		HALF_WIDTH: 'half',
	};

	const BaseViewStyles = {
		backgroundColor: Color.bgContentPrimary.toHex(),
		flexDirection: 'column',
		justifyContent: 'center',
	};

	const ViewModeStyles = {
		[ViewMode.FULL_WIDTH]: {
			...BaseViewStyles,
			marginBottom: Component.cardListGap.toNumber(),
		},
		[ViewMode.HALF_WIDTH]: {
			...BaseViewStyles,
			flexGrow: 1,
		},
	};

	/**
	 * @function BaseViewWrapper
	 * @param {Object} props
	 * @param {string} [props.title]
	 * @param {Object} [props.content]
	 * @param {string} [props.testId]
	 * @param {Object} [props.viewMode]
	 * @param {Function} [props.onClick]
	 * @param {Object} [props.style]
	 * @return BaseViewWrapper
	 */
	const BaseViewWrapper = (props) => {
		const {
			title,
			content,
			testId,
			viewMode = ViewMode.FULL_WIDTH,
			onClick,
			cardProps = {},
			style: customStyles = {},
			titleStyle: customTitleStyles = {},
		} = props;

		const viewModeStyles = ViewModeStyles[viewMode] ?? ViewModeStyles[ViewMode.FULL_WIDTH];

		const renderTitle = () => {
			if (!title)
			{
				return null;
			}

			return Text4({
				accent: true,
				text: title,
				color: Color.base1,
				style: {
					marginBottom: Indent.XL2.toNumber(),
					...customTitleStyles,
				},
			});
		};

		const renderContent = () => {
			return content;
		};

		return Card(
			{
				style: {
					...viewModeStyles,
					...customStyles,
				},
				onClick,
				testId,
				...cardProps,
			},
			renderTitle(),
			renderContent(),
		);
	};

	BaseViewWrapper.defaultProps = {
		title: null,
		content: null,
		testId: null,
		viewMode: ViewMode.FULL_WIDTH,
	};

	BaseViewWrapper.propTypes = {
		testId: PropTypes.string.isRequired,
		viewMode: PropTypes.oneOf([ViewMode.FULL_WIDTH, ViewMode.HALF_WIDTH]),
		title: PropTypes.string,
		content: PropTypes.object,
		onClick: PropTypes.func,
		style: PropTypes.object,
	};

	module.exports = {
		BaseViewWrapper,
		ViewMode,
	};
});

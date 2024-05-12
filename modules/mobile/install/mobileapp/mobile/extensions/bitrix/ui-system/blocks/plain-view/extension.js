/**
 * @module ui-system/blocks/plain-view
 */
jn.define('ui-system/blocks/plain-view', (require, exports, module) => {
	const { mergeImmutable } = require('utils/object');
	const { Indent, IndentTypes, Color } = require('tokens');

	/**
	 * @function PlainView
	 * @params {object} props
	 * @params {string} [props.text]
	 * @params {number} [props.fontSize]
	 * @params {object} [props.after]
	 * @params {object} [props.before]
	 * @params {function} [props.onClick]
	 * @params {string} [props.indent]
	 * @params {string} [props.uri]
	 *
	 * @return PlainView
	 */
	const PlainView = (props) => {
		const {
			text = '',
			color = Color.base2,
			fontSize = 16,
			after = null,
			before = null,
			indent = IndentTypes.XS,
			...restProps
		} = props;

		if (!text && !after && !before)
		{
			return null;
		}

		const isText = Boolean(text.trim());
		const marginHorizontal = Indent[indent] ?? 0;

		let marginLeft = 0;
		let marginRight = 0;

		if (after)
		{
			marginLeft = marginHorizontal;
		}

		if (before)
		{
			marginRight = marginHorizontal;
		}

		const mergedProps = mergeImmutable(
			{
				style: {
					flexDirection: 'row',
					flexShrink: 2,
				},
			},
			restProps,
		);

		return View(
			mergedProps,
			after,
			isText && Text({
				style: {
					color,
					fontSize,
					marginLeft,
					marginRight,
					flexShrink: 2,
				},
				numberOfLines: 1,
				ellipsize: 'end',
				text,
			}),
			before,
		);
	};

	PlainView.defaultProps = {
		text: '',
		after: null,
		before: null,
		fontSize: 16,
		color: Color.base1,
		indent: IndentTypes.XS,
	};

	PlainView.propTypes = {
		text: PropTypes.string,
		fontSize: PropTypes.number,
		after: PropTypes.object,
		before: PropTypes.object,
		indent: PropTypes.string,
	};

	module.exports = { PlainView };
});

/**
 * @module ui-system/layout/area
 */
jn.define('ui-system/layout/area', (require, exports, module) => {
	const { Component, Color } = require('tokens');
	const { mergeImmutable } = require('utils/object');
	const { AreaTop } = require('ui-system/layout/area/src/top');

	/**
	 * @function Area
	 * @param {object} props
	 * @param {text} [props.title]
	 * @param {boolean} [props.isFirst=false]
	 * @param {boolean} [props.divider=false]
	 * @param {object=} [props.excludePaddingSide={}]
	 * @param {boolean} [props.excludePaddingSide.bottom=false]
	 * @param {boolean} [props.excludePaddingSide.horizontal=false]
	 * @param {...*} children
	 * @return Area
	 */
	function Area(props = {}, ...children)
	{
		PropTypes.validate(Area.propTypes, props, 'Area');

		const {
			title,
			isFirst = false,
			divider = false,
			excludePaddingSide = {},
			...restProps
		} = props;

		const { bottom, horizontal } = excludePaddingSide;

		const style = {
			paddingLeft: horizontal ? 0 : Component.areaPaddingLr.toNumber(),
			paddingRight: horizontal ? 0 : Component.areaPaddingLr.toNumber(),
			paddingBottom: bottom ? 0 : Component.areaPaddingB.toNumber(),
		};

		let paddingTop = Component.areaPaddingT;
		if (title)
		{
			paddingTop = Component.areaPaddingTAt;
		}

		if (isFirst)
		{
			paddingTop = Component.areaPaddingTFirst;
		}

		if (divider)
		{
			style.borderBottomWidth = 1;
			style.borderBottomColor = Color.bgSeparatorPrimary.toHex();
		}

		style.paddingTop = paddingTop.toNumber();

		return View(
			mergeImmutable(restProps, { style }),
			title ? AreaTop({
				title,
				excludePaddingSide: {
					horizontal: !horizontal,
				},
			}) : null,
			...children,
		);
	}

	Area.defaultProps = {
		title: null,
		isFirst: false,
		divider: false,
		excludePaddingSide: {},
	};

	Area.propTypes = {
		title: PropTypes.string,
		isFirst: PropTypes.bool,
		divider: PropTypes.bool,
		excludePaddingSide: PropTypes.objectOf(PropTypes.bool),
	};

	module.exports = { Area, AreaTop };
});

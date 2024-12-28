/**
 * @module ui-system/layout/box
 */
jn.define('ui-system/layout/box', (require, exports, module) => {
	const { animate } = require('animation');
	const { Component, Color } = require('tokens');
	const { mergeImmutable, isEmpty } = require('utils/object');
	const { ScrollView } = require('layout/ui/scroll-view');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');

	/**
	 * @typedef {Object} BoxProps
	 * @property {Color | Color.bgPrimary | Color.bgSecondary} [backgroundColor]
	 * @property {boolean} [withScroll = false]
	 * @property {boolean} [scrollProps = {}]
	 * @property {boolean} [withPaddingLeft = false]
	 * @property {boolean} [paddingRight = false]
	 * @property {boolean} [withPaddingHorizontal = false]
	 * @property {DialogFooter} [footer]
	 *
	 * @function Box
	 * @param {BoxProps} props
	 * @param {Array<View>} children
	 */
	const Box = (props = {}, ...children) => {
		PropTypes.validate(Box.propTypes, props, 'Box');

		const {
			backgroundColor,
			withScroll = false,
			withPaddingLeft = false,
			withPaddingRight = false,
			withPaddingHorizontal = false,
			scrollProps = {},
			footer = null,
			...restProps
		} = props;

		const style = {
			paddingLeft: withPaddingLeft || withPaddingHorizontal ? Component.paddingLr.toNumber() : 0,
			paddingRight: withPaddingRight || withPaddingHorizontal ? Component.paddingLr.toNumber() : 0,
		};

		const safeArea = restProps.safeArea || {};
		if (withScroll)
		{
			safeArea.bottom = true;
		}

		if (backgroundColor && (backgroundColor.equal(Color.bgPrimary) || backgroundColor.equal(Color.bgSecondary)))
		{
			style.backgroundColor = backgroundColor.toHex();
		}
		else
		{
			style.backgroundColor = null;
		}

		let boxFooter = null;
		let stubFooter = null;
		const bottomSafeArea = safeArea?.bottom;

		if (footer)
		{
			let stubRef = null;
			let stubHeight = 0;

			const footerParams = {
				safeArea: bottomSafeArea,
				onLayoutFooterHeight: ({ height }) => {
					if (stubHeight !== height)
					{
						stubHeight = height;
						animate(stubRef, {
							duration: 50,
							height,
						});
					}
				},
			};

			if (style.backgroundColor)
			{
				footerParams.style = {
					backgroundColor: style.backgroundColor,
				};
			}

			boxFooter = footer(footerParams);

			stubFooter = View({
				ref: (ref) => {
					stubRef = ref;
				},
				style: {
					width: '100%',
					height: stubHeight,
				},
			});
		}

		const resizableByKeyboard = Boolean(restProps.resizableByKeyboard || !isEmpty(boxFooter?.props?.keyboardButton));

		const render = View(
			mergeImmutable(
				restProps,
				{ style },
			),
			...children,
			withScroll ? null : stubFooter,
			withScroll ? null : boxFooter,
		);

		if (withScroll)
		{
			return View(
				{
					safeArea,
					resizableByKeyboard,
				},
				ScrollView(
					mergeImmutable(scrollProps, {
						style: {
							height: '100%',
						},
					}),
					render,
					stubFooter,
				),
				boxFooter,
			);
		}

		return render;
	};

	Box.defaultProps = {
		withScroll: false,
		withPaddingLeft: false,
		withPaddingRight: false,
		withPaddingHorizontal: false,
	};

	Box.propTypes = {
		withScroll: PropTypes.bool,
		scrollProps: PropTypes.object,
		backgroundColor: PropTypes.object,
		withPaddingLeft: PropTypes.bool,
		withPaddingRight: PropTypes.bool,
		withPaddingHorizontal: PropTypes.bool,
		footer: PropTypes.func,
	};

	module.exports = { Box, BoxFooter };
});

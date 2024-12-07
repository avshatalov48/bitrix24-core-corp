/**
 * @module ui-system/blocks/chips/chip-status
 */
jn.define('ui-system/blocks/chips/chip-status', (require, exports, module) => {
	const { Indent, Component } = require('tokens');
	const { mergeImmutable } = require('utils/object');
	const { Ellipsize } = require('utils/enums/style');
	const { ChipStatusDesign } = require('ui-system/blocks/chips/chip-status/src/design-enum');
	const { ChipStatusMode } = require('ui-system/blocks/chips/chip-status/src/mode-enum');
	const { ChipStatusSize } = require('ui-system/blocks/chips/chip-status/src/size-enum');

	/**
	 * @function ChipStatus
	 * @params {object} props
	 * @params {string} [props.text]
	 * @params {object} [props.design]
	 * @params {object} [props.mode]
	 * @params {string} [props.ellipsize]
	 * @params {boolean} [props.compact]
	 * @return ChipStatus
	 */
	const ChipStatus = (props) => {
		PropTypes.validate(ChipStatus.propTypes, props, 'ChipStatus');

		const {
			text,
			testId,
			ellipsize,
			compact = false,
			design = ChipStatusDesign.PRIMARY,
			mode = ChipStatusMode.SOLID,
			...restProps
		} = props;

		const statusDesign = ChipStatusDesign.resolve(design, ChipStatusDesign.PRIMARY);
		const { color, ...chipStyle } = statusDesign.getStyle(mode);
		const size = compact ? ChipStatusSize.SMALL : ChipStatusSize.NORMAL;
		const Typography = size.getTypography();

		const viewProps = mergeImmutable({
			testId: testId && `${testId}_${statusDesign.getName()}`,
			style: {
				flexShrink: 1,
				alignItems: 'flex-start',
				justifyContent: 'flex-start',
			},
		}, restProps);

		return View(
			viewProps,
			View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'center',
						height: size.getHeight(),
						borderRadius: Component.elementAccentCorner.toNumber(),
						paddingHorizontal: Indent.L.toNumber(),
						...chipStyle,
					},
				},
				Typography({
					text,
					color,
					ellipsize: Ellipsize.resolve(ellipsize, Ellipsize.END).toString(),
					numberOfLines: 1,
				}),
			),
		);
	};

	ChipStatus.defaultProps = {
		compact: false,
	};

	ChipStatus.propTypes = {
		testId: PropTypes.string.isRequired,
		text: PropTypes.string.isRequired,
		compact: PropTypes.bool,
		design: PropTypes.object,
		mode: PropTypes.object,
		ellipsize: PropTypes.string,
	};

	module.exports = { ChipStatus, ChipStatusDesign, ChipStatusMode, Ellipsize };
});

/**
 * @module ui-system/popups/color-picker
 */
jn.define('ui-system/popups/color-picker', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { BottomSheet } = require('bottom-sheet');
	const { Box } = require('ui-system/layout/box');
	const { Area } = require('ui-system/layout/area');
	const { Button, ButtonSize, ButtonDesign } = require('ui-system/form/buttons');
	const { StringInput, InputSize, InputMode, InputDesign } = require('ui-system/form/inputs/string');

	const { ColorPickerPalette } = require('ui-system/popups/color-picker/palette-enum');

	const colorWrapperSize = Math.round(device.screen.width * 0.15);
	const colorSize = Math.round(colorWrapperSize * 0.8);

	/**
	 * @class ColorPicker
	 */
	class ColorPicker extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.ref = null;
			this.palette = this.getPalette();
			this.colors = this.palette.getValue();
			this.state = {
				currentColor: this.props.currentColor,
			};

			this.onChange = this.onChange.bind(this);
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				currentColor: props.currentColor,
			};
		}

		getPalette()
		{
			const { palette = ColorPickerPalette.BASE } = this.props;

			return ColorPickerPalette.resolve(
				palette,
				ColorPickerPalette.BASE,
			);
		}

		/**
		 * @public
		 * @param props
		 * @param {string} [props.testId]
		 * @param {string} [props.title]
		 * @param {string} [props.buttonText]
		 * @param {string} [props.inputLabel]
		 * @param {ColorPickerPalette} [props.palette]
		 * @param {function} [props.onChange]
		 * @param {string} [props.currentColor]
		 * @param {PageManager} [props.parentWidget]
		 */
		static show(props)
		{
			const component = (layoutWidget) => new this({ layoutWidget, ...props });
			const parentWidget = props.parentWidget ?? PageManager;

			void new BottomSheet({ component })
				.setParentWidget(parentWidget)
				.setBackgroundColor(Color.bgContentPrimary.toHex())
				.setNavigationBarColor(Color.bgContentPrimary.toHex())
				.disableContentSwipe()
				.disableHorizontalSwipe()
				.setMediumPositionPercent(70)
				.setTitleParams({
					text: props.title,
					type: 'dialog',
				})
				.open()
			;
		}

		render()
		{
			return Box(
				{
					backgroundColor: Color.bgContentPrimary,
					style: {
						flex: 1,
					},
					safeArea: {
						bottom: true,
					},
				},
				this.#renderContent(),
				this.#renderConfirmButton(),
			);
		}

		#renderContent()
		{
			return Area(
				{
					style: {
						flex: 1,
					},
					isFirst: true,
				},
				this.#renderColorList(),
				this.#renderColorDetail(),
			);
		}

		#renderColorList()
		{
			return View(
				{
					testId: `${this.props.testId}-color-list`,
					style: {
						flexWrap: 'wrap',
						flexDirection: 'row',
						justifyContent: 'center',
					},
				},
				...this.colors.map((color) => this.#renderColor(color)),
			);
		}

		#renderColor(color)
		{
			return View(
				{
					style: {
						backgroundColor: Color.bgContentPrimary.toHex(),
						width: colorWrapperSize,
						height: colorWrapperSize,
						justifyContent: 'center',
						alignItems: 'center',
						borderWidth: 2,
						borderRadius: Math.round(colorWrapperSize / 2),
						borderColor: this.state.currentColor === color
							? Color.base1.toHex()
							: Color.bgContentPrimary.toHex()
						,
					},
					onClick: () => {
						this.setState({
							currentColor: color,
						});
					},
				},
				View(
					{
						style: {
							width: colorSize,
							height: colorSize,
							borderRadius: Math.round(colorSize / 2),
							backgroundColor: color,
						},
					},
				),
			);
		}

		#renderColorDetail()
		{
			return View(
				{
					style: {
						marginTop: Indent.XL4.toNumber(),
					},
				},
				StringInput({
					testId: `${this.props.testId}-color-detail`,
					value: this.state.currentColor,
					label: this.props.inputLabel,
					size: InputSize.L,
					design: InputDesign.GREY,
					mode: InputMode.STROKE,
					readOnly: true,
					leftContent: this.#renderInputColor(),
				}),
			);
		}

		#renderInputColor()
		{
			return View(
				{
					style: {
						width: 22,
						height: 18,
						borderRadius: 2,
						marginRight: Indent.XS.toNumber(),
						backgroundColor: this.state.currentColor,
					},
				},
			);
		}

		#renderConfirmButton()
		{
			return Area(
				{
					isFirst: false,
				},
				Button({
					testId: `${this.props.testId}-confirm-button`,
					text: this.props.buttonText,
					size: ButtonSize.L,
					design: ButtonDesign.FILLED,
					stretched: true,
					onClick: this.onChange,
				}),
			);
		}

		onChange()
		{
			if (this.props.onChange)
			{
				this.props.onChange(this.state.currentColor);
			}

			this.props.layoutWidget.close();
		}
	}

	ColorPicker.defaultProps = {
		title: null,
		buttonText: null,
		inputLabel: null,
		palette: null,
		onChange: null,
		currentColor: null,
	};

	ColorPicker.propTypes = {
		testId: PropTypes.string.isRequired,
		parentWidget: PropTypes.object,
		title: PropTypes.string,
		buttonText: PropTypes.string,
		onChange: PropTypes.func,
		currentColor: PropTypes.string,
	};

	module.exports = { ColorPicker, ColorPickerPalette };
});

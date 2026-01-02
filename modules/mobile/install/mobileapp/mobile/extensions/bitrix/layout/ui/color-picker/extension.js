jn.define('layout/ui/color-picker', (require, exports, module) => {
	const { ColorPicker: ColorPickerPopup, ColorPickerPalette } = require('ui-system/popups/color-picker');
	const { Loc } = require('loc');
	const { Color, Indent } = require('tokens');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Text4 } = require('ui-system/typography/text');

	const ColorPalette = [
		'#2fc6f6',
		'#9dcf00',
		'#29d9d0',
		'#f7a700',
		'#ff668e',
		'#c2c6cb',
		'#68e2ea',
		'#a64bff',
		'#f1184e',
		'#98dad3',
		'#1969f1',
		'#a5e9ff',
		'#c6ff7d',
		'#ff6b1a',
		'#ffe600',
		'#5d5f74',
	];

	const ITEM_SIZE = 42;
	const COLOR_PALETTE_SIZE = 54;

	/**
	 * @class UI.ColorPicker
	 */
	class ColorPicker extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				currentColor: this.getCurrentColor(),
				colors: this.getColors(),
			};
		}

		get title()
		{
			return BX.prop.getString(this.props, 'title', null);
		}

		getCurrentColor()
		{
			const currentColor = BX.prop.getString(this.props, 'currentColor', null);

			if (currentColor)
			{
				return currentColor.toLowerCase();
			}

			return currentColor;
		}

		getColors()
		{
			if (this.isDefaultColor())
			{
				return [...this.getColorsFromProps(), ...ColorPalette];
			}

			const currentColor = this.getCurrentColor();

			if (currentColor)
			{
				return [this.getCurrentColor(), ...this.getColorsFromProps(), ...ColorPalette];
			}

			return [...this.getColorsFromProps(), ...ColorPalette];
		}

		isDefaultColor()
		{
			return [
				...ColorPalette,
				...ColorPickerPalette.SECOND.getValue(),
				...this.getColorsFromProps(),
			].includes(this.getCurrentColor());
		}

		getColorsFromProps()
		{
			if (this.props.colors && Array.isArray(this.props.colors))
			{
				return this.props.colors;
			}

			return [];
		}

		render()
		{
			return View(
				{
					style: {
						paddingTop: Indent.S.toNumber(),
						paddingBottom: Indent.XL3.toNumber(),
						borderColor: Color.bgSeparatorPrimary.toHex(),
						borderBottomWidth: 1,
					},
				},
				Text4({
					text: this.title || Loc.getMessage('COLOR_PICKER_TITLE'),
					style: {
						marginHorizontal: Indent.XL3.toNumber(),
						marginVertical: Indent.L.toNumber(),
					},
					color: Color.base4,
				}),
				View(
					{},
					ScrollView(
						{
							style: {
								minHeight: COLOR_PALETTE_SIZE,
							},
							horizontal: true,
							showsHorizontalScrollIndicator: false,
						},
						View(
							{
								style: {
									flexDirection: 'row',
									alignItems: 'center',
								},
							},
							...this.state.colors.map((color, index) => this.renderColorPalette(color, index)),
							this.renderMenuButton(),
						),
					),
				),
			);
		}

		renderColorPalette(color, index)
		{
			const isSelected = this.state.currentColor === color;

			return View(
				{
					testId: `ColorContainer-${color}-${isSelected}`,
					style: {
						height: COLOR_PALETTE_SIZE,
						width: COLOR_PALETTE_SIZE,
						alignItems: 'center',
						justifyContent: 'center',
						marginRight: Indent.XS.toNumber(),
						backgroundColor: Color.bgContentPrimary.toHex(),
						borderWidth: 2,
						borderColor: isSelected ? Color.base1.toHex() : Color.bgContentPrimary.toHex(),
						borderRadius: COLOR_PALETTE_SIZE / 2,
						marginLeft: index === 0 ? Indent.XL.toNumber() : 0,
					},
					onClick: () => {
						this.setState({
							currentColor: color,
						}, () => {
							this.onChangeColor();
						});
					},
				},
				View(
					{
						style: {
							width: ITEM_SIZE,
							height: ITEM_SIZE,
							borderRadius: ITEM_SIZE / 2,
							backgroundColor: color,
						},
					},
				),
			);
		}

		onChangeColor()
		{
			if (this.props.onChangeColor)
			{
				this.props.onChangeColor(this.state.currentColor);
			}
		}

		renderMenuButton()
		{
			return View(
				{
					style: {
						height: COLOR_PALETTE_SIZE,
						width: COLOR_PALETTE_SIZE,
						alignItems: 'center',
						justifyContent: 'center',
						backgroundColor: Color.bgContentPrimary.toHex(),
						borderColor: this.isMenuColor() ? this.state.currentColor : Color.bgContentPrimary.toHex(),
						borderWidth: 2,
						borderRadius: COLOR_PALETTE_SIZE / 2,
						marginRight: Indent.XL.toNumber(),
					},
					onClick: () => {
						ColorPickerPopup.show({
							palette: ColorPickerPalette.SECOND,
							testId: 'mobile-color-picker',
							parentWidget: this.props.layout,
							title: Loc.getMessage('COLOR_PICKER_POPUP_TITLE'),
							buttonText: Loc.getMessage('COLOR_PICKER_POPUP_BUTTON'),
							inputLabel: Loc.getMessage('COLOR_PICKER_POPUP_INPUT_TITLE'),
							currentColor: this.state.currentColor,
							onChange: (color) => {
								this.setState({
									currentColor: color,
								}, () => {
									this.onChangeColor();
								});
							},
						});
					},
				},
				View(
					{
						style: {
							width: ITEM_SIZE,
							height: ITEM_SIZE,
							justifyContent: 'center',
							alignItems: 'center',
						},
					},
					IconView({
						size: 32,
						icon: Icon.PALETTE,
						color: Color.base3,
					}),
				),
			);
		}

		isMenuColor()
		{
			return ColorPickerPalette.SECOND.getValue().includes(this.state.currentColor);
		}
	}

	module.exports = {
		ColorPicker,
	};
});

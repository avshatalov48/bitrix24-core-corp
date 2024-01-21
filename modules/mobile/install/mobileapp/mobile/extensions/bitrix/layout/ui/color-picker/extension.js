(() => {
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
	const require = (ext) => jn.require(ext);
	const AppTheme = require('apptheme');
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
				...UI.ColorMenu.Colors,
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
					style: styles.colorPickerContainer,
				},
				Text(
					{
						style: styles.colorPickerTitle,
						text: BX.message('COLOR_PICKER_TITLE'),
					},
				),
				View(
					{},
					ScrollView(
						{
							style: styles.colorPickerWrapper,
							horizontal: true,
							showsHorizontalScrollIndicator: false,
						},
						View(
							{
								style: styles.colorPickerList,
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
					style: styles.colorPaletteContainer(this.state.currentColor, color, index),
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
						style: styles.colorPalette(color),
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
					style: styles.menuButton(this.isMenuColor()),
					onClick: () => {
						UI.ColorMenu.open(
							{
								currentColor: this.state.currentColor,
								onChangeColor: (color) => {
									this.setState({
										currentColor: color,
									}, () => {
										this.onChangeColor();
									});
								},
							},
							this.props.layout,
						);
					},
				},
				View(
					{
						style: styles.menuButtonIconContainer,
					},
					Image(
						{
							style: styles.menuButtonIcon,
							svg: {
								content: svgImages.menuIcon,
							},
						},
					),
				),
			);
		}

		isMenuColor()
		{
			return UI.ColorMenu.Colors.includes(this.state.currentColor);
		}
	}

	const styles = {
		colorPickerContainer: {
			flexDirection: 'column',
			borderRadius: 12,
			backgroundColor: AppTheme.colors.bgContentPrimary,
			paddingTop: 10,
			paddingBottom: 16,
		},
		colorPickerTitle: {
			color: AppTheme.colors.base1,
			fontSize: 15,
			fontWeight: '500',
			marginBottom: 9,
			marginLeft: 20,
			marginRight: 20,
		},
		colorPickerWrapper: {
			height: 50,
		},
		colorPickerList: {
			flexDirection: 'row',
			alignItems: 'center',
		},
		colorPaletteContainer: (currentColor, color, index) => ({
			marginRight: 2,
			marginLeft: index === 0 ? 20 : 0,
			backgroundColor: AppTheme.colors.bgContentPrimary,
			borderWidth: 3,
			borderColor: currentColor === color ? AppTheme.colors.accentBrandBlue : AppTheme.colors.bgContentPrimary,
			borderRadius: 25,
			justifyContent: 'center',
			alignItems: 'center',
			width: 50,
			height: 50,
		}),
		colorPalette: (color) => ({
			width: 38,
			height: 38,
			borderRadius: 19,
			backgroundColor: color,
		}),
		menuButton: (isDefaultColor) => ({
			marginRight: 20,
			backgroundColor: AppTheme.colors.bgContentPrimary,
			borderWidth: 3,
			borderColor: isDefaultColor ? AppTheme.colors.accentBrandBlue : AppTheme.colors.bgContentPrimary,
			borderRadius: 25,
			justifyContent: 'center',
			alignItems: 'center',
			width: 50,
			height: 50,
		}),
		menuButtonIconContainer: {
			width: 30,
			height: 30,
			borderRadius: 15,
			backgroundColor: AppTheme.colors.accentBrandGreen,
			justifyContent: 'center',
			alignItems: 'center',
		},
		menuButtonIcon: {
			width: 20,
			height: 5,
		},
	};

	const svgImages = {
		menuIcon: '<svg width="20" height="5" viewBox="0 0 20 5" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2.5 5C3.88071 5 5 3.88071 5 2.5C5 1.11929 3.88071 0 2.5 0C1.11929 0 0 1.11929 0 2.5C0 3.88071 1.11929 5 2.5 5Z" fill="white"/><path d="M10 5C11.3807 5 12.5 3.88071 12.5 2.5C12.5 1.11929 11.3807 0 10 0C8.61929 0 7.5 1.11929 7.5 2.5C7.5 3.88071 8.61929 5 10 5Z" fill="white"/><path d="M20 2.5C20 3.88071 18.8807 5 17.5 5C16.1193 5 15 3.88071 15 2.5C15 1.11929 16.1193 0 17.5 0C18.8807 0 20 1.11929 20 2.5Z" fill="white"/></svg>',
	};

	this.UI = this.UI || {};
	this.UI.ColorPicker = ColorPicker;
})();

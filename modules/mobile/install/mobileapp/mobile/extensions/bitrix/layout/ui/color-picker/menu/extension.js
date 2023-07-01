(() => {

	/**
	 * @class UI.ColorMenu.Colors
	 */
	const Colors = [
		'#56fb7d',
		'#c6ebf0',
		'#e6f1a3',
		'#e73af7',
		'#7bdcdd',
		'#f4c19f',
		'#f4c991',
		'#e7925a',
		'#5fb353',
		'#336fb9',
		'#558ac8',
		'#9d86bb',
		'#59105d',
		'#e79fc0',
		'#c2bbe9',
		'#c0c5cc',
		'#969da8',
		'#3a6be8',
		'#df7351',
		'#a9a133',
		'#59b8b3',
		'#aed2a0',
		'#7bfa4c',
		'#f0e28a',
		'#aa7357',
		'#dec7bd',
		'#dbdde0',
		'#555555',
		'#84a4d5',
		'#000000',
	];

	/**
	 * @class UI.ColorMenu
	 */
	class ColorMenu extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				currentColor: this.props.currentColor,
			};
		}

		componentDidMount()
		{
			if (this.props.layoutWidget)
			{
				this.props.layoutWidget.setTitle({ text: BX.message('COLOR_MENU_NAVIGATION_TITLE') });

				this.props.layoutWidget.setRightButtons([
					{
						name: BX.message('COLOR_MENU_NAVIGATION_BUTTON'),
						type: 'text',
						color: '#2066b0',
						callback: () => {
							this.onChangeColor().then(() => {
								this.props.layoutWidget.close();
							});
						},
					},
				]);
			}
		}

		onChangeColor()
		{
			return new Promise((resolve, reject) => {
				if (this.props.onChangeColor)
				{
					this.props.onChangeColor(this.state.currentColor);
				}
				resolve();
			});
		}

		render()
		{
			return View(
				{
					style: styles.container,
				},
				this.renderColorList(),
				this.renderColorDetail(),
			);
		}

		renderColorList()
		{
			return View(
				{
					style: styles.colorList,
				},
				...Colors.map(color => this.renderColorPalette(color)),
			);
		}

		renderColorDetail()
		{
			return View(
				{
					style: styles.colorDetailWrapper,
				},
				Text({
					text: BX.message('COLOR_MENU_DETAIL_TITLE'),
					style: styles.colorDetailTitle,
				}),
				View(
					{
						style: styles.colorDetailContainer,
					},
					View(
						{
							style: styles.colorDetailPalette(this.state.currentColor),
						},
					),
					Text({
						style: styles.colorDetailGrid,
						text: '#',
					}),
					Text({
						style: styles.colorDetailText,
						text: this.state.currentColor.replace('#', ''),
					}),
				),
			);
		}

		renderColorPalette(color)
		{
			return View(
				{
					style: styles.colorPaletteWrapper(this.state.currentColor, color),
					onClick: () => {
						this.setState({
							currentColor: color,
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
	}

	const styles = {
		container: {
			paddingTop: 14,
			paddingBottom: 18,
			borderRadius: 12,
			backgroundColor: '#ffffff',
		},
		colorList: {
			marginBottom: 24,
			flexWrap: 'wrap',
			flexDirection: 'row',
			justifyContent: 'center',
		},
		colorPaletteWrapper: (currentColor, color) => ({
			marginRight: 4,
			backgroundColor: '#ffffff',
			borderWidth: 3,
			borderColor: currentColor === color ? '#2fc6f6' : '#ffffff',
			borderRadius: 27,
			justifyContent: 'center',
			alignItems: 'center',
			width: 54,
			height: 54,
		}),
		colorPalette: (color) => ({
			width: 42,
			height: 42,
			borderRadius: 21,
			backgroundColor: color,
		}),
		colorDetailWrapper: {
			paddingLeft: 18,
			paddingRight: 18,
		},
		colorDetailTitle: {
			color: '#525c69',
			fontSize: 13,
			marginBottom: 6,
		},
		colorDetailContainer: {
			backgroundColor: '#eeeef0',
			borderRadius: 4,
			padding: 6,
			flexDirection: 'row',
		},
		colorDetailPalette: (currentColor) => ({
			borderRadius: 2,
			backgroundColor: currentColor,
			width: 38,
			height: 25,
			marginRight: 9,
		}),
		colorDetailGrid: {
			color: '#3333334d',
			fontSize: 18,
			marginRight: 6,
		},
		colorDetailText: {
			color: '#333333',
			fontSize: 18,
		},
	};

	this.UI = this.UI || {};
	this.UI.ColorMenu = ColorMenu;
	this.UI.ColorMenu.Colors = Colors;
})();
/**
 * @module settings-v2/ui/items/src/theme-switch
 */
jn.define('settings-v2/ui/items/src/theme-switch', (require, exports, module) => {
	const { createTestIdGenerator } = require('utils/test');
	const { Card, CardDesign, BadgeStatusMode } = require('ui-system/layout/card');
	const { CardList } = require('ui-system/layout/card-list');
	const { Text3 } = require('ui-system/typography/text');
	const { Color, Indent } = require('tokens');
	const { Loc } = require('loc');
	const { ASSET_PATH, ThemeType } = require('settings-v2/const');

	const THEME_ASSET_PATH = `${ASSET_PATH}theme/`;

	const ThemeTypeLoc = {
		[ThemeType.LIGHT]: Loc.getMessage('SETTINGS_V2_STRUCTURE_UI_ITEMS_THEME_LIGHT'),
		[ThemeType.DARK]: Loc.getMessage('SETTINGS_V2_STRUCTURE_UI_ITEMS_THEME_DARK'),
		[ThemeType.SYSTEM]: Loc.getMessage('SETTINGS_V2_STRUCTURE_UI_ITEMS_THEME_SYSTEM'),
	};

	class ThemeSwitchItem extends LayoutComponent
	{
		/**
		 * @param {ItemProps} props
		 */
		constructor(props)
		{
			super(props);

			this.getTestId = createTestIdGenerator({
				testId: props.testId || 'settings-theme-item',
				context: this,
			});

			this.state.selectedTheme = props.value;
		}

		render()
		{
			return CardList(
				{
					horizontal: true,
					withScroll: false,
					style: {
						justifyContent: 'center',
						padding: Indent.XL3.toNumber(),
					},
				},
				this.renderThemeCard(ThemeType.LIGHT),
				this.renderThemeCard(ThemeType.DARK),
				this.renderThemeCard(ThemeType.SYSTEM),
			);
		}

		renderThemeCard(themeType)
		{
			const selected = this.state.selectedTheme === themeType;

			return Card(
				{
					excludePaddingSide: { all: true },
					onClick: () => this.onChange(themeType),
				},
				Card(
					{
						design: CardDesign.PRIMARY,
						accent: selected,
						border: true,
						badgeMode: selected ? BadgeStatusMode.SUCCESS : null,
					},
					this.renderImage(themeType),
				),

				Text3({
					text: ThemeTypeLoc[themeType],
					color: Color.base1,
					style: {
						alignSelf: 'center',
						marginTop: Indent.S.toNumber(),
					},
				}),
			);
		}

		renderImage(themeType)
		{
			return Image(
				{
					resizeMode: 'contain',
					style: {
						width: 29,
						height: 54,
						marginHorizontal: Indent.XL4.toNumber(),
					},
					svg: {
						uri: `${THEME_ASSET_PATH}${themeType}.svg`,
					},
				},
			);
		}

		onChange = (themeType) => {
			const { id, controller, onChange } = this.props;

			this.setState({
				selectedTheme: themeType,
			}, () => {
				if (onChange)
				{
					onChange(id, controller, themeType);
				}
			});
		};
	}

	module.exports = {
		ThemeSwitchItem,
	};
});

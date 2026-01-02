/**
 * @module settings-v2/ui/items/src/style-switch
 */
jn.define('settings-v2/ui/items/src/style-switch', (require, exports, module) => {
	const { createTestIdGenerator } = require('utils/test');
	const { Card, CardDesign, BadgeStatusMode } = require('ui-system/layout/card');
	const { CardList } = require('ui-system/layout/card-list');
	const { Indent } = require('tokens');
	const { ASSET_PATH, StyleType } = require('settings-v2/const');
	const AppTheme = require('apptheme');

	const STYLE_ASSET_PATH = `${ASSET_PATH}style/`;

	class StyleSwitchItem extends LayoutComponent
	{
		/**
		 * @param {ItemProps} props
		 */
		constructor(props)
		{
			super(props);

			this.getTestId = createTestIdGenerator({
				prefix: 'settings-style-item',
				context: this,
			});

			this.state.selectedStyle = props.value;
		}

		render()
		{
			return CardList(
				{
					horizontal: false,
					withScroll: false,
					style: {
						justifyContent: 'center',
						paddingVertical: Indent.M.toNumber(),
					},
				},
				this.renderStyleCard(StyleType.DEFAULT),
				this.renderStyleCard(StyleType.ZEFIR),
			);
		}

		renderStyleCard(styleType)
		{
			const selected = this.state.selectedStyle === styleType;

			return Card(
				{
					excludePaddingSide: { all: true },
					onClick: () => this.onChange(styleType),
				},
				Card(
					{
						design: CardDesign.PRIMARY,
						accent: selected,
						border: true,
						badgeMode: selected ? BadgeStatusMode.SUCCESS : null,
					},
					this.renderImage(styleType),
				),
			);
		}

		renderImage(styleType)
		{
			return Image(
				{
					resizeMode: 'stretch',
					uri: `${STYLE_ASSET_PATH}${AppTheme.id}/${styleType}.png`,
					style: {
						height: 70,
						width: '100%',
					},
				},
			);
		}

		onChange = (styleType) => {
			const { id, controller, onChange } = this.props;

			this.setState({
				selectedStyle: styleType,
			}, () => {
				if (onChange)
				{
					onChange(id, controller, styleType);
				}
			});
		};
	}

	module.exports = {
		StyleSwitchItem,
	};
});

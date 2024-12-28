/**
 * @module layout/ui/setting-selector-list
 */
jn.define('layout/ui/setting-selector-list', (require, exports, module) => {
	const { Type } = require('type');
	const { Color, Corner, Indent } = require('tokens');
	const {
		SettingSelectorListItem,
		SettingSelectorListItemDesign,
		IconOrSwitcherPlacement,
	} = require('layout/ui/setting-selector-list/src/item');

	class SettingSelectorList extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.#initializeState(props);
		}

		componentWillReceiveProps(props)
		{
			this.#initializeState(props);
		}

		get testId()
		{
			return this.props.testId ?? '';
		}

		#initializeState = (props) => {
			this.state = {
				items: props.items,
			};
		};

		render()
		{
			const { style } = this.props;

			return View(
				{
					testId: this.testId,
					style: {
						...this.#getDefaultStyle(),
						...style,
					},
				},
				...this.#getRenderedItems(),
			);
		}

		#getRenderedItems = () => {
			const { items } = this.state;
			const { onItemClick, onCheckedChange, itemRef } = this.props;
			if (!Type.isArrayFilled(items))
			{
				return [];
			}

			return items.map((item, index) => SettingSelectorListItem({
				testId: `${this.testId}-item`,
				item,
				onItemClick,
				onCheckedChange,
				itemRef,
				isLast: index === items.length - 1,
			}));
		};

		#getDefaultStyle = () => {
			return {
				width: '100%',
				borderRadius: Corner.L.toNumber(),
				borderColor: Color.bgSeparatorPrimary.toHex(),
				paddingLeft: Indent.XL3.toNumber(),
				borderWidth: 1,
			};
		};
	}

	module.exports = {
		SettingSelectorList: (props) => new SettingSelectorList(props),
		SettingSelectorListItemDesign,
		IconOrSwitcherPlacement,
	};
});

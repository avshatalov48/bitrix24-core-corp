/**
 * @module layout/ui/setting-selector-list/src/item
 */
jn.define('layout/ui/setting-selector-list/src/item', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { Text3, Text5 } = require('ui-system/typography/text');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { PureComponent } = require('layout/pure-component');
	const { Switcher, SwitcherSize } = require('ui-system/blocks/switcher');

	const SettingSelectorListItemDesign = {
		CHOOSER: 'chooser',
		OPENER: 'opener',
		TOGGLE: 'toggle',
	};

	const IconOrSwitcherPlacement = {
		TITLE: 'title',
		ROOT: 'root',
	};

	class SettingSelectorListItem extends PureComponent
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
			return `${this.props.testId}-${this.item.id}`;
		}

		get item()
		{
			return this.props.item;
		}

		get iconOrSwitcherPlacement()
		{
			return this.item.iconOrSwitcherPlacement ?? IconOrSwitcherPlacement.ROOT;
		}

		#initializeState = (props) => {
			if (this.#toShowSwitcher())
			{
				this.state = {
					checked: props.item?.checked ?? false,
				};
			}
		};

		render()
		{
			const { style } = this.item;

			return View(
				{
					ref: this.#bindRef,
					style: {
						...this.#getDefaultStyle(),
						...style,
					},
					onClick: this.#onItemClick,
				},
				this.#renderContent(),
				this.#isIconOrSwitcherPlacementRoot() && this.#renderRootIconOrSwitcher(),
			);
		}

		#renderContent = () => {
			const { title, subtitle } = this.item;

			return View(
				{
					style: {
						flex: 1,
					},
				},
				title && this.#renderTitle(),
				subtitle && this.#renderSubTitle(),
			);
		};

		#renderRootIconOrSwitcher = () => {
			return View(
				{},
				this.#renderIconOrSwitcher(),
			);
		};

		#renderIconOrSwitcher()
		{
			switch (this.item.design)
			{
				case SettingSelectorListItemDesign.TOGGLE:
					return Switcher({
						testId: `${this.testId}-switcher`,
						size: SwitcherSize.L,
						checked: this.state.checked,
						onClick: this.#onSwitcherClick,
					});
				case SettingSelectorListItemDesign.OPENER:
				case SettingSelectorListItemDesign.CHOOSER:
					return IconView({
						icon: this.#getIcon(),
						color: this.#getIconColor(),
						size: 24,
					});
				default:
					return null;
			}
		}

		#bindRef = (ref) => {
			this.props.itemRef?.(ref, this.item);
		};

		#onItemClick = () => {
			this.props.onItemClick?.(this.item);
		};

		#renderTitle = () => {
			const { title } = this.item;

			return View(
				{
					testId: `${this.testId}-title`,
					style: {
						width: '100%',
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'flex-start',
					},
				},
				Text3({
					testId: `${this.testId}-title-text`,
					text: title,
					color: Color.base1,
					style: {
						flex: 1,
						marginRight: Indent.XL4.toNumber(),
						marginVertical: Indent.XS2.toNumber(),
					},
				}),
				this.#isIconOrSwitcherPlacementTitle() && this.#renderIconOrSwitcher(),
			);
		};

		#isIconOrSwitcherPlacementTitle = () => {
			return this.iconOrSwitcherPlacement === IconOrSwitcherPlacement.TITLE;
		};

		#isIconOrSwitcherPlacementRoot = () => {
			return this.iconOrSwitcherPlacement === IconOrSwitcherPlacement.ROOT;
		};

		#onSwitcherClick = (value) => {
			this.setState({
				checked: value,
			}, () => {
				this.props.onCheckedChange?.(this.item.id, value);
			});
		};

		#toShowSwitcher = () => {
			return this.item.design === SettingSelectorListItemDesign.TOGGLE;
		};

		#getIconColor = () => {
			return this.item.iconColor ?? Color.base4;
		};

		#getIcon = () => {
			switch (this.item.design)
			{
				case SettingSelectorListItemDesign.OPENER:
					return Icon.CHEVRON_TO_THE_RIGHT;
				case SettingSelectorListItemDesign.CHOOSER:
					return Icon.CHEVRON_DOWN;
				default:
					return null;
			}
		};

		#renderSubTitle = () => {
			const { subtitle, testId } = this.item;

			return Text5({
				testId: `${testId}-subtitle`,
				text: subtitle,
				color: Color.base3,
				style: {
					marginVertical: Indent.XS.toNumber(),
				},
			});
		};

		#getDefaultStyle = () => {
			return {
				width: '100%',
				borderBottomWidth: this.props.isLast ? 0 : 1,
				borderBottomColor: Color.bgSeparatorSecondary.toHex(),
				paddingRight: Indent.XL3.toNumber(),
				paddingVertical: Indent.XL.toNumber(),
				alignItems: 'center',
				flexDirection: 'row',
			};
		};
	}

	module.exports = {
		SettingSelectorListItem: (props) => new SettingSelectorListItem(props),
		SettingSelectorListItemDesign,
		IconOrSwitcherPlacement,
	};
});

/**
 * @module calendar/event-edit-form/selector
 */
jn.define('calendar/event-edit-form/selector', (require, exports, module) => {
	const { UIScrollView } = require('layout/ui/scroll-view');
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Box } = require('ui-system/layout/box');
	const { Area } = require('ui-system/layout/area');
	const { Color, Indent, Component } = require('tokens');
	const { BottomSheet } = require('bottom-sheet');
	const { ChipInnerTab } = require('ui-system/blocks/chips/chip-inner-tab');
	const { Icon } = require('ui-system/blocks/icon');

	const { SelectorItem } = require('calendar/event-edit-form/selector/item');

	const CATEGORY_SCROLL_HEIGHT = 50;

	class Selector extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				categoryId: Type.isArrayFilled(this.categories) ? this.selectedCategoryId : null,
			};

			this.layout = props.layout;
			this.initLayout();
		}

		get items()
		{
			if (this.state.categoryId === null)
			{
				return this.props.items;
			}

			return this.props.items.filter((item) => item.categoryId === this.state.categoryId);
		}

		get categories()
		{
			return this.props.categories;
		}

		get selectedCategoryId()
		{
			if (this.props.selectedCategoryId !== null)
			{
				return this.props.selectedCategoryId;
			}

			return this.categories[0].id;
		}

		initLayout()
		{
			this.layout.setListener((eventName) => {
				if ((eventName === 'onViewHidden' || eventName === 'onViewRemoved') && this.props.onClose)
				{
					this.props.onClose();
				}
			});
		}

		render()
		{
			return Box(
				{
					backgroundColor: Color.bgSecondary,
					style: {
						flex: 1,
					},
					safeArea: {
						bottom: true,
					},
				},
				Type.isArrayFilled(this.categories) && this.renderCategories(),
				this.renderList(),
			);
		}

		renderCategories()
		{
			const categoryLength = this.categories.length;

			return View(
				{
					style: {
						paddingTop: Indent.S.toNumber(),
						paddingHorizontal: Component.paddingLr.toNumber(),
					},
				},
				ScrollView(
					{
						showsHorizontalScrollIndicator: false,
						horizontal: true,
						style: { height: CATEGORY_SCROLL_HEIGHT },
					},
					View(
						{
							style: {
								flexDirection: 'row',
								alignItems: 'center',
							},
						},
						...this.categories.map(
							(category, index) => this.renderCategory(
								category,
								index === categoryLength - 1,
							),
						),
					),
				),
			);
		}

		renderCategory(category, isLast)
		{
			return ChipInnerTab({
				testId: `calendar-event-edit-form-selector-category-${category.id}`,
				text: category.name,
				selected: this.state.categoryId === category.id,
				onClick: () => this.onCategoryClick(category.id),
				style: {
					marginRight: isLast ? 0 : Indent.M.toNumber(),
				},
			});
		}

		renderList()
		{
			return Area(
				{
					style: {
						flex: 1,
					},
				},
				UIScrollView(
					{
						showsVerticalScrollIndicator: false,
						style: {
							flex: 1,
						},
					},
					...this.items.map((item) => this.renderItem(item)),
				),
			);
		}

		renderItem(item)
		{
			return View(
				{
					clickable: true,
					onClick: () => this.onItemClick(item.id),
					testId: `calendar-event-edit-form-selector-item-${item.id}`,
				},
				SelectorItem({
					item,
					icon: this.props.selectorIcon,
					isSelected: this.isItemSelected(item.id),
					isReserved: this.isItemReserved(item.id),
				}),
			);
		}

		isItemSelected(itemId)
		{
			return Number(itemId) === Number(this.props.selectedId);
		}

		isItemReserved(itemId)
		{
			return this.props.reservedInfo?.[itemId] === true;
		}

		onCategoryClick(categoryId)
		{
			this.setState({ categoryId });
		}

		onItemClick(sectionId)
		{
			if (this.props.onItemClick)
			{
				this.props.onItemClick(sectionId);
			}

			this.layout.close();
		}

		/**
		 * @param items {array}
		 * @param categories {array}
		 * @param reservedInfo {array}
		 * @param selectedId {number|null}
		 * @param selectedCategoryId {number|null}
		 * @param selectorTitle {string}
		 * @param selectorIcon {Icon}
		 * @param onItemClick {function}
		 * @param onClose {function}
		 * @param parentLayout {PageManager}
		 */
		static open({
			items,
			categories = [],
			reservedInfo = [],
			selectedId,
			selectedCategoryId,
			selectorTitle = Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_SECTION_SELECTOR_TITLE'),
			selectorIcon,
			onItemClick,
			onClose,
			parentLayout = PageManager,
		})
		{
			const component = (layout) => new Selector({
				layout,
				items,
				categories,
				reservedInfo,
				selectedId,
				selectedCategoryId,
				selectorIcon,
				onItemClick,
				onClose,
			});

			void new BottomSheet({ component })
				.setParentWidget(parentLayout)
				.setMediumPositionPercent(60)
				.setBackgroundColor(Color.bgSecondary.toHex())
				.setNavigationBarColor(Color.bgSecondary.toHex())
				.disableSwipe()
				.setTitleParams({
					text: selectorTitle,
					type: 'wizard',
				})
				.open()
			;
		}

		/**
		 * @param items {array}
		 * @param categories {array}
		 * @param reservedInfo {array}
		 * @param selectedId {number|null}
		 * @param selectedCategoryId {number|null}
		 * @param selectorTitle {string}
		 * @param selectorIcon {Icon}
		 * @param onItemClick {function}
		 * @param onClose {function}
		 * @param parentLayout {PageManager}
		 */
		static openInContext({
			items,
			categories = [],
			reservedInfo = [],
			selectedId,
			selectedCategoryId,
			selectorTitle = Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_SECTION_SELECTOR_TITLE'),
			selectorIcon,
			onItemClick,
			onClose,
			parentLayout = PageManager,
		})
		{
			void parentLayout.openWidget('layout', {
				titleParams: {
					text: selectorTitle,
					type: 'wizard',
				},
				onReady: (layout) => {
					layout.setRightButtons([
						{
							type: Icon.CROSS.getIconName(),
							callback: () => layout.close(),
						},
					]);

					const component = new Selector({
						layout,
						items,
						categories,
						reservedInfo,
						selectedId,
						selectedCategoryId,
						selectorIcon,
						onItemClick,
						onClose,
					});

					layout.showComponent(component);
				},
			});
		}
	}

	module.exports = { Selector };
});

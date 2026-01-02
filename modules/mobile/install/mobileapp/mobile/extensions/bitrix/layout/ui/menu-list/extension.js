/**
 * @module layout/ui/menu-list
 */
jn.define('layout/ui/menu-list', (require, exports, module) => {
	const { Color } = require('tokens');
	const { EntityCell, EntityCellMode } = require('ui-system/blocks/entity-cell');
	const { withPressed } = require('utils/color');
	const { createTestIdGenerator } = require('utils/test');
	const { Area } = require('ui-system/layout/area');

	/**
	 * @class MenuList
	 * @property {Array} structure
	 * @property {Function} onItemClick
	 * @property {bool} highlightOnPress
	 * @property {string} testId
	 */
	class MenuList extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.getTestId = createTestIdGenerator({
				testId: props.testId || 'sectioned-list',
				context: this,
			});
		}

		render()
		{
			return View(
				{},
				...this.renderSections(),
			);
		}

		renderSections()
		{
			const { structure } = this.props;
			const preparedStructure = structure.filter((item) => !item.hidden);

			return preparedStructure.map((section, index) => {
				if (section.hidden)
				{
					return null;
				}

				const showDivider = this.isNotLastSection(index, preparedStructure);

				return View(
					{
						testId: this.getTestId(`section-${section.id}`),
					},
					this.renderSection(section, showDivider),
				);
			});
		}

		isNotLastSection(index, items)
		{
			return index !== (items.length - 1);
		}

		renderSection(section, showDivider)
		{
			const { title, items } = section;

			return Area(
				{
					excludePaddingSide: {
						bottom: true,
						horizontal: true,
					},
					title,
					divider: showDivider,
				},
				...this.renderItems(items),
			);
		}

		/**
		 *
		 * @param {array} items
		* @return {array}
		 */
		renderItems(items)
		{
			return items.map((item, index) => {
				return this.renderItem(item, index);
			});
		}

		renderItem(item, index)
		{
			const { highlightOnPress } = this.props;

			return EntityCell({
				mode: EntityCellMode.GROUP,
				icon: item.imageName,
				title: item.title,
				badge: item.counterValue,
				nextLevel: true,
				devider: false,
				testId: this.getTestId(`item_${index}`),
				onClick: this.onItemClick,
				entityData: item,
				style: {
					backgroundColor: highlightOnPress && withPressed(Color.bgContentPrimary.toHex()),
				},
			});
		}

		onItemClick = (item) => {
			const { onItemClick } = this.props;

			onItemClick?.(item);
		};
	}

	module.exports = {
		MenuList,
	};
});

/**
 * @module more-menu/ui/list
 */
jn.define('more-menu/ui/list', (require, exports, module) => {
	const { Indent, Color } = require('tokens');
	const { MenuList } = require('layout/ui/menu-list');
	const { Text4 } = require('ui-system/typography/text');
	const { ListItem } = require('more-menu/ui/list/item');
	const { withPressed } = require('utils/color');
	const { PropTypes } = require('utils/validation');

	/**
	 * @typedef {Object} MenuSection
	 * @property {string} id
	 * @property {string} code
	 * @property {string} title
	 * @property {number} sort
	 * @property {boolean} hidden
	 * @property {Array<MenuItem>} items
	 */

	/**
	 * @typedef {Object} MenuItem
	 * @property {string} id
	 * @property {string} imageName
	 * @property {string} path
	 * @property {string} title
	 * @property {number} sort
	 * @property {MenuItemParams} params
	 */

	/**
	 * @typedef {Object} MenuItemParams
	 * @property {string} counter
	 * @property {string} url
	 * @property {boolean} useSearchBar
	 * @property {boolean} cache
	 * @property {Object|boolean} backdrop
	 * @property {string} onclick
	 *
	 */
	/**
	 * @class List
	 */
	class List extends MenuList
	{
		renderSection(section, showDivider)
		{
			const { title, items, id } = section;

			return View(
				{
					style: {
						paddingTop: Indent.XS.toNumber(),
					},
				},
				title && Text4({
					testId: this.getTestId(`section-${id}-title`),
					text: title,
					color: Color.base4,
					style: {
						marginHorizontal: Indent.S.toNumber(),
						marginVertical: Indent.L.toNumber(),
					},
					numberOfLines: 1,
					ellipsize: 'end',
				}),
				...this.renderItems(items),
				showDivider && View({
					testId: this.getTestId(`section-${id}-divider`),
					style: {
						marginVertical: Indent.L.toNumber(),
						height: 1,
						backgroundColor: Color.bgContentTertiary.toHex(),
					},
				}),
			);
		}

		renderItem(item, index)
		{
			const { highlightOnPress = true } = this.props;

			return new ListItem({
				icon: item.imageName,
				title: item.title,
				badge: item.counterValue,
				testId: this.getTestId(`item-${item?.id}`),
				onClick: this.onItemClick,
				itemData: item,
				style: {
					backgroundColor: highlightOnPress && withPressed(Color.bgContentPrimary.toHex()),
				},
			});
		}
	}

	List.propTypes = {
		menuList: PropTypes.arrayOf(PropTypes.shape({
			id: PropTypes.string,
			code: PropTypes.string,
			title: PropTypes.string,
			sort: PropTypes.number,
			hidden: PropTypes.bool,
			items: PropTypes.arrayOf(PropTypes.shape({
				id: PropTypes.string,
				imageName: PropTypes.string,
				path: PropTypes.string,
				title: PropTypes.string,
				sort: PropTypes.number,
				params: PropTypes.oneOfType([
					PropTypes.shape({
						counter: PropTypes.string,
						url: PropTypes.string,
						useSearchBar: PropTypes.bool,
						cache: PropTypes.bool,
						backdrop: PropTypes.oneOfType([PropTypes.object, PropTypes.bool]),
						onclick: PropTypes.string,
					}),
					PropTypes.object,
				]),
			})),
		})),
		counters: PropTypes.object,
		onBeforeItemClick: PropTypes.func,
	};

	module.exports = { List };
});

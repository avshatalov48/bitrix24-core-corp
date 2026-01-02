/**
 * @module layout/ui/jn-list/src/item
 */
jn.define('layout/ui/jn-list/src/item', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { UIMenu } = require('layout/ui/menu');
	const { BBCodeText } = require('ui-system/typography/bbcodetext');
	const { SafeImage } = require('layout/ui/safe-image');
	const { BadgeCounter, BadgeCounterSize } = require('ui-system/blocks/badges/counter');
	const { withCurrentDomain } = require('utils/url');

	/**
	 * @typedef {Object} JnListItemProps
	 * @property {string} testId
	 * @property {string} searchMatchColor
	 * @property {string} searchString
	 * @property {string} listItemImagePlaceholder
	 * @property {string} badgeText
	 * @property {string} badgeDesign
	 * @property {boolean} isContextMenuButtonVisible
	 * @property {boolean} isBadgeVisible
	 * @property {function} onListItemClick
	 * @property {function} getListItemContextMenuItems

	 * @class JnListItem
	 */
	class JnListItem extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.state = {
				isSelected: false,
			};
		}

		getTestId(suffix)
		{
			const { testId } = this.props;

			return [testId, suffix].join('-').trim();
		}

		render()
		{
			const {
				item,
				onListItemClick,
				searchMatchColor = Color.accentMainPrimary,
				searchString,
				isContextMenuButtonVisible,
				isBadgeVisible,
			} = this.props;

			const preparedName = searchString === ''
				? item.name
				: item.name.replaceAll(
					new RegExp(searchString, 'gi'),
					(match) => `[COLOR=${searchMatchColor.toHex()}]${match}[/COLOR]`,
				);

			return View(
				{
					style: {
						height: 70,
						flexDirection: 'row',
						paddingHorizontal: Indent.XL2.toNumber(),
						alignItems: 'center',
						backgroundColor: Color.bgContentPrimary.toHex(),
					},
					onClick: () => {
						onListItemClick?.(item);
					},
					onLongClick: () => {
						if (isContextMenuButtonVisible)
						{
							this.onItemContextMenuButtonClick(item);
						}
					},
				},
				this.renderListItemImage(item),
				View(
					{
						style: {
							flex: 1,
							height: '100%',
							paddingVertical: 15,
							borderBottomWidth: 1,
							borderBottomColor: Color.bgSeparatorSecondary.toHex(),
							flexDirection: 'row',
							alignItems: 'center',
							marginLeft: Indent.XL.toNumber(),
						},
					},
					BBCodeText({
						size: 2,
						value: preparedName,
						showBBCode: true,
						color: Color.base1,
						style: {
							marginRight: Indent.XL.toNumber(),
							flex: 1,
						},
					}),
					isBadgeVisible && this.renderBadge(),
					isContextMenuButtonVisible && IconView({
						testId: this.getTestId('item-context-menu'),
						forwardRef: this.bindContextMenuButtonRef,
						icon: Icon.MORE,
						size: 28,
						color: Color.base4,
						onClick: this.onItemContextMenuButtonClick.bind(this, item),
					}),
				),
			);
		}

		renderListItemImage = (item) => {
			const { listItemImagePlaceholder } = this.props;
			const size = 40;

			return SafeImage({
				uri: encodeURI(withCurrentDomain(item.avatar.small)),
				style: {
					width: size,
					height: size,
					borderRadius: size / 2,
				},
				placeholder: listItemImagePlaceholder,
			});
		};

		renderBadge()
		{
			const { badgeText, badgeDesign } = this.props;

			return BadgeCounter({
				testId: this.getTestId('badge'),
				design: badgeDesign,
				style: {
					marginRight: Indent.XL.toNumber(),
					marginLeft: Indent.XS.toNumber(),
				},
				size: BadgeCounterSize.M,
				value: badgeText,
			});
		}

		onItemContextMenuButtonClick(item)
		{
			this.menu = new UIMenu(this.props.getListItemContextMenuItems(item));
			this.menu.show({
				target: this.listItemContextMenuButtonRef,
			});
		}

		bindContextMenuButtonRef = (ref) => {
			this.listItemContextMenuButtonRef = ref;
		};
	}

	module.exports = {
		/**
		 * @param {JnListItemProps} props
		 * @returns {JnListItem}
		 */
		JnListItem: (props) => new JnListItem(props),
	};
});

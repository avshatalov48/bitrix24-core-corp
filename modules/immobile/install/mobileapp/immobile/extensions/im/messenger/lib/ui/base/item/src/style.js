/**
 * @module im/messenger/lib/ui/base/item/style
 */
jn.define('im/messenger/lib/ui/base/item/style', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { transparent } = require('utils/color');
	// region ItemContainerStyles
	/**
	 * @typedef {Object} ItemContainerStyle
	 * @property {string} flexDirection
	 * @property {number} paddingTop
	 * @property {number} paddingLeft
	 */
	// endregion
	// region itemInfoStyles
	/**
	 * @typedef {Object} Title
	 * @property {number} marginBottom
	 * @property {number} fontSize
	 */
	/**
	 * @typedef {Object} SubTitle
	 * @property {string} color
	 * @property {number} fontSize
	 */
	/**
	 * @typedef {Object} MainContainer
	 * @property {number} marginLeft
	 * @property {number} borderBottomWidth
	 * @property {string} borderBottomColor
	 * @property {string} flexDirection
	 * @property {number | string} width
	 * @property {number} flexGrow
	 */

	/**
	 * @typedef {Object} ItemInfoStyle
	 * @property {MainContainer} mainContainer
	 * @property {Title} title
	 * @property {isYouTitle} isYouTitle
	 * @property {SubTitle} subtitle
	 */

	// endregion
	// region AvatarStyles
	/**
	 * @typedef {Object} Icon
	 * @property {number|string} width
	 * @property {number|string} height
	 * @property {number} borderRadius
	 * @property {number} marginBottom
	 */
	// endregion

	// region ItemStyle
	/**
	 * @typedef {Object} ItemSizeStyle
	 * @property {ItemContainerStyle} itemContainer
	 * @property {ItemInfoStyle} itemInfo
	 * @property {AvatarStyle} avatar
	 */

	/**
	 * @typedef {Object} ItemStyles
	 * @property {ItemSizeStyle} medium
	 * @property {ItemSizeStyle} large
	 */
	// endregion

	/** @type{ItemStyles} */
	const styles = {
		medium: {
			parentView: {
				backgroundColor: AppTheme.colors.bgContentPrimary,
			},
			itemContainer: {
				flexDirection: 'row',
				marginLeft: 18,
				alignItems: 'center',
				height: 70,
				borderBottomWidth: 1,
				borderBottomColor: AppTheme.colors.bgSeparatorPrimary,
				paddingTop: 14,
				paddingBottom: 15,
			},
			itemInfo: {
				mainContainer: {
					marginLeft: 12,
					flex: 1,
					overflow: 'hidden',
				},
				title: {
					marginBottom: 1,
					fontSize: 17,
					fontWeight: '400',
					color: AppTheme.colors.base1,
				},
				isYouTitle: {
					marginLeft: 4,
					marginBottom: 1,
					color: AppTheme.colors.base4,
					fontSize: 15,
				},
				subtitle: {
					color: AppTheme.colors.base4,
					fontSize: 15,
				},
			},
		},
		large: {
			parentView: {
				backgroundColor: AppTheme.colors.bgContentPrimary,
			},
			itemContainer: {
				flexDirection: 'row',
				marginLeft: 18,
			},
			itemInfo: {
				mainContainer: {
					marginLeft: 13,
					borderBottomWidth: 1,
					borderBottomColor: '#e9e9e9',
					flexDirection: 'row',
					flexGrow: 2,
				},
				title: {
					marginBottom: 2,
					fontSize: 16,
				},
				isYouTitle: {
					marginLeft: 4,
					marginBottom: 2,
					fontSize: 16,
					color: AppTheme.colors.base4,
				},
				subtitle: {
					color: AppTheme.colors.base4,
					fontSize: 14,
				},
			},
		},
	};

	const selectedItemStyles = {
		selectColor: transparent(AppTheme.colors.accentSoftBlue2, 0.6),
		unselectColor: AppTheme.colors.bgContentPrimary,

	};

	module.exports = { styles, selectedItemStyles };
});

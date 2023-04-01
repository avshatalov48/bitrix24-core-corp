/**
 * @module im/messenger/lib/ui/base/item/style
 */
jn.define('im/messenger/lib/ui/base/item/style', (require, exports, module) => {

	//region ItemContainerStyles
	/**
	 * @typedef {Object} ItemContainerStyle
	 * @property {string} flexDirection
	 * @property {number} paddingTop
	 * @property {number} paddingLeft
	 */
	//endregion
	//region itemInfoStyles
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
	 * @property {SubTitle} subtitle
	 */

	//endregion
	//region AvatarStyles
	/**
	 * @typedef {Object} Icon
	 * @property {number|string} width
	 * @property {number|string} height
	 * @property {number} borderRadius
	 * @property {number} marginBottom
	 */
	//endregion

	//region ItemStyle
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
	//endregion

	/** @type{ItemStyles} */
	const styles = {
		medium: {
			itemContainer: {
				flexDirection: 'row',
				marginLeft: 20,
				alignItems: 'center',
			},
			itemInfo: {
				mainContainer: {
					marginLeft: 13,
					flexGrow: 2,
					maxWidth: '80%'
				},
				title: {
					marginBottom: 2,
					fontSize: 15,
					fontWeight: '500',
				},
				subtitle: {
					color: '#80333333',
					fontSize: 14,
				},
			},
		},
		large: {
			itemContainer: {
				flexDirection: 'row',
				marginLeft: 20,
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
				subtitle: {
					color: '#80333333',
					fontSize: 14,
				},
			},
		}
	};

	const selectedItemStyles = {
		selectColor: '#e9e9e9',
		unselectColor: '#ffffff'

	};

	module.exports = {styles, selectedItemStyles}
});
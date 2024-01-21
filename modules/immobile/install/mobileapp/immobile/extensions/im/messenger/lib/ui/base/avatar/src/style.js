/**
 * @module im/messenger/lib/ui/base/avatar/style
 */
jn.define('im/messenger/lib/ui/base/avatar/style', (require, exports, module) => {

	/**
	 * @typedef {Object} AvatarStyle
	 * @property {Icon} icon
	 * @property {DefaultIcon} defaultIcon
	 * @property {string} justifyContent
	 */

	/**
	 * @typedef {Object} DefaultIcon
	 * @property {number|string} width
	 * @property {number|string} height
	 * @property {number} borderRadius
	 * @property {number} marginBottom
	 * @property {string} alignContent
	 * @property {string} justifyContent
	 * @property {DefaultIconText} text
	 */

	/**
	 * @typedef {Object} DefaultIconText
	 * @property {number} fontSize
	 * @property {string} alignSelf
	 * @property {string} color
	 */

	const avatarStyle = {
		'M': {
			justifyContent: 'center',
			icon: {
				width: 40,
				height: 40,
				borderRadius: 20,
			},
			defaultIcon: {
				width: 40,
				height: 40,
				borderRadius: 20,
				alignContent: 'center',
				justifyContent: 'center',
				text: {
					fontSize: 18,
					alignSelf: 'center',
					color: '#FFF',
				}
			},
		},
		'L': {
			justifyContent: 'center',
			icon: {
				width: 60,
				height: 60,
				borderRadius: 30,
			},
			defaultIcon: {
				width: 60,
				height: 60,
				borderRadius: 30,
				alignContent: 'center',
				justifyContent: 'center',
				text: {
					fontSize: 24,
					alignSelf: 'center',
					color: '#FFF',
				}
			},
		},
		'XL': {
			justifyContent: 'center',
			icon: {
				width: 72,
				height: 72,
				borderRadius: 72,
			},
			defaultIcon: {
				width: 72,
				height: 72,
				borderRadius: 72,
				alignContent: 'center',
				justifyContent: 'center',
				text: {
					fontSize: 30,
					alignSelf: 'center',
					color: '#FFF',
				}
			},
		},
	};

	module.exports = { avatarStyle };
});
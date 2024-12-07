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
		S: {
			justifyContent: 'center',
			icon: {
				width: 18,
				height: 18,
				borderRadius: 9,
				squareBorderRadius: 4,
			},
			defaultIcon: {
				width: 18,
				height: 18,
				borderRadius: 9,
				squareBorderRadius: 4,
				alignContent: 'center',
				justifyContent: 'center',
				text: {
					fontSize: 8,
					alignSelf: 'center',
					color: '#FFF',
				},
			},
		},
		M: {
			justifyContent: 'center',
			icon: {
				width: 40,
				height: 40,
				borderRadius: 20,
				squareBorderRadius: 9,
			},
			defaultIcon: {
				width: 40,
				height: 40,
				borderRadius: 20,
				squareBorderRadius: 9,
				alignContent: 'center',
				justifyContent: 'center',
				text: {
					fontSize: 18,
					alignSelf: 'center',
					color: '#FFF',
				},
			},
		},
		L: {
			justifyContent: 'center',
			icon: {
				width: 60,
				height: 60,
				borderRadius: 30,
				squareBorderRadius: 10,
			},
			defaultIcon: {
				width: 60,
				height: 60,
				borderRadius: 30,
				squareBorderRadius: 10,
				alignContent: 'center',
				justifyContent: 'center',
				text: {
					fontSize: 24,
					alignSelf: 'center',
					color: '#FFF',
				},
			},
		},
		XL: {
			justifyContent: 'center',
			icon: {
				width: 72,
				height: 72,
				borderRadius: 72,
				squareBorderRadius: 12,
			},
			defaultIcon: {
				width: 72,
				height: 72,
				borderRadius: 72,
				squareBorderRadius: 12,
				alignContent: 'center',
				justifyContent: 'center',
				text: {
					fontSize: 30,
					alignSelf: 'center',
					color: '#FFF',
				},
			},
		},
	};

	module.exports = { avatarStyle };
});

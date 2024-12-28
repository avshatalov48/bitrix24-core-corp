/**
 * @module layout/ui/user/avatar/src/base-avatar
 */
jn.define('layout/ui/user/avatar/src/base-avatar', (require, exports, module) => {
	const { Avatar: UIAvatar } = require('ui-system/blocks/avatar');

	/**
	 * @deprecated
	 * @see ui-system/blocks/avatar
	 * @function Avatar
	 * @param {number} id - user id
	 * @param {string} name - user full name or login
	 * @param {string} [image] - user image uri
	 * @param {string} [testId]
	 * @param {number} [size=24]
	 * @param {object} [additionalStyles]
	 * @param {object} [additionalStyles.image]
	 * @param {object} [additionalStyles.wrapper]
	 * @param {function} [onClick]
	 * @param restProps
	 * @return {UIAvatar}
	 */
	const Avatar = ({
		id,
		name,
		image,
		testId,
		size = 24,
		additionalStyles = {},
		onClick,
		...restProps
	}) => {
		return UIAvatar({
			id,
			name,
			testId,
			size,
			onClick,
			uri: image,
			style: additionalStyles.wrapper,
			...restProps,
		});
	};

	module.exports = { Avatar };
});

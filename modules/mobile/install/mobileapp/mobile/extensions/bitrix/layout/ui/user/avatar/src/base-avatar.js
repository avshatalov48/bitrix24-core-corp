/**
 * @module layout/ui/user/avatar/src/base-avatar
 */
jn.define('layout/ui/user/avatar/src/base-avatar', (require, exports, module) => {
	const { SafeImage } = require('layout/ui/safe-image');
	const { EmptyAvatar } = require('layout/ui/user/empty-avatar');
	const { withCurrentDomain } = require('utils/url');
	const { Type } = require('type');

	const DEFAULT_AVATAR = '/bitrix/mobileapp/mobile/extensions/bitrix/layout/ui/user/avatar/icons/default.svg';

	/**
	 * @function Avatar
	 * @param {number} id - user id
	 * @param {string} name - user full name or login
	 * @param {string} [image] - user image uri
	 * @param {string} [testId]
	 * @param {number} [size=24]
	 * @param {object} [additionalStyles]
	 * @param {object} [additionalStyles.image]
	 * @param {object} [additionalStyles.wrapper]
	 * @return {SafeImage}
	 */
	const Avatar = ({
		id,
		name,
		image,
		testId,
		size = 24,
		additionalStyles = {},
	}) => SafeImage({
		testId,
		style: {
			width: size,
			height: size,
			borderRadius: size / 2,
			...additionalStyles.image,
		},
		wrapperStyle: additionalStyles.wrapper,
		renderPlaceholder: () => {
			if (!Type.isNumber(id) || !Type.isStringFilled(name))
			{
				return null;
			}

			return EmptyAvatar({
				id,
				name,
				size,
				additionalStyles: additionalStyles.image,
			});
		},
		placeholder: {
			uri: withCurrentDomain(DEFAULT_AVATAR),
		},
		uri: image ? encodeURI(withCurrentDomain(image)) : undefined,
	});

	module.exports = { Avatar };
});

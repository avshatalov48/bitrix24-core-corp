/**
 * @module crm/crm-mode/wizard/layouts/src/images
 */
jn.define('crm/crm-mode/wizard/layouts/src/images', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { CreateBannerImage } = require('layout/ui/banners');
	const { EXTENSION_PATH } = require('crm/crm-mode/wizard/layouts/src/constants');

	/**
	 * @function makeImagesPath
	 * @param {string} imageName
	 * @param {boolean} [active]
	 * @return string
	 */
	const makeImagesPath = (imageName, active = false) => {
		const image = active ? `${imageName}-active` : imageName;

		return `${EXTENSION_PATH}/${AppTheme.id}/${image}.svg`;
	};

	/**
	 * @function BannerImage
	 * @param {string} imageName
	 * @return {View}
	 */
	const BannerImage = (imageName) => CreateBannerImage({
		image: {
			svg: {
				uri: makeImagesPath(imageName),
			},
		},
	});

	module.exports = { makeImagesPath, BannerImage };
});

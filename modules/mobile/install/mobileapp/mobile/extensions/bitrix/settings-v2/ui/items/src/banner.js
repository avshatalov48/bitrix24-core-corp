/**
 * @module settings-v2/ui/items/src/banner
 */
jn.define('settings-v2/ui/items/src/banner', (require, exports, module) => {
	const { createTestIdGenerator } = require('utils/test');
	const { CardBanner } = require('ui-system/blocks/banners/card-banner');
	const { Text4 } = require('ui-system/typography/text');
	const { SafeImage } = require('layout/ui/safe-image');
	const { ASSET_PATH } = require('settings-v2/const');
	const AppTheme = require('apptheme');

	const BANNER_ASSET_PATH = `${ASSET_PATH}banner/${AppTheme.id}`;

	class BannerItem extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.getTestId = createTestIdGenerator({
				testId: props.testId || 'settings-banner-item',
				context: this,
			});
		}

		render()
		{
			const { id, text } = this.props;

			return CardBanner({
				image: this.getImage(),
				testId: this.getTestId(id),
				description: text,
				descriptionTypography: Text4,
				hideCross: true,
			});
		}

		getImage()
		{
			const { bannerImageName } = this.props;

			return SafeImage({
				uri: `${BANNER_ASSET_PATH}/${bannerImageName}.png`,
				style: {
					width: 84,
					height: 84,
				},
				resizeMode: 'contain',
			});
		}
	}

	module.exports = {
		BannerItem,
	};
});

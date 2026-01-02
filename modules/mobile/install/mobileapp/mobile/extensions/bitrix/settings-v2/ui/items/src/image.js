/**
 * @module settings-v2/ui/items/src/image
 */
jn.define('settings-v2/ui/items/src/image', (require, exports, module) => {
	const { createTestIdGenerator } = require('utils/test');
	const { SafeImage } = require('layout/ui/safe-image');
	const AppTheme = require('apptheme');
	const { ASSET_PATH } = require('settings-v2/const');

	const IMAGE_ASSET_PATH = `${ASSET_PATH}image/${AppTheme.id}/`;

	class ImageItem extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.getTestId = createTestIdGenerator({
				testId: props.testId || 'settings-image-item',
				context: this,
			});
		}

		render()
		{
			const { name, id, externalStyle } = this.props;

			return SafeImage({
				testId: this.getTestId(id),
				uri: `${IMAGE_ASSET_PATH}/${name}.png`,
				resizeMode: 'stretch',
				style: {
					width: '100%',
					...externalStyle,
				},
			});
		}
	}

	module.exports = {
		ImageItem,
	};
});

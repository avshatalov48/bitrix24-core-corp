/**
 * @module settings-v2/ui/items/src/video-banner
 */
jn.define('settings-v2/ui/items/src/video-banner', (require, exports, module) => {
	const { Indent, Corner } = require('tokens');
	const { createTestIdGenerator } = require('utils/test');
	const { Area } = require('ui-system/layout/area');
	const { ASSET_PATH, EventType } = require('settings-v2/const');
	const { SafeImage } = require('layout/ui/safe-image');
	const VIDEO_ASSET_PATH = `${ASSET_PATH}video/`;

	class VideoBannerItem extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.getTestId = createTestIdGenerator({
				testId: props.testId || 'settings-video-banner-item',
				context: this,
			});

			this.initState();
		}

		initState()
		{
			this.state = {
				qualityType: this.props.value,
			};
		}

		componentDidMount()
		{
			BX.addCustomEvent(EventType.changeVideoQuality, this.changeQualityType);
		}

		componentWillUnmount()
		{
			BX.removeCustomEvent(EventType.changeVideoQuality, this.changeQualityType);
		}

		changeQualityType = (qualityType) => {
			this.setState({ qualityType });
		};

		render()
		{
			const imageUri = `${VIDEO_ASSET_PATH}${this.state.qualityType}-banner.png`;

			return Area(
				{
					style: {
						paddingVertical: Indent.L.toNumber(),
					},
				},
				SafeImage({
					resizeMode: 'stretch',
					withShimmer: true,
					style: {
						width: 327,
						height: 190,
						borderRadius: Corner.XL.toNumber(),
						alignSelf: 'center',
					},
					uri: imageUri,
				}),
			);
		}
	}

	module.exports = {
		VideoBannerItem,
	};
});

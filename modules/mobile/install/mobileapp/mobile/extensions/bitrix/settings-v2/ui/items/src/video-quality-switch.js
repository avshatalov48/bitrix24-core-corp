/**
 * @module settings-v2/ui/items/src/video-quality-switch
 */
jn.define('settings-v2/ui/items/src/video-quality-switch', (require, exports, module) => {
	const { createTestIdGenerator } = require('utils/test');
	const { Card, CardDesign, BadgeStatusMode } = require('ui-system/layout/card');
	const { CardList } = require('ui-system/layout/card-list');
	const { Text3 } = require('ui-system/typography/text');
	const { Color, Indent } = require('tokens');
	const { Loc } = require('loc');
	const { ASSET_PATH, VideoQualityType } = require('settings-v2/const');

	const VIDEO_ASSET_PATH = `${ASSET_PATH}video/`;

	const VideoQualityTypeLoc = {
		[VideoQualityType.HIGH]: Loc.getMessage('SETTINGS_V2_STRUCTURE_UI_ITEMS_VIDEO_QUALITY_HIGH'),
		[VideoQualityType.MEDIUM]: Loc.getMessage('SETTINGS_V2_STRUCTURE_UI_ITEMS_VIDEO_QUALITY_MEDIUM'),
		[VideoQualityType.LOW]: Loc.getMessage('SETTINGS_V2_STRUCTURE_UI_ITEMS_VIDEO_QUALITY_LOW'),
	};

	class VideoQualitySwitchItem extends LayoutComponent
	{

		/**
		 * @param {ItemProps} props
		 */
		constructor(props)
		{
			super(props);

			this.getTestId = createTestIdGenerator({
				testId: props.testId || 'settings-video-quality-switch-item',
				context: this,
			});

			this.state.selectedQuality = props.value;
		}

		render()
		{
			return CardList(
				{
					horizontal: true,
					withScroll: false,
					style: {
						justifyContent: 'center',
						padding: Indent.XL3.toNumber(),
					},
				},
				this.renderQualityCard(VideoQualityType.HIGH),
				this.renderQualityCard(VideoQualityType.MEDIUM),
				this.renderQualityCard(VideoQualityType.LOW),
			);
		}

		renderQualityCard(qualityType)
		{
			const selected = this.state.selectedQuality === qualityType;

			return Card(
				{
					excludePaddingSide: { all: true },
					onClick: () => this.onChange(qualityType),
					testId: this.getTestId('quality-main-card'),
				},
				Card(
					{
						design: CardDesign.PRIMARY,
						accent: selected,
						border: true,
						badgeMode: selected ? BadgeStatusMode.SUCCESS : null,
						testId: this.getTestId('quality-image-card'),
					},
					this.renderImage(qualityType),
				),

				Text3({
					text: VideoQualityTypeLoc[qualityType],
					color: Color.base1,
					style: {
						alignSelf: 'center',
						marginTop: Indent.S.toNumber(),
					},
				}),
			);
		}

		renderImage(qualityType)
		{
			return Image(
				{
					resizeMode: 'contain',
					style: {
						width: 41,
						height: 41,
						marginHorizontal: Indent.XL3.toNumber(),
						marginVertical: Indent.S.toNumber(),
					},
					svg: {
						uri: `${VIDEO_ASSET_PATH}${qualityType}.svg`,
					},
				},
			);
		}

		onChange = (qualityType) => {
			const { id, controller, onChange } = this.props;
			this.setState({
				selectedQuality: qualityType,
			}, () => {
				if (onChange)
				{
					onChange(id, controller, qualityType);
				}
			});
		};
	}

	module.exports = {
		VideoQualitySwitchItem,
	};
});

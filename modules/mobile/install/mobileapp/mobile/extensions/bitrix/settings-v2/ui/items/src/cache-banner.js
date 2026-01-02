/**
 * @module settings-v2/ui/items/src/cache-banner
 */
jn.define('settings-v2/ui/items/src/cache-banner', (require, exports, module) => {
	const { createTestIdGenerator } = require('utils/test');
	const { Area } = require('ui-system/layout/area');
	const { Indent, Color } = require('tokens');
	const { EventType } = require('settings-v2/const');
	const { NativeCacheService } = require('settings-v2/services/native');
	const { formatFileSize } = require('utils/file');
	const { H4, Text4 } = require('ui-system/typography');
	const { Loc } = require('loc');

	const CIRCLE_SIZE = 150;
	const STROKE_WIDTH = 26;
	const CIRCLE_RADIUS = (CIRCLE_SIZE - STROKE_WIDTH) / 2;
	const VIEW_BOX_SIZE = CIRCLE_SIZE + 1;
	const GAP_PERCENTAGE = 8;

	class CacheBannerItem extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.getTestId = createTestIdGenerator({
				testId: props.testId || 'settings-cache-banner-item',
				context: this,
			});
		}

		loadCacheSize = async () => {
			const fileSize = await NativeCacheService.getSettingValueById('cache_files');
			const imagesSize = await NativeCacheService.getSettingValueById('cache_images');
			this.setState({
				fileSize,
				imagesSize,
			});
		};

		componentDidMount()
		{
			BX.addCustomEvent(EventType.changeCacheSize, this.onChangeCacheSize);

			this.loadCacheSize();
		}

		componentWillUnmount()
		{
			BX.removeCustomEvent(EventType.changeCacheSize, this.onChangeCacheSize);
		}

		onChangeCacheSize = () => {
			this.loadCacheSize();
		};

		render()
		{
			const { fileSize, imagesSize } = this.state;
			const totalSize = fileSize + imagesSize;
			const GB_SIZE = 1024 * 1024 * 1024;
			const precision = totalSize > GB_SIZE ? 2 : 0;

			return Area(
				{
					style: {
						paddingVertical: Indent.L.toNumber(),
						alignItems: 'center',
						justifyContent: 'center',
					},
				},
				View(
					{
						style: {
							position: 'relative',
							width: CIRCLE_SIZE,
							height: CIRCLE_SIZE,
							alignItems: 'center',
							justifyContent: 'center',
						},
					},
					Image({
						testId: this.getTestId('cache-circle'),
						svg: {
							content: this.createSvgCircle(),
						},
						style: {
							position: 'absolute',
							width: CIRCLE_SIZE,
							height: CIRCLE_SIZE,
						},
					}),
					View(
						{
							style: {
								position: 'absolute',
								width: '100%',
								height: '100%',
								alignItems: 'center',
								justifyContent: 'center',
							},
						},
						H4({
							testId: this.getTestId('cache-size-text'),
							text: formatFileSize(totalSize, precision),
							color: Color.base1,
						}),
					),
				),
				View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'center',
							justifyContent: 'center',
							marginTop: Indent.XL3.toNumber(),
						},
					},
					this.createDotText(Loc.getMessage('SETTINGS_V2_STRUCTURE_UI_ITEMS_CACHE_BANNER_FILES'), Color.accentMainPrimaryalt.toHex()),
					this.createDotText(Loc.getMessage('SETTINGS_V2_STRUCTURE_UI_ITEMS_CACHE_BANNER_IMAGES'), Color.accentMainSuccess.toHex()),
				),
			);
		}

		createDotText(text, dotColor)
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						marginRight: Indent.XL3.toNumber(),
						alignItems: 'center',
					},
				},
				View(
					{
						style: {
							width: 6,
							height: 6,
							borderRadius: 3,
							backgroundColor: dotColor,
							marginRight: Indent.S.toNumber(),
						},
					},
				),
				Text4({
					text,
					color: Color.base1,
				}),
			);
		}

		createSvgCircle()
		{
			const { fileSize = 0, imagesSize = 0 } = this.state;
			const totalSize = fileSize + imagesSize;

			if (totalSize === 0)
			{
				return this.renderEmptyCircle();
			}

			const filePercentage = (fileSize / totalSize) * 100 > 90 ? 90 : (fileSize / totalSize) * 100;
			const imagesPercentage = (imagesSize / totalSize) * 100 < 10 ? 10 : (imagesSize / totalSize) * 100;

			const center = VIEW_BOX_SIZE / 2;

			return `
				<svg width="${VIEW_BOX_SIZE}" height="${CIRCLE_SIZE}" viewBox="0 0 ${VIEW_BOX_SIZE} ${CIRCLE_SIZE}" fill="none" xmlns="http://www.w3.org/2000/svg">
					${this.generateArcSegment(center, center, CIRCLE_RADIUS, 0, filePercentage, Color.accentMainPrimaryalt.toHex())}
					${this.generateArcSegment(center, center, CIRCLE_RADIUS, filePercentage, 100, Color.accentMainSuccess.toHex())}
				</svg>
			`;
		}

		generateArcSegment(centerX, centerY, radius, startPercentage, endPercentage, color)
		{
			if (startPercentage >= endPercentage)
			{
				return '';
			}

			const adjustedEndPercentage = Math.max(startPercentage, endPercentage - GAP_PERCENTAGE);

			if (adjustedEndPercentage <= startPercentage)
			{
				return '';
			}

			const startAngle = (startPercentage / 100) * 360 - 90;
			const endAngle = (adjustedEndPercentage / 100) * 360 - 90;

			const startRadians = (startAngle * Math.PI) / 180;
			const endRadians = (endAngle * Math.PI) / 180;

			const startX = centerX + radius * Math.cos(startRadians);
			const startY = centerY + radius * Math.sin(startRadians);
			const endX = centerX + radius * Math.cos(endRadians);
			const endY = centerY + radius * Math.sin(endRadians);

			const largeArcFlag = endAngle - startAngle > 180 ? 1 : 0;

			return `
				<g>
					<path d="M${startX} ${startY} 
						A${radius} ${radius} 0 ${largeArcFlag} 1 ${endX} ${endY}"
						stroke="${color}" stroke-width="${STROKE_WIDTH}" stroke-linecap="round" fill="none"
					/>
				</g>
			`;
		}

		renderEmptyCircle()
		{
			const center = VIEW_BOX_SIZE / 2;

			return `
				<svg width="${VIEW_BOX_SIZE}" height="${CIRCLE_SIZE}" viewBox="0 0 ${VIEW_BOX_SIZE} ${CIRCLE_SIZE}" fill="none" xmlns="http://www.w3.org/2000/svg">
					<circle cx="${center}" cy="${center}" r="${CIRCLE_RADIUS}" 
						stroke="${Color.base6.toHex()}" stroke-width="${STROKE_WIDTH}" fill="none" />
				</svg>
			`;
		}
	}

	module.exports = {
		CacheBannerItem,
	};
});

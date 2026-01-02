/**
 * @module ui-system/blocks/banners/safety-banner/src/center-banner
 */
jn.define('ui-system/blocks/banners/safety-banner/src/center-banner', (require, exports, module) => {
	const { Indent, Color } = require('tokens');
	const { SafeImage } = require('layout/ui/safe-image');
	const { Text5 } = require('ui-system/typography');
	const { createTestIdGenerator } = require('utils/test');

	/**
	 * @typedef {Object} CenterSafetyBannerProps
	 * @property {string} [testId]
	 * @property {string} [description]
	 * @property {SafetyType} safetyType
	 *
	 * @function SafetyBanner
	 * @param {CenterSafetyBannerProps} props
	 */
	function CenterSafetyBanner(props)
	{
		const { testId, description, safetyType } = props;
		const getTestId = createTestIdGenerator({
			prefix: testId || 'center-banner',
			context: this,
		});

		return View(
			{
				testId: getTestId(),
				style: {
					paddingTop: Indent.XL4.toNumber(),
					paddingHorizontal: Indent.XL3.toNumber(),
					paddingBottom: Indent.XL3.toNumber(),
				},
			},
			View(
				{
					style: {
						justifyContent: 'center',
						alignItems: 'center',
					},
				},
				SafeImage({
					uri: safetyType.getImageUri(),
					testId: getTestId('image'),
					resizeMode: 'contain',
					style: {
						height: 108,
						width: 108,
					},
				}),
				Text5({
					text: description,
					color: Color.base2,
					style: {
						marginTop: Indent.XL2.toNumber(),
						textAlign: 'center',
					},
				}),
			),
		);
	}

	module.exports = {
		CenterSafetyBanner,
	};
});

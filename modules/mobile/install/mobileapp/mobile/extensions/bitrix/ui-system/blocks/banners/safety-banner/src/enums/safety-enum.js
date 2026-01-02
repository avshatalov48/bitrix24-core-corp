/**
 * @module ui-system/blocks/banners/safety-banner/src/enums/safety-enum
 */
jn.define('ui-system/blocks/banners/safety-banner/src/enums/safety-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');
	const { withCurrentDomain } = require('utils/url');
	const ASSET_PATH = withCurrentDomain(
		'/bitrix/mobileapp/mobile/extensions/bitrix/ui-system/blocks/banners/safety-banner/assets/');

	/**
	 * @class SafetyType
	 */
	class SafetyType extends BaseEnum
	{
		static SUCCESS = new SafetyType('SUCCESS', 'success');

		static ATTENTION = new SafetyType('ATTENTION', 'attention');

		static WARNING = new SafetyType('WARNING', 'warning');

		getImageUri()
		{
			const SafetyImageUri = {
				[SafetyType.SUCCESS]: `${ASSET_PATH}success-shield.png`,
				[SafetyType.ATTENTION]: `${ASSET_PATH}attention-shield.png`,
				[SafetyType.WARNING]: `${ASSET_PATH}warning-shield.png`,
			};

			return SafetyImageUri[this.value];
		}
	}

	module.exports = {
		SafetyType: SafetyType.export(),
	};
});

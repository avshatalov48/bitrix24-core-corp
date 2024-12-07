/**
 * @module ui-system/blocks/link/src/mode-enum
 */
jn.define('ui-system/blocks/link/src/mode-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class LinkMode
	 * @template TLinkMode
	 * @extends {BaseEnum<LinkMode>}
	 */
	class LinkMode extends BaseEnum
	{
		static PLAIN = new LinkMode('PLAIN', {});

		static DASH = new LinkMode('DASH', {
			borderStyle: 'dash',
			borderDashSegmentLength: 4,
			borderDashGapLength: 4,
			borderBottomWidth: 1,
		});

		static SOLID = new LinkMode('SOLID', {
			borderBottomWidth: 1,
		});

		getStyle()
		{
			return this.getValue();
		}
	}

	module.exports = { LinkMode };
});

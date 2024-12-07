/**
 * @module ui-system/blocks/link/src/design-enum
 */
jn.define('ui-system/blocks/link/src/design-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');
	const { Color } = require('tokens');

	/**
	 * @class LinkDesign
	 * @template TLinkDesign
	 * @extends {BaseEnum<LinkDesign>}
	 */
	class LinkDesign extends BaseEnum
	{
		static PRIMARY = new LinkDesign('PRIMARY', {
			color: Color.accentMainLink,
		});

		static BLACK = new LinkDesign('BLACK', {
			color: Color.base1,
		});

		static GREY = new LinkDesign('GREY', {
			color: Color.base3,
		});

		static LIGHT_GREY = new LinkDesign('LIGHT_GREY', {
			color: Color.base4,
		});

		static ALERT = new LinkDesign('ALERT', {
			color: Color.accentMainAlert,
		});

		static WHITE = new LinkDesign('ALERT', {
			color: Color.baseWhiteFixed,
		});

		getStyle()
		{
			return this.getValue();
		}
	}

	module.exports = { LinkDesign };
});

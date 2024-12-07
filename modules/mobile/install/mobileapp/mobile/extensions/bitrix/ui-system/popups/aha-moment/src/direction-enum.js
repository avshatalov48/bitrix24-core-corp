/**
 * @module ui-system/popups/aha-moment/src/direction-enum
 */
jn.define('ui-system/popups/aha-moment/src/direction-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	const TOP = 'TOP';
	const BOTTOM = 'BOTTOM';
	const EAR_SIZE = {
		width: 24,
		height: 10,
	};

	/**
	 * @class AhaMomentDirection
	 * @template TAhaMomentDirection
	 * @extends {BaseEnum<AhaMomentDirection>}
	 */
	class AhaMomentDirection extends BaseEnum
	{
		static TOP = new AhaMomentDirection(TOP, {
			position: TOP,
			ear: (color) => `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="10" viewBox="0 0 24 10" fill="none"><path d="M-3.8147e-06 10L7.75736 2.24264C10.1005 -0.100503 13.8995 -0.100506 16.2426 2.24264L24 10L-3.8147e-06 10Z" fill="${color}"/></svg>`,
		});

		static BOTTOM = new AhaMomentDirection(BOTTOM, {
			position: BOTTOM,
			ear: (color) => `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="10" viewBox="0 0 24 10" fill="none"><path d="M24 0L16.2426 7.75736C13.8995 10.1005 10.1005 10.1005 7.75736 7.75736L0 0H24Z" fill="${color}" /></svg>`,
		});

		getPosition()
		{
			return this.getValue().position.toLowerCase();
		}

		getSvg(color)
		{
			return this.getValue().ear(color);
		}

		getSvgSize()
		{
			return EAR_SIZE;
		}

		isTop()
		{
			return this.getValue().position === TOP;
		}
	}

	module.exports = { AhaMomentDirection };
});

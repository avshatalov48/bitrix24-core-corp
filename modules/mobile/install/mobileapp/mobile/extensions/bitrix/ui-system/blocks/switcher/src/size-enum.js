/**
 * @module ui-system/blocks/switcher/src/size-enum
 */
jn.define('ui-system/blocks/switcher/src/size-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	const CIRCLE = 512;

	/**
	 * @class SwitcherSize
	 * @template TSwitcherSize
	 * @extends {BaseEnum<SwitcherSize>}
	 */
	class SwitcherSize extends BaseEnum
	{
		static XL = new SwitcherSize('XL', {
			thumb: {
				style: {
					top: 3,
					width: 19,
					height: 19,
					borderRadius: CIRCLE,
				},
				position: 2,
			},
			track: {
				width: 51,
				height: 25,
				borderRadius: CIRCLE,
			},
		});

		static L = new SwitcherSize('L', {
			thumb: {
				style: {
					top: 2,
					width: 15,
					height: 15,
					borderRadius: CIRCLE,
				},
				position: 2,
			},
			track: {
				width: 40,
				height: 19,
				borderRadius: CIRCLE,
			},
		});

		static M = new SwitcherSize('M', {
			thumb: {
				style: {
					top: 2,
					width: 11,
					height: 11,
					borderRadius: CIRCLE,
				},
				position: 2,
			},
			track: {
				width: 30,
				height: 15,
				borderRadius: CIRCLE,
			},
		});

		static S = new SwitcherSize('S', {
			thumb: {
				style: {
					top: 2,
					width: 8,
					height: 8,
					borderRadius: CIRCLE,
				},
				position: 2,
			},
			track: {
				width: 24,
				height: 12,
				borderRadius: CIRCLE,
			},
		});

		getThumbPosition(checked)
		{
			const position = this.getValue().thumb.position;

			return checked
				? this.getTrackStyle().width - (this.getThumbStyle().width + position)
				: position;
		}

		getTrackStyle()
		{
			return this.getValue().track;
		}

		getThumbStyle({ checked } = {})
		{
			return {
				...this.getThumbPosition(checked),
				...this.getValue().thumb.style,
			};
		}

		getWidth()
		{
			return this.getValue().track.width;
		}
	}

	module.exports = { SwitcherSize };
});

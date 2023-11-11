/**
 * @module im/messenger/lib/utils/color
 */
jn.define('im/messenger/lib/utils/color', (require, exports, module) => {
	const { Type } = require('type');
	class ColorUtils
	{
		constructor()
		{
			this.colors = jnExtensionData.get('im:messenger/lib/utils').colors;
		}

		getColorByNumber(number)
		{
			const colors = Object.keys(this.colors);
			let color = '';

			if (!Type.isNumber(number))
			{
				color = colors[mt_rand(0, 9)];

				return color;
			}
			let index = Number(number.toString().slice(-1));

			index = index === 0 ? 9 : index - 1;

			color = colors[index];

			return color;
		}
	}

	// eslint-disable-next-line camelcase
	function mt_rand(min, max)
	{
		const argc = arguments.length;
		if (argc === 0)
		{
			min = 0;
			max = 2_147_483_647;
		}
		else if (argc === 1)
		{
			throw new Error('Warning: mt_rand() expects exactly 2 parameters, 1 given');
		}
		else
		{
			min = parseInt(min, 10);
			max = parseInt(max, 10);
		}

		return Math.floor(Math.random() * (max - min + 1)) + min;
	}
	window.ColorUtils = new ColorUtils();

	module.exports = { ColorUtils };
});

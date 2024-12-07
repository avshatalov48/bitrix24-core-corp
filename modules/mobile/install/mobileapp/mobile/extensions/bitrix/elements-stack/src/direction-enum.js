/**
 * @module elements-stack/src/direction-enum
 */
jn.define('elements-stack/src/direction-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class ElementsStackDirection
	 */
	class ElementsStackDirection extends BaseEnum
	{
		static LEFT = new ElementsStackDirection('LEFT', 'left');

		static RIGHT = new ElementsStackDirection('RIGHT', 'right');

		isRight()
		{
			return this.equal(ElementsStackDirection.RIGHT);
		}
	}

	module.exports = { ElementsStackDirection };
});

/**
 * @module testing/tests/utils/enums/src/test
 */
jn.define('testing/tests/utils/enums/src/test', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class IndentEnum
	 * @extends {BaseEnum<IndentEnum>}
	 */
	class IndentEnum extends BaseEnum
	{}

	// polyfill static properties
	IndentEnum.CIRCLE = new IndentEnum('CIRCLE', 512);
	IndentEnum.XL2 = new IndentEnum('XL2', 18);
	IndentEnum.XL = new IndentEnum('XL', 18);
	IndentEnum.L = new IndentEnum('L', 12);
	IndentEnum.M = new IndentEnum('M', 8);
	IndentEnum.S = new IndentEnum('S', 6);
	IndentEnum.XS = new IndentEnum('XS', 4);

	/**
	 * @class CarEnum
	 * @extends {BaseEnum<CarEnum>}
	 */
	class CarEnum extends BaseEnum
	{}

	CarEnum.TESLA = new CarEnum('TESLA', 700);
	CarEnum.LAND_ROVER = new CarEnum('LAND_ROVER', 300);
	CarEnum.BMW = new CarEnum('BMW', 550);
	CarEnum.VOLKSWAGEN = new CarEnum('VOLKSWAGEN', 250);
	CarEnum.MAZDA = new CarEnum('MAZDA', 200);
	CarEnum.FORD = new CarEnum('FORD', 350);
	CarEnum.TOYOTA = new CarEnum('TOYOTA', 180);
	CarEnum.XS = new CarEnum('XS', 4);

	module.exports = { IndentEnum, CarEnum };
});

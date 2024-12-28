/**
 * @module ui-system/popups/color-picker/palette-enum
 */
jn.define('ui-system/popups/color-picker/palette-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class PaletteEnum
	 */
	class ColorPickerPalette extends BaseEnum
	{
		static BASE = new ColorPickerPalette('BASE', [
			'#3BA7EF',
			'#3CC7F6',
			'#5DD0E1',
			'#4CE3C4',
			'#FCAA1C',
			'#9DCF00',
			'#C2A659',
			'#A8B6C9',
			'#FF799C',
			'#4492E1',
			'#8E52EC',
			'#FF5752',
			'#7588CD',
			'#599FB5',
			'#F3C58E',
			'#02BB9A',
			'#A56300',
			'#C21B16',
			'#B15EF5',
			'#88C8F8',
			'#FEA8A6',
			'#3BF39C',
			'#BBED21',
			'#37C5D8',
			'#D0D0D0',
			'#A7A7A7',
			'#909090',
			'#555555',
			'#333333',
			'#000000',
		]);

		static SECOND = new ColorPickerPalette('SECOND', [
			'#56FB7D',
			'#C6EBF0',
			'#E6F1A3',
			'#E73AF7',
			'#7BDCDD',
			'#F4C19F',
			'#F4C991',
			'#E7925A',
			'#5FB353',
			'#336FB9',
			'#558AC8',
			'#9D86BB',
			'#59105D',
			'#E79FC0',
			'#C2BBE9',
			'#C0C5CC',
			'#969DA8',
			'#3A6BE8',
			'#DF7351',
			'#A9A133',
			'#59B8B3',
			'#AED2A0',
			'#7BFA4C',
			'#F0E28A',
			'#AA7357',
			'#DEC7BD',
			'#DBDDE0',
			'#555555',
			'#84A4D5',
			'#000000',
		]);
	}

	module.exports = { ColorPickerPalette };
});

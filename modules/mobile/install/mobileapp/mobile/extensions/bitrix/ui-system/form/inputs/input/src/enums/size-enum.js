/**
 * @module ui-system/form/inputs/input/src/enums/size-enum
 */
jn.define('ui-system/form/inputs/input/src/enums/size-enum', (require, exports, module) => {
	const { Component, Indent } = require('tokens');
	const { BaseEnum } = require('utils/enums/base');
	const { Text5, Text6 } = require('ui-system/typography/text');

	/**
	 * @class InputSize
	 * @template TInputSize
	 * @extends {BaseEnum<InputSize>}
	 */
	class InputSize extends BaseEnum
	{
		static L = new InputSize('L', {
			input: {
				height: 42,
				textSize: 2,
				paddingHorizontal: Indent.XL,
				paddingVertical: Indent.M,
			},
			container: {
				borderRadius: Component.elementMCorner,
			},
			label: {
				typography: Text5,
				minPosition: Indent.M,
			},
		});

		static M = new InputSize('M', {
			input: {
				height: 36,
				textSize: 4,
				paddingHorizontal: Indent.XL,
				paddingVertical: Indent.M,
			},
			container: {
				borderRadius: Component.elementMCorner,
			},
			label: {
				typography: Text5,
				minPosition: Indent.M,
			},
		});

		static S = new InputSize('S', {
			input: {
				height: 28,
				textSize: 5,
				paddingHorizontal: Indent.M,
				paddingVertical: Indent.S,
			},
			container: {
				borderRadius: Component.elementSCorner,
			},
			label: {
				typography: Text6,
				minPosition: Indent.XS,
			},
		});

		getContainer()
		{
			return this.getValue().container;
		}

		getInput()
		{
			return this.getValue().input;
		}

		getLabel()
		{
			return this.getValue().label;
		}
	}

	module.exports = { InputSize };
});

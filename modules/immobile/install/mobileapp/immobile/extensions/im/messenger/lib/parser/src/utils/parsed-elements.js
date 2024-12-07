/* eslint-disable @bitrix24/bitrix24-rules/no-pseudo-private */
/* eslint-disable  no-underscore-dangle */

/**
 * @module im/messenger/lib/parser/utils/parsed-elements
 */
jn.define('im/messenger/lib/parser/utils/parsed-elements', (require, exports, module) => {

	const PLACEHOLDER = '####REPLACEMENT_';

	const parsedElements = {
		_list: [],

		clean()
		{
			this._list = [];
		},

		add(element)
		{
			const newLength = this._list.push(element);

			return Number(newLength - 1);
		},

		getOrderedList(text)
		{
			const textLines = text.split('\n');

			const orderedList = [];
			textLines.forEach((line) => {
				if (line.includes(PLACEHOLDER))
				{
					const [, replacementIdText] = line.split(PLACEHOLDER);
					const replacementId = Number(replacementIdText.trim());

					orderedList.push(this._list[replacementId]);
				}
			});

			return orderedList;
		},
	};

	module.exports = {
		parsedElements,
		PLACEHOLDER,
	};
});

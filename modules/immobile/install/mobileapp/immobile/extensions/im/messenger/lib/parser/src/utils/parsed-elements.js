/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-pseudo-private */

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
			textLines.forEach(line => {
				if (line.includes(PLACEHOLDER))
				{
					let [, replacementId] = line.split(PLACEHOLDER);

					orderedList.push(this._list[replacementId]);
				}
			});

			return orderedList;
		}
	};

	module.exports = {
		parsedElements,
		PLACEHOLDER,
	};
});

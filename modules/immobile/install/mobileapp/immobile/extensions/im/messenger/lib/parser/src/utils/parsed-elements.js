/* eslint-disable @bitrix24/bitrix24-rules/no-pseudo-private */
/* eslint-disable  no-underscore-dangle */
/* eslint-disable  no-param-reassign */

/**
 * @module im/messenger/lib/parser/utils/parsed-elements
 */
jn.define('im/messenger/lib/parser/utils/parsed-elements', (require, exports, module) => {

	const PLACEHOLDER = '####REPLACEMENT_';
	const TAG_PLACEHOLDER = '####REPLACEMENT_TAG_';

	const parsedElements = {
		_list: [],
		_replacedTags: [],

		/**
		 * @param {string} text
		 * @return {string}
		 */
		cutTags(text)
		{
			text = text.replaceAll(/\[(.+?)](.*?)\[\/(.+?)]/gi, (tag) => {
				const id = this._replacedTags.length;
				this._replacedTags.push(tag);

				return `${TAG_PLACEHOLDER}${id}`;
			});

			return text;
		},

		/**
		 * @param {string} text
		 * @return {string}
		 */
		restoreTags(text) {
			this._replacedTags.forEach((originalTag, index) => {
				text = text.replace(`${TAG_PLACEHOLDER}${index}`, originalTag);
			});

			return text;
		},

		cleanTags()
		{
			this._replacedTags = [];
		},

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

/**
 * @module utils/search
 */
jn.define('utils/search', (require, exports, module) => {
	const { splitByWords, compareWords } = require('utils/string');
	const { unique } = require('utils/array');

	/**
	 * @function checkValueMatchQuery
	 * @param {string} query
	 * @param {string} value
	 * @returns {boolean}
	 */
	const checkValueMatchQuery = (query, value) => {
		if (value && typeof value === 'string'
			&& query && typeof query === 'string')
		{
			const queryWords = splitByWords(query);
			const uniqueQueryWords = unique(queryWords);
			const matchedWords = [];

			const valueWords = splitByWords(value);
			valueWords.forEach((word) => {
				queryWords.forEach((queryWord) => {
					const match = compareWords(queryWord, word);
					if (match && !matchedWords.includes(queryWord))
					{
						matchedWords.push(queryWord);
					}
				});
			});

			return matchedWords.length >= uniqueQueryWords.length;
		}

		return false;
	};

	/**
	 * @function search
	 */
	function search(items, query, predicates = [], excludeFields = [])
	{
		try
		{
			query = query.toLowerCase();
			const queryWords = splitByWords(query);
			const uniqueQueryWords = unique(queryWords);

			return items.filter((item) => {
				const matchedWords = [];
				if (predicates.length > 0 && query)
				{
					[...predicates]
						.reverse()
						.forEach((name) => {
							if (excludeFields.includes(name))
							{
								return;
							}

							let field = item[name];
							if (!field && item.params)
							{
								const { customData } = item.params;
								if (customData)
								{
									field = customData[name];
								}
							}

							if (field && typeof field === 'string')
							{
								const result = splitByWords(field).filter((word) => {
									const items = queryWords.filter((queryWord) => {
										const match = compareWords(queryWord, word);
										if (match && !matchedWords.includes(queryWord))
										{
											matchedWords.push(queryWord);
										}

										return match;
									});

									return items.length > 0;
								});
							}
						});
				}

				return matchedWords.length >= uniqueQueryWords.length;
			});
		}
		catch (e)
		{
			console.error(e);

			return items;
		}
	}

	module.exports = { search, checkValueMatchQuery };
});

/**
 * @module 'im/messenger/db/helper/start-words'
 */
jn.define('im/messenger/db/helper/start-words', (require, exports, module) => {

	/**
	 * @param fieldName
	 * @param searchText
	 * @returns {{sqlCondition: string, values: string[]}}
	 * @description this method are needed for improve search in Cyrillic locale with upper and lower cases
	 */
	function getStartWordsSearchVariants(fieldName, searchText)
	{
		const upperSearchCase = searchText.toLocaleUpperCase(env.languageId);
		const lowerSearchCase = searchText.toLocaleLowerCase(env.languageId);
		const withFirstLetterUpperCaseSearch = searchText.charAt(0).toLocaleUpperCase(env.languageId)
			+ searchText.slice(1).toLocaleLowerCase(env.languageId);

		return {
			sqlCondition: `
				(
					(${fieldName}) LIKE ? COLLATE NOCASE OR
					(${fieldName}) LIKE ? COLLATE NOCASE OR
					(${fieldName}) LIKE ? COLLATE NOCASE OR
					(${fieldName}) LIKE ? COLLATE NOCASE OR
					(${fieldName}) LIKE ? COLLATE NOCASE OR
					(${fieldName}) LIKE ? COLLATE NOCASE OR
					(${fieldName}) LIKE ? COLLATE NOCASE OR
					(${fieldName}) LIKE ? COLLATE NOCASE
				)
			`,
			values: [
				`${searchText}%`,
				`% ${searchText}%`,
				`${upperSearchCase}%`,
				`% ${upperSearchCase}%`,
				`${lowerSearchCase}%`,
				`% ${lowerSearchCase}%`,
				`${withFirstLetterUpperCaseSearch}%`,
				`% ${withFirstLetterUpperCaseSearch}%`,
			],
		};
	}

	module.exports = {
		getStartWordsSearchVariants,
	};
});

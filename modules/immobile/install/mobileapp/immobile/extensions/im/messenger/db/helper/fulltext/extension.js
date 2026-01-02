/**
 * @module 'im/messenger/db/helper/fulltext'
 */
jn.define('im/messenger/db/helper/fulltext', (require, exports, module) => {

	/**
	 * @param fieldName
	 * @param searchText
	 * @returns {{sqlCondition: string, values: string[]}}
	 * @description this method are needed for improve search in Cyrillic locale with upper and lower cases
	 */
	function getFullTextSearchVariants(fieldName, searchText)
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
					(${fieldName}) LIKE ? COLLATE NOCASE
				)
			`,
			values: [
				`%${searchText}%`,
				`%${upperSearchCase}%`,
				`%${lowerSearchCase}%`,
				`%${withFirstLetterUpperCaseSearch}%`,
			],
		};
	}

	module.exports = {
		getFullTextSearchVariants,
	};
});

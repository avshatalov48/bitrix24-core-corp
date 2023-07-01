/* eslint-disable bitrix-rules/no-pseudo-private */
/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/lib/page-navigation
 */
jn.define('im/messenger/lib/page-navigation', (require, exports, module) => {

	const { Type } = require('type');

	class PageNavigation
	{
		constructor(options = {})
		{
			this._currentPage = Type.isNumber(options.currentPage) ? options.currentPage : 0;
			this._itemsPerPage = Type.isNumber(options.itemsPerPage) ? options.itemsPerPage : 0;

			this._hasNextPage = Type.isBoolean(options.hasNextPage) ? options.hasNextPage : true;
			this._isPageLoading = Type.isBoolean(options.isPageLoading) ? options.isPageLoading : false;
		}

		get currentPage()
		{
			return this._currentPage;
		}

		set currentPage(pageNumber)
		{
			if (!Type.isNumber(pageNumber))
			{
				throw new Error('PageNavigation: pageNumber is not a number');
			}

			this._currentPage = pageNumber;
		}

		get itemsPerPage()
		{
			return this._itemsPerPage;
		}

		get hasNextPage()
		{
			return this._hasNextPage;
		}

		set hasNextPage(hasPage)
		{
			if (!Type.isBoolean(hasPage))
			{
				throw new Error('PageNavigation: hasPage is not a boolean value');
			}

			this._hasNextPage = hasPage;
		}

		get isPageLoading()
		{
			return this._isPageLoading;
		}

		set isPageLoading(isLoading)
		{
			if (!Type.isBoolean(isLoading))
			{
				throw new Error('PageNavigation: isLoading is not a boolean value');
			}

			this._isPageLoading = isLoading;
		}

		turnPage(count = 1)
		{
			if (!Type.isNumber(count))
			{
				throw new Error('PageNavigation: count is not a number');
			}

			this._currentPage += count;
		}
	}

	module.exports = {
		PageNavigation,
	};
});

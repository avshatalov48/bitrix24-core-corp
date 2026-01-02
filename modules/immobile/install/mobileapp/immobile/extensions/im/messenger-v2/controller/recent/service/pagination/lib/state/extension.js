/**
 * @module im/messenger-v2/controller/recent/service/pagination/lib/state
 */
jn.define('im/messenger-v2/controller/recent/service/pagination/lib/state', (require, exports, module) => {
	const { Type } = require('type');

	class PaginationState
	{
		static DATABASE_SOURCE = 'database';
		static SERVER_SOURCE = 'server';

		constructor()
		{
			this.sources = {
				[PaginationState.DATABASE_SOURCE]: {
					isLoading: false,
					hasMore: false,
					nextPage: 1,
					isEnabled: true,
					lastItem: null,
				},
				[PaginationState.SERVER_SOURCE]: {
					isLoading: false,
					hasMore: false,
					nextPage: 1,
					isEnabled: true,
					lastItem: null,
				},
			};
		}

		/**
		 * @param {PaginationState[DATABASE_SOURCE] | PaginationState[SERVER_SOURCE]} source
		 * @param {?boolean} hasMore
		 * @param {number|undefined} nextPage
		 * @param {object} lastItem
		 */
		update(source, { hasMore, nextPage, lastItem })
		{
			if (!this.sources[source])
			{
				return;
			}

			this.sources[source] = {
				...this.sources[source],
				isLoading: false,
				hasMore: Type.isUndefined(hasMore) ? this.sources[source].hasMore : hasMore,
				nextPage: Type.isUndefined(nextPage) ? this.sources[source].nextPage + 1 : nextPage,
				lastItem,
			};
		}

		/**
		 * @param {PaginationState[DATABASE_SOURCE] | PaginationState[SERVER_SOURCE]} source
		 */
		startLoading(source)
		{
			if (this.sources[source])
			{
				this.sources[source].isLoading = true;
			}
		}

		/**
		 * @param {PaginationState[DATABASE_SOURCE] | PaginationState[SERVER_SOURCE]} source
		 */
		markAsFailed(source)
		{
			if (this.sources[source])
			{
				this.sources[source] = {
					...this.sources[source],
					isLoading: false,
					hasMore: false,
				};
			}
		}

		/**
		 * @param {PaginationState[DATABASE_SOURCE] | PaginationState[SERVER_SOURCE]} source
		 */
		disable(source)
		{
			if (this.sources[source])
			{
				this.sources[source].isEnabled = false;
				this.sources[source].isLoading = false;
				this.sources[source].hasMore = false;
			}
		}

		/**
		 * @param {PaginationState[DATABASE_SOURCE] | PaginationState[SERVER_SOURCE]} source
		 * @return {*|boolean}
		 */
		canLoad(source)
		{
			return this.sources[source]
				&& this.sources[source].isEnabled
				&& this.sources[source].hasMore
				&& !this.sources[source].isLoading
			;
		}

		/**
		 * @param {PaginationState[DATABASE_SOURCE] | PaginationState[SERVER_SOURCE] | null} [source]
		 * @return {boolean}
		 */
		isLoading(source = null)
		{
			if (source)
			{
				return this.sources[source]?.isLoading ?? false;
			}

			return Object.values(this.sources).some((s) => s.isLoading);
		}

		/**
		 * @param {PaginationState[DATABASE_SOURCE] | PaginationState[SERVER_SOURCE]} source
		 * @return {number}
		 */
		getNextPage(source)
		{
			return this.sources[source]?.nextPage ?? 1;
		}

		/**
		 * @param {PaginationState[DATABASE_SOURCE] | PaginationState[SERVER_SOURCE]} [source]
		 * @return {boolean}
		 */
		hasMore(source = null)
		{
			if (source)
			{
				return this.sources[source]?.hasMore ?? false;
			}

			return Object.values(this.sources).some((s) => s.hasMore);
		}

		/**
		 * @param {PaginationState[DATABASE_SOURCE] | PaginationState[SERVER_SOURCE]} source
		 * @return {RecentItemData|null}
		 */
		getLastItem(source)
		{
			return this.sources[source].lastItem;
		}
	}

	module.exports = { PaginationState };
});

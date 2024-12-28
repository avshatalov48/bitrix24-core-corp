/**
 * @module selector/widget/entity/tree-selectors/directory-selector-entity
 */
jn.define('selector/widget/entity/tree-selectors/directory-selector-entity', (require, exports, module) => {
	const { DirectoryProvider } = require('selector/providers/tree-providers/directory-provider');
	const { BaseSelectorEntity } = require('selector/widget/entity');

	/**
	 * @class DirectorySelectorEntity
	 */
	class DirectorySelectorEntity extends BaseSelectorEntity
	{
		static make(props)
		{
			return super.make(
				DirectorySelectorEntity.getDefaultProps(props),
			);
		}

		static getDefaultProps(props)
		{
			return {
				...props,
				shouldRenderHiddenItemsInList: false,
				provider: {
					...props.provider,
					class: DirectoryProvider,
				},
				sectionTitles: {
					recent: '',
					search: '',
				},
			};
		}

		/**
		 * @returns {string|string[]}
		 */
		static getEntityId()
		{
			return 'file';
		}

		static getSearchFields()
		{
			return null;
		}

		getPreselectedItems()
		{
			return [];
		}

		getRecentItemsLimit()
		{
			return null;
		}

		shouldClearUnavailableItems()
		{
			return false;
		}
	}

	module.exports = { DirectorySelectorEntity };
});

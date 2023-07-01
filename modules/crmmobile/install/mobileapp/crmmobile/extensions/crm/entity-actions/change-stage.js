/**
 * @module crm/entity-actions/change-stage
 */
jn.define('crm/entity-actions/change-stage', (require, exports, module) => {
	const { Type } = require('crm/type');
	const { NotifyManager } = require('notify-manager');
	const { CategoryStorage } = require('crm/storage/category');
	const { CategoryListView } = require('crm/category-list-view');

	/**
	 * @function getActionToChangeStage
	 * @returns {Object}
	 */
	const getActionToChangeStage = () => {
		const loadCategoryList = (entityTypeId) => new Promise((resolve) => {
			const categoryList = CategoryStorage.getCategoryList(entityTypeId);
			if (categoryList)
			{
				resolve(categoryList);
			}
			else
			{
				NotifyManager.showLoadingIndicator();

				CategoryStorage.subscribeOnChange(() => {
					NotifyManager.hideLoadingIndicatorWithoutFallback();
					resolve(CategoryStorage.getCategoryList(entityTypeId));
				});
			}
		});

		/**
		 * @param params.title string
		 * @param params.entityTypeId number
		 * @returns {Promise}
		 */
		const onAction = (params) => new Promise((resolve) => {
			const { title, entityTypeId } = params;
			if (!Type.isEntitySupportedById(entityTypeId))
			{
				return Promise.resolve();
			}

			loadCategoryList(entityTypeId).then(({ categories }) => {
				if (!Array.isArray(categories))
				{
					return;
				}

				if (categories.length > 1)
				{
					CategoryListView.open(
						{
							entityTypeId,
							readOnly: true,
							currentCategoryId: null,
							showPreselectedCategory: false,
							showCounters: false,
							showTunnels: false,
							onSelectCategory: (category, categoryListLayout) => {
								categoryListLayout.close(() => resolve(category.id));
							},
						},
						{
							title,
						},
					);
				}
				else
				{
					resolve(categories[0].id);
				}
			});
		});

		return { onAction };
	};

	module.exports = { getActionToChangeStage };
});

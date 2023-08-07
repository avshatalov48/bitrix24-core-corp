/**
 * @module crm/entity-actions/change-stage
 */
jn.define('crm/entity-actions/change-stage', (require, exports, module) => {
	const { Type } = require('crm/type');
	const { NotifyManager } = require('notify-manager');
	const { CategoryStorage } = require('crm/storage/category');

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
		 * @function onAction
		 * @param params.title string
		 * @param params.entityTypeId number
		 * @returns {Promise}
		 */
		const onAction = async (params) => {
			const { title, entityTypeId, layoutWidget } = params;
			let selectedCategoryId = null;

			if (!Type.isEntitySupportedById(entityTypeId))
			{
				return null;
			}

			const { categories } = await loadCategoryList(entityTypeId);

			if (!Array.isArray(categories) || categories.length === 0)
			{
				return null;
			}

			if (categories.length === 1)
			{
				return categories[0].id;
			}

			const { CategoryListView } = await requireLazy('crm:category-list-view');

			const getSelectedCategoryId = () => new Promise((resolve) => {
				CategoryListView.open(
					{
						entityTypeId,
						readOnly: true,
						currentCategoryId: null,
						showPreselectedCategory: false,
						showCounters: false,
						showTunnels: false,
						onSelectCategory: (category, categoryListLayout) => {
							selectedCategoryId = category.id;
							categoryListLayout.close();
						},
					},
					{ title },
					layoutWidget,
				).then((layoutListView) => {
					layoutListView.setListener((eventName) => {
						if (eventName === 'onViewRemoved')
						{
							resolve(selectedCategoryId);
						}
					});
				}).catch(console.error);
			});

			selectedCategoryId = await getSelectedCategoryId();

			return selectedCategoryId;
		};

		return { onAction };
	};

	module.exports = { getActionToChangeStage };
});

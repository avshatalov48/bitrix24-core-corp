/**
 * @module crm/entity-actions/change-stage
 */
jn.define('crm/entity-actions/change-stage', (require, exports, module) => {
	const { Type } = require('crm/type');
	const { NotifyManager } = require('notify-manager');
	const store = require('statemanager/redux/store');
	const { fetchCrmKanbanList } = require('crm/statemanager/redux/slices/kanban-settings');

	/**
	 * @function getActionToChangeStage
	 * @returns {Object}
	 */
	const getActionToChangeStage = () => {
		const loadCategoryList = (entityTypeId) => new Promise((resolve) => {
			NotifyManager.showLoadingIndicator();
			store.dispatch(fetchCrmKanbanList({
				entityTypeId,
			})).then((response) => {
				resolve(response.payload.data);
				NotifyManager.hideLoadingIndicatorWithoutFallback();
			}).catch((error) => {
				NotifyManager.hideLoadingIndicatorWithoutFallback();
				NotifyManager.showDefaultError();
			});
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

			const { CategoryListView } = await requireLazy('crm:category-list-view').catch(console.error);

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
							selectedCategoryId = category.categoryId;
							categoryListLayout.close();
						},
						onViewHidden: () => {
							resolve(selectedCategoryId);
						},
					},
					{ title },
					layoutWidget,
				).catch(console.error);
			});

			selectedCategoryId = await getSelectedCategoryId();

			return selectedCategoryId;
		};

		return { onAction };
	};

	module.exports = { getActionToChangeStage };
});

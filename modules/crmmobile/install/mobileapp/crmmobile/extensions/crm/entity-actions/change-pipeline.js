/**
 * @module crm/entity-actions/change-pipeline
 */
jn.define('crm/entity-actions/change-pipeline', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Alert } = require('alert');
	const { Type } = require('crm/type');
	const { getPublicErrors } = require('crm/entity-actions/public-errors');
	const { openCategoryListView } = require('crm/category-list-view/open');

	const AJAX_ACTION = 'crmmobile.Kanban.changeCategory';

	/**
	 * @function getActionToChangePipeline
	 * @param imageColor string
	 * @returns {Object}
	 */
	const getActionToChangePipeline = (imageColor = '#6a737f') => {
		const id = 'changeCategory';

		const title = Loc.getMessage('M_CRM_ENTITY_ACTION_CHANGE_CATEGORY');

		const svgIcon = `<svg width="24" height="26" viewBox="0 0 24 26" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M2.59368 5.97754H21.4063C21.8918 5.97754 22.2854 6.35347 22.2854 6.81721C22.2854 6.91121 22.2689 7.00454 22.2365 7.09331L21.6681 8.65272C21.5451 8.99017 21.212 9.2163 20.8379 9.2163H3.1575C2.7826 9.2163 2.44895 8.9892 2.32657 8.65073L1.76275 7.09132C1.60427 6.65298 1.84781 6.17492 2.30671 6.02354C2.39903 5.99308 2.49601 5.97754 2.59368 5.97754ZM5.69222 11.9644H18.3078C18.7933 11.9644 19.1868 12.3403 19.1868 12.8041C19.1868 12.9048 19.1679 13.0046 19.1309 13.0989L18.5187 14.6583C18.3901 14.986 18.0621 15.2032 17.6956 15.2032H6.27181C5.89968 15.2032 5.56788 14.9794 5.44338 14.6444L4.86379 13.085C4.70137 12.648 4.94059 12.1679 5.39812 12.0128C5.49255 11.9808 5.59201 11.9644 5.69222 11.9644ZM9.60888 17.97H14.3911C14.8766 17.97 15.2702 18.346 15.2702 18.8097C15.2702 18.8914 15.2577 18.9727 15.2331 19.0509L14.7435 20.6103C14.632 20.9655 14.2897 21.2088 13.9015 21.2088H10.1515C9.77289 21.2088 9.43678 20.9772 9.31734 20.634L8.77468 19.0746C8.62154 18.6345 8.87088 18.1592 9.33159 18.0129C9.42102 17.9845 9.51464 17.97 9.60888 17.97Z" fill="${imageColor}"/></svg>`;

		const iconUrl = '/bitrix/mobileapp/crmmobile/extensions/crm/entity-actions/images/change_pipeline.png';

		/**
		 * @param params.itemId number
		 * @param params.categoryId number
		 * @param params.entityTypeId number
		 * @param params.parentWidget string
		 * @param params.needConfirm boolean
		 * @returns {Promise}
		 */
		const onAction = (params) => new Promise((resolve, reject) => {
			const { categoryId, itemId, parentWidget, entityTypeId, needConfirm } = params;

			if (!Type.existsById(entityTypeId))
			{
				return Promise.resolve();
			}

			return openCategoryListView({
				categoryId,
				entityTypeId,
				needConfirm,
				parentWidget,
				onChangeCategory: ({ category, categoryListLayout }) => {
					categoryListLayout.setListener((eventName) => {
						if (eventName === 'onViewHidden')
						{
							const selectedCategoryId = Number(category.id);
							if (!itemId && !categoryId)
							{
								resolve({ categoryId: selectedCategoryId });
								return;
							}

							const ids = [Number(itemId)];
							const changeData = {
								ids,
								entityType: Type.resolveNameById(entityTypeId),
								categoryId: selectedCategoryId,
							};

							BX.ajax.runAction(AJAX_ACTION, { data: changeData })
								.then(({ errors }) => {
									if (errors.length > 0)
									{
										alert(errors);
										reject(errors);
									}
									else
									{
										resolve(changeData);
									}
								})
								.catch(console.error);
						}
					});
					categoryListLayout.close();
				},
			});
		});

		return { id, title, svgIcon, iconUrl, onAction };
	};

	/**
	 * @param errors
	 */
	const alert = (errors) => {
		Alert.alert(
			Loc.getMessage('M_CRM_ENTITY_ACTION_ERROR_ON_CHANGE'),
			getPublicErrors(errors) || Loc.getMessage('M_CRM_ENTITY_ACTION_DEFAULT_ERROR'),
		);
	};

	module.exports = { getActionToChangePipeline };
});

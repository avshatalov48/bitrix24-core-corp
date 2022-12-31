/**
 * @module crm/category-list-view/open
 */
jn.define('crm/category-list-view/open', (require, exports, module) => {

	const { Alert } = require('alert');
	const { Loc } = require('loc');
	const { CategoryListView } = require('crm/category-list-view');

	/**
	 * @param {Number} props.entityTypeId
	 * @param {Number} props.categoryId
	 * @param {Function} props.onChangeCategory
	 * @param {Object} props.parentWidget
	 */
	const openCategoryListView = (props) => {
		const { entityTypeId, categoryId: currentCategoryId, onChangeCategory, parentWidget } = props;

		return CategoryListView.open(
			{
				entityTypeId,
				currentCategoryId,
				readOnly: true,
				showCounters: false,
				showTunnels: false,
				onSelectCategory: (category, categoryListLayout) => {
					if (parseInt(currentCategoryId) !== parseInt(category.id))
					{
						return askToChangeCategory().then(() => {
							onChangeCategory({ category, categoryListLayout });
						});
					}

					categoryListLayout.close();

					return Promise.resolve();
				},
			},
			{
				title: Loc.getMessage('M_CRM_CATEGORY_LIST_ACTION_CHANGE_CATEGORY_TITLE'),
			},
			parentWidget,
		);
	};

	const askToChangeCategory = () => {
		return new Promise((resolve, reject) => {
			Alert.confirm(
				Loc.getMessage('M_CRM_CATEGORY_LIST_ACTION_CHANGE_CATEGORY_ALERT_TITLE'),
				Loc.getMessage('M_CRM_CATEGORY_LIST_ACTION_CHANGE_CATEGORY_ALERT_TEXT'),
				[
					{
						text: Loc.getMessage('M_CRM_CATEGORY_LIST_ACTION_CHANGE_CATEGORY_ALERT_OK'),
						onPress: resolve,
					},
					{
						type: 'cancel',
						onPress: reject,
					},
				],
			);
		});
	};

	module.exports = { openCategoryListView };
});
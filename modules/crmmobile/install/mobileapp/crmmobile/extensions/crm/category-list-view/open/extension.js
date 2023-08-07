/**
 * @module crm/category-list-view/open
 */
jn.define('crm/category-list-view/open', (require, exports, module) => {
	const { Alert } = require('alert');
	const { Loc } = require('loc');

	/**
	 * @param {Number} props.entityTypeId
	 * @param {Object} props.parentWidget
	 * @param {Boolean} props.readOnly
	 * @param {Function} props.onChangeCategory
	 * @param {Number} props.categoryId
	 */
	const openCategoryListView = async (props) => {
		const {
			entityTypeId,
			parentWidget,
			readOnly = true,
			needConfirm = true,
			onChangeCategory,
			categoryId: currentCategoryId,
		} = props;

		const { CategoryListView } = await requireLazy('crm:category-list-view');

		return CategoryListView.open(
			{
				entityTypeId,
				currentCategoryId,
				readOnly,
				showCounters: false,
				showTunnels: false,
				onSelectCategory: (category, categoryListLayout) => {
					if (parseInt(currentCategoryId, 10) !== parseInt(category.id, 10))
					{
						if (needConfirm)
						{
							return askToChangeCategory().then(() => {
								onChangeCategory({ category, categoryListLayout });
							});
						}

						onChangeCategory({ category, categoryListLayout });
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

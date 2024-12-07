/**
 * @bxjs_lang_path extension.php
 * @module crm/entity-tab/sort
 */

jn.define('crm/entity-tab/sort', (require, exports, module) => {
	const { Loc } = require('loc');
	const { getEntityMessage } = require('crm/loc');
	const { Icon } = require('ui-system/blocks/icon');

	/**
	 * @class TypeSort
	 */
	const TypeSort = {
		Id: 'BY_ID',
		LastActivityTime: 'BY_LAST_ACTIVITY_TIME',
	};

	class ItemsSortManager
	{
		static createFromEntityTypeObject(entityTypeObject, saveSortTypePath)
		{
			const data = { saveSortTypePath };
			if (entityTypeObject)
			{
				data.sortType = entityTypeObject.data.sortType;
				data.entityTypeId = entityTypeObject.id;
				data.isLastActivityEnabled = entityTypeObject.isLastActivityEnabled;
				data.categoryId = entityTypeObject.data.currentCategoryId;
			}
			else
			{
				data.sortType = null;
				data.entityTypeId = null;
				data.isLastActivityEnabled = false;
				data.categoryId = 0;
			}

			return new ItemsSortManager(data);
		}

		constructor({ entityTypeId, categoryId, sortType, saveSortTypePath, isLastActivityEnabled })
		{
			this.entityTypeId = entityTypeId;
			this.categoryId = categoryId;
			this.typeSort = sortType || TypeSort.LastActivityTime;
			this.savePath = saveSortTypePath;
			this.isLastActivityEnabled = isLastActivityEnabled;
		}

		getSortType()
		{
			return this.typeSort;
		}

		getSortMenuAction(callback)
		{
			return {
				id: 'kanban-sort-toggle',
				title: Loc.getMessage('M_CRM_ENTITY_TAB_SORT_TOGGLE_TEXT'),
				checked: (this.typeSort === TypeSort.LastActivityTime),
				sectionCode: 'action',
				icon: Icon.PING,
				onItemSelected: this.onItemSelected.bind(this, callback),
			};
		}

		onItemSelected(callback)
		{
			this.setSortType(callback);
		}

		setSortType(callback)
		{
			const type = (
				this.getSortType() === TypeSort.LastActivityTime
					? TypeSort.Id
					: TypeSort.LastActivityTime
			);

			const data = {
				entityTypeId: this.entityTypeId,
				categoryId: this.categoryId,
				type,
			};

			BX.ajax
				.runAction(this.savePath, { data })
				.then((response) => {
					if (response.errors.length > 0)
					{
						console.error(response);

						return;
					}

					this.showNotify(type);
					this.typeSort = type;

					if (callback)
					{
						callback(type);
					}
				})
				.catch((response) => console.error(response))
			;
		}

		showNotify(type)
		{
			let message = 'M_CRM_ENTITY_TAB_SORT_BY_ID_MESSAGE';
			let title = 'M_CRM_ENTITY_TAB_SORT_BY_ID_TITLE';

			if (type === TypeSort.LastActivityTime)
			{
				message = 'M_CRM_ENTITY_TAB_SORT_BY_LAST_ACTIVITY_MESSAGE';
				title = 'M_CRM_ENTITY_TAB_SORT_BY_LAST_ACTIVITY_TITLE';
			}

			Notify.showUniqueMessage(
				getEntityMessage(message, this.entityTypeId),
				Loc.getMessage(title),
				{ time: 5 },
			);
		}
	}

	module.exports = { TypeSort, ItemsSortManager };
});

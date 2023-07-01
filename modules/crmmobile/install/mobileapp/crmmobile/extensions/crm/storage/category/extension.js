/**
 * @module crm/storage/category
 */
jn.define('crm/storage/category', (require, exports, module) => {
	const { merge, mergeImmutable } = require('utils/object');
	const { Type } = require('crm/type');
	const { CategoryAjax } = require('crm/ajax');
	const { BaseStorage } = require('crm/storage/base');

	const CATEGORIES_FOLDER = 'categories';
	const ACTION_GET_CATEGORY = 'get';
	const ACTION_GET_CATEGORIES = 'getList';

	/**
	 * @class CategoryStorage
	 */
	class CategoryStorage extends BaseStorage
	{
		/**
		 * @return {CategoryAjax}
		 */
		getAjax()
		{
			return CategoryAjax;
		}

		getEventNamespace()
		{
			return 'Crm.CategoryStorage';
		}

		getStorageKey()
		{
			return 'category';
		}

		getPathToCategoryList(entityTypeId)
		{
			return this.getPathTo(ACTION_GET_CATEGORIES, entityTypeId);
		}

		getPathToCategoryInCategoryList(entityTypeId)
		{
			return this.getPathTo(ACTION_GET_CATEGORIES, entityTypeId, CATEGORIES_FOLDER);
		}

		getPathToCategory(entityTypeId, categoryId)
		{
			return this.getPathTo(ACTION_GET_CATEGORY, entityTypeId, categoryId);
		}

		/**
		 * @param {Number} entityTypeId
		 * @return {Object|null}
		 */
		getCategoryList(entityTypeId)
		{
			if (!Type.existsById(entityTypeId))
			{
				throw new Error(`Wrong entity type id {${entityTypeId}}.`);
			}

			const pathToCategoryList = this.getPathToCategoryList(entityTypeId);
			if (this.cacheExpired(pathToCategoryList))
			{
				// fake timeout to delay fetching heavy getList request
				setTimeout(
					() => this.fetch(pathToCategoryList, ACTION_GET_CATEGORIES, { entityTypeId }),
					50,
				);
			}

			return this.getDataValue(pathToCategoryList);
		}

		/**
		 * @param {Number} entityTypeId
		 * @param {Number} categoryId
		 * @return {Object|null}
		 */
		getCategory(entityTypeId, categoryId)
		{
			if (!Type.existsById(entityTypeId))
			{
				throw new Error(`Wrong entity type id {${entityTypeId}}.`);
			}

			categoryId = parseInt(categoryId, 10);
			if (!Number.isInteger(categoryId))
			{
				throw new TypeError(`Wrong category id {${categoryId}}.`);
			}

			const pathToCategory = this.getPathToCategory(entityTypeId, categoryId);
			if (this.cacheExpired(pathToCategory))
			{
				this.fetch(pathToCategory, ACTION_GET_CATEGORY, { entityTypeId, categoryId });
			}

			return this.getDataValue(pathToCategory);
			// ToDo removed categories list cache usages because of absence of stages in it and other data
			// // if category cache expires - we can look for category list cache
			// const pathToCategoryList = this.getPathToCategoryList(entityTypeId);
			// if (!this.cacheExpired(pathToCategoryList))
			// {
			// 	const category = this.findCategoryInCategoryList(entityTypeId, categoryId);
			// 	if (category)
			// 	{
			// 		return category;
			// 	}
			// }
			//
			// this.fetch(pathToCategory, ACTION_GET_CATEGORY, { entityTypeId, categoryId });
			//
			// // try to find most recent data in both caches
			// return this.findMostRecentCategory(entityTypeId, categoryId);
		}

		setCategory(entityTypeId, category)
		{
			const pathToCategory = this.getPathToCategory(entityTypeId, category.id);
			this.updateDataInStorage(pathToCategory, category);
		}

		/**
		 * @param {Number} entityTypeId
		 * @param {Object} fields
		 * @return {Promise<Object, void>}
		 */
		createCategory(entityTypeId, fields)
		{
			return new Promise((resolve, reject) => {
				this.getAjax()
					.create(entityTypeId, fields)
					.then((response) => {
						if (response.errors && response.errors.length > 0)
						{
							reject(response);
							return;
						}

						const id = response.data;
						if (Number.isInteger(id))
						{
							// clear list storage
							const pathToList = this.getPathToCategoryList(entityTypeId);
							this.clearTtlValue(pathToList);

							resolve(id);
						}
					});
			});
		}

		/**
		 * @param {Number} entityTypeId
		 * @param {Number} categoryId
		 * @param {Object} fields
		 * @return {Promise<Object, void>}
		 */
		updateCategory(entityTypeId, categoryId, fields)
		{
			return new Promise((resolve, reject) => {
				this
					.getAjax()
					.update(entityTypeId, categoryId, fields)
					.then((response) => {
						if (response.errors && response.errors.length > 0)
						{
							reject(response);
							return;
						}

						// clear list storage
						const pathToList = this.getPathToCategoryList(entityTypeId);
						this.clearTtlValue(pathToList);

						const pathToUpdatedInList = this.getPathToCategoryInCategoryList(entityTypeId);
						const listCategoryData = this.getDataValue(pathToUpdatedInList, []) || [];

						// modify category data in list
						const categoryDataInList = listCategoryData.find((category) => category.id === categoryId);
						if (categoryDataInList)
						{
							merge(categoryDataInList, fields);
						}

						// save modified list in storage
						this.updateDataInStorage(pathToUpdatedInList, listCategoryData, true);

						const pathToUpdated = this.getPathToCategory(entityTypeId, categoryId);
						this.clearTtlValue(pathToUpdated);

						const categoryData = this.getDataValue(pathToUpdated, {}) || {};
						this.updateDataInStorage(pathToUpdated, mergeImmutable(categoryData, fields), true);

						// clear cache for tunnels with changed category
						listCategoryData.forEach((category, index) => {
							category.tunnels
								.filter((tunnel) => tunnel.dstCategoryId === categoryId)
								.forEach((tunnel) => {
									const pathToUpdated = this.getPathToCategory(entityTypeId, tunnel.srcCategoryId);
									this.clearTtlValue(pathToUpdated);
								})
							;
						});

						resolve();
					})
				;
			});
		}

		/**
		 * @param {Number} entityTypeId
		 * @param {Number} categoryId
		 * @return {Promise<Object, void>}
		 */
		deleteCategory(entityTypeId, categoryId)
		{
			return new Promise((resolve, reject) => {
				this
					.getAjax()
					.delete(entityTypeId, categoryId)
					.then((response) => {
						if (response.errors && response.errors.length > 0)
						{
							reject(response);
							return;
						}

						const pathToUpdated = this.getPathToCategory(entityTypeId, categoryId);
						this.clearTtlValue(pathToUpdated);

						const pathToList = this.getPathToCategoryList(entityTypeId);
						this.clearTtlValue(pathToList);

						const pathToUpdatedInList = this.getPathToCategoryInCategoryList(entityTypeId);
						const listCategoryData = this.getDataValue(pathToUpdatedInList, []) || [];

						// clear cache for tunnels with deleted category
						listCategoryData.forEach((category, index) => {
							category.tunnels
								.filter((tunnel) => tunnel.dstCategoryId === categoryId)
								.forEach((tunnel) => {
									const pathToUpdated = this.getPathToCategory(entityTypeId, tunnel.srcCategoryId);
									this.clearTtlValue(pathToUpdated);
								})
							;
						});

						resolve();
					})
				;
			});
		}

		/**
		 * @param {Number} entityTypeId
		 * @param {Number} categoryId
		 * @return {Object|null}
		 */
		findCategoryInCategoryList(entityTypeId, categoryId)
		{
			const pathToCategoryList = this.getPathToCategoryList(entityTypeId);
			const categoryList = this.getDataValue(pathToCategoryList);
			if (categoryList)
			{
				const categories = categoryList[CATEGORIES_FOLDER];
				if (Array.isArray(categories))
				{
					const category = categories.find((category) => category.id === categoryId);
					if (category)
					{
						return category;
					}
				}
			}

			return null;
		}

		/**
		 * @param {Number} entityTypeId
		 * @param {Number} categoryId
		 * @return {Object|null}
		 */
		findMostRecentCategory(entityTypeId, categoryId)
		{
			const pathToCategory = this.getPathToCategory(entityTypeId, categoryId);
			const categoryData = this.getDataValue(pathToCategory);
			const categoryListData = this.findCategoryInCategoryList(entityTypeId, categoryId);

			if (categoryData && categoryListData)
			{
				const categoryTtl = this.getTtlValue(pathToCategory);
				const pathToCategoryList = this.getPathToCategoryList(entityTypeId);
				const categoryListTtl = this.getTtlValue(pathToCategoryList);

				return categoryTtl > categoryListTtl ? categoryData : categoryListData;
			}

			if (categoryData)
			{
				return categoryData;
			}

			return categoryListData;
		}
	}

	module.exports = { CategoryStorage: new CategoryStorage() };
});

/**
 * @module crm/storage/stage
 */
jn.define('crm/storage/stage', (require, exports, module) => {
	const { get } = require('utils/object');
	const { StageAjax } = require('crm/ajax');
	const { CategoryStorage } = require('crm/storage/category');

	/**
	 * @class StageStorage
	 * @extends CategoryStorage
	 */
	class StageStorage extends CategoryStorage.constructor
	{
		/**
		 * @return {StageAjax}
		 */
		getAjax()
		{
			return StageAjax;
		}

		/**
		 * @param {Number} entityTypeId
		 * @param {Number} categoryId
		 * @param {Object} fields
		 * @return {Promise<Object, void>}
		 */
		createStage(entityTypeId, categoryId, fields)
		{
			return new Promise((resolve, reject) => {
				this.getAjax()
					.create(entityTypeId, categoryId, fields)
					.then((response) => {
						if (response.errors && response.errors.length > 0)
						{
							reject(response);

							return;
						}

						const { data: stage } = response;

						const pathToList = this.getPathToCategoryList(entityTypeId);
						this.clearTtlValue(pathToList);

						const pathToUpdatedCategory = this.getPathToCategory(entityTypeId, categoryId);
						this.clearTtlValue(pathToUpdatedCategory);

						let key = 'processStages';

						if (stage.semantics === 'S')
						{
							key = 'successStages';
						}
						else if (stage.semantics === 'F')
						{
							key = 'failedStages';
						}

						const updatedCategory = this.getDataValue(pathToUpdatedCategory, []);

						const stagesListToUpdate = get(updatedCategory, key, []);
						stagesListToUpdate.push(stage);

						// save modified list in storage
						this.updateDataInStorage(pathToUpdatedCategory, updatedCategory);

						resolve(stage);
					});
			});
		}

		/**
		 * @param {Number} entityTypeId
		 * @param {Number} categoryId
		 * @param {Object} fields
		 * @return {Promise<Object, void>}
		 */
		updateStage(entityTypeId, categoryId, fields)
		{
			return new Promise((resolve, reject) => {
				this
					.getAjax()
					.update(entityTypeId, fields)
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

						resolve();
					})
				;
			});
		}

		/**
		 * @param {Number} entityTypeId
		 * @param {Number} categoryId
		 * @param {Number} statusId
		 * @return {Promise<Object, void>}
		 */
		deleteStage(entityTypeId, categoryId, statusId)
		{
			return new Promise((resolve, reject) => {
				this
					.getAjax()
					.delete(entityTypeId, statusId)
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

						resolve();
					})
				;
			});
		}
	}

	module.exports = { StageStorage: new StageStorage() };
});

/**
 * @module disk/statemanager/redux/slices/files/model/file
 */
jn.define('disk/statemanager/redux/slices/files/model/file', (require, exports, module) => {
	const { Type } = require('type');

	class FileModel
	{
		/**
		 * Method maps fields from disk.api responses module to redux store.
		 *
		 * @public
		 * @param {object} sourceServerFile
		 * @returns {FileReduxModel}
		 */
		static prepareReduxFileFromServerFile(sourceServerFile)
		{
			const preparedFile = { ...sourceServerFile };

			if (Type.isUndefined(preparedFile.id))
			{
				throw new TypeError(`id for file ${JSON.stringify(preparedFile)} is not defined`);
			}

			preparedFile.id = Number(preparedFile.id);
			if (preparedFile.trackedId)
			{
				preparedFile.trackedId = Number(preparedFile.trackedId);
			}

			preparedFile.typeFile = Number(preparedFile.typeFile);
			preparedFile.isFolder = !preparedFile.typeFile;
			preparedFile.createdBy = Number(preparedFile.createdBy);
			preparedFile.updatedBy = Number(preparedFile.updatedBy);
			preparedFile.updateTime = (new Date(preparedFile.updateTime)).getTime() / 1000;
			preparedFile.createTime = (new Date(preparedFile.createTime)).getTime() / 1000;

			if (preparedFile.links?.external)
			{
				preparedFile.links.external.deathTime = preparedFile.links.external?.deathTime
					? (new Date(preparedFile.links.external.deathTime) - Date.now()) / 1000
					: null;
			}

			return preparedFile;
		}
	}

	module.exports = { FileModel };
});

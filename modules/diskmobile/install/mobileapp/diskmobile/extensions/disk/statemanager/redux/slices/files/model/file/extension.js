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
			preparedFile.typeFile = Number(preparedFile.typeFile);
			preparedFile.isFolder = !preparedFile.typeFile;
			preparedFile.createdBy = Number(preparedFile.createdBy);
			preparedFile.updatedBy = Number(preparedFile.updatedBy);
			preparedFile.updateTime = (new Date(preparedFile.updateTime)).getTime() / 1000;
			preparedFile.createTime = (new Date(preparedFile.createTime)).getTime() / 1000;

			return preparedFile;
		}
	}

	module.exports = { FileModel };
});

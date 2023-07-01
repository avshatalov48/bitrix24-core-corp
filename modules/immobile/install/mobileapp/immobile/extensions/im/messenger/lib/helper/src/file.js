/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/lib/helper/file
 */
jn.define('im/messenger/lib/helper/file', (require, exports, module) => {

	const { Loc } = require('loc');

	function formatFileSize(fileSize)
	{
		if (!fileSize || fileSize <= 0)
		{
			fileSize = 0;
		}

		const sizes = ['BYTE', 'KB', 'MB', 'GB', 'TB'];
		const KILOBYTE_SIZE = 1024;

		let position = 0;
		while (fileSize >= KILOBYTE_SIZE && position < sizes.length - 1)
		{
			fileSize /= KILOBYTE_SIZE;
			position++;
		}

		const phrase = Loc.getMessage(`IMMOBILE_HELPER_FILE_SIZE_${sizes[position]}`);
		const roundedSize = Math.round(fileSize);

		return `${roundedSize} ${phrase}`;
	}

	function getShortFileName(fileName, maxLength)
	{
		if (!fileName || fileName.length < maxLength)
		{
			return fileName;
		}

		const DOT_LENGTH = 1;
		const SYMBOLS_TO_TAKE_BEFORE_EXTENSION = 10;

		const extension = getFileExtension(fileName);
		const symbolsToTakeFromEnd = extension.length + DOT_LENGTH + SYMBOLS_TO_TAKE_BEFORE_EXTENSION;
		const secondPart = fileName.slice(-symbolsToTakeFromEnd);
		const firstPart = fileName.slice(0, maxLength - secondPart.length - DOT_LENGTH * 3);

		return `${firstPart.trim()}...${secondPart.trim()}`;
	}

	function getFileExtension(fileName)
	{
		return fileName.split('.').splice(-1)[0];
	}

	module.exports = {
		formatFileSize,
		getShortFileName,
		getFileExtension,
	};
});

import { Type } from 'main.core';

export const processCheckListFileIds = (fileIds) => {
	if (!Array.isArray(fileIds))
	{
		return [];
	}

	return fileIds.reduce((result, item) => {
		if (Type.isObjectLike(item) && 'id' in item && 'fileId' in item)
		{
			result.push({
				id: item.id,
				fileId: item.fileId,
			});
		}
		else if (Type.isString(item) && item.startsWith('n'))
		{
			result.push({
				id: item,
				fileId: item,
			});
		}

		return result;
	}, []);
};

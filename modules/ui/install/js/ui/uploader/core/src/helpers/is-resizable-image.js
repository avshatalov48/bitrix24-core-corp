import { Type } from 'main.core';
import getFileExtension from './get-file-extension';

const imageExtensions: string[] = ['jpg', 'bmp', 'jpeg', 'jpe', 'gif', 'png', 'webp'];

const isResizableImage = (file: File | string, mimeType: string = null): boolean => {
	const fileName: string = Type.isFile(file) ? file.name : file;
	const type: string = Type.isFile(file) ? file.type : mimeType;
	const extension: string = getFileExtension(fileName).toLowerCase();

	if (imageExtensions.includes(extension))
	{
		if (type === null || /^image\/[a-z0-9.-]+$/i.test(type))
		{
			return true;
		}
	}

	return false;
};

export default isResizableImage;

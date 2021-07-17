import {Cache, Tag, Text, Type, Runtime} from 'main.core';
import File from './file';

export default class Image extends File
{
	static checkForPaternity(fileData)
	{
		return fileData['preview'] !== undefined;
	}

	constructor(fileData, container, options) {
		super(fileData, container, options);
		BitrixMobile.LazyLoad.registerImages([this.id], (typeof oMSL != 'undefined' ? oMSL.checkVisibility : false));
	}
}
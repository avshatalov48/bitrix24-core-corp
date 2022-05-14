import * as BaseField from '../base/controller';
import * as Item from './item';
import * as Component from './component';
import {DefaultOptions} from "../list/controller";

type Options = BaseField.Options;

class Controller extends BaseField.Controller
{
	contentTypes: Array<string> = [];

	static type(): string
	{
		return 'file';
	}

	static component()
	{
		return Component.FieldFile;
	}

	static createItem(options: Item.Options): Item.Item
	{
		return new Item.Item(options);
	}

	constructor(options: Options = DefaultOptions)
	{
		super(options);
		this.contentTypes = (Array.isArray(options.contentTypes) ? options.contentTypes: []) || [];
	}

	getAcceptTypes(): string
	{
		return this.contentTypes
			.map(item => {
				switch (item)
				{
					case 'image/*':
						return [
							item,
							'.jpeg',
							'.png',
							'.ico',
						];
					case 'video/*':
						return [
							item,
							'.mp4',
							'.avi',
						];
					case 'audio/*':
						return [
							item,
							'.mp3',
							'.ogg',
							'.wav',
						];
					case 'x-bx/doc':
						return [
							'application/pdf',
							'application/msword',
							'text/csv',
							'text/plain',
							'application/vnd.*',
							'.pdf',
							'.doc',
							'.docx',
							'.txt',
							'.ppt',
							'.pptx',
							'.xls',
							'.xlsx',
							'.csv',
							'.vsd',
							'.vsdx',
						].join(',');
					case 'x-bx/arc':
						return [
							'application/zip',
							'application/gzip',
							'application/x-tar',
							'application/x-rar-compressed',
							'.zip',
							'.7z',
							'.tar',
							'.gzip',
							'.rar',
						].join(',');
					default:
						return item;
				}
			})
			.join(',')
		;
	}
}

export {Controller, Options}
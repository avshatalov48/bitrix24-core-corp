import {Event} from 'main.core';
import 'main.imageeditor';
import buildOptions from './internal/build.options';
import getFilename from './internal/get.filename';

export class ImageEditor extends Event.EventEmitter
{
	static edit(options: {image: string, dimensions: {width: number, height: number}})
	{
		const imageEditor = BX.Main.ImageEditor.getInstance();
		const preparedOptions = buildOptions(options);

		return imageEditor
			.edit(preparedOptions)
			.then((file) => {
				file.name = getFilename(options.image);
				return file;
			});
	}
}
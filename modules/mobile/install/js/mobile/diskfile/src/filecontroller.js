import {Type,Event,Runtime,Dom} from "main.core";
import File from "./files/file";
import Audio from "./files/audio";
import Image from "./files/image";

const fileTypeMappings = [
	File,
	Image,
	Audio,
];

export default class FileController
{
	constructor({files, container})
	{
		this.container = container;
		if (Type.isDomNode(this.container))
		{
			this.initFiles(files);
			this.bindInterface();
		}
	}

	initFiles(files: Array)
	{
		if (files && files.length > 0)
		{
			files.forEach((fileData) => {
				let fileTypeClass = File;
				fileTypeMappings.forEach((altFileTypeClass) => {
					if (altFileTypeClass.checkForPaternity(fileData))
					{
						fileTypeClass = altFileTypeClass;
					}
				});
				new fileTypeClass(fileData, this.container);
			});
		}
	}

	bindInterface()
	{
		const moreBlock = this.container.querySelector('.post-item-attached-file-more');
		if (Type.isDomNode(moreBlock))
		{
			Event.bindOnce(moreBlock, 'click', function(){
				this.container
					.querySelectorAll('.post-item-attached-file')
					.forEach((node) => {
						node.classList.remove('post-item-attached-file-hidden');
					});

				moreBlock.parentNode.removeChild(moreBlock);
			}.bind(this));
		}
	}
}
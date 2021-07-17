import Entity from './entity';
import {Loc, Type} from 'main.core';
import {Utils} from 'mobile.utils';

export default class File extends Entity
{
	static counter = 0;
	constructor(data)
	{
		super();
		this.data = data || {};
		this.file = null;

		this.id = ['crm_timeline_file_comment', File.counter++].join('_');
	}

	prepare()
	{
		if (this.data.url && (/^file:\/\//.test(this.data.url) || this.data.type === 'audio/mp4'))
		{
			return Promise.resolve();
		}
		return Promise.reject('Empty file body');
	}

	submit()
	{
		return new Promise((resolve, reject) => {
			const name = (typeof Utils.getUploadFilename === 'function'
					? Utils.getUploadFilename(this.data.name, this.data.type)
					: this.data.name
			);
			let uploadTask = {
				taskId: this.id,
				type: this.data.type,
				mimeType: BX.MobileUtils.getFileMimeType(this.data.type),
				folderId: parseInt(Loc.getMessage('MOBILE_EXT_UTILS_USER_FOLDER_FOR_SAVED_FILES')),
				name: name,
				url: this.data.url,
				previewUrl: (this.data.previewUrl ? this.data.previewUrl : null),
				resize: BX.MobileUtils.getResizeOptions(this.data.type)
			};
			if (this.data.type === 'audio/mp4')
			{
				uploadTask = {
					taskId: this.id,
					type: 'mp3',
					mimeType: 'audio/mp4',
					folderId: parseInt(Loc.getMessage('MOBILE_EXT_UTILS_USER_FOLDER_FOR_SAVED_FILES')),
					name: 'mobile_audio_'+(new Date).toJSON().slice(0, 19).replace('T', '_').split(':').join('-')+'.mp3',
					url: this.data.url,
					previewUrl: null,
				};
			}
			const fileReceive = function({event, data, taskId}) {
				if (taskId !== this.id)
				{
					return;
				}
				if (event === 'onfilecreated')
				{
					BX.removeCustomEvent('onFileUploadStatusChanged', fileReceive);
					if (data.result.status !== 'error')
					{
						const file = data.result.data.file;
						this.file = {
							ID: file.id,
							IMAGE: typeof file.extra.imagePreviewUri != 'undefined' ? file.extra.imagePreviewUri : '',
							NAME: file.name,
							URL: {
								URL: typeof file.extra.downloadUri != 'undefined' ? file.extra.downloadUri : '',
								EXTERNAL: 'YES',
								PREVIEW: typeof file.extra.imagePreviewUri != 'undefined' ? file.extra.imagePreviewUri : ''
							},
							VALUE: 'n' + file.id
						};
						return resolve({data: this.file});
					}
					const errors = [];
					if (Type.isArrayFilled(data.result.errors))
					{
						data.result.errors.forEach(({message, code}) => {
							errors.push(Type.isStringFilled(message) ? message : code);
						});
					}
					data.error = {message: errors.join(''), code: 'Receiver response error.'};
				}
				if (data && data.error)
				{
					BX.removeCustomEvent('onFileUploadStatusChanged', fileReceive);
					const errorMessage = Type.isStringFilled(data.error.message) ? data.error.message : 'File uploading error.';
					const errorCode = Type.isStringFilled(data.error.code) ? data.error.code : 'File uploading code.';
					return reject(new Error(errorMessage, errorCode));
				}
			}.bind(this);
			BX.addCustomEvent('onFileUploadStatusChanged', fileReceive);
			BXMobileApp.onCustomEvent('onFileUploadTaskReceived', {files: [uploadTask]}, true);
		});
	}
	getText()
	{
		if (this.file !== null)
		{
			return '[DISK FILE ID=' + this.file['VALUE'] + ']'
		}
		return 'text';
	}
	getSavedData()
	{
		return this.file;
	}
}
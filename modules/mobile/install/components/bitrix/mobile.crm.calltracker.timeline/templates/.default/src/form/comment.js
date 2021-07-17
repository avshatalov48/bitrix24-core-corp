import {Type} from 'main.core';
import Entity from './entity';
import File from './file';
import CommentSender from './commentsender';
import FileSender from './filesender';
import Backend from './../backend';

export default class Comment extends Entity
{
	constructor(data)
	{
		super();

		this.text = '';
		if (Type.isStringFilled(data.text))
		{
			this.text = data.text;
		}
		this.files = [];
		if (Type.isArrayFilled(data.files))
		{
			this.files = data.files
		}

		if (data.events)
		{
			['start', 'success', 'error', 'finish'].forEach((eventName) => {
				if (data.events[eventName])
				{
					this.subscribe(eventName, data.events[eventName]);
				}
			});
		}

		CommentSender.getInstance().send(this);
	}

	prepare()
	{
		if (Type.isArrayFilled(this.files))
		{
			return new Promise((resolve, reject) =>
			{
				const fileSender = new FileSender(true);
				fileSender.subscribe('success', () => {
					if (!Type.isStringFilled(this.text))
					{
						this.files.forEach((file) => {
							this.text += file.getText();
						});
					}
					resolve();
				});
				fileSender.subscribe('error', () => {
					const errors = [];
					this.files.forEach((file) => {
						if (file.isFailed())
						{
							errors.push(file.error.message);
						}
					});
					reject(new Error(errors.join(' '), 'File upload error.'))
				});

				this.files.forEach((fileData, index) => {
					this.files[index] = new File(fileData);
					fileSender.send(this.files[index]);
				});
			});
		}

		if (Type.isStringFilled(this.text))
		{
			return Promise.resolve();
		}

		return Promise.reject('Empty comment data.');
	}

	submit()
	{
		return Backend.createItem({
			text: this.text,
			files: this.files.map((file) => {
				return file.file['VALUE'];
			}),
		}).catch((result) => {
			const errors = [];
			if (Type.isArrayFilled(result.errors))
			{
				result.errors.forEach(({message, code}) => {
					errors.push(Type.isStringFilled(message) ? message : code);
				});
			}
			else
			{
				errors.push('Receiver response error.');
			}
			return Promise.reject({message: errors.join(''), code: 'Receiver response error.'});
		});
	}
}
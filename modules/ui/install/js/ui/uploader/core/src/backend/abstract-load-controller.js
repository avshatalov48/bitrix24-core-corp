import { Type } from 'main.core';
import { EventEmitter } from 'main.core.events';

import type Server from './server';
import type UploaderFile from '../uploader-file';

export default class AbstractLoadController extends EventEmitter
{
	#server: Server;
	#options: { [key: string]: any };

	constructor(server: Server, options: { [key: string]: any } = {})
	{
		super();
		this.setEventNamespace('BX.UI.Uploader.LoadController');

		this.#server = server;
		this.#options = options;
	}

	getServer(): Server
	{
		return this.#server;
	}

	getOptions(): { [key: string]: any }
	{
		return this.#options;
	}

	getOption(option: string, defaultValue?: any): any
	{
		if (!Type.isUndefined(this.#options[option]))
		{
			return this.#options[option];
		}
		else if (!Type.isUndefined(defaultValue))
		{
			return defaultValue;
		}

		return null;
	}

	load(file: UploaderFile): void
	{
		throw new Error('You must implement load() method.');
	}

	abort(): void
	{
		throw new Error('You must implement abort() method.');
	}
}

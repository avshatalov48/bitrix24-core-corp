import {Type} from 'main.core';
import {Logger} from '../lib/logger';
import {Debug} from '../lib/debug';

export class Program
{
	constructor(name, title, url)
	{
		this.appName = name;
		this.appCode = Program.createCode(this.appName);
		this.desktopCode = Program.createCode(BXDesktopSystem.UserAccount(), BXDesktopSystem.UserOsMark());

		if (url !== '')
		{
			let host;
			try
			{
				host = new URL(url).host;
			}
			catch (err)
			{
				host = url;
			}

			this.host = host;
			this.siteUrl = url;
			this.siteTitle = title;
			this.code = Program.createCode(this.appCode, this.host, this.desktopCode);
			this.pageCode = Program.createCode(this.appCode, this.siteUrl, this.desktopCode);
			this.siteCode = Program.createCode(this.host);
			Logger.log('New browser activity ' + url);

			Debug.log('Started NEW site', `Host: ${this.host}`, `title: ${this.siteTitle}`, `URL: ${this.siteUrl}`);
		}
		else
		{
			this.code = Program.createCode(this.appCode, this.desktopCode);
			Logger.log('New program activity ' + this.appName);

			Debug.log('Started NEW app', `Name: ${this.appName}`);
		}

		this.time = [{ start: new Date(), finish: null }];
	}

	static createCode(...params)
	{
		return BX.md5(params.join(''))
	}
}
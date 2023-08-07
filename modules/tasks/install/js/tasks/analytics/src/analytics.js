import {ajax as Ajax} from 'main.core';

export class Analytics
{
	static instance = null;
	constructor()
	{
		this.action = 'tasks.analytics.hit';
	}

	static getInstance(): this
	{
		if (Analytics.instance === null)
		{
			Analytics.instance = new this();
		}

		return Analytics.instance;
	}

	sendLabel(label: string): void
	{
		Ajax.runAction(this.action, {
			analyticsLabel: {
				scenario: label
			},
		}).then(response => {

		})
	}

	sendData(data = {}): void
	{
		Ajax.runAction(this.action, {
			analyticsLabel: data,
		}).then(response => {

		})
	}
}
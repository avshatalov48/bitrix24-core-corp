import {EntityType} from 'timeman.const';
import {Loc} from 'main.core';

export class Entity
{
	constructor(params = {})
	{
		switch (params.type)
		{
			case EntityType.site:
				this.createSite(params);
				break;

			case EntityType.app:
				this.createApp(params);
				break;

			case EntityType.absence:
				this.createAbsence();
				break;

			case EntityType.unknown:
				this.createUnknown(params);
				break;

			case EntityType.incognito:
				this.createIncognito();
				break;
		}
	}

	createSite(params)
	{
		this.type = EntityType.site;

		let host;
		try
		{
			host = new URL(params.url).host;
		}
		catch (err)
		{
			host = params.url;
		}

		if (host === '')
		{
			let hostFragments = params.url.split('/');

			host = hostFragments[hostFragments.length - 1] !== ''
				? hostFragments[hostFragments.length - 1]
				: params.url;
		}
		else if (host.split('.')[0] === 'www')
		{
			host = host.substring(4);
		}

		this.title = host.toString();
		this.siteUrl = params.url.toString();
		this.siteTitle = params.title.toString();
	}

	createApp(params)
	{
		this.type = EntityType.app;
		this.title = params.name.toString();

		if (params.isBitrix24Desktop)
		{
			this.isBitrix24Desktop = params.isBitrix24Desktop;
		}
	}

	createAbsence()
	{
		this.type = EntityType.absence;
		this.title = Loc.getMessage('TIMEMAN_PWT_REPORT_ABSENCE');
	}

	createUnknown(params)
	{
		this.type = EntityType.unknown;
		this.title = Loc.getMessage('TIMEMAN_PWT_REPORT_UNKNOWN');

		this.pureName = params.name;
		this.pureTitle = params.title;
	}

	createIncognito()
	{
		this.type = EntityType.incognito;
		this.title = Loc.getMessage('TIMEMAN_PWT_REPORT_INCOGNITO');
	}
}
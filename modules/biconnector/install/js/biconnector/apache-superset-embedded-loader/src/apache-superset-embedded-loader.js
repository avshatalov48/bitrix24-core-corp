import { Dom, Type, Event } from 'main.core';
import type { LoaderOption } from './loader-option';
import { Switchboard } from './switchboard';

export class ApacheSupersetEmbeddedLoader
{
	static IFRAME_COMMS_MESSAGE_TYPE = '__embedded_comms__';
	static DASHBOARD_UI_FILTER_CONFIG_URL_PARAM_KEY: { [index: string]: any } = {
		visible: 'show_filters',
		expanded: 'expand_filters',
		nativeFiltersKey: 'native_filters_key',
		preselectFilters: 'preselect_filters',
		nativeFilters: 'native_filters',
	};

	#options: LoaderOption;

	communicationsChannel: MessageChannel;
	constructor(options: LoaderOption): void
	{
		this.#options = options;
		this.communicationsChannel = new MessageChannel();
	}

	async embedDashboard(): Promise
	{
		const guestToken = this.#options.fetchGuestToken;
		this.log('embedding');

		const [result]: [Switchboard] = await Promise.all([
			this.mountIframe(),
		]);

		const ourPort = result;

		ourPort.emit('guestToken', { guestToken });

		this.log('sent guest token');

		const getScrollSize = () => ourPort.get('getScrollSize');
		const getDashboardPermalink = (anchor: string) => ourPort.get('getDashboardPermalink', { anchor });
		const getActiveTabs = () => ourPort.get('getActiveTabs');

		return {
			getScrollSize,
			getDashboardPermalink,
			getActiveTabs,
		};
	}

	calculateConfig(): number
	{
		let configNumber = 0;
		const dashboardUiConfig = this.#options.dashboardUiConfig;

		if (!dashboardUiConfig)
		{
			return configNumber;
		}

		if (dashboardUiConfig.hideTitle)
		{
			configNumber += 1;
		}

		if (dashboardUiConfig.hideTab)
		{
			configNumber += 2;
		}

		if (dashboardUiConfig.hideChartControls)
		{
			configNumber += 8;
		}

		return configNumber;
	}

	async mountIframe(): Promise
	{
		return new Promise((resolve) => {
			const iframe = Dom.create('iframe');
			const id = this.#options.id;
			const dashboardConfig = this.#options.dashboardUiConfig ? `?uiConfig=${this.calculateConfig()}` : '';
			const filterConfig = this.#options.dashboardUiConfig?.filters || {};
			const filterConfigKeys = Object.keys(filterConfig);

			let filterConfigUrlParams = '';
			if (filterConfigKeys.length > 0)
			{
				const stringParams = filterConfigKeys
					.map((key) => `${ApacheSupersetEmbeddedLoader.DASHBOARD_UI_FILTER_CONFIG_URL_PARAM_KEY[key]}=${filterConfig[key]}`)
					.join('&')
				;
				filterConfigUrlParams += `&${stringParams}`;
			}

			const supersetDomain = this.#options.supersetDomain;
			const debug = this.#options.debug;

			// set up the iframe's sandbox configuration
			iframe.sandbox.add('allow-same-origin'); // needed for postMessage to work
			iframe.sandbox.add('allow-scripts'); // obviously the iframe needs scripts
			iframe.sandbox.add('allow-presentation'); // for fullscreen charts
			iframe.sandbox.add('allow-downloads'); // for downloading charts as image
			iframe.sandbox.add('allow-forms'); // for forms to submit
			iframe.sandbox.add('allow-popups'); // for exporting charts as csv
			// add these if it turns out we need them:
			// iframe.sandbox.add("allow-top-navigation");

			Event.bind(iframe, 'load', () => {
				const commsChannel = new MessageChannel();
				const ourPort = commsChannel.port1;
				const theirPort = commsChannel.port2;

				// Send one of the message channel ports to the iframe to initialize embedded comms
				// See https://developer.mozilla.org/en-US/docs/Web/API/Window/postMessage
				// we know the content window isn't null because we are in the load event handler.

				iframe.contentWindow.postMessage(
					{
						type: ApacheSupersetEmbeddedLoader.IFRAME_COMMS_MESSAGE_TYPE,
						handshake: 'port transfer',
					},
					supersetDomain,
					[theirPort],
				);
				this.log('sent message channel to the iframe');
				// return our port from the promise

				resolve(new Switchboard({ port: ourPort, name: 'superset-embedded-sdk', debug }));
			});

			iframe.src = `${supersetDomain}/embedded/${id}${dashboardConfig}${filterConfigUrlParams}`;

			if (Type.isDomNode(this.#options.mountPoint))
			{
				Dom.append(iframe, this.#options.mountPoint);
			}

			this.log('placed the iframe');
		});
	}

	log(...info: []): void
	{
		if (this.isDebug())
		{
			console.debug(`[superset-embedded-sdk][dashboard ${this.#options.id}]`, ...info);
		}
	}

	isDebug(): boolean
	{
		return this.#options.debug === true;
	}
}

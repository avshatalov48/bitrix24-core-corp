// @flow

import SyncButton from './controls/syncbutton';
import {EventEmitter} from "main.core.events";
import {GoogleProvider} from "./connectionproviders/googleprovider";
import {Office365Provider} from "./connectionproviders/office365provider";
import {ICloudProvider} from "./connectionproviders/icloudprovider";
import {AndroidProvider} from "./connectionproviders/androidprovider";
import {CaldavConnection} from "./connectionproviders/caldavconnection";
import {CaldavProvider} from "./connectionproviders/caldavprovider";
import {ExchangeProvider} from "./connectionproviders/exchangeprovider";
import {IphoneProvider} from "./connectionproviders/iphoneprovider";
import {MacProvider} from "./connectionproviders/macprovider";
import {OutlookProvider} from "./connectionproviders/outlookprovider";
import {YandexProvider} from "./connectionproviders/yandexprovider";
import SyncStatusPopup from "./controls/syncstatuspopup";
import {Util} from "calendar.util";

type ManagerOptions = {
	wrapper: string,
	syncInfo: Array,
	userId: number,
	syncLinks: Array,
	section: Array,
	portalAddress: string,
	isRuZone: boolean,
	calendarInstance: window.BXEventCalendar.Core,
	isSetSyncCaldavSettings: boolean,
};

export default class Manager extends EventEmitter
{
	status = 'not_connected';
	STATUS_SUCCESS = 'success';
	STATUS_FAILED = 'failed';

	constructor(options)
	{
		super();
		this.setEventNamespace('BX.Calendar.Sync.Manager.Manager');

		this.wrapper = options.wrapper;
		this.syncInfo = options.syncInfo;
		this.userId = options.userId;
		this.syncLinks = options.syncLinks;
		this.sections = options.sections;
		this.portalAddress = options.portalAddress;
		this.isRuZone = options.isRuZone;
		this.calendarInstance = options.calendar;
		this.isSetSyncCaldavSettings = options.isSetSyncCaldavSettings;

		this.init();
		this.subscribeOnEvent();
	}

	subscribeOnEvent()
	{
		EventEmitter.subscribe('BX.Calendar.Sync.Interface.SyncStatusPopup:onRefresh', event =>
		{
			this.refresh(event);
		});

		EventEmitter.subscribe('BX.Calendar.Sync.Interface.InterfaceTemplate:reDrawCalendarGrid', event =>
		{
			this.reDrawCalendarGrid();
		});

		window.addEventListener('message', (event) => {
			if (event.data.title === 'googleOAuthSuccess')
			{
				window.location.reload()
			}
		});
	}

	showSyncButton()
	{
		this.syncButton = SyncButton.createInstance({
			status: this.status,
			wrapper: this.wrapper,
			connectionsProviders: this.connectionsProviders,
			userId: this.userId,
		});
		this.syncButton.show();
	}

	init()
	{
		this.connectionsProviders = {};
		this.webItems = [];
		this.mobileItems = [];
		const yandexConnections = [];
		const caldavConnections = [];

		const syncInfo = this.syncInfo;

		const sectionsByType = this.sortSections();

		for (const key in syncInfo)
		{
			switch (syncInfo[key].type)
			{
				case 'yandex':
					yandexConnections.push({
						syncInfo: syncInfo[key],
						sections: sectionsByType.caldav['caldav' + syncInfo[key].id],
						isRuZone: this.isRuZone,
					});
					break;
				case 'caldav':
					caldavConnections.push({
						syncInfo: syncInfo[key],
						sections: sectionsByType.caldav['caldav' + syncInfo[key].id],
					});
					break;
			}

			this.calculateStatus(syncInfo[key]);
		}

		this.connectionsProviders = {
			google: GoogleProvider.createInstance({
				syncInfo: syncInfo.google || {},
				sections: sectionsByType.google || {},
				syncLink: this.syncLinks.google || null,
				isSetSyncCaldavSettings: this.isSetSyncCaldavSettings,
				mainPanel: true
			}),
			office365: Office365Provider.createInstance({
				syncInfo: syncInfo.office365 || {},
				syncLink: this.syncLinks.office365 || null,
				mainPanel: true
			}),
			icloud: ICloudProvider.createInstance({
				syncInfo: syncInfo.icloud || {},
				mainPanel: true
			}),
			caldav: CaldavProvider.createInstance({
				status: CaldavConnection.calculateStatus(caldavConnections),
				connected: (caldavConnections.length > 0),
				connections: caldavConnections,
			}),
			iphone: IphoneProvider.createInstance({
				syncInfo: syncInfo.iphone,
			}),
			android: AndroidProvider.createInstance({
				syncInfo: syncInfo.android,
			}),
			mac: MacProvider.createInstance({
				syncInfo: syncInfo.mac,
				portalAddress: this.portalAddress,
			}),
		};

		if (this.isRuZone)
		{
			this.connectionsProviders.yandex = YandexProvider.createInstance({
				status: CaldavConnection.calculateStatus(yandexConnections),
				connected: (yandexConnections.length > 0),
				connections: yandexConnections,
			});
		}

		if (!BX.browser.IsMac())
		{
			this.connectionsProviders.outlook = OutlookProvider.createInstance({
				syncInfo: syncInfo.outlook,
				sections: sectionsByType.outlook,
				infoBySections: syncInfo.outlook.infoBySections || {},
			});
		}
		const has = Object.prototype.hasOwnProperty;
		if (has.call(syncInfo, `exchange`))
		{
			this.connectionsProviders.exchange = ExchangeProvider.createInstance({
				syncInfo: syncInfo.exchange,
			});
		}
	}

	calculateStatus(provider)
	{
		if (provider.connected === true)
		{
			if (provider.status === true && this.status !== this.STATUS_FAILED)
			{
				this.status = this.STATUS_SUCCESS;
			}
			else if (provider.status === false)
			{
				this.status = this.STATUS_FAILED;
			}
		}
	}

	sortSections()
	{
		const sections = this.sections;
		const exchangeSections = [];
		const googleSections = [];
		const sectionsByType = {};
		const outlookSections = [];
		sectionsByType.caldav = {};

		sections.forEach(section => {
			if (section.belongsToView() && section.data.OUTLOOK_JS)
			{
				outlookSections.push({
					id: section.id,
					connectURL: section.data.OUTLOOK_JS,
					text: section.name,
				});
			}

			if (section.data['IS_EXCHANGE'] === true)
			{
				exchangeSections.push(section.data);
			}
			else if (
				section.data['GAPI_CALENDAR_ID']
				&& section.data['CAL_DAV_CON']
				&& section.data['EXTERNAL_TYPE'] !== 'local'
			)
			{
				googleSections.push(section.data);
			}
			else if (section.data['CAL_DAV_CON'] && section.data['CAL_DAV_CAL'])
			{
				sectionsByType.caldav['caldav' + section.data['CAL_DAV_CON']] = section.data;
			}
		});

		sectionsByType.google = googleSections;
		sectionsByType.exchange = exchangeSections;
		sectionsByType.outlook = outlookSections;

		return sectionsByType;
	}

	refresh(event)
	{
		const activePopup = event.getTarget();
		BX.ajax.runAction('calendar.api.calendarajax.updateConnection', {
			data: {
				type: 'user',
				requestUid: Util.registerRequestId(),
			}
		}).then((response) => {
			this.syncInfo = response.data;
			this.status = this.STATUS_SUCCESS;
			this.refreshContent(activePopup);
		});
	}

	refreshContent(activePopup = {})
	{
		this.init();
		this.refreshCalendarGrid();
		this.refreshSyncButton();
		this.refreshActivePopup(activePopup);
		this.refreshOpenSliders(activePopup);
	}

	refreshCalendarGrid()
	{
		this.calendarInstance.reload();
	}

	refreshSyncButton()
	{
		this.syncButton.refresh(this.status, this.connectionsProviders);
	}

	refreshActivePopup(activePopup)
	{
		if (activePopup instanceof SyncStatusPopup && activePopup.getId() === 'calendar-syncPanel-status')
		{
			activePopup.refresh(this.getConnections());
		}
		else if (this.syncButton.popup instanceof SyncStatusPopup && this.syncButton.popup.getId() === 'calendar-syncButton-status')
		{
			this.syncButton.popup.refresh(this.getConnections());
		}
	}

	refreshOpenSliders(activePopup = {})
	{
		const openSliders = BX.SidePanel.Instance.getOpenSliders();
		if (openSliders.length > 0)
		{
			openSliders.forEach(slider =>
			{
				if (slider.getUrl() === 'calendar:auxiliary-sync-slider')
				{
					this.refreshMainSlider(this.syncButton.getSyncPanel());
				}
				else if (slider.getUrl().indexOf('calendar:item-sync-') !== -1)
				{
					this.refreshConnectionSlider(slider, activePopup);
				}
			});
		}
	}

	refreshConnectionSlider(slider, activePopup)
	{
		let updatedConnection = undefined;
		const itemInterface = slider.getData().get('itemInterface');
		const connection = slider.getData().get('connection');
		if (connection)
		{
			updatedConnection = this.connectionsProviders[connection.getType()].getConnectionById(connection.getId());
		}

		if (activePopup instanceof SyncStatusPopup && updatedConnection)
		{
			activePopup.refresh([updatedConnection]);
		}

		if (itemInterface && updatedConnection)
		{
			itemInterface.refresh(updatedConnection);
		}

		slider.reload();
	}

	refreshMainSlider(syncPanel)
	{
		syncPanel.refresh(this.status, this.connectionsProviders);
	}

	getConnections()
	{
		const connections = [];
		const items = Object.values(this.connectionsProviders);

		items.forEach(item => {
			const itemConnections = item.getConnections();
			if(itemConnections.length > 0)
			{
				itemConnections.forEach(connection =>
					{
						if (connection.getConnectStatus() === true)
						{
							connections.push(connection);
						}
					}
				)
			}
		});

		return connections;
	}

	reDrawCalendarGrid()
	{
		this.calendarInstance.reload();
	}

	updateSyncStatus(params)
	{
		if (!BX.Calendar.Util.checkRequestId(params.requestUid))
		{
			return;
		}

		for (const connectionName in params.syncInfo)
		{
			if (this.syncInfo[connectionName])
			{
				this.syncInfo[connectionName] = {
					...this.syncInfo[connectionName],
					...params.syncInfo[connectionName]
				};
			}
		}

		this.status = this.STATUS_SUCCESS;
		this.refreshContent();
	}

	addSyncConnection(params)
	{
		for (const connectionName in params.syncInfo)
		{
			if (['yandex', 'caldav', 'google'].includes(params.syncInfo[connectionName].type))
			{
				BX.reload();
			}

			if (BX.Calendar.Util.checkRequestId(params.requestUid))
			{
				if (this.syncInfo[connectionName])
				{
					this.syncInfo[connectionName] = {
						...this.syncInfo[connectionName],
						...params.syncInfo[connectionName]
					};
				}
			}
		}

		this.status = this.STATUS_SUCCESS;
		this.refreshContent();
	}

	deleteSyncConnection(params)
	{
		if (!BX.Calendar.Util.checkRequestId(params.requestUid))
		{
			return;
		}

		for (const connectionName in params.syncInfo)
		{
			if (this.syncInfo[connectionName])
			{
				delete this.syncInfo[connectionName];
			}
		}

		if (this.status !== 'not_connected')
		{
			this.status = this.STATUS_SUCCESS;
		}
		this.refreshContent();
	}

	getProviderById(id)
	{
		let connection = undefined;
		for (const providerName in this.connectionsProviders)
		{
			if (
				!this.connectionsProviders[providerName].connected
				|| !['google', 'caldav', 'yandex'].includes(providerName)
			)
			{
				continue;
			}

			connection = this.connectionsProviders[providerName].getConnectionById(id);
			if (connection)
			{
				return [this.connectionsProviders[providerName], connection];
			}
		}

		return null;
	}
}

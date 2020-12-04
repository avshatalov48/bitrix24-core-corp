// @flow
'use strict';

import SyncButton from "./controls/syncbutton";
import {EventEmitter} from "main.core.events";
import "./css/syncinterface.css"
import {MacProvider} from "./connectionproviders/macprovider"
import {OutlookProvider} from "./connectionproviders/outlookprovider";
import {AndroidProvider} from "./connectionproviders/androidprovider";
import {CaldavProvider} from "./connectionproviders/caldavprovider";
import {ExchangeProvider} from "./connectionproviders/exchangeprovider";
import {GoogleProvider} from "./connectionproviders/googleprovider";
import {IphoneProvider} from "./connectionproviders/iphoneprovider";
import {YandexProvider} from "./connectionproviders/yandexprovider";
import {CaldavConnection} from "./connectionproviders/caldavconnection";


export default class SyncInterfaceManager extends EventEmitter
{
	status = 'not_connected';

	constructor(options)
	{
		super();
		this.setEventNamespace('BX.Calendar.Sync.Interface.SyncInterfaceManager');

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

		EventEmitter.subscribe('BX.Calendar.Sync.Interface.SyncStatusPopup:onRefresh', event => {
			this.refresh(event);
		});

		EventEmitter.subscribe('BX.Calendar.Sync.Interface.InterfaceTemplate:reDrawCalendarGrid', event => {
			this.reDrawCalendarGrid();
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

		for (let key in syncInfo)
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

			if (syncInfo[key].connected === true)
			{
				if (syncInfo[key].status === true && this.status !== 'failed')
				{
					this.status = 'success';
				}
				else if (syncInfo[key].status === false)
				{
					this.status = 'failed';
				}
			}
		}

		this.connectionsProviders = {
			google: GoogleProvider.createInstance({
				syncInfo: syncInfo.google || {},
				sections: sectionsByType.google || {},
				syncLink: this.syncLinks.google || null,
				isSetSyncCaldavSettings: this.isSetSyncCaldavSettings,
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
			else if (section.data['GAPI_CALENDAR_ID'] && section.data['CAL_DAV_CON'])
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
		BX.ajax.runAction('calendar.api.calendarajax.updateConnection', {
			data: {
				type: 'user',
			}
		}).then(response => {
			this.syncInfo = response.data;
			this.init();
			this.calendarInstance.reload();

			this.syncButton.refresh(this.status, this.connectionsProviders);

			//popup refresh
			if (event.getTarget().getId() === 'calendar-syncPanel-status')
			{
				event.getTarget().refresh(this.getConnections());
			}

			const openSliders = BX.SidePanel.Instance.getOpenSliders();
			if (openSliders.length > 0)
			{
				const syncPanel = this.syncButton.getSyncPanel();
				openSliders.forEach(slider => {
					if (slider.getUrl() === 'calendar:sync-slider')
					{
						syncPanel.refresh(this.status, this.connectionsProviders);
						slider.reload();
					}
					else
					{
						const itemInterface = slider.getData().get('itemInterface');
						const connection = slider.getData().get('connection');
						const updatedConnection = this.connectionsProviders[connection.getType()].getConnectionById(connection.getId());
						event.getTarget().refresh([updatedConnection]);
						itemInterface.setConnection(updatedConnection);
						slider.reload();
					}
				});
			}
		});
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
}
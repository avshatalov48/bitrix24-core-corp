import {UI} from 'ui.notification';
import {MenuManager, PopupManager} from "main.popup";
import {Tag, Loc, Type, Dom, Reflection} from "main.core";
import {EventEmitter} from "main.core.events";
import {Loader} from "main.loader";


const namespace = Reflection.namespace('BX.Crm');

export class DedupeAutosearch
{
	constructor()
	{
		this._componentName = '';
		this._componentSignedParams = '';
		this._entityTypeId = 0;
		this._instanceId = '';
		this._internalMergeStatus = '';
		this._mergeCheckerTimeoutId = null;
		this._mergerUrl = '';
		this._dedupeListUrl = '';
		this._execInterval = null;
		this._intervalsList = [];
		this._isDropdownMenuShown = false;
		this._selectedExecInterval = null;
		this._selectedExecIntervalNode = null;
		this._status = '';
		this._infoHelperId = '';
		this._progressData = {};

		this._settingsPopupId = 'autosearch-settings-popup';
	}

	initialize(params)
	{
		this._componentName = BX.prop.getString(params, 'componentName', '');
		this._componentSignedParams = BX.prop.getString(params, 'signedParameters', '');
		this._entityTypeId = BX.prop.getInteger(params, 'entityTypeId', 0);
		this._instanceId = BX.Text.getRandom(8);
		this._internalMergeStatus = 'waiting';
		this._mergerUrl = BX.prop.getString(params, 'mergerUrl', '');
		this._dedupeListUrl = BX.prop.getString(params, 'dedupeListUrl', '');
		this._execInterval = BX.prop.getString(params, 'selectedInterval', '0');
		this._intervalsList = BX.prop.getArray(params, 'intervals', []);
		this._status = BX.prop.getString(params, 'status', '');
		this._infoHelperId = BX.prop.getString(params, 'infoHelperId', '');
		this._progressData = BX.prop.getObject(params, 'progressData', {});

		this.tryToStartMerge(true);

		this.subscribeEvents();
	}

	subscribeEvents()
	{
		if (BX.PULL)
		{
			BX.PULL.subscribe({
				type: BX.PullClient.SubscriptionType.Server,
				moduleId: 'crm',
				command: 'dedupe.autosearch.startMerge',
				callback: (params) =>
				{
					if (BX.prop.getInteger(params, 'entityTypeId', 0) === this._entityTypeId)
					{
						this._status = BX.prop.getString(params, 'status', '');
						this._progressData = BX.prop.getObject(params, 'progressData', {});

						if (this._status === 'MERGING')
						{
							let notification = UI.Notification.Center.getBalloonById('crm.autosearch.start_merge');
							if (notification)
							{
								notification.close();
							}
						}
						this.tryToStartMerge();
					}
				}
			});
			BX.PULL.subscribe({
				type: BX.PullClient.SubscriptionType.Server,
				moduleId: 'crm',
				command: 'dedupe.autosearch.mergeComplete',
				callback: (params) =>
				{
					if (BX.prop.getInteger(params, 'entityTypeId', 0) === this._entityTypeId)
					{
						const data = BX.prop.getObject(params, 'data', {});
						this._status =  BX.prop.getInteger(data, 'CONFLICT_COUNT', 0) > 0 ?
							'CONFLICTS_RESOLVING' : '';
						clearTimeout(this._mergeCheckerTimeoutId);
						this.showMergeCompleteNotification(data);
					}
				}
			});
		}
		EventEmitter.subscribe('onLocalStorageSet', this.onExternalEvent.bind(this));
	}

	showSettings()
	{
		this._selectedExecInterval = this._execInterval;
		const popup = PopupManager.create({
			id: this._settingsPopupId,
			cacheable: false,
			titleBar: Loc.getMessage('CRM_DP_AUTOSEARCH_SETTINGS_TITLE'),
			content: this.needLoadConflictsCount() ? this.getSettingsPopupLoader() : this.getSettingsPopupContent(),
			closeByEsc: true,
			closeIcon: true,
			draggable: true,
			width: 500,
			buttons: [
				new BX.UI.SaveButton({
					onclick: () =>
					{
						popup.close();
						this.saveSelectedExecInterval();
					}
				}),
				new BX.UI.CancelButton({
					onclick: () =>
					{
						popup.close();
					}
				}),
			]
		});
		popup.show();
		if (this.needLoadConflictsCount())
		{
			this.loadConflictsCount()
				.then((conflictsCount) =>
				{
					popup.setContent(this.getSettingsPopupContent(conflictsCount));
					popup.adjustPosition();
				}, () => {
					popup.setContent(this.getSettingsPopupContent(0));
					popup.adjustPosition();
				});
		}
	}

	tryToStartMerge(immediately = false)
	{
		if (this._status === 'READY_TO_MERGE')
		{
			this.showStartConfirmation();
		}
		if (this._status === 'MERGING')
		{
			this.startMerging(immediately ? 1 : this.getShortTimeout());
		}
		if (this._status === 'CONFLICTS_RESOLVING')
		{
			this.showMergeCompleteNotification(this._progressData);
		}
	}

	showStartConfirmation()
	{
		if (!BX.prop.getBoolean(this._progressData, 'SHOW_NOTIFICATION', true))
		{
			// message was already shown in this session
			return;
		}
		const entityTypeName = BX.CrmEntityType.resolveName(this._entityTypeId);
		const foundItemsCount = BX.prop.getInteger(this._progressData, 'FOUND_ITEMS', 0);
		const totalEntitiesCount = BX.prop.getInteger(this._progressData, 'TOTAL_ENTITIES', 0);
		const notificationContent = Tag.render`
			<span>
				${Loc.getMessage('CRM_DP_AUTOSEARCH_START_CONFIRMATION_TEXT_' + entityTypeName)
					.replace('#FOUND_ITEMS_COUNT#', foundItemsCount)
					.replace('#TOTAL_ENTITIES_COUNT#', totalEntitiesCount)}
				<br>
				${Loc.getMessage('CRM_DP_AUTOSEARCH_START_CONFIRMATION_TEXT')}
				<span class="ui-hint notification-hint-inline">
					<span class="ui-hint-icon" onclick="${this.onHintClick.bind(this)}"></span>
				</span>
			</span>`;
		UI.Notification.Center.notify({
			content: notificationContent,
			autoHide: false,
			id: 'crm.autosearch.start_merge',
			width: 600,
			actions: [
				{
					title: Loc.getMessage('CRM_DP_AUTOSEARCH_START_CONFIRMATION_BUTTON'),
					events: {
						click: (event, balloon, action) =>
						{
							this.showSettings();
							balloon.close();
						}
					}
				}
			],
			events: {
				onOpen: function(event)
				{
					var balloon = event.getBalloon();
					BX.UI.Hint.init(balloon.getContainer());
				}
			}
		});
	}

	showMergeCompleteNotification(data)
	{
		if (!BX.prop.getBoolean(data, 'SHOW_NOTIFICATION', true))
		{
			// message was already shown in this session
			return;
		}

		const entityTypeName = BX.CrmEntityType.resolveName(this._entityTypeId);
		const successCount = BX.prop.getInteger(data, 'SUCCESS_COUNT', 0);
		const conflictsCount = BX.prop.getInteger(data, 'CONFLICT_COUNT', 0);

		if (successCount === 0 && conflictsCount === 0)
		{
			return;
		}

		let message = Tag.render`<div></div>`;
		const automaticallyFoundText = successCount > 0 ?
			Loc.getMessage('CRM_DP_AUTOSEARCH_COMPLETE_TEXT_' + entityTypeName)
				.replace('#FOUND_ITEMS_COUNT#', successCount)
			:
			Loc.getMessage('CRM_DP_AUTOSEARCH_EMPTY_RESULTS_' + entityTypeName);
		Dom.append(
			Tag.render`<div>${automaticallyFoundText}</div>`,
			message
		);
		let actions = [];
		let notificationWidth = 400;
		if (conflictsCount > 0)
		{
			Dom.append(Tag.render`<div>${Loc.getMessage('CRM_DP_AUTOSEARCH_COMPLETE_CONFLICTED_TEXT').replace('#CONFLICTS_COUNT#', conflictsCount)}</div>`, message);
			actions.push({
				title: Loc.getMessage('CRM_DP_AUTOSEARCH_COMPLETE_RESOLVE_CONFLICT_BUTTON'),
				events: {
					click: (event, balloon, action) =>
					{
						this.openMerger();
						balloon.close();
					}
				}
			});

			notificationWidth = 670;
		}
		UI.Notification.Center.notify({
			content: message,
			autoHide: false,
			id: 'crm.autosearch.merge_complete',
			width: notificationWidth,
			actions: actions
		});
	}

	saveSelectedExecInterval()
	{
		this._execInterval = this._selectedExecInterval;
		BX.ajax.runComponentAction(
			this._componentName,
			'setExecInterval',
			{
				mode: 'class',
				signedParameters: this._componentSignedParams,
				data: {
					interval: this._selectedExecInterval
				}
			});
	}

	startMerging(timeout)
	{
		const p = new Promise((resolve, reject) => {
			this.askIfNoActiveMerging(timeout, resolve);
		});
		p.then(() => this.doMerge());
	}

	doMerge()
	{
		BX.ajax.runComponentAction(
			this._componentName,
			'merge',
			{
				mode: 'class',
				signedParameters: this._componentSignedParams,
				data: {
					mergeId: this._instanceId
				}
			}).then((response) =>
			{
				const data = BX.prop.getObject(response, "data", {});
				const instanceId = BX.prop.getString(data, "MERGE_ID", "");
				if (instanceId === this._instanceId)
				{
					const status = BX.prop.getString(data, "STATUS", "");

					if (status !== "COMPLETED")
					{
						window.setTimeout(() => this.doMerge(), 400);
					}
				}
				else
				{
					this.startMerging(this.getLongTimeout());
				}
			},
			(response) =>
			{
				this.startMerging(this.getShortTimeout());
			}
		);
	}

	getSettingsPopupLoader()
	{
		const loaderContainer = Tag.render`<div></div>`;
		loaderContainer.style.height = '180px';

		const loader = new Loader({
			target: loaderContainer
		});
		setTimeout(() => {
			loader.show();
		}, 10);
		return loaderContainer;
	}

	getSettingsPopupContent(conflictsCount)
	{
		const selectedExecInterval =
			this._intervalsList.reduce((prev, item) => (item.value === this._selectedExecInterval ? item.title : prev), '');

		this._selectedExecIntervalNode = Tag.render`<div class="ui-ctl-element">${selectedExecInterval}</div>`;
		return Tag.render`
			<div>
				${this.getConflictsInfo(conflictsCount)}
				<p>${Loc.getMessage('CRM_DP_AUTOSEARCH_SETTINGS_NOTE')}</p>
				<div class="ui-ctl-label-text">${Loc.getMessage('CRM_DP_AUTOSEARCH_SETTINGS_INTERVAL_TITLE')}</div>
				<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100" onclick="${this.toggleIntervalsList.bind(this)}">
					${this._selectedExecIntervalNode}
					<div class="ui-ctl-after ui-ctl-icon-angle"></div>
				</div>
			</div>
		`;
	}

	needLoadConflictsCount()
	{
		return (this._status === 'CONFLICTS_RESOLVING');
	}

	loadConflictsCount()
	{
		return BX.ajax.runComponentAction(
			this._componentName,
			'getStatistic',
			{
				mode: 'class',
				signedParameters: this._componentSignedParams
			}).then((response) =>
			{
				const data = BX.prop.getObject(response, "data", {});
				return BX.prop.getInteger(data, "conflictsCount", 0);
			}
		);
	}

	getConflictsInfo(conflictsCount)
	{
		if (!this.needLoadConflictsCount())
		{
			return '';
		}
		if (conflictsCount <= 0)
		{
			return '';
		}
		return Tag.render`
			<div class="ui-alert ui-alert-warning">
				<span class="ui-alert-message">
					${Loc.getMessage('CRM_DP_AUTOSEARCH_SETTINGS_CONFLICTS_FOUND').replace('#CONFLICTS_COUNT#', conflictsCount)}
					<span class="ui-link" onclick="${this.onStartMergeButtonClick.bind(this)}">${Loc.getMessage('CRM_DP_AUTOSEARCH_COMPLETE_RESOLVE_CONFLICT_BUTTON')}</span>
				</span>
			</div>`;
	}

	getShortTimeout()
	{
		return Math.round(Math.random() * 5000) + 500;
	}

	getLongTimeout()
	{
		return Math.floor(Math.random() * 10000) + 30000;
	}

	openDedupeList()
	{
		let url = BX.util.add_url_param(this._dedupeListUrl, {'is_automatic': 'yes'});
		BX.Crm.Page.openSlider(url);
	}

	openMerger()
	{
		let url = BX.util.add_url_param(this._mergerUrl, {'is_automatic': 'yes'});
		BX.Crm.Page.openSlider(url);
		this.bindMergerSliderEvent();
	}

	toggleIntervalsList(e)
	{
		if (this._isDropdownMenuShown)
		{
			this.closeDropdownMenu();
		}
		else
		{
			this.showDropdownMenu(e.target);
		}
	}

	showDropdownMenu(bindElement)
	{
		if (this._isDropdownMenuShown || !bindElement)
		{
			return;
		}

		let menu = [];
		for (let i = 0; i < this._intervalsList.length; i++)
		{
			menu.push(
				{
					text: this._intervalsList[i].title,
					value: this._intervalsList[i].value,
					onclick: this.onSelectInterval.bind(this, this._intervalsList[i].value)
				}
			);
		}

		MenuManager.show(
			'autosearch-settings-intervals-dropdown',
			bindElement,
			menu,
			{
				width: bindElement.offsetWidth,
				angle: false,
				cacheable: false,
				events:
					{
						onPopupShow: () =>
						{
							this._isDropdownMenuShown = true;
						},
						onPopupClose: () =>
						{
							this._isDropdownMenuShown = false;
						}
					}
			}
		);
	}

	closeDropdownMenu()
	{
		if (!this._isDropdownMenuShown)
		{
			return;
		}

		let menu = MenuManager.getMenuById('autosearch-settings-intervals-dropdown');
		if (menu)
		{
			menu.popupWindow.close();
		}
	}

	askIfNoActiveMerging(timeout, callback)
	{
		clearTimeout(this._mergeCheckerTimeoutId);
		this._mergeCheckerTimeoutId = setTimeout(() =>
		{
			this._internalMergeStatus = 'ready';
			// ask another tabs
			BX.localStorage.set(
				"BX.Crm.onCrmEntityAutosearchStartMerge",
				{
					entityTypeId: this._entityTypeId,
					instanceId: this._instanceId
				},
				5
			);
			this._mergeCheckerTimeoutId = setTimeout(() =>
			{
				// if another tabs don't change status
				if (this._internalMergeStatus === 'ready')
				{
					// we can start merging
					this._internalMergeStatus = 'merging';
					callback();
				}
				else
				{
					// if there is another tab with active merging, try to wait ~30 sec
					this.askIfNoActiveMerging(this.getLongTimeout(), callback);
				}
			}, 5000);
		}, timeout);
	}

	bindMergerSliderEvent()
	{
		const slider = BX.Crm.Page.getTopSlider();
		if (!slider)
		{
			return;
		}
		EventEmitter.subscribe(slider, "SidePanel.Slider:onCloseStart", this.onCloseMergeSlider.bind(this));
	}

	onExternalEvent(event)
	{
		let dataArray = event.getData();
		if (!Type.isArray(dataArray))
		{
			return;
		}
		let data = dataArray[0];

		let eventName = BX.prop.getString(data, "key", "");

		if (eventName === "BX.Crm.onCrmEntityAutosearchStartMerge" ||
			eventName === "BX.Crm.onCrmEntityAutosearchMergeStatusNotify")
		{
			let value = BX.prop.getObject(data, "value", {});
			let entityTypeId = BX.prop.getInteger(value, "entityTypeId", 0);
			if (entityTypeId !== this._entityTypeId)
			{
				return;
			}
			let instanceId = BX.prop.getString(value, "instanceId", "");

			if (eventName === "BX.Crm.onCrmEntityAutosearchStartMerge")
			{
				if (instanceId !== this._instanceId && this._internalMergeStatus !== 'waiting')  // event from another tab
				{
					if (instanceId < this._instanceId || this._internalMergeStatus === 'merging')
					// notify only if current instance is already merging or if current instanceId is lower then another ready candidate
					{
						BX.localStorage.set(
							"BX.Crm.onCrmEntityAutosearchMergeStatusNotify",
							{
								entityTypeId: this._entityTypeId,
								instanceId: instanceId,
								status: this._internalMergeStatus
							},
							5
						);
					}
					else
					{
						this._internalMergeStatus = 'waiting';
					}
				}
			}
			if (eventName === "BX.Crm.onCrmEntityAutosearchMergeStatusNotify")
			{
				if (instanceId === this._instanceId)
				{
					// another tab canceled this merging
					this._internalMergeStatus = 'waiting';
				}

			}
		}
	}

	onStartMergeButtonClick()
	{
		const popup = PopupManager.getPopupById(this._settingsPopupId);
		if (popup && popup.isShown())
		{
			popup.close();
		}
		this.openMerger();
	}

	onSelectInterval(interval)
	{
		this._selectedExecInterval = interval;
		if (Type.isDomNode(this._selectedExecIntervalNode))
		{
			this._selectedExecIntervalNode.textContent =
				this._intervalsList.reduce((prev, item) => (item.value === this._selectedExecInterval ? item.title : prev), '');
		}
		this.closeDropdownMenu();
	}

	onHintClick()
	{
		if (this._infoHelperId)
		{
			BX.Helper.show("redirect=detail&code="+this._infoHelperId);
		}
	}

	onCloseMergeSlider(event)
	{
		if (top.BX.CRM && top.BX.CRM.Kanban)
		{
			var kanban = top.BX.CRM.Kanban.Grid.getInstance();
			if (kanban)
			{
				kanban.reload();
			}
		}
		if (top.BX.Main.gridManager)
		{
			var gridId = 'CRM_' +  BX.CrmEntityType.resolveName(this._entityTypeId) + '_LIST_V12'; // does not support deal categories
			var grid = top.BX.Main.gridManager.getInstanceById(gridId);
			if (grid)
			{
				grid.reload();
			}
		}
	}

	static create(params)
	{
		let autosearch = new DedupeAutosearch();
		autosearch.initialize(params);
		return autosearch;
	}

	static setDefault(instance, entityTypeName)
	{
		if (!Type.isObject(DedupeAutosearch.defaultInstance))
		{
			DedupeAutosearch.defaultInstance = {};
		}
		DedupeAutosearch.defaultInstance[entityTypeName] = instance
	}

	static getDefault(entityTypeName)
	{
		return DedupeAutosearch.defaultInstance[entityTypeName];
	}
}


namespace.DedupeAutosearch = DedupeAutosearch;
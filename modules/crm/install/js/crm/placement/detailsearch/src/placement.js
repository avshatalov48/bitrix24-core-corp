import {Runtime, Type} from "main.core";
import "./placement.css"

class Loader
{
	static instances = new Map;

	constructor(placementCode, settings)
	{
		this.placementCode = Type.isStringFilled(placementCode) ? placementCode : "";
		this.placementInterface = null;
		this.settings = settings;
		this.queue = [];
		this.isActive = false;
		this.isInited = false;

		const appLayout = BX.Reflection.getClass("BX.rest.AppLayout");
		const placement = (appLayout ? appLayout.getPlacement(this.placementCode) : null);
		if (placement)
		{
			this.initialize();
		}
		else
		{
			BX.ajax.runComponentAction(
				"bitrix:app.placement",
				"getComponent",
				{
					data: {
						placementId: this.placementCode,
						placementOptions: {}
					}
				}
			).then(function(result) {
				const hiddenDiv = BX.create("DIV", {
					style: {display: "none", overflow: "hidden"}
				});
				document.body.appendChild(hiddenDiv);
				BX.html(hiddenDiv, result.data.html, {
					callback: function() {
						setTimeout(this.initialize.bind(this), 10);
					}.bind(this)
				});
			}.bind(this), this.initialize.bind(this));
		}
	}

	initialize()
	{
		this.isInited = true;
		this.placement = (BX["rest"] ? BX.rest.AppLayout.getPlacement(this.placementCode) : null);
		if (this.placement)
		{
			this.placementInterface = BX.rest.AppLayout.initializePlacement(this.placementCode);
			this.isActive = true;
		}

		this.executeCallbacks();
	}

	addCallback(callback)
	{
		this.queue.push(callback);
		this.executeCallbacks();
	}

	executeCallbacks()
	{
		if (!this.isInited)
		{
			return;
		}

		let callback;
		while ((callback = this.queue.shift()) && Type.isFunction(callback)) {
			callback(
				{
					success: this.isActive,
					placement: this.placement,
					placementInterface: this.placementInterface,
					placementEventObject: this,
				}
			);
		}
	}

	static getCallbackFromSettings(settings)
	{
		let callback = null;

		if (
			Type.isPlainObject(settings)
			&& settings.hasOwnProperty("callback")
			&& Type.isFunction(settings["callback"])
		)
		{
			callback = settings["callback"];
			delete settings["callback"];
		}

		return callback;
	}

	static create(placementCode, settings)
	{
		const copiedSettings = Runtime.clone(settings);
		const callback = this.getCallbackFromSettings(copiedSettings);

		if (!this.instances.has(placementCode))
		{
			this.instances.set(placementCode, new this(placementCode, copiedSettings));
		}

		const instance = this.instances.get(placementCode);

		if (callback !== null)
		{
			instance.addCallback(callback);
		}

		return instance;
	}
}

class DetailSearchItem
{
	constructor(placementInfo, eventObject)
	{
		this.placementInfo = placementInfo;
		this.eventObject = eventObject;
		this.menuItem = null;
		this.menuItemLoader = null;
		this.appSid = null;
		this.searchResults = null;
		this.isActive = false;
		this.chaperonTimeoutId = 0;
		this.expectedResponseTime = 20000;

		this.activateHandler = this.activate.bind(this);
		this.checkResponseHandler = this.checkResponse.bind(this);
		this.checkActiveHandler = this.checkActive.bind(this);
		this.destroyHandler = this.destroy.bind(this);

		BX.addCustomEvent(this.eventObject, "Placements:destroy", this.destroyHandler);
		BX.addCustomEvent(this.eventObject, "Placement:click", this.checkActiveHandler);
	}

	activate(event)
	{
		if (event)
		{
			if (event.hasOwnProperty("isTrusted") && !event["isTrusted"])
			{
				this.setActive(false);
			}

			BX.PreventDefault(event);
		}

		if (!this.isActive)
		{
			BX.onCustomEvent(this.eventObject, "Placement:click", [this]);
			this.setActive(true);
			this.find();
		}

		return false;
	}

	setActive(isActive)
	{
		this.isActive = !!isActive;
		if (this.menuItem)
		{
			if (this.isActive)
			{
				this.menuItem.layout.item.classList.add("menu-popup-item-open");
			}
			else
			{
				this.menuItem.layout.item.classList.remove("menu-popup-item-open");
			}
		}
	}

	checkActive(placementItem)
	{
		if (placementItem !== this)
		{
			this.setActive(false);
		}
	}

	getMenuItemContainer(someMenu)
	{
		if (!this.menuItem)
		{
			this.menuItem = someMenu.addMenuItemInternal({
				id: DetailSearch.MENU_ITEM_ID_PREFIX + this.placementInfo.id,
				text: BX.util.htmlspecialchars(this.placementInfo.title),
				subMenuOffsetX: 27,
				onclick: this.activateHandler,
				allowHtml: true,
				cacheable: true,
				className: this.placementInfo.icon ? "menu-popup-item-accept" : ""
			});
			if (this.placementInfo.icon)
			{
				const icon = this.menuItem.getContainer().querySelector(".menu-popup-item-icon");
				icon.style.backgroundImage = "url('" + BX.util.htmlspecialchars(this.placementInfo.icon.src) + "')";
				icon.style.backgroundSize = "contain";
			}
		}
		return this.menuItem.getContainer();
	}

	find()
	{
		this.showLoader();

		const data = {
			placementId: this.placementInfo.id,
			placementOptions: null
		};
		BX.onCustomEvent(this.eventObject, "Placement:send", [data]);

		BX.ajax.runComponentAction(
			"bitrix:app.layout",
			"getComponent",
			{data: data}
		).then(function(response) {
			if (!this.menuItem)
			{
				return;
			}

			if (!(response && response["data"] && response["data"]["componentResult"]))
			{
				return this.makeError(new Error("Empty response"));
			}

			const componentResult = response["data"]["componentResult"];

			this.appSid = componentResult["APP_SID"];

			BX.addCustomEvent(this.eventObject, "Placements:found", this.checkResponseHandler);

			const iframeNode = BX.create("DIV", {
				attrs: {"data-app-sid" : componentResult["APP_SID"]},
				style: {"display": "none", overflow: "hidden"}
			});
			document.body.appendChild(iframeNode);
			BX.html(iframeNode, response["data"]["html"]);
			this.chaperonTimeoutId = setTimeout(this.chaperon.bind(this), this.expectedResponseTime, this.appSid);
		}.bind(this), this.makeError.bind(this));
	}

	checkResponse(applayout, placementSearchResults)
	{
		if (this.appSid === null || this.appSid !== applayout.params.appSid)
		{
			return;
		}
		BX.removeCustomEvent(this.eventObject, "Placements:found", this.checkResponseHandler);

		if (this.chaperonTimeoutId > 0)
		{
			clearTimeout(this.chaperonTimeoutId);
			this.chaperonTimeoutId = 0;
		}

		this.searchResults = (BX.type.isArray(placementSearchResults) ? BX.clone(placementSearchResults, true) : []);

		this.hideLoader();

		if (this.menuItem)
		{
			if (this.searchResults.length <= 0)
			{
				this.menuItem.disable();
			}
			else if (this.menuItem.isDisabled())
			{
				this.menuItem.enable();
			}
		}
		BX.onCustomEvent(this.eventObject, "Placement:found", [this, this.searchResults]);
	}

	chaperon()
	{
		this.chaperonTimeoutId = 0;
		this.makeError(new Error("The placement is responding too long."));
	}

	makeError(error)
	{
		this.hideLoader(true);
		BX.removeCustomEvent(this.eventObject, "Placements:found", this.checkResponseHandler);
		BX.onCustomEvent(this.eventObject, "Placement:errored", [this, error]);
	}

	showLoader()
	{
		if (!this.menuItem)
		{
			return;
		}
		this.menuItemLoader = (this.menuItemLoader || new BX.Loader({
			target: this.menuItem.getContainer(),
			size: 20,
			mode: "inline"
		}));
		this.menuItemLoader.show();
		this.menuItem.disable();
	}

	hideLoader(errored)
	{
		errored = (errored === true);
		if (!this.menuItem)
		{
			return;
		}
		if (this.menuItemLoader)
		{
			this.menuItemLoader.hide();
		}
		if (errored)
		{
			this.menuItem.disable();
		}
	}

	destroy()
	{
		BX.removeCustomEvent(this.eventObject, "Placements:destroy", this.destroyHandler);
		BX.removeCustomEvent(this.eventObject, "Placement:click", this.checkActiveHandler);

		if (this.appSid !== null && BX.rest.AppLayout.get(this.appSid))
		{
			BX.rest.AppLayout.get(this.appSid).destroy();
		}
		if (this.menuItemLoader)
		{
			this.menuItemLoader.destroy();
			this.menuItemLoader = null;
		}
		if (this.chaperonTimeoutId > 0)
		{
			clearTimeout(this.chaperonTimeoutId);
			this.chaperonTimeoutId = 0;
		}
		this.menuItem = null;
		this.searchResults = null;
		this.eventObject = null;
	}
}

class DetailSearch
{
	static MENU_ITEM_ID_PREFIX = "placement-";
	
	constructor(placementCode: string)
	{
		this.placementCode = placementCode;
		this.searchParams = {};
		this.container = BX.create("div", {props: {className: "placement-container"}});
		this.containerLoader = null;
		this.placementList = new Map();
		this.firstPlacementAppId = 0;
		this.placementInterface = null;
		this.placementEventObject = null;
		this.isInited = false;
		this.isDestroyed = false;
		this.isShown = false;
		this.active = null;
		this.activeProcesses = 0;
		this.menu = null;
		this.placementParams = null;
		this.isLoderEnabled = true;

		this.restCrmShowFoundEntities = this.restCrmShowFoundEntities.bind(this);
		this.restCrmShowCreatedEntity = this.restCrmShowCreatedEntity.bind(this);

		BX.addCustomEvent(this, "Placements:destroy", this.destroy.bind(this));

		Loader.create(this.placementCode, {callback: this.init.bind(this)});
	}

	init(data)
	{
		if (this.isInited === true || this.isDestroyed === true)
		{
			return;
		}
		if (data.success === true
			&& data.placement
			&& data.placement.param
			&& data.placement.param.extendedList instanceof Map)
		{
			this.placementList = data.placement.param.extendedList;
			this.placementEventObject = data.placementEventObject;
			const isPlacementInterface = data.hasOwnProperty("placementInterface");
			const isNeedInitializeInterface = (
				isPlacementInterface
				&& !data["placementInterface"].hasOwnProperty("crmShowFoundEntities")
			);
			if (isPlacementInterface)
			{
				this.placementInterface =
					(isNeedInitializeInterface)
						? this.initializeInterface(data["placementInterface"])
						: data["placementInterface"]
				;
			}
		}
		this.isInited = true;
		if (this.isShown)
		{
			this.showMenu();
		}
	}

	initializeInterface(placementInterface)
	{
		const placementEventObject = this.placementEventObject;

		placementInterface.prototype.crmShowFoundEntities = function (data) {
			BX.onCustomEvent(placementEventObject, "restCrmShowFoundEntities", [this, data.data]);
		};
		placementInterface.prototype.crmShowCreatedEntity = function (data) {
			BX.onCustomEvent(placementEventObject, "restCrmShowCreatedEntity", [this, data]);
		};
		placementInterface.prototype.events.push("onCrmEntityIsNeedToCreate");

		return placementInterface;
	}

	show(container, nextSibling, params)
	{
		if (BX.Type.isDomNode(nextSibling))
		{
			nextSibling.parentNode.insertBefore(this.container, nextSibling);
		}
		else if (BX.Type.isDomNode(container))
		{
			container.appendChild(this.container);
		}

		this.isShown = true;

		this.isLoderEnabled = !BX.prop.getBoolean(params, "hideLoader", false);

		if (this.isInited !== true)
		{
			if (this.isLoderEnabled)
			{
				this.showLoader();
			}
		}
		else
		{
			this.showMenu();
		}
	}

	showLoader()
	{
		this.containerLoader = (this.containerLoader || new BX.Loader({
			target: this.container,
			size: 50
		}));
		this.containerLoader.show();
	}

	hideLoader()
	{
		if (this.containerLoader)
		{
			this.containerLoader.hide();
		}
	}

	getCountryMap(placementData)
	{
		const result = new Map();

		const countriesOption = BX.prop.getString(
			BX.prop.getObject(placementData, "options", {}),
			"countries",
			""
		);
		if (Type.isStringFilled(countriesOption) && /^[1-9][0-9]*(,[1-9][0-9]*)*$/.test(countriesOption))
		{
			countriesOption.split(',').forEach(
				(countryCode) => {
					const countryId = parseInt(countryCode);
					if (!isNaN(countryId) && !result.has(countryId))
					{
						result.set(countryId, true);
					}
				}
			);
		}

		return result;
	}

	showMenu()
	{
		if (this.isLoderEnabled)
		{
			this.hideLoader();
		}

		const eventParams = {
			placementParams: null,
			currentSearchQuery: "",
		};
		BX.onCustomEvent(this, "Placements:params", [eventParams]);

		this.placementParams = Runtime.clone(eventParams["placementParams"]);
		const currentSearchQuery = BX.prop.getString(eventParams, "currentSearchQuery", "");

		if (Type.isObject(this.placementParams))
		{
			const isPlacementsAvailable = (this.placementParams["numberOfPlacements"] > 0);
			this.refreshContainerVisibility(
				{
					currentSearchQuery: currentSearchQuery,
					placementParams: Runtime.clone(this.placementParams)
				}
			);
			if (!isPlacementsAvailable)
			{
				return;
			}
		}

		BX.adjust(this.container, {
			props: {className: "placement-container menu-popup"}
		});

		this.replaceEvents();

		this.destroyMenu();
		this.menu = new BX.PopupMenuWindow({id: "someId"});

		BX.onCustomEvent(this, "Placements:beforeAppendItems");

		const itemsNode = BX.create("div", {props: {className: "menu-popup-items"}});
		this.container.appendChild(itemsNode);
		this.firstPlacementAppId = 0;
		this.placementList.forEach(
			function(placementData) {
				const countries = this.getCountryMap(placementData);
				const countryId = BX.prop.getInteger(this.placementParams, "countryId", 0);
				if (countries.size === 0 || countries.has(countryId))
				{
					if (this.firstPlacementAppId === 0)
					{
						this.firstPlacementAppId = parseInt(placementData["id"]);
					}
					const placement = new DetailSearchItem(placementData, this);
					itemsNode.appendChild(placement.getMenuItemContainer(this.menu));
				}
			}.bind(this)
		);

		if (
			Type.isObject(this.placementParams)
			&& this.placementParams["isPlacement"]
			&& this.placementParams["numberOfPlacements"] <= 1
		)
		{
			this.setFirstPlacementMenuItemVisibility(false);
		}
	}

	restCrmShowFoundEntities(applayout, placementSearchResults)
	{
		// applayout is an instance Of BX.rest.AppLayout
		BX.onCustomEvent(this, "Placements:found", [applayout, placementSearchResults]);
	}

	restCrmEntityCreate(data)
	{
		this.activeProcesses++;
		BX.onCustomEvent("onCrmEntityIsNeedToCreate", data);
	}

	restCrmShowCreatedEntity(applayout, createdEntity)
	{
		this.activeProcesses--;

		BX.onCustomEvent(
			this,
			"Placements:select",
			[createdEntity]
		);
	}

	onPlacementHasBeenClicked(placementItem)
	{
		if (this.active === placementItem)
		{
			return;
		}

		this.active = placementItem;
		this.activeProcesses++;
	}

	onPlacementIsReadyToSend(params)
	{
		BX.onCustomEvent(this, "Placements:searchParams", [this.searchParams]);
		params["placementOptions"] = this.searchParams;
	}

	onPlacementHasBeenFound(placementItem, searchResults)
	{
		if (this.active === placementItem)
		{
			BX.onCustomEvent(this, "Placements:setFoundItems", [placementItem, searchResults]);
		}

		this.activeProcesses--;
	}

	onPlacementIsErrored(/*placementItem, error*/)
	{
		this.activeProcesses--;
	}

	replaceEvents() {
		BX.addCustomEvent(this.placementEventObject, "restCrmShowFoundEntities", this.restCrmShowFoundEntities);
		BX.addCustomEvent(this.placementEventObject, "restCrmShowCreatedEntity", this.restCrmShowCreatedEntity);

		BX.addCustomEvent(this, "Placement:click", this.onPlacementHasBeenClicked);
		BX.addCustomEvent(this, "Placement:send", this.onPlacementIsReadyToSend.bind(this));
		BX.addCustomEvent(this, "Placement:found", this.onPlacementHasBeenFound.bind(this));
		BX.addCustomEvent(this, "Placement:errored", this.onPlacementIsErrored.bind(this));

		BX.addCustomEvent(this, "Placements:active", this.onCheckActive.bind(this));
		BX.addCustomEvent(this, "Placements:pick", this.restCrmEntityCreate.bind(this));
		BX.addCustomEvent(this, "Placements:changeQuery", this.onChangeSearchQuery.bind(this));
		BX.addCustomEvent(this, "Placements:startDefaultSearch", this.onStartDefaultSearch.bind(this));
	}

	restoreEvents()
	{
		BX.removeAllCustomEvents(this, "Placement:click");
		BX.removeAllCustomEvents(this, "Placement:send");
		BX.removeAllCustomEvents(this, "Placement:found");
		BX.removeAllCustomEvents(this, "Placement:errored");

		BX.removeAllCustomEvents(this, "Placements:found");
		BX.removeAllCustomEvents(this, "Placements:active");
		BX.removeAllCustomEvents(this, "Placements:pick");
		BX.removeAllCustomEvents(this, "Placements:changeQuery");
		BX.removeAllCustomEvents(this, "Placements:startDefaultSearch");
		BX.removeAllCustomEvents(this, "Placements:destroy");

		BX.removeCustomEvent(this.placementEventObject, "restCrmShowFoundEntities", this.restCrmShowFoundEntities);
		BX.removeCustomEvent(this.placementEventObject, "restCrmShowCreatedEntity", this.restCrmShowCreatedEntity);
	}

	onCheckActive(result)
	{
		result.active = (this.activeProcesses > 0);
	}

	onPlacementsDestroy()
	{
		this.destroy();
	}

	destroyMenu()
	{
		if (this.menu)
		{
			this.menu.destroy();
			this.menu = null;
		}
	}

	destroy()
	{
		this.isDestroyed = true;
		this.destroyMenu();
		this.restoreEvents();
		this.placementInterface = null;
		this.placementEventObject = null;
		if (this.containerLoader)
		{
			this.containerLoader.destroy();
			this.containerLoader = null;
		}
		for (let i in this)
		{
			if (this.hasOwnProperty(i))
			{
				delete this[i];
			}
		}
	}

	setFirstPlacementAppId(id)
	{
		this.firstPlacementAppId = parseInt(id);
	}

	getFirstPlacementAppId()
	{
		return this.firstPlacementAppId;
	}

	getFirstPlacementMenuItemId()
	{
		const appId = this.getFirstPlacementAppId();
		return (appId > 0) ? (DetailSearch.MENU_ITEM_ID_PREFIX + appId) : "";
	}

	getFirstPlacementMenuItem()
	{
		let menuItem = null;

		const firstMenuItemId = this.getFirstPlacementMenuItemId();
		if (Type.isStringFilled(firstMenuItemId) && this.menu)
		{
			menuItem = this.menu.getMenuItem(firstMenuItemId);
		}

		return menuItem;
	}

	refreshContainerVisibility(params)
	{
		const placementParams = BX.prop.getObject(params, "placementParams", {});
		const numberOfPlacements = BX.prop.getInteger(placementParams, "numberOfPlacements", 0);
		const isPlacementsAvailable = (numberOfPlacements > 0);
		const isPlacement = BX.prop.getBoolean(placementParams, "isPlacement", false);
		const currentSearchQuery = BX.prop.getString(params, "currentSearchQuery", "");
		const isVisible = (
			isPlacementsAvailable
			&& (!isPlacement || (isPlacement && numberOfPlacements > 1))
			&& currentSearchQuery.length > 2
		);
		this.setContainerVisibility(this.container, isVisible);
	}

	setFirstPlacementMenuItemVisibility(isVisible)
	{
		const menuItem = this.getFirstPlacementMenuItem();
		if (menuItem)
		{
			this.setContainerVisibility(menuItem.getContainer(), isVisible);
		}
	}

	setContainerVisibility(container, isVisible)
	{
		if (Type.isDomNode(container))
		{
			if (isVisible && container.style.display === "none")
			{
				container.style.display = "";
			}
			else if (!isVisible && container.style.display === "")
			{
				container.style.display = "none";
			}
		}
	}

	activateFirstPlacementMenuItem()
	{
		setTimeout(() => {
			const menuItem = this.getFirstPlacementMenuItem();
			if (menuItem)
			{
				menuItem.layout.text.dispatchEvent(
					new MouseEvent(
						"click",
						{
							bubbles: true,
							cancelable: true
						}
					)
				);
			}
		}, 0);
	}

	onChangeSearchQuery(params)
	{
		this.refreshContainerVisibility(
			{
				currentSearchQuery: BX.prop.getString(params, "currentSearchQuery", ""),
				placementParams: Runtime.clone(this.placementParams)
			}
		);
	}

	onStartDefaultSearch(params)
	{
		if (Type.isPlainObject(params) && params.hasOwnProperty("appId"))
		{
			params["appId"] = this.getFirstPlacementAppId();
		}

		this.activateFirstPlacementMenuItem();
	}
}

export {
	DetailSearch
};

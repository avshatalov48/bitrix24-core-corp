import {Dom, Loc, Tag, Text, Type} from "main.core";
import {EventEmitter} from "main.core.events";
import 'ui.design-tokens';
import {DetailSearch as DetailSearchPlacement} from "crm.placement.detailsearch"
import {MessageBox, MessageBoxButtons} from "ui.dialogs.messagebox"
import "./autocomplete.css"

export class RequisiteAutocompleteField extends EventEmitter
{
	constructor()
	{
		super();
		this.setEventNamespace('BX.Crm.Requisite.Autocomplete');

		this._id = "";
		this._settings = {};
		this._placeholderText = null;
		this._isLoading = false;
		this._isEnabled = false;
		this._context = {};
		this._currentItem = null;

		this._domNodes = {};
		this._dropdown = null;

		this.currentSearchQueryText = "";
		this.detailAutocompletePlacement = null;
		this.entitySearchPopupCloseHandler = null;
		this.placementsParamsHandler = null;
		this.searchQueryInputHandler = null;
		this.beforeAddPlacementItemsHandler = null;
		this.placementSearchParamsHandler = null;
		this.placementSetFoundItemsHandler = null;
		this.placementEntitySelectHandler = null;
		this.externalSearchHandler = null;
		this.externalSearchResultHandlerList = null;

		this.beforeEntitySearchPopupCloseHandler = null;
		this.onDocumentClickConfirm = null;

		this.creatingItem = null;

		this.clientResolverPlacementParams = null;
	}

	initialize(id, settings)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
		this._settings = settings ? settings : {};
		this._placeholderText = BX.prop.getString(this._settings, "placeholderText", "");
		this._context = BX.prop.getObject(this._settings, "context", {});
		this._isEnabled = BX.prop.getBoolean(this._settings, "enabled", false);
		this._featureRestrictionCallback = BX.prop.getString(this._settings, "featureRestrictionCallback", '');
		this._isPermitted = (this._featureRestrictionCallback === '');
		this._showFeedbackLink = this._isPermitted ? BX.prop.getBoolean(this._settings, "showFeedbackLink", false) : false;
		this.clientResolverPlacementParams = this.filterclientResolverPlacementParams(
			BX.prop.getObject(this._settings, "clientResolverPlacementParams", null)
		);
		this.externalSearchHandler = this.onExternalSearch.bind(this);
		this.externalSearchResultHandlerList = new Map();

		this.doInitialize();
	}

	filterclientResolverPlacementParams(params)
	{
		if (!Type.isObject(params))
		{
			return null;
		}

		return {
			isPlacement: BX.prop.getBoolean(params, "isPlacement", false),
			numberOfPlacements: BX.prop.getInteger(params, "numberOfPlacements", 0),
			countryId: BX.prop.getInteger(params, "countryId", 0),
			defaultAppInfo: BX.prop.getObject(params, "defaultAppInfo", {}),
		};
	}

	doInitialize()
	{
	}

	layout(container)
	{
		if (!Type.isDomNode(container))
		{
			return;
		}
		this._domNodes.requisiteClearButton = Tag.render`
			<button class="ui-ctl-after ui-ctl-icon-clear" onclick="${this.onSearchStringClear.bind(this)}"></button>`;

		this._domNodes.requisiteSearchButton = Tag.render`
			<button class="ui-ctl-after ui-ctl-icon-search" onclick="${this.onSearchButtonClick.bind(this)}"></button>`;

		let placeholder = this._placeholderText;
		this._domNodes.requisiteSearchString = Tag.render`
			<input type="text" placeholder="${Text.encode(placeholder)}" class="ui-ctl-element ui-ctl-textbox" />`;

		if (!this._isPermitted)
		{
			this._domNodes.requisiteSearchString.setAttribute('onclick', this._featureRestrictionCallback);
		}
		this.refreshLayout();

		Dom.append(Tag.render`
		<div class="ui-ctl ui-ctl-w100 ui-ctl-after-icon">
			${this._domNodes.requisiteSearchButton}
			${this._domNodes.requisiteClearButton}
			${this._domNodes.requisiteSearchString}
		</div>`, container);

		this.initDropdown();

		this.refreshLayout();
	}

	initDropdown()
	{
		if (!this._dropdown)
		{
			const isPlacement = BX.prop.getBoolean(this.clientResolverPlacementParams, "isPlacement", false);
			this._dropdown = new Dropdown(
				{
					isDisabled: !this._isPermitted,
					searchAction: BX.prop.getString(this._settings, "searchAction", ""),
					externalSearchHandler: isPlacement ? this.externalSearchHandler : null,
					items: [],
					enableCreation: true,
					enableCreationOnBlur: true,
					autocompleteDelay: 1500,
					messages:
					{
						creationLegend: Loc.getMessage('REQUISITE_AUTOCOMPLETE_ADD_REQUISITE'),
						notFound: Loc.getMessage('REQUISITE_AUTOCOMPLETE_NOT_FOUND'),
					},
					placementParams: this.clientResolverPlacementParams
				}
			);
			EventEmitter.subscribe(this._dropdown, 'BX.UI.Dropdown:onSelect', this.onEntitySelect.bind(this));
			EventEmitter.subscribe(this._dropdown, 'BX.UI.Dropdown:onAdd', this.onEntityAdd.bind(this));
			EventEmitter.subscribe(this._dropdown, 'BX.UI.Dropdown:onReset', this.onEntityReset.bind(this));
			EventEmitter.subscribe(this._dropdown, 'BX.UI.Dropdown:onBeforeSearchStart', this.onEntitySearchStart.bind(this));
			EventEmitter.subscribe(this._dropdown, 'BX.UI.Dropdown:onSearchComplete', this.onEntitySearchComplete.bind(this));
			BX.addCustomEvent(this._dropdown, 'Dropdown:onGetPopupAlertContainer', this.onGetPopupAlertContainer.bind(this));
			BX.addCustomEvent(this._dropdown, 'Dropdown:onAfterInstallDefaultApp', this.onAfterInstallDefaultApp.bind(this));
		}
		this._dropdown.searchOptions = this._context;
		this._dropdown.targetElement = this._domNodes.requisiteSearchString;
		this._dropdown.init();

		this.setEnabled(this._isEnabled);
		this.setShowFeedbackLink(this._showFeedbackLink);
	}

	getDropdownPopup(tryCreate = false)
	{
		if (tryCreate)
		{
			return this._dropdown.getPopupWindow();
		}

		return this._dropdown.popupWindow;
	}

	closeDropdownPopup()
	{
		const dropownPopup = this.getDropdownPopup();
		if (dropownPopup)
		{
			dropownPopup.close();
		}
	}

	setCurrentItem(autocompleteItem)
	{
		this._currentItem = Type.isPlainObject(autocompleteItem) ? autocompleteItem : null;
		this.refreshLayout();
	}

	setShowFeedbackLink(show)
	{
		this._showFeedbackLink = !!show;
		if (this._dropdown)
		{
			this._dropdown.setFeedbackFormParams(this._showFeedbackLink ? BX.prop.getObject(this._settings, "feedbackFormParams", {}) : {});
		}
	}

	refreshLayout()
	{

		if (!Type.isDomNode(this._domNodes.requisiteSearchString) ||
			!Type.isDomNode(this._domNodes.requisiteSearchButton) ||
			!Type.isDomNode(this._domNodes.requisiteClearButton))
		{
			return;
		}

		let text = '';

		if (Type.isObject(this._currentItem) && this._isPermitted)
		{
			let textParts = [this._currentItem.title];
			if (Type.isStringFilled(this._currentItem.subTitle))
			{
				textParts.push(this._currentItem.subTitle);
			}
			text = textParts.join(', ');

			Dom.style(this._domNodes.requisiteSearchButton, "display", "none");
			Dom.style(this._domNodes.requisiteClearButton, "display", "");
			if (this._dropdown)
			{
				this._dropdown.setCanAddRequisite(false);
			}
		}
		else
		{
			Dom.style(this._domNodes.requisiteClearButton, "display", "none");
			if (this._isEnabled || !this._isPermitted)
			{
				Dom.style(this._domNodes.requisiteSearchButton, "display", "");
			}
			else
			{
				Dom.style(this._domNodes.requisiteSearchButton, "display", "none");
			}
			if (this._dropdown)
			{
				this._dropdown.setCanAddRequisite(this._isPermitted && BX.prop.getBoolean(this._settings, "canAddRequisite", false));
			}
		}

		this._domNodes.requisiteSearchString.value = text;
		this.onSearchQueryInput();
		this.setLoading(false);
	}

	getState()
	{
		return {
			currentItem: this._currentItem,
			searchQuery: Type.isDomNode(this._domNodes.requisiteSearchString) ?
				this._domNodes.requisiteSearchString.value : null,
			items: Type.isObject(this._dropdown) ? this._dropdown.getItems() : []
		};
	}

	setState(state)
	{
		if (!Type.isPlainObject(state))
		{
			return;
		}
		this.setCurrentItem(Type.isPlainObject(state.currentItem) ? state.currentItem : null);
		if (Type.isString(state.searchQuery) && Type.isDomNode(this._domNodes.requisiteSearchString) && this._isPermitted)
		{
			this._domNodes.requisiteSearchString.value = state.searchQuery;
			this.onSearchQueryInput();
		}
		if (Type.isArray(state.items))
		{
			this._dropdown.setItems(state.items);
		}
	}

	setContext(context)
	{
		this._context = Type.isPlainObject(context) ? context : {};
		if (this._dropdown)
		{
			this._dropdown.searchOptions = this._context;
		}
	}

	setPlaceholderText(text)
	{
		this._placeholderText = Type.isStringFilled(text) ? text : "";
		if (Type.isDomNode(this._domNodes.requisiteSearchString))
		{
			this._domNodes.requisiteSearchString.placeholder = this._placeholderText;
		}
	}

	setClientResolverPlacementParams(params)
	{
		this.clientResolverPlacementParams = this.filterclientResolverPlacementParams(params);
		if (this._dropdown)
		{
			this._dropdown.setPlacementParams(this.clientResolverPlacementParams);
			const isPlacement = BX.prop.getBoolean(this.clientResolverPlacementParams, "isPlacement", false);
			if (isPlacement)
			{
				this._dropdown.setExternalSearchHandler(this.externalSearchHandler);
			}
		}
	}

	setEnabled(enabled)
	{
		this._isEnabled = !!enabled;
		if (this._dropdown)
		{
			this._dropdown.setMinSearchStringLength(this._isEnabled ? 3: 99999);
		}
	}

	setLoading(isLoading)
	{
		isLoading = !!isLoading;
		if (isLoading === this._isLoading)
		{
			return;
		}
		this._isLoading = isLoading;
		let searchBtn = this._domNodes.requisiteSearchButton;
		if (Type.isDomNode(searchBtn))
		{
			if (isLoading)
			{
				searchBtn.classList.remove('ui-ctl-icon-search');
				searchBtn.classList.add('ui-ctl-icon-loader');
			}
			else
			{
				searchBtn.classList.remove('ui-ctl-icon-loader');
				searchBtn.classList.add('ui-ctl-icon-search');
			}
		}

		let clearBtn = this._domNodes.requisiteClearButton;
		if (Type.isDomNode(clearBtn))
		{
			if (isLoading)
			{
				clearBtn.classList.remove('ui-ctl-icon-clear');
				clearBtn.classList.add('ui-ctl-icon-loader');
			}
			else
			{
				clearBtn.classList.remove('ui-ctl-icon-loader');
				clearBtn.classList.add('ui-ctl-icon-clear');
			}
		}
	}

	onSearchStringClear()
	{
		this.emit('onClear');
	}

	onSearchButtonClick()
	{
		if (Type.isObject(this._dropdown))
		{
			this._dropdown.handleTypeInField();
		}
	}

	onEntitySelect(event)
	{
		let data = event.getData();
		let dropdown = data[0];
		let selected = data[1];

		if (dropdown === this._dropdown && selected["appSid"] && !selected["created"])
		{
			if (!this.creatingItem)
			{
				this.creatingItem = selected;
				selected._loader = new BX.Loader({
					target: selected.node,
					size: 40
				});
				selected.node.classList.add('client-editor-active');
				selected.node.parentNode.classList.add('client-editor-inactive');
				selected._loader.show();

				BX.onCustomEvent(
					this.detailAutocompletePlacement,
					"Placements:pick",
					[{appSid: selected["appSid"], data: selected}]
				);
			}

			return;
		}

		this.selectEntity(selected);
	}

	selectEntity(selected)
	{
		if (this.creatingItem)
		{
			for (let prop in selected)
			{
				if (selected.hasOwnProperty(prop))
				{
					this.creatingItem[prop] = selected[prop];
				}
			}
			this.creatingItem["created"] = true;
			this.creatingItem = null;
			this._dropdown.setItems([]);
		}

		if (this.onDocumentClickConfirm)
		{
			this.onDocumentClickConfirm.close();
			this.onDocumentClickConfirm = null;
		}

		this.closeDropdownPopup();

		this.setCurrentItem(selected);
		this.emit('onSelectValue', selected);
	}

	onEntityAdd(event)
	{
		let data = event.getData();
		let dropdown = data[0];
		dropdown.getPopupWindow().close();
		this.emit('onCreateNewItem');
	}

	onEntityReset()
	{
		this.setCurrentItem(null);
	}

	onEntitySearchStart()
	{
		this.setLoading(true);
		this._dropdown.setItems([]);
	}

	onExternalSearch()
	{
		return new Promise(
			(resolve) => {
				if (this.detailAutocompletePlacement)
				{
					const params = {appId: 0};
					BX.onCustomEvent(this.detailAutocompletePlacement, "Placements:startDefaultSearch", [params]);
					if (Type.isInteger(params["appId"]) && params["appId"] > 0)
					{
						if (!this.externalSearchResultHandlerList.has(params["appId"]))
						{
							this.externalSearchResultHandlerList.set(params["appId"], (result) => { resolve(result) });
						}

						return;
					}
				}

				resolve([]);
			}
		);
	}

	initPlacement(searchControl, container)
	{
		if (!this.detailAutocompletePlacement)
		{
			let isAutocompletePlacementEnabled = (
				Type.isPlainObject(this.clientResolverPlacementParams)
				&& this.clientResolverPlacementParams.hasOwnProperty("numberOfPlacements")
				&& Type.isNumber(this.clientResolverPlacementParams["numberOfPlacements"])
				&& this.clientResolverPlacementParams["numberOfPlacements"] > 0
			);

			if (isAutocompletePlacementEnabled)
			{
				this.detailAutocompletePlacement = new DetailSearchPlacement("CRM_REQUISITE_AUTOCOMPLETE");
			}

			if (this.detailAutocompletePlacement)
			{
				const dropdownPopup = this.getDropdownPopup(true);

				if (dropdownPopup)
				{
					this.beforeEntitySearchPopupCloseHandler = this.onBeforeEntitySearchPopupClose.bind(
						this,
						dropdownPopup._tryCloseByEvent.bind(dropdownPopup)
					);
					dropdownPopup._tryCloseByEvent = this.beforeEntitySearchPopupCloseHandler;

					this.entitySearchPopupCloseHandler = this.onEntitySearchPopupClose.bind(this);
					BX.addCustomEvent(
						dropdownPopup,
						'onPopupClose',
						this.entitySearchPopupCloseHandler
					);

					dropdownPopup.show();
				}

				this.placementsParamsHandler = this.onPlacementsParams.bind(this);
				BX.addCustomEvent(
					this.detailAutocompletePlacement,
					"Placements:params",
					this.placementsParamsHandler
				);

				this.beforeAddPlacementItemsHandler = this.onBeforeAppendPlacementItems.bind(this);
				BX.addCustomEvent(
					this.detailAutocompletePlacement,
					"Placements:beforeAppendItems",
					this.beforeAddPlacementItemsHandler
				);

				this.placementSearchParamsHandler = this.onPlacamentSearchParams.bind(this);
				BX.addCustomEvent(
					this.detailAutocompletePlacement,
					"Placements:searchParams",
					this.placementSearchParamsHandler
				);

				this.placementSetFoundItemsHandler = this.onPlacementSetFoundItems.bind(this);
				BX.addCustomEvent(
					this.detailAutocompletePlacement,
					"Placements:setFoundItems",
					this.placementSetFoundItemsHandler
				);

				this.placementEntitySelectHandler = this.onPlacementEntitySelect.bind(this);
				BX.addCustomEvent(
					this.detailAutocompletePlacement,
					"Placements:select",
					this.placementEntitySelectHandler
				);

				this.detailAutocompletePlacement.show(
					container,
					container.querySelector('div.crm-rq-popup-item-add-new'),
					{
						hideLoader: true
					}
				);
			}

			if (Type.isDomNode(this._domNodes.requisiteSearchString))
			{
				if (!this.searchQueryInputHandler)
				{
					this.searchQueryInputHandler = this.onSearchQueryInput.bind(this);
				}
				this._domNodes.requisiteSearchString.addEventListener("input", this.searchQueryInputHandler);
				this._domNodes.requisiteSearchString.addEventListener("keyup", this.searchQueryInputHandler);
			}
		}
	}

	onSearchQueryInput()
	{
		if (this._domNodes.requisiteSearchString.value !== this.currentSearchQueryText)
		{
			this.currentSearchQueryText = this._domNodes.requisiteSearchString.value;
			this.fireChangeSearchQueryEvent();
		}
	}

	fireChangeSearchQueryEvent()
	{
		if (this.detailAutocompletePlacement && Type.isDomNode(this._domNodes.requisiteSearchString))
		{
			BX.onCustomEvent(
				this.detailAutocompletePlacement,
				"Placements:changeQuery",
				[{currentSearchQuery: this._domNodes.requisiteSearchString.value}]
			);
		}
	}

	onEntitySearchComplete(/*dropdown, items*/)
	{
		this.setLoading(false);
	}

	onGetPopupAlertContainer(searchControl, container)
	{
		this.initPlacement(searchControl, container);
	}

	onAfterInstallDefaultApp(dropdown)
	{
		this.emit("onInstallDefaultApp");
	}

	onBeforeEntitySearchPopupClose(originalHandler, event)
	{
		if (this.onDocumentClickConfirm)
		{
			return BX.eventReturnFalse(event);
		}
		const eventResult = {active: false};
		BX.onCustomEvent(this.detailAutocompletePlacement, "Placements:active", [eventResult]);
		if (eventResult.active)
		{
			BX.unbind(document, 'click', this._dropdown.documentClickHandler);
			const f = function(messageBox, e) {
				BX.bind(document, 'click', this._dropdown.documentClickHandler);
				messageBox.close();
				this.onDocumentClickConfirm = null;
				BX.eventCancelBubble(e);
			}.bind(this);
			this.onDocumentClickConfirm = MessageBox.create({
				message: BX.message('CRM_EDITOR_PLACEMENT_CAUTION') || 'Dow you want to terminate process?',
				buttons: MessageBoxButtons.OK_CANCEL,
				modal: true,
				onOk: function(messageBox, button, e) {
					f(messageBox, e);
					this._dropdown.documentClickHandler(e);
					originalHandler(e);
				}.bind(this),
				onCancel: function(messageBox, button, e) {
					f(messageBox, e);
				}
			});
			BX.eventCancelBubble(event);
			this.onDocumentClickConfirm.show();
			return BX.eventReturnFalse(event);
		}
		originalHandler(event);
	}

	onEntitySearchPopupClose()
	{
		this.destroyPlacement();
	}

	destroyPlacement()
	{
		if (this.searchQueryInputHandler) {
			this._domNodes.requisiteSearchString.removeEventListener("input", this.searchQueryInputHandler);
			this._domNodes.requisiteSearchString.removeEventListener("keyup", this.searchQueryInputHandler);
			this.searchQueryInputHandler = null;
		}

		if (this._dropdown && this._dropdown.hasOwnProperty("documentClickHandler"))
		{
			BX.unbind(document, 'click', this._dropdown.documentClickHandler);
		}
		if (this.detailAutocompletePlacement)
		{
			const dropdownPopup = this.getDropdownPopup();

			if (dropdownPopup)
			{
				BX.removeCustomEvent(
					dropdownPopup,
					"onPopupClose",
					this.entitySearchPopupCloseHandler
				);
			}
			this.entitySearchPopupCloseHandler = null;

			BX.onCustomEvent(this.detailAutocompletePlacement, "Placements:destroy");

			BX.removeCustomEvent(
				this.detailAutocompletePlacement,
				"Placements:params",
				this.placementsParamsHandler
			);
			this.placementsParamsHandler = null;

			BX.removeCustomEvent(
				this.detailAutocompletePlacement,
				"Placements:beforeAppendItems",
				this.beforeAddPlacementItemsHandler
			);
			this.beforeAddPlacementItemsHandler = null;

			BX.removeCustomEvent(
				this.detailAutocompletePlacement,
				"Placements:searchParams",
				this.placementSearchParamsHandler
			);
			this.placementSearchParamsHandler = null;

			BX.removeCustomEvent(
				this.detailAutocompletePlacement,
				"Placements:setFoundItems",
				this.placementSetFoundItemsHandler
			);
			this.placementSetFoundItemsHandler = null;

			BX.removeCustomEvent(
				this.detailAutocompletePlacement,
				"Placements:select",
				this.placementEntitySelectHandler
			);
			this.placementEntitySelectHandler = null;

			this.detailAutocompletePlacement = null;

			this.creatingItem = null;

			this._dropdown.setItems([]);
		}
	}

	onPlacementsParams(params)
	{
		if (Type.isObject(params))
		{
			if (this._dropdown)
			{
				this._dropdown.setPlacementParams(this.clientResolverPlacementParams);
			}

			if (this.clientResolverPlacementParams)
			{
				if (params.hasOwnProperty("placementParams"))
				{
					params["placementParams"] = this.clientResolverPlacementParams;
				}
				if (params.hasOwnProperty("currentSearchQuery"))
				{
					params["currentSearchQuery"] = this._domNodes.requisiteSearchString.value;
				}
			}
		}
	}

	onBeforeAppendPlacementItems()
	{
		const dropdownPopup = this.getDropdownPopup();

		if (dropdownPopup)
		{
			BX.addClass(dropdownPopup.popupContainer, "client-editor-popup");
		}
	}

	onPlacamentSearchParams(params)
	{
		params["searchQuery"] = this._domNodes.requisiteSearchString.value;
	}

	onPlacementSetFoundItems(placementItem, results)
	{
		const items = [];
		results.forEach(function(result) {
			items.push({
				id: result.id,
				title: result.name,
				appSid: placementItem["appSid"],
				module: 'crm',
				subModule: 'rest',
				subTitle: placementItem["title"],
				attributes: {
					phone: result.phone ? [{value: result.phone}] : '',
					email: result.email ? [{value: result.email}] : '',
					web: result.web ? [{value: result.web}] : ''
				}
			});
		}.bind(this));

		let appId = parseInt(placementItem["placementInfo"]["id"]);
		if (appId > 0 && this.externalSearchResultHandlerList.has(appId))
		{
			this.externalSearchResultHandlerList.get(appId)(items);
			this.externalSearchResultHandlerList.delete(appId);
		}
		else
		{
			this._dropdown.setItems(items);
		}
	}

	onPlacementEntitySelect(data) {
		const entityData = {
			type: data["entityType"],
			id: data["id"],
			title: data["title"],
			fields: data["fields"]
		};
		if (
			Type.isPlainObject(entityData["fields"])
			&& entityData["fields"].hasOwnProperty("RQ_ADDR")
			&& Type.isPlainObject(entityData["fields"]["RQ_ADDR"])
		)
		{
			const responseHandler = function(response)
			{
				const status = BX.prop.getString(response, "status", "");
				const data = BX.prop.getObject(response, "data", {});
				const messages = [];

				if (status === "error")
				{
					const errors = BX.prop.getArray(response, "errors", []);
					for (let i = 0; i < errors.length; i++)
					{
						messages.push(BX.prop.getString(errors[i], "message"));
					}
				}

				if (messages.length > 0)
				{
					BX.UI.Notification.Center.notify(
						{
							content: messages.join("<br>"),
							position: "top-center",
							autoHideDelay: 10000
						}
					);
					delete entityData["fields"]["RQ_ADDR"];
				}
				else
				{
					entityData["fields"]["RQ_ADDR"] = data;
				}
				this.selectEntity(entityData);
			}.bind(this);

			BX.ajax.runAction(
				'crm.requisite.address.getLocationAddressJsonByFields',
				{
					data: {
						addresses: entityData["fields"]["RQ_ADDR"]
					}
				}
			).then(responseHandler, responseHandler);
		}
		else
		{
			this.selectEntity(entityData);
		}
	}

	static create(id, settings)
	{
		let self = new RequisiteAutocompleteField();
		self.initialize(id, settings);
		return self;
	}
}

class Dropdown extends BX.UI.Dropdown
{
	constructor(options)
	{
		super(options);
		this.feedbackFormParams = BX.prop.getObject(options, "feedbackFormParams", {});
		this.canAddRequisite = BX.prop.getBoolean(options, "canAddRequisite", false);
		this.externalSearchHandler = BX.prop.getFunction(options, "externalSearchHandler", null);
		this.placementParams = BX.prop.getObject(options, "placementParams", {});
		this.installDefaultAppHandler = this.onClickInstallDefaultApp.bind(this);
		this.installDefaultAppTimeout = 7000;
		this.afterInstallDefaultAppHandler = this.onAfterInstallDefaultApp.bind(this);
		this.popupAlertContainer = null;
		this.defaultAppInstallLoader = null;
		this.isDefaultAppInstalled = false;
	}

	setPlacementParams(params)
	{
		this.placementParams = params;
	}

	setExternalSearchHandler(handler)
	{
		this.externalSearchHandler = handler
	}

	isTargetElementChanged()
	{
		return false;
	}

	getItemsListContainer()
	{
		if (!this.itemListContainer)
		{
			this.itemListContainer = BX.create('div', {
				attrs: {
					className: 'ui-dropdown-container rq-dropdown-container'
				}
			});
		}
		return this.itemListContainer;
	}

	setFeedbackFormParams(feedbackFormParams)
	{
		this.feedbackFormParams = Type.isPlainObject(feedbackFormParams) ? feedbackFormParams : {};
	}

	setCanAddRequisite(canAdd)
	{
		this.canAddRequisite = !!canAdd;
	}

	setMinSearchStringLength(length)
	{
		this.minSearchStringLength = length;
	}
	
	getDefaultAppInfo()
	{
		return BX.prop.getObject(this.placementParams, "defaultAppInfo", {});
	}
	
	isDefaultAppCanInstall()
	{
		const defaultAppInfo = this.getDefaultAppInfo();
		
		return (
			Type.isStringFilled(BX.prop.get(defaultAppInfo, "code", ""))
			&& Type.isStringFilled(BX.prop.get(defaultAppInfo, "title", ""))
			&& BX.prop.get(defaultAppInfo, "isAvailable", "N") === "Y"
			/*&& BX.prop.get(defaultAppInfo, "isInstalled", "N") !== "Y"*/
		);
	}

	getPopupAlertContainer()
	{
		if (!this.popupAlertContainer)
		{
			let items = [];

			if (this.isDefaultAppCanInstall())
			{
				const appTitleText = Text.encode(this.getDefaultAppInfo()["title"]);
				const appInstallText = Text.encode(Loc.getMessage('REQUISITE_AUTOCOMPLETE_INSTALL'));
				items.push(
					Tag.render`
					<div class="crm-rq-popup-item crm-rq-popup-item-add-new">
						<button
							class="crm-rq-popup-item-add-inst-app-btn"><span></span><span
								class="crm-rq-popup-item-add-new-btn-text"><span>${appTitleText}</span><span
								style="margin-left: 20px; width: 100px;"><a
									href="#"
									onclick="${this.installDefaultAppHandler}">${appInstallText}</a>
							</span></span>
						</button>
					</div>`
				);
			}

			let feedbackAvailable = (Object.keys(this.getFeedbackFormParams()).length > 0);
			if (feedbackAvailable)
			{
				const textParts =
					Loc.getMessage('REQUISITE_AUTOCOMPLETE_ADVICE_NEW_SERVICE').split('#ADVICE_NEW_SERVICE_LINK#')
				;
				const item = Tag.render`<div class="crm-rq-popup-item crm-rq-popup-item-helper"></div>`;

				if (textParts[0] && textParts[0].length)
				{
					Dom.append(document.createTextNode(textParts[0]), item);
				}
				const newServiceLinkText = Loc.getMessage('REQUISITE_AUTOCOMPLETE_ADVICE_NEW_SERVICE_LINK');
				Dom.append(
					Tag.render`<a href="" onclick="${this.showFeedbackForm.bind(this)}">${newServiceLinkText}</a>`,
					item
				);

				if (textParts[1] && textParts[1].length)
				{
					Dom.append(document.createTextNode(textParts[1]), item);
				}

				items.push(item);
			}

			if (this.canAddRequisite)
			{
				items.push(Tag.render`
					<div class="crm-rq-popup-item crm-rq-popup-item-add-new">
						<button class="crm-rq-popup-item-add-new-btn">
							<span class="ui-btn crm-rq-btn ui-btn-icon-custom ui-btn-primary ui-btn-round"
								onclick="${this.onEmptyValueEvent.bind(this)}"></span>
							<span class="crm-rq-popup-item-add-new-btn-text"
								onclick="${this.onEmptyValueEvent.bind(this)}"
								>${BX.prop.getString(this.messages, "creationLegend")}</span>
						</button>
					</div>`);
			}

			this.popupAlertContainer = items.length ? Tag.render`
				<div class="crm-rq-popup-wrapper">
					<div class="crm-rq-popup-items-list">${items}</div>
				</div>
			` : Tag.render`<div></div>`;

			BX.onCustomEvent(this, "Dropdown:onGetPopupAlertContainer", [this, this.popupAlertContainer]);
		}

		this.togglePopupAlertVisibility();

		return this.popupAlertContainer;
	}

	getTargetElementValue()
	{
		if (this.targetElement !== "undefined" && Type.isStringFilled(this.targetElement["value"]))
		{
			return this.targetElement["value"].trim();
		}

		return "";
	}

	togglePopupAlertVisibility()
	{
		if (Type.isDomNode(this.popupAlertContainer))
		{
			const numberOfPlacements = BX.prop.getInteger(this.placementParams, "numberOfPlacements", 0);
			const isVisible = (numberOfPlacements > 0 || this.getItems().length <= 0);
			this.popupAlertContainer.style.display = isVisible ? "" : "none";
		}
	}

	setItems(items)
	{
		super.setItems([{id: 1, name: "N", title: "T"}]);
		this.togglePopupAlertVisibility();
		super.setItems(items);
	}

	getNewAlertContainer(/*items*/)
	{
		return null;
	}

	disableTargetElement()
	{
		// cancel original handler
	}

	getFeedbackFormParams()
	{
		return this.feedbackFormParams;
	}

	showFeedbackForm(event)
	{
		event.preventDefault();
		this.getPopupWindow().close();
		if (!this._feedbackForm)
		{
			this._feedbackForm = new BX.UI.Feedback.Form(this.getFeedbackFormParams());
		}
		this._feedbackForm.openPanel();
	}

	searchItemsByStr(target)
	{
		if (this.externalSearchHandler)
		{
			return this.externalSearchHandler().then((result) => {return result;});
		}

		return super.searchItemsByStr(target);
	}

	onClickInstallDefaultApp(event)
	{
		event.stopPropagation();
		event.preventDefault();

		if (this.defaultAppInstallLoader)
		{
			return;
		}

		if (Type.isDomNode(event.target) && Type.isDomNode(event.target.parentNode))
		{
			const parent = event.target.parentNode;

			Dom.hide(event.target);

			this.defaultAppInstallLoader = (
				this.defaultAppInstallLoader
				|| new BX.Loader({
					target: parent,
					size: 30,
					offset: { top: "-23px" }
				})
			);
			this.defaultAppInstallLoader.show();
		}

		if (this.isDefaultAppCanInstall())
		{
			this.isDefaultAppInstalled = false;
			BX.loadExt('marketplace').then(
				() => {
					setTimeout(
						() => {
							if (!this.isDefaultAppInstalled)
							{
								this.finalInstallDefaultApp();
							}
						},
						this.installDefaultAppTimeout
					);
					top.BX.addCustomEvent(
						top,
						"Rest:AppLayout:ApplicationInstall",
						this.afterInstallDefaultAppHandler
					);
					BX.rest.Marketplace.install(
						{
							CODE: this.placementParams["defaultAppInfo"]["code"],
							SILENT_INSTALL: "Y",
							DO_NOTHING: "Y"
						}
					);
				}
			).catch(
				() => {
					top.BX.removeCustomEvent(
						top,
						"Rest:AppLayout:ApplicationInstall",
						this.afterInstallDefaultAppHandler
					);
				}
			);
		}
	}

	wait(ms)
	{
		return new Promise((resolve) => setTimeout(resolve, parseInt(ms)));
	}

	checkDefAppHandler()
	{
		const countryId = BX.prop.getInteger(this.placementParams, "countryId", 0);

		return new Promise(
			(resolve, reject) => {
				BX.ajax.runAction(
					'crm.requisite.autocomplete.checkDefaultAppHandler',
					{ data: { countryId: countryId } }
				).then(
					(data) => {
						if (
							Type.isPlainObject(data)
							&& data.hasOwnProperty("data")
							&& data.hasOwnProperty("status")
							&& data["status"] === "success"
							&& Type.isBoolean(data["data"])
							&& data["data"]
						)
						{
							resolve();
						}
						else
						{
							reject();
						}
					}
				);
			}
		);
	}

	awaitHandler(context)
	{
		this.wait(context.waitTime)
			.then(() => {
				this.checkDefAppHandler()
					.then(() => {
						this.finalInstallDefaultAppSuccess();
					})
					.catch(() => {
						if (--context.numberOfTimes > 0) {
							this.awaitHandler(context)
						}
						else {
							this.finalInstallDefaultApp();
						}
					});
			});
	}

	onAfterInstallDefaultApp(installed, eventResult)
	{
		this.isDefaultAppInstalled = true;
		top.BX.removeCustomEvent(top, "Rest:AppLayout:ApplicationInstall", this.afterInstallDefaultAppHandler);

		const numberOfTimes = 3;
		const waitTime = Math.floor(this.installDefaultAppTimeout / numberOfTimes);
		if (!!installed)
		{
			this.awaitHandler({waitTime: waitTime, numberOfTimes: 3})
		}
		else
		{
			this.finalInstallDefaultApp();
		}
	}

	finalInstallDefaultAppSuccess()
	{
		BX.onCustomEvent(this, "Dropdown:onAfterInstallDefaultApp", [this]);
		this.finalInstallDefaultApp();
	}

	finalInstallDefaultApp()
	{
		if (this.defaultAppInstallLoader)
		{
			this.defaultAppInstallLoader.destroy();
			this.defaultAppInstallLoader = null;
		}

		if (this.popupWindow)
		{
			this.popupWindow.close();
		}
	}
}
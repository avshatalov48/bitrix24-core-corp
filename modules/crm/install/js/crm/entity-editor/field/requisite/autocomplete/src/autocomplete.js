import {Dom, Loc, Tag, Text, Type} from "main.core";
import {EventEmitter} from "main.core.events";
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

		this.doInitialize();
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

		let placeholder = this._placeholderText.length ?
			Loc.getMessage('REQUISITE_AUTOCOMPLETE_FILL_IN').replace('#FIELD_NAME#', this._placeholderText) : "";
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
			this._dropdown = new Dropdown(
				{
					searchAction: BX.prop.getString(this._settings, "searchAction", ""),
					items: [],
					enableCreation: true,
					enableCreationOnBlur: false,
					autocompleteDelay: 1000,
					messages:
						{
							creationLegend: Loc.getMessage('REQUISITE_AUTOCOMPLETE_ADD_REQUISITE'),
							notFound: Loc.getMessage('REQUISITE_AUTOCOMPLETE_NOT_FOUND'),
						}
				}
			);
			EventEmitter.subscribe(this._dropdown, 'BX.UI.Dropdown:onSelect', this.onEntitySelect.bind(this));
			EventEmitter.subscribe(this._dropdown, 'BX.UI.Dropdown:onAdd', this.onEntityAdd.bind(this));
			EventEmitter.subscribe(this._dropdown, 'BX.UI.Dropdown:onReset', this.onEntityReset.bind(this));
			EventEmitter.subscribe(this._dropdown, 'BX.UI.Dropdown:onBeforeSearchStart', this.onEntitySearchStart.bind(this));
			EventEmitter.subscribe(this._dropdown, 'BX.UI.Dropdown:onSearchComplete', this.onEntitySearchComplete.bind(this));
		}
		this._dropdown.searchOptions = this._context;
		this._dropdown.targetElement = this._domNodes.requisiteSearchString;
		this._dropdown.init();
		this.setEnabled(this._isEnabled);
		this.setShowFeedbackLink(this._showFeedbackLink);
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
		this.setLoading(false);
	}

	getState()
	{
		let state = {
			currentItem: this._currentItem,
			searchQuery: Type.isDomNode(this._domNodes.requisiteSearchString) ?
				this._domNodes.requisiteSearchString.value : null,
			items: Type.isObject(this._dropdown) ? this._dropdown.getItems() : []
		};
		return state;
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
			let placeholder = this._placeholderText.length ?
				Loc.getMessage('REQUISITE_AUTOCOMPLETE_FILL_IN').replace('#FIELD_NAME#', this._placeholderText) : "";

			this._domNodes.requisiteSearchString.placeholder = placeholder;
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

		dropdown.getPopupWindow().close();
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

	onEntitySearchComplete()
	{
		this.setLoading(false);
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

	getPopupAlertContainer()
	{
		if (!this.popupAlertContainer)
		{
			let items = [];
			let feedbackAvailable = (Object.keys(this.getFeedbackFormParams()).length > 0);
			if (feedbackAvailable)
			{
				let textParts = Loc.getMessage('REQUISITE_AUTOCOMPLETE_ADVICE_NEW_SERVICE').split('#ADVICE_NEW_SERVICE_LINK#');
				let item = Tag.render`<div class="crm-rq-popup-item crm-rq-popup-item-helper"></div>`;

				if (textParts[0] && textParts[0].length)
				{
					Dom.append(document.createTextNode(textParts[0]), item);
				}

				Dom.append(Tag.render`<a href="" onclick="${this.showFeedbackForm.bind(this)}">${Loc.getMessage('REQUISITE_AUTOCOMPLETE_ADVICE_NEW_SERVICE_LINK')}</a>`, item);

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
							<span class="ui-btn crm-rq-btn ui-btn-icon-custom ui-btn-primary ui-btn-round" onclick="${this.onEmptyValueEvent.bind(this)}"></span>
							<span class="crm-rq-popup-item-add-new-btn-text" onclick="${this.onEmptyValueEvent.bind(this)}">${BX.prop.getString(this.messages, "creationLegend")}</span>
						</button>
					</div>`);
			}

			this.popupAlertContainer = items.length ? Tag.render`
				<div class="crm-rq-popup-wrapper">
					<div class="crm-rq-popup-items-list">${items}</div>
				</div>
			` : Tag.render`<div></div>`;
		}
		this.togglePopupAlertVisibility();
		return this.popupAlertContainer;
	}

	togglePopupAlertVisibility()
	{
		if (Type.isDomNode(this.popupAlertContainer))
		{
			this.popupAlertContainer.style.display = (this.getItems().length > 0) ? "none" : "";
		}
	}

	setItems(items)
	{
		super.setItems(items);
		this.togglePopupAlertVisibility();
	}

	getNewAlertContainer(items)
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
}
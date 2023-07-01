BX.namespace("BX.Crm");


if(typeof BX.Crm.EntityEditorClientMode === "undefined")
{
	BX.Crm.EntityEditorClientMode =
		{
			undefined: 0,
			select: 1,
			create: 2,
			edit: 3,
			loading: 4
		};
}

if(typeof BX.Crm.EntityEditorClientSearchBox === "undefined")
{
	BX.Crm.EntityEditorClientSearchBox = function()
	{
		this._id = "";
		this._settings = {};

		this._editor = null;

		this._clientEntityEditor = null;
		this._clientEntityEditorEnabled = null;
		this._clientEntityEditorFields = null;
		this._clientEntityEditorFieldsParams = null;
		this._clientEntityEditorChangeHandler =  BX.delegate(this.onClientEntityEditorChange, this);

		this._container = null;
		this._wrapper = null;

		this._badgeElement = null;
		this._editButton = null;
		this._changeButton = null;
		this._deleteButton = null;
		this._loadingIcon = null;

		this._parentField = null;
		this._entityInfo = null;
		this._entityTypeName = "";

		this._externalEditorPages = null;

		this._searchInput = null;
		this._searchControl = null;

		this._loaderConfig = null;

		this._changeNotifier = null;
		this._titleChangeNotifier = null;
		this._resetNotifier = null;
		this._deletionNotifier = null;

		this._enableDeletion = true;

		this._editButtonHandler = BX.delegate(this.onEditButtonClick, this);
		this._changeButtonHandler = BX.delegate(this.onChangeButtonClick, this);
		this._deleteButtonHandler = BX.delegate(this.onDeleteButtonClick, this);
		this._inputFocusHandler = BX.delegate(this.onInputFocus, this);
		this._inputBlurHandler = BX.delegate(this.onInputBlur, this);
		this._inputDblClickHandler = BX.delegate(this.onInputDblClick, this);

		this._mode = BX.Crm.EntityEditorClientMode.undefined;
		this._multifieldChangeNotifier = null;

		this._maskedPhone = null;
		this._emailInput = null;

		this._phoneId = "";
		this._emailId = "";

		this._enableQuickEdit = true;

		this._hasFocus = false;
		this._hasLayout = false;
		this._hasMultifieldLayout = false;
		this._isRequired = false;
		this._enableRequisiteSelection = false;

		this._categoryId = 0;
		this._extraCategoryIds = [];

		this.detailSearchPlacement = null;

		this.entitySearchPopupCloseHandler = null;
		this.placementSearchParamsHandler = null;
		this.beforeAddPlacementItemsHandler = null;
		this.placementEntitySelectHandler = null;
		this.placementSetFoundItemsHandler = null;

		this.searchControlPopup = null;

		this.onDocumentClickConfirm = null;

		this.creatingItem = null;

		this._selectEntityExternalHandler = null;
	};
	BX.Crm.EntityEditorClientSearchBox.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};

				this._editor = BX.prop.get(this._settings, "editor", null);
				this._parentField = BX.prop.get(this._settings, "parentField", null);
				this._container = BX.prop.getElementNode(this._settings, "container", null);

				this._isRequired = BX.prop.getBoolean(this._settings, 'isRequired', false);

				var entityInfo = BX.prop.get(this._settings, "entityInfo", null);
				if(entityInfo)
				{
					this._entityInfo = entityInfo;
					this._entityTypeName = entityInfo.getTypeName();
				}
				else
				{
					this._entityTypeName = BX.prop.getString(this._settings, "entityTypeName", "");
				}
				this._entityTypeId = BX.prop.getInteger(this._settings, "entityTypeId", 0);
				this._categoryId = BX.prop.getInteger(this._settings, "categoryId", 0);
				this._extraCategoryIds =  BX.prop.getArray(this._settings, "extraCategoryIds", []);

				this._mode = BX.prop.getInteger(this._settings, "mode", BX.Crm.EntityEditorClientMode.select);
				if(this._mode === BX.Crm.EntityEditorClientMode.edit && !(this._entityInfo && this._entityInfo.canUpdate()))
				{
					this._mode = BX.Crm.EntityEditorClientMode.select;
				}

				this._enableQuickEdit = BX.prop.getBoolean(this._settings, "enableQuickEdit", true);
				this._enableDeletion = BX.prop.getBoolean(this._settings, "enableDeletion", true);
				this._loaderConfig = BX.prop.get(this._settings, "loaderConfig", null);

				this._changeNotifier = BX.CrmNotifier.create(this);
				this._titleChangeNotifier = BX.CrmNotifier.create(this);
				this._deletionNotifier = BX.CrmNotifier.create(this);
				this._resetNotifier = BX.CrmNotifier.create(this);

				this._multifieldChangeNotifier = BX.CrmNotifier.create(this);
				this._clientEntityEditorEnabled = BX.prop.getBoolean(this._settings, "clientEditorEnabled", false);
				this._clientEntityEditorFields = BX.prop.get(this._settings, "clientEditorFields", []);
				this._clientEntityEditorFieldsParams = BX.prop.get(this._settings, "clientEditorFieldsParams", {});

				this._enableRequisiteSelection = BX.prop.getBoolean(this._settings, "enableRequisiteSelection", false);
			},
			getMessage: function(name)
			{
				return BX.prop.getString(BX.Crm.EntityEditorClientSearchBox.messages, name);
			},
			getEntity: function()
			{
				return this._entityInfo;
			},
			setEntityTypeName: function(entityTypeName)
			{
				if(this._entityTypeName !== entityTypeName)
				{
					this._entityTypeName = entityTypeName;
				}
			},
			setEntity: function(entityInfo, enableNotification)
			{
				var previousEntityInfo = this._entityInfo;

				this._entityInfo = entityInfo;

				if(entityInfo)
				{
					this._entityTypeName = entityInfo.getTypeName();
				}

				if(this._entityInfo && this._entityInfo.getId() === 0)
				{
					this.setMode(BX.Crm.EntityEditorClientMode.create);
				}
				else if(this._mode === BX.Crm.EntityEditorClientMode.loading)
				{
					this.setMode(BX.Crm.EntityEditorClientMode.edit);
				}
				else
				{
					this.setMode(BX.Crm.EntityEditorClientMode.select);
					this.releaseEntityEditor();
				}

				this.clearMultifieldLayout();
				this.adjust();

				if(enableNotification)
				{
					this._changeNotifier.notify([ this._entityInfo , previousEntityInfo ]);
				}
			},
			setupEntity: function(entityTypeName, entityId)
			{
				if(entityId <= 0)
				{
					return;
				}

				this.setEntityTypeName(entityTypeName);
				this.loadEntityInfo(entityId);
			},
			hasEntity: function()
			{
				return !!this._entityInfo;
			},
			isNewEntity: function()
			{
				return this._entityInfo && this._entityInfo.getId() === 0;
			},
			canUpdateEntity: function()
			{
				return this._entityInfo && this._entityInfo.canUpdate();
			},
			getMode: function()
			{
				return this._mode;
			},
			setMode: function(mode)
			{
				if(!BX.type.isNumber(mode))
				{
					mode = parseInt(mode);
					if(!BX.type.isNumber(mode))
					{
						throw "EntityEditorClientSearchBox: Argument must be integer.";
					}
				}

				if(this._mode === mode)
				{
					return;
				}

				this._mode = mode;
			},
			setSelectRequisiteSelectionEnabled: function(enableRequisiteSelection)
			{
				if (this._enableRequisiteSelection !== enableRequisiteSelection)
				{
					this._enableRequisiteSelection = enableRequisiteSelection;
					if (this._clientEntityEditor)
					{
						var requisitesController = null;
						var controllers = this._clientEntityEditor._controllers;
						if (controllers.length > 0)
						{
							for (var controllerIndex in controllers)
							{
								if (controllers[controllerIndex] instanceof BX.Crm.EntityEditorRequisiteController)
								{
									requisitesController = controllers[controllerIndex];
								}
							}
						}
						if (requisitesController)
						{
							requisitesController.setSelectModeEnabled(this._enableRequisiteSelection);
						}
					}
				}
			},
			layout: function(options)
			{
				if(this._hasLayout)
				{
					return;
				}

				if(!BX.type.isPlainObject(options))
				{
					options = {};
				}

				this._wrapper = BX.create("div", { props: { className: "crm-entity-widget-content-search-row" } });
				this.innerWrapper = BX.create("div", { props: { className: "crm-entity-widget-content-search-inner" } });

				var anchor = BX.prop.getElementNode(options, "anchor", null);
				if(anchor)
				{
					this._container.insertBefore(this._wrapper, anchor);
				}
				else
				{
					this._container.appendChild(this._wrapper);
				}

				this._wrapper.appendChild(this.innerWrapper);

				var boxWrapper = BX.create("div", { props: { className: "crm-entity-widget-content-search-box" } });
				this.innerWrapper.appendChild(boxWrapper);

				var icon = BX.create("div", { props: { className: "crm-entity-widget-img-box" } });
				if(this._entityTypeName === BX.CrmEntityType.names.company)
				{
					BX.addClass(icon, "crm-entity-widget-img-company");
				}
				else if(this._entityTypeName === BX.CrmEntityType.names.contact)
				{
					BX.addClass(icon, "crm-entity-widget-img-contact");
				}
				boxWrapper.appendChild(icon);

				this._searchInput = BX.create("input",
					{
						props:
							{
								type: "text",
								placeholder: BX.prop.getString(this._settings, "placeholder", ""),
								className: "crm-entity-widget-content-input crm-entity-widget-content-search-input",
								autocomplete: "nope"
							}
					}
				);
				boxWrapper.appendChild(this._searchInput);
				BX.bind(this._searchInput, "focus", this._inputFocusHandler);
				BX.bind(this._searchInput, "blur", this._inputBlurHandler);
				BX.bind(this._searchInput, "dblclick", this._inputDblClickHandler);

				this._badgeElement = BX.create("div", { props: { className: "crm-entity-widget-badge" } });
				boxWrapper.appendChild(this._badgeElement);

				this._editButton = BX.create("div", { props: { className: "crm-entity-widget-btn-edit" } });
				boxWrapper.appendChild(this._editButton);

				BX.bind(this._editButton, "click", this._editButtonHandler);

				this._changeButton = BX.create(
					"div",
					{
						props:
							{
								className: "crm-entity-widget-btn-select",
								title: this.getMessage(this._entityTypeName.toLowerCase() + "ChangeButtonHint")
							}
					}
				);
				boxWrapper.appendChild(this._changeButton);

				BX.bind(this._changeButton, "click", this._changeButtonHandler);

				this._loadingIcon = BX.create("div", {
					style: {
						display: "none"
					},
					props: {
						className: "ui-ctl-after ui-ctl-icon-loader",

					}
				});
				boxWrapper.appendChild(this._loadingIcon);

				if(this._entityInfo)
				{
					//Move it in BX.UI.Dropdown
					this._searchInput.value = this._entityInfo.getTitle();
				}

				var searchOptions = {
					types: [ this._entityTypeName ],
					categoryId: this._categoryId,
					extraCategoryIds: this._extraCategoryIds,
					scope: 'index',
				};
				if (BX.prop.getBoolean(this._settings, 'enableMyCompanyOnly', false))
				{
					searchOptions.isMyCompany = 'Y';
				}

				var creationLegend = BX.prop.getString(this._settings, 'creationLegend', '');
				if (creationLegend === '')
				{
					creationLegend = this.getMessage(this._entityTypeName.toLowerCase() + "ToCreateLegend");
				}

				this._searchControl = new BX.UI.Dropdown(
					{
						searchAction: 'crm.api.entity.search',
						searchOptions: searchOptions,
						searchResultRenderer: null,
						targetElement: this._searchInput,
						items: BX.prop.getArray(this._settings, "lastEntityInfos", []),
						enableCreation: BX.prop.getBoolean(this._settings, "enableCreation", false),
						enableCreationOnBlur: this._enableQuickEdit,
						autocompleteDelay: 500,
						context: { origin: "crm.entity.editor", isEmbedded: this._editor.isEmbedded()  },
						messages:
							{
								creationLegend: creationLegend,
								notFound: this.getMessage("notFound")
							},
						events:
							{
								onSelect: this.onEntitySelect.bind(this),
								onAdd: this.onEntityAdd.bind(this),
								onReset: this.onEntityReset.bind(this),
								onGetNewAlertContainer: this.onEntitySearch.bind(this)
							}
					}
				);

				this._deleteButton = BX.create("div", { props: { className: "crm-entity-widget-btn-close" } });
				if(!this._enableDeletion)
				{
					this._deleteButton.style.display = "none";
				}
				this.innerWrapper.appendChild(this._deleteButton);
				BX.bind(this._deleteButton, "click", this._deleteButtonHandler);

				window.setTimeout(function(){ this.adjust(options); }.bind(this), 0);
				this._hasLayout = true;
			},
			clearLayout: function()
			{
				if(!this._hasLayout)
				{
					return;
				}

				if (this.onDocumentClickConfirm)
				{
					this.onDocumentClickConfirm.close();
					this.onDocumentClickConfirm = null;
				}

				this.clearMultifieldLayout();

				BX.unbind(this._editButton, "click", this._editButtonHandler);
				BX.unbind(this._deleteButton, "click", this._deleteButtonHandler);
				BX.unbind(this._changeButton, "click", this._changeButtonHandler);

				this._deleteButton = this._changeButton = this._searchControl = this._badgeElement = this._loadingIcon = null;
				this._wrapper = BX.remove(this._wrapper);

				this.releaseEntityEditor();
				this._hasLayout = false;
			},
			release: function()
			{
				this.releaseEntityEditor();
			},
			releaseEntityEditor: function()
			{
				if (this._clientEntityEditor)
				{
					BX.Event.EventEmitter.unsubscribe(
						this,
						'onSelectEntityExternal',
						this._selectEntityExternalHandler
					);
					this._clientEntityEditor.removeControlChangeListener(this._clientEntityEditorChangeHandler);
					this._clientEntityEditor.release();
					this._clientEntityEditor = null;
				}
			},
			validate: function(result)
			{
				if (this._isRequired && this._searchInput && this._searchInput.value === '')
				{
					return false;
				}
				if (this._clientEntityEditorEnabled && this._clientEntityEditor)
				{
					return this._clientEntityEditor.validate(result);
				}
				return true;
			},
			prepareMultifieldLayout: function()
			{
				if (this._hasMultifieldLayout)
				{
					return;
				}

				this._multifieldContainer = BX.create("div", { props: { className: "crm-entity-widget-content-multifield" } });
				this._wrapper.appendChild(this._multifieldContainer);

				if (this._clientEntityEditorEnabled)
				{
					this._editorContainer = BX.create("div", { props: { className: "crm-entity-widget-content-client-editor" } });
					this.createClientEntityEditor(
						this._editorContainer,
						this._entityInfo.getTypeName() + '_' + this._entityInfo.getId() + '_client_editor'
					);
					this._multifieldContainer.appendChild(this._editorContainer);
				}
				else
				{
					this._phoneInput = BX.create("input", { props: { type: "hidden" } });
					this._phoneCountryCodeInput = BX.create("input", { props: { type: "hidden" } });
					this._countryFlagNode = BX.create("span", { props: {className: "crm-entity-widget-content-country-flag"}});
					this._maskedPhoneInput = BX.create("input",
						{
							props:
								{
									type: "text",
									placeholder: BX.message("CRM_EDITOR_PHONE"),
									className: "crm-entity-widget-content-input crm-entity-widget-content-input-phone",
									autocomplete: "nope"
								}
						}
					);

					this._multifieldContainer.appendChild(
						BX.create("div",
							{
								props: { className: "crm-entity-widget-content-multifield-item" },
								children:
									[
										this._countryFlagNode,
										this._maskedPhoneInput,
										this._phoneInput,
										this._phoneCountryCodeInput
									]
							}
						)
					);

					var defaultCountry = null;
					if (
						this._parentField
						&& this._parentField._schemeElement
						&& BX.Type.isPlainObject(this._parentField._schemeElement._options)
						&& BX.Type.isStringFilled(this._parentField._schemeElement._options.defaultCountry)
					)
					{
						defaultCountry = this._parentField._schemeElement._options.defaultCountry
					}

					var countryCode = this.getCountryCode();

					this._maskedPhone = new BX.Crm.PhoneNumberInput({
						node: this._maskedPhoneInput,
						flagNode: this._countryFlagNode,
						isSelectionIndicatorEnabled: true,
						searchDialogContextCode: 'CRM_ENTITY_EDITOR_PHONE',
						userDefaultCountry: defaultCountry,
						savedCountryCode: countryCode,
						onChange: BX.delegate(this.onPhoneChange, this),
						onCountryChange: BX.delegate(this.onPhoneCountryChange, this),
					});

					this._emailInput = BX.create("input",
						{
							props:
								{
									type: "text",
									placeholder: BX.message("CRM_EDITOR_EMAIL"),
									className: "crm-entity-widget-content-input",
									autocomplete: "nope"
								}
						}
					);
					BX.bind(this._emailInput, "input", BX.delegate(this.onEmailChange, this));

					this._multifieldContainer.appendChild(
						BX.create("div",
							{
								props: { className: "crm-entity-widget-content-multifield-item" },
								children: [ this._emailInput ]
							}
						)
					);
				}

				var fieldCounter = 0;
				var phoneId = '';
				var emailId = '';

				this._phoneId = '';
				this._emailId = '';

				if (this._entityInfo)
				{
					var phones = this._entityInfo.getPhones();
					if (phones.length > 0)
					{
						this._phoneId = BX.prop.getString(phones[0], "ID", "");
						phoneId = this.parseMultifieldPseudoId(this._phoneId);
						if (phoneId >= 0)
						{
							fieldCounter = phoneId + 1;
						}

						this.setPhoneFieldValue(BX.prop.getString(phones[0], "VALUE", ""), countryCode);
					}

					var emails = this._entityInfo.getEmails();
					if(emails.length === 0)
					{
						this.setEmailFieldValue("");
					}
					else
					{
						this._emailId = BX.prop.getString(emails[0], "ID", "");
						emailId = this.parseMultifieldPseudoId(this._emailId);
						if(emailId >= 0)
						{
							fieldCounter = emailId + 1;
						}
						this.setEmailFieldValue(BX.prop.getString(emails[0], "VALUE", ""));
					}
				}
				else
				{
					this.setEmailFieldValue("");
				}

				if(this._phoneId === "")
				{
					this._phoneId = this.prepareMultifieldPseudoId(fieldCounter);
					fieldCounter++;
				}

				if(this._emailId === "")
				{
					this._emailId = this.prepareMultifieldPseudoId(fieldCounter);
					fieldCounter++;
				}

				this._hasMultifieldLayout = true;
			},
			clearMultifieldLayout: function()
			{
				if(!this._hasMultifieldLayout)
				{
					return;
				}

				this._multifieldContainer = BX.remove(this._multifieldContainer);

				this._phoneInput = this._maskedPhone = this._emailInput = this._phoneCountryCodeInput = null;
				this._phoneId = this._emailId = "";

				this._hasMultifieldLayout = false;
			},
			prepareMultifieldPseudoId: function(num)
			{
				return ("n" + num.toString());
			},
			parseMultifieldPseudoId: function(pseudoId)
			{
				var m = pseudoId.match(/^n(\d+)/);
				return BX.type.isArray(m) && m.length > 1 ? parseInt(m[1]) : -1;
			},
			isNeedToSave: function()
			{
				return (this._mode === BX.Crm.EntityEditorClientMode.create
					|| this._mode === BX.Crm.EntityEditorClientMode.edit
				);
			},
			save: function()
			{
				if(this._mode !== BX.Crm.EntityEditorClientMode.create && this._mode !== BX.Crm.EntityEditorClientMode.edit)
				{
					return;
				}

				if(!this._entityInfo)
				{
					return;
				}

				if(this._searchInput && this._searchInput.value !== this._entityInfo.getTitle())
				{
					this._entityInfo.setTitle(this._searchInput.value);
				}

				if(this.hasPhoneField())
				{
					this._entityInfo.setMultifieldById(
						{
							"ID": this._phoneId,
							"TYPE_ID": "PHONE",
							"VALUE": this.getPhoneFieldValue(),
							"VALUE_COUNTRY_CODE": this.getPhoneCountryCode(),
						},
						this._phoneId
					);
				}

				if(this.hasEmailField())
				{
					this._entityInfo.setMultifieldById(
						{ "ID": this._emailId, "TYPE_ID": "EMAIL", "VALUE": this.getEmailFieldValue() },
						this._emailId
					);
				}

				if(this.hasRequisitesField())
				{
					this._entityInfo.setRequisitesForSave(this.getRequisitesFieldValueForSave());
				}

				if(this._clientEntityEditorEnabled && BX.type.isInteger(this._categoryId))
				{
					this._entityInfo.setCategoryId(this._categoryId);
				}
			},
			focus: function()
			{
				if(this._searchInput)
				{
					this._searchInput.focus();
				}
			},
			hasValue: function()
			{
				return !!this._entityInfo;
			},
			addMultifieldChangeListener: function(listener)
			{
				this._multifieldChangeNotifier.addListener(listener);
			},
			removeMultifieldChangeListener: function(listener)
			{
				this._multifieldChangeNotifier.removeListener(listener);
			},
			addTitleChangeListener: function(listener)
			{
				this._titleChangeNotifier.addListener(listener);
			},
			removeTitleChangeListener: function(listener)
			{
				this._titleChangeNotifier.removeListener(listener);
			},
			addChangeListener: function(listener)
			{
				this._changeNotifier.addListener(listener);
			},
			removeChangeListener: function(listener)
			{
				this._changeNotifier.removeListener(listener);
			},
			addDeletionListener: function(listener)
			{
				this._deletionNotifier.addListener(listener);
			},
			removeDeletionListener: function(listener)
			{
				this._deletionNotifier.removeListener(listener);
			},
			addResetListener: function(listener)
			{
				this._resetNotifier.addListener(listener);
			},
			removeResetListener: function(listener)
			{
				this._resetNotifier.removeListener(listener);
			},
			isQuickEditEnabled: function()
			{
				return this._enableQuickEdit;
			},
			enableQuickEdit: function(enable)
			{
				enable = !!enable;
				if(this._enableQuickEdit === enable)
				{
					return;
				}

				this._enableQuickEdit = enable;

				if(this._searchControl)
				{
					this._searchControl.enableCreationOnBlur = this._enableQuickEdit;
				}
			},
			enableDeletion: function(enable)
			{
				enable = !!enable;
				if(this._enableDeletion === enable)
				{
					return;
				}

				this._enableDeletion = enable;

				if(this._hasLayout)
				{
					this._deleteButton.style.display = enable ? "" : "none";
				}
			},
			adjust: function()
			{
				if(!this._hasLayout)
				{
					return;
				}

				if (!this.isClientEntityEditorDataLoaded() &&
					this._mode === BX.Crm.EntityEditorClientMode.edit)
				{
					this.setMode(BX.Crm.EntityEditorClientMode.loading);
					this.loadEntityInfo(this._entityInfo.getId());
				}
				if (this._mode === BX.Crm.EntityEditorClientMode.loading)
				{
					BX.addClass(this._wrapper, "crm-entity-widget-content-block-loading");
				}
				else
				{
					BX.removeClass(this._wrapper, "crm-entity-widget-content-block-loading");
				}

				if(this._hasFocus)
				{
					BX.removeClass(this._wrapper, "crm-entity-widget-content-block-complete");
					BX.addClass(this._wrapper, "crm-entity-widget-content-block-inprogress");
				}
				else
				{
					BX.removeClass(this._wrapper, "crm-entity-widget-content-block-inprogress");
					BX.addClass(this._wrapper, "crm-entity-widget-content-block-complete");
				}

				if(this.hasEntity())
				{
					if(this._mode === BX.Crm.EntityEditorClientMode.create
						|| this._mode === BX.Crm.EntityEditorClientMode.edit
						|| this._mode === BX.Crm.EntityEditorClientMode.loading
					)
					{
						this._badgeElement.innerHTML = this.getMessage(
							this._mode === BX.Crm.EntityEditorClientMode.create
								? this._entityTypeName.toLowerCase() + "ToCreateTag"
								: "entityEditTag"
						);

						BX.removeClass(this._wrapper, "crm-entity-widget-content-block-selection-mode");

						BX.addClass(
							this._wrapper,
							this._mode === BX.Crm.EntityEditorClientMode.create
								? "crm-entity-widget-content-block-new-mode"
								: "crm-entity-widget-content-block-edit-mode"
						);

						if(this._searchInput.value.length < 0)
						{
							BX.removeClass(this._wrapper, "crm-entity-widget-content-block-textreset");
						}
						else
						{
							BX.addClass(this._wrapper, "crm-entity-widget-content-block-textreset");
						}

						if (this._mode === BX.Crm.EntityEditorClientMode.loading)
						{
							this._loadingIcon.style.display = "";
							this._changeButton.style.display = "none";
							this.clearMultifieldLayout();
						}
						else
						{
							this._loadingIcon.style.display = "none";
							this._changeButton.style.display = "";
							this.prepareMultifieldLayout();
						}

						if(this._searchControl)
						{
							this._searchControl.isDisabled = true;
						}
					}
					else if(this._mode === BX.Crm.EntityEditorClientMode.select)
					{
						BX.removeClass(this._wrapper, "crm-entity-widget-content-block-badge");
						BX.addClass(this._wrapper, "crm-entity-widget-content-block-selection-mode");

						this.clearMultifieldLayout();

						if(this._searchControl)
						{
							this._searchControl.isDisabled = false;
						}
					}

					if(this._searchInput.value.length > 0)
					{
						BX.addClass(this._wrapper, "crm-entity-widget-content-block-textreset");
					}
					else
					{
						BX.removeClass(this._wrapper, "crm-entity-widget-content-block-textreset");
					}
				}
				else
				{
					BX.removeClass(this._wrapper, "crm-entity-widget-content-block-new-mode");
					BX.removeClass(this._wrapper, "crm-entity-widget-content-block-edit-mode");
					BX.removeClass(this._wrapper, "crm-entity-widget-content-block-selection-mode");
					BX.removeClass(this._wrapper, "crm-entity-widget-content-block-textreset");
					BX.removeClass(this._wrapper, "crm-entity-widget-content-block-complete");
					BX.removeClass(this._wrapper, "crm-entity-widget-content-block-inprogress");

					this.clearMultifieldLayout();

					if(this._searchControl)
					{
						this._searchControl.isDisabled = false;
					}
					if (this._loadingIcon)
					{
						this._loadingIcon.style.display =
							(this._mode === BX.Crm.EntityEditorClientMode.loading ? "" : "none");
					}
				}
			},
			getParentContextId: function()
			{
				return this._parentField.getContextId();
			},
			getEntityCreateUrl: function(entityTypeName)
			{
				return this._parentField.getEntityCreateUrl(entityTypeName);
			},
			getEntityEditUrl: function(entityTypeName, entityId)
			{
				return this._parentField.getEntityEditUrl(entityTypeName, entityId);
			},
			openEntityCreatePage: function(params)
			{
				var url = this.getEntityCreateUrl(this._entityTypeName);
				if(url === "")
				{
					return;
				}

				var contextId = this.getParentContextId() + "_" + BX.util.getRandomString(6).toUpperCase();

				var urlParams = BX.prop.getObject(params, "urlParams", {});
				urlParams["external_context_id"] = contextId;
				url = BX.util.add_url_param(url, urlParams);

				if(!this._externalEventHandler)
				{
					this._externalEventHandler = BX.delegate(this.onExternalEvent, this);
					BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
				}

				if(!this._externalEditorPages)
				{
					this._externalEditorPages = {};
				}
				this._externalEditorPages[contextId] = url;
				BX.Crm.Page.open(url);
			},
			openEntityEditPage: function(params)
			{
				var url = this.getEntityEditUrl(this._entityTypeName, BX.prop.getInteger(params, "entityId", 0));
				if(url === "")
				{
					return;
				}

				var contextId = this.getParentContextId() + "_" + BX.util.getRandomString(6).toUpperCase();

				var urlParams = BX.prop.getObject(params, "urlParams", {});
				urlParams["external_context_id"] = contextId;
				url = BX.util.add_url_param(url, urlParams);

				if(!this._externalEventHandler)
				{
					this._externalEventHandler = BX.delegate(this.onExternalEvent, this);
					BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
				}

				if(!this._externalEditorPages)
				{
					this._externalEditorPages = {};
				}
				this._externalEditorPages[contextId] = url;
				BX.Crm.Page.open(url);
			},
			getDuplicateControlConfig: function()
			{
				const result =  BX.Runtime.clone(BX.prop.getObject(this._settings, "duplicateControl", {}));

				result["enabled"] = (
					result.hasOwnProperty("enabled")
					&& this.getMode() === BX.Crm.EntityEditorClientMode.create
				);

				if (this._editor && this._editor.hasOwnProperty("_ajaxForm") && this._editor["_ajaxForm"])
				{
					result["form"] = this._editor["_ajaxForm"];
				}
				result["clientSearchBox"] = this;
				result["enableEntitySelect"] = true;

				return result;
			},
			createClientEntityEditor: function(container, editorId)
			{
				if (this._clientEntityEditor === null)
				{
					var fields = {
						current: [],
						available: []
					};
					var visibleFields = [];
					var fieldsNames = BX.Type.isArray(this._clientEntityEditorFields) ? this._clientEntityEditorFields : [];
					var fieldType;

					fieldType = 'available';
					if (fieldsNames.indexOf('PHONE') > -1)
					{
						visibleFields.push({
							'name': 'PHONE'
						});
						fieldType = 'current';
					}

					fields[fieldType].push({
						'name': 'PHONE',
						'title': BX.message("CRM_EDITOR_PHONE"),
						'type': 'phone',
						'editable': true,
						'placeholders': {
							'creation':  BX.message("CRM_EDITOR_PHONE"),
							'change':  BX.message("CRM_EDITOR_PHONE")
						},
						'showTitle': false,
						'virtual': true,
						'options':
							this._parentField && this._parentField.getSchemeElement()
								? this._parentField.getSchemeElement()._options
								: {}
						,
						'data': {'duplicateControl': {'groupId': 'phone'}}
					});

					fieldType = 'available';
					if (fieldsNames.indexOf('EMAIL') > -1)
					{
						visibleFields.push({
							'name': 'EMAIL'
						});
						fieldType = 'current';
					}
					fields[fieldType].push({
						'name': 'EMAIL',
						'title': BX.message("CRM_EDITOR_EMAIL"),
						'type': 'text',
						'editable': true,
						'placeholders': {
							'creation':  BX.message("CRM_EDITOR_EMAIL"),
							'change':  BX.message("CRM_EDITOR_EMAIL")
						},
						'showTitle': false,
						'virtual': true,
						'data': {'duplicateControl': {'groupId': 'email'}}
					});

					fieldType = 'available';
					if (fieldsNames.indexOf('ADDRESS') > -1)
					{
						visibleFields.push({
							'name': 'ADDRESS'
						});
						fieldType = 'current';
					}
					fields[fieldType].push({
						'name': 'ADDRESS',
						'title': BX.message("CRM_EDITOR_ADDRESS"),
						'type': 'requisite_address',
						'editable': true,
						'virtual': true,
						'data': BX.prop.get(this._clientEntityEditorFieldsParams, 'ADDRESS', {})
					});

					fieldType = 'available';
					if (fieldsNames.indexOf('REQUISITES') > -1)
					{
						visibleFields.push({
							'name': 'REQUISITES'
						});
						fieldType = 'current';
					}
					fields[fieldType].push({
						'name': 'REQUISITES',
						'title': BX.message("CRM_EDITOR_REQUISITES"),
						'type': 'requisite',
						'editable': true,
						'data': BX.prop.get(this._clientEntityEditorFieldsParams, 'REQUISITES', {})
					});

					var sectionId = editorId + '_SECTION';

					var config = BX.UI.EntityConfig.create(
						editorId,
						{
							data: [
								{
									'name': sectionId,
									'type': 'section',
									'elements': visibleFields
								}
							],
							scope: "C",
							enableScopeToggle: false,
							canUpdatePersonalConfiguration: false,
							canUpdateCommonConfiguration: false,
							options: [],
							categoryName: 'crm.entity.editor'
						}
					);

					var scheme = BX.UI.EntityScheme.create(
						editorId,
						{
							current: [
								{
									'name': sectionId,
									'type': 'section',
									'enableToggling': false,
									'transferable': false,
									'data': {
										'isRemovable': false,
										'enableTitle': false,
										'enableToggling': false
									},
									'elements': fields.current
								}
							],
							available: fields.available
						}
					);

					var values = {};
					var extraData = {};
					var phones = this._entityInfo.getPhones();
					if(phones.length > 0)
					{
						values['PHONE'] = BX.prop.getString(phones[0], "VALUE", "");
						extraData['EXTRA'] = BX.prop.getObject(phones[0], "VALUE_EXTRA", {});
					}
					var emails = this._entityInfo.getEmails();
					if(emails.length > 0)
					{
						values['EMAIL'] = BX.prop.getString(emails[0], "VALUE", "");
					}

					var requisites = this._entityInfo.getRequisites();
					if(BX.Type.isArray(requisites) && requisites.length > 0)
					{
						values['REQUISITES'] = requisites;
					}

					if (this._selectEntityExternalHandler === null)
					{
						this._selectEntityExternalHandler = this.onEntitySelectExternal.bind(this);
					}
					BX.Event.EventEmitter.subscribe(this, 'onSelectEntityExternal', this._selectEntityExternalHandler);
					this._clientEntityEditor = BX.Crm.EntityEditor.create(
						editorId,
						{
							container: container,
							entityTypeId: this._entityTypeId,
							entityId: this._entityInfo.getId(),
							model: BX.Crm.EntityEditorModelFactory.create(
								this._editor.getEntityTypeId(),
								this._entityInfo.getId(),
								{
									data: values,
									extraData: extraData
								}
							),
							config: config,
							scheme: scheme,
							validators: [],
							controllers: [{
								'name': 'REQUISITE_CONTROLLER',
								'type': 'requisite_controller',
								'config': {
									'requisiteFieldId': 'REQUISITES',
									'addressFieldId': 'ADDRESS',
									'requisiteBinding': BX.prop.getObject(this._settings, "requisiteBinding", null),
									'enableRequisiteSelection': this._enableRequisiteSelection,
									'enableMyCompanyOnly': BX.prop.getBoolean(this._settings, 'enableMyCompanyOnly', false),
									'permissionToken': BX.prop.getString(this._settings, 'permissionToken', null),
									'entityCategoryId': this._categoryId
								}
							}],
							initialMode: BX.UI.EntityEditorMode.names.edit,
							enableModeToggle: false,
							enableVisibilityPolicy: false,
							enableToolPanel: false,
							enableBottomPanel: false,
							enableFieldsContextMenu: false,
							enablePageTitleControls: false,
							readOnly: false,
							enableAjaxForm: false,
							enableRequiredUserFieldCheck: true,
							enableSectionEdit: false,
							enableSectionCreation: false,
							enableSettingsForAll: false,
							containerId: editorId,
							serviceUrl: this._editor.getServiceUrl(),
							externalContextId: "",
							contextId: "",
							context: {},
							requisiteEditUrl: this._editor.getRequisiteEditUrl('#requisite_id#'),
							options: {'show_always': 'Y'},
							attributeConfig: '',
							showEmptyFields: false,
							isEmbedded: true,
							enableConfigControl: false,
							enableSectionDragDrop: false,
							enableFieldDragDrop: false,
							enableContextDataLayout: false,
							formTagName: 'div',
							duplicateControl: this.getDuplicateControlConfig()
						}
					);
					this._clientEntityEditor.addControlChangeListener(this._clientEntityEditorChangeHandler);
					this._clientEntityEditor._toolPanel = BX.Crm.EntityEditorToolPanelProxy.create(
						this._clientEntityEditor.getId(),
						{
							editor: this._clientEntityEditor,
							visible: false,
							parentPanel: this._editor ? this._editor._toolPanel : null
						}
					);
				}

				return this._clientEntityEditor;
			},
			isClientEntityEditorDataLoaded: function()
			{
				if (this._clientEntityEditorEnabled)
				{
					if(this.isNewEntity())
					{
						return true;
					}
					return this._entityInfo && this._entityInfo.hasEditRequisiteData()
				}
				else
				{
					return true;
				}
			},
			getClientEditorField: function(fieldName)
			{
				return this._clientEntityEditor.getControlByIdRecursive(fieldName);
			},
			getClientEditorFieldValue: function(fieldName)
			{
				var control = this.getClientEditorField(fieldName);
				if (control)
				{
					return control.getRuntimeValue();
				}
				return "";
			},
			setClientEditorFieldValue: function(fieldName, value)
			{
				var control = this.getClientEditorField(fieldName);
				if (control)
				{
					this._clientEntityEditor._model.setField(fieldName, value);
					control.refreshLayout();
				}
			},
			hasPhoneField: function()
			{
				if (this._clientEntityEditorEnabled)
				{
					return !!this.getClientEditorField('PHONE');
				}
				else
				{
					return !!this._phoneInput;
				}
			},
			getPhoneFieldValue: function()
			{
				if (this._clientEntityEditorEnabled)
				{
					return this.getClientEditorFieldValue('PHONE');
				}
				else
				{
					return this._phoneInput.value;
				}
			},
			setPhoneFieldValue: function(value, countryCode)
			{
				if (this._clientEntityEditorEnabled)
				{
					return this.setClientEditorFieldValue('PHONE', value);
				}
				else
				{
					this._maskedPhone.setValue((this._phoneInput.value = value), countryCode);
				}
			},
			getPhoneCountryCode: function()
			{
				if (this._clientEntityEditorEnabled)
				{
					var control = this.getClientEditorField('PHONE');
					if (control)
					{
						return control.getPhoneCountryCodeInputValue();
					}

					return '';
				}
				else
				{
					return this._phoneCountryCodeInput.value;
				}
			},
			hasEmailField: function()
			{
				if (this._clientEntityEditorEnabled)
				{
					return !!this.getClientEditorField('EMAIL');
				}
				else
				{
					return !!this._emailInput;
				}
			},
			getEmailFieldValue: function()
			{
				if (this._clientEntityEditorEnabled)
				{
					return this.getClientEditorFieldValue('EMAIL');
				}
				else
				{
					return this._emailInput.value;
				}
			},
			setEmailFieldValue: function(value)
			{
				if (this._clientEntityEditorEnabled)
				{
					return this.setClientEditorFieldValue('EMAIL', value);
				}
				else
				{
					this._emailInput.value = value;
				}
			},
			hasRequisitesField: function()
			{
				if (this._clientEntityEditorEnabled)
				{
					return (this.getClientEditorField('ADDRESS') || this.getClientEditorField('REQUISITES'));
				}
				else
				{
					return false;
				}
			},
			getRequisitesFieldValueForSave: function()
			{
				if (this._clientEntityEditorEnabled)
				{
					var data = this._clientEntityEditor.prepareControllersData();
					if (data.hasOwnProperty('REQUISITES'))
					{
						return data['REQUISITES'];
					}
				}
				return null;
			},
			onExternalEvent: function(params)
			{
				var eventName = BX.prop.getString(params, "key", "");

				if(eventName !== "onCrmEntityCreate" && eventName !== "onCrmEntityUpdate")
				{
					return;
				}

				var value = BX.prop.getObject(params, "value", {});
				var contextId = BX.prop.getString(value, "context", "");

				if(BX.prop.getString(this._externalEditorPages, contextId, "") === "")
				{
					return;
				}

				var entityTypeName = BX.prop.getString(value, "entityTypeName", "");
				var entityId = BX.prop.getInteger(value, "entityId", 0);

				if(this._entityTypeName !== entityTypeName)
				{
					return;
				}

				if(eventName === "onCrmEntityUpdate" && !(this._entityInfo && this._entityInfo.getId() === entityId))
				{
					return;
				}

				this.setupEntity(this._entityTypeName, entityId);

				window.setTimeout(
					function()
					{
						BX.Crm.Page.close(
							this._externalEditorPages[contextId],
							{ identity: { key: "external_context_id", value: contextId } }
						);
						delete this._externalEditorPages[contextId];
					}.bind(this),
					100
				);
			},

			onPhoneChange: function(event)
			{
				if (!this._phoneInput)
				{
					return;
				}

				if (this.getPhoneFieldValue() !== event.value)
				{
					this.setPhoneFieldValue(event.value);
					if (BX.Crm.PhoneNumberInput.isCountryCodeOnly(this.getPhoneFieldValue(), event.countryCode))
					{
						this._phoneInput.value = '';
					}

					this._multifieldChangeNotifier.notify();
				}
			},

			onPhoneCountryChange: function(event)
			{
				if (!this._phoneCountryCodeInput)
				{
					return;
				}

				this._phoneCountryCodeInput.value = event.country;
			},

			onEmailChange: function(e)
			{
				this._multifieldChangeNotifier.notify();
			},
			onEditButtonClick: function()
			{
				if(this.isNewEntity()
					|| !this.canUpdateEntity()
					|| this.getMode() === BX.Crm.EntityEditorClientMode.edit
				)
				{
					return;
				}

				if(this._searchControl)
				{
					this._searchControl.destroyPopupWindow();
				}

				if(!this.isQuickEditEnabled())
				{
					this.openEntityEditPage(
						{
							entityId: this._entityInfo.getId(),
							urlParams: { init_mode: "edit" }
						}
					);
					return;
				}

				this.setMode(BX.Crm.EntityEditorClientMode.edit);
				this.clearMultifieldLayout();
				this.adjust();
			},
			onChangeButtonClick: function(e)
			{
				var isCreationMode = (this.getMode() === BX.Crm.EntityEditorClientMode.create);
				if (isCreationMode)
				{
					this.setEntity(null, true);
					this.adjust();
				}
				else
				{
					this.setMode(BX.Crm.EntityEditorClientMode.select);
				}
				this.releaseEntityEditor();

				if(!isCreationMode && this._searchInput)
				{
					this._searchInput.focus();
				}

				if(this.searchControlPopup)
				{
					if (isCreationMode)
					{
						var emptySearchResultsCallback = function()
						{
							BX.removeCustomEvent(
								this._searchControl,
								'BX.UI.Dropdown:onSearchComplete',
								emptySearchResultsCallback
							);
							this.searchControlPopup.show();
						}.bind(this);
						BX.addCustomEvent(
							this._searchControl,
							'BX.UI.Dropdown:onSearchComplete',
							emptySearchResultsCallback
						);
						this._searchControl.previousSearchQuery = '';
						this._searchControl.handleTypeInField();
					}
					else
					{
						this.searchControlPopup.show();
					}
				}
			},
			onDeleteButtonClick: function(e)
			{
				if(this._enableDeletion)
				{
					this._deletionNotifier.notify([ this._entityInfo ]);
				}
			},
			onInputFocus: function(e)
			{
				this._hasFocus = true;
				window.setTimeout(BX.delegate(this.adjust, this), 150);
			},
			onInputBlur: function(e)
			{
				this._hasFocus = false;
				window.setTimeout(BX.delegate(this.adjust, this), 300);

				if(this._mode === BX.Crm.EntityEditorClientMode.edit && this._searchInput.value !== this._entityInfo.getTitle())
				{
					this._titleChangeNotifier.notify([]);
				}
			},
			onInputDblClick: function(e)
			{
			},
			onEntityAdd: function(sender, item)
			{
				var title = BX.prop.getString(item, "title", "");
				if(title === "")
				{
					return;
				}

				if(this._searchControl)
				{
					this._searchControl.destroyPopupWindow();
				}

				if(!this.isQuickEditEnabled())
				{
					this.openEntityCreatePage({ urlParams: { title: title } });
					return;
				}

				var entityData = { typeName: this._entityTypeName, title: title };
				if(BX.validation.checkIfEmail(title))
				{
					entityData["title"] = this.getMessage(
						this._entityTypeName === BX.CrmEntityType.names.contact ? "unnamed" : "untitled"
					);
					entityData["advancedInfo"] =
						{
							"multiFields": [ { "ID": this.prepareMultifieldPseudoId(0), "TYPE_ID": "EMAIL", "VALUE": title } ]
						};
				}
				else if(BX.validation.checkIfPhone(title))
				{
					entityData["title"] = this.getMessage(
						this._entityTypeName === BX.CrmEntityType.names.contact ? "unnamed" : "untitled"
					);
					entityData["advancedInfo"] =
						{
							"multiFields": [ { "ID": this.prepareMultifieldPseudoId(0), "TYPE_ID": "PHONE", "VALUE": title } ]
						};
				}

				if(this._searchInput.value !== entityData["title"])
				{
					this._searchInput.value = entityData["title"];
				}

				this.setEntity(BX.CrmEntityInfo.create(entityData), true);

				if (this._searchControl)
				{
					this._searchControl.destroyPopupWindow();
				}
			},
			onEntityReset: function()
			{
				this.reset();
				if (this._searchControl)
				{
					this._searchControl.destroyPopupWindow();
				}
			},
			onEntitySearch: function(searchControl, container)
			{
				if (!this.searchControlPopup)
				{
					this.searchControlPopup = this._searchControl.getPopupWindow();
				}
				this.onBeforeEntitySearchPopupCloseHandler = this.onBeforeEntitySearchPopupClose.bind(
					this,
					this.searchControlPopup._tryCloseByEvent.bind(
						this.searchControlPopup
					)
				);
				this.searchControlPopup._tryCloseByEvent = this.onBeforeEntitySearchPopupCloseHandler;

				this.detailSearchPlacement = new BX.Crm.Placement.DetailSearch("CRM_DETAIL_SEARCH");
				if (this.detailSearchPlacement)
				{
					this.searchControlPopup = this._searchControl.getPopupWindow();
					this.entitySearchPopupCloseHandler = this.onEntitySearchPopupClose.bind(this);
					BX.addCustomEvent(
						this.searchControlPopup,
						'onPopupClose',
						this.entitySearchPopupCloseHandler
					);

					this.placementSearchParamsHandler = this.onPlacamentSearchParams.bind(this);
					BX.addCustomEvent(
						this.detailSearchPlacement,
						"Placements:searchParams",
						this.placementSearchParamsHandler
					);

					this.beforeAddPlacementItemsHandler = this.onBeforeAppendPlacementItems.bind(this);
					BX.addCustomEvent(
						this.detailSearchPlacement,
						"Placements:beforeAppendItems",
						this.beforeAddPlacementItemsHandler
					);

					this.placementEntitySelectHandler = this.onPlacementEntitySelect.bind(this);
					BX.addCustomEvent(
						this.detailSearchPlacement,
						"Placements:select",
						this.placementEntitySelectHandler
					);

					this.placementSetFoundItemsHandler = this.onPlacementSetFoundItems.bind(this);
					BX.addCustomEvent(
						this.detailSearchPlacement,
						"Placements:setFoundItems",
						this.placementSetFoundItemsHandler
					);

					this.detailSearchPlacement.show(
						container,
						container.querySelector('div.ui-dropdown-alert-text')
					);
				}
			},
			onEntitySearchPopupClose: function()
			{
				if (this._searchControl && this._searchControl.hasOwnProperty("documentClickHandler"))
				{
					BX.unbind(document, 'click', this._searchControl.documentClickHandler);
				}
				if (this.detailSearchPlacement)
				{
					BX.removeCustomEvent(
						this.detailSearchPlacement,
						"Placements:setFoundItems",
						this.placementSetFoundItemsHandler
					);
					this.placementSetFoundItemsHandler = null;

					BX.removeCustomEvent(
						this.detailSearchPlacement,
						"Placements:select",
						this.placementEntitySelectHandler
					);
					this.placementEntitySelectHandler = null;

					BX.removeCustomEvent(
						this.detailSearchPlacement,
						"Placements:beforeAppendItems",
						this.beforeAddPlacementItemsHandler
					);
					this.beforeAddPlacementItemsHandler = null;

					BX.removeCustomEvent(
						this.detailSearchPlacement,
						"Placements:searchParams",
						this.placementSearchParamsHandler
					);
					this.placementSearchParamsHandler = null;

					if (this.searchControlPopup)
					{
						BX.removeCustomEvent(
							this.searchControlPopup,
							"onPopupClose",
							this.entitySearchPopupCloseHandler
						);
					}
					this.entitySearchPopupCloseHandler = null;
					this.searchControlPopup = null;

					BX.onCustomEvent(this.detailSearchPlacement, "Placements:destroy");
					this.detailSearchPlacement = null;

					this.creatingItem = null;

					this._searchControl.setItems([]);
				}
			},
			onBeforeEntitySearchPopupClose: function(originalHandler, event)
			{
				if (this.onDocumentClickConfirm)
				{
					return BX.eventReturnFalse(event);
				}
				var eventResult = {active: false};
				BX.onCustomEvent(this.detailSearchPlacement, "Placements:active", [eventResult]);
				if (eventResult.active)
				{
					BX.unbind(document, 'click', this._searchControl.documentClickHandler);
					var f = function(messageBox, e) {
						BX.bind(document, 'click', this._searchControl.documentClickHandler);
						messageBox.close();
						this.onDocumentClickConfirm = null;
						BX.eventCancelBubble(e);
					}.bind(this);
					this.onDocumentClickConfirm = BX.UI.Dialogs.MessageBox.create({
						message: BX.message('CRM_EDITOR_PLACEMENT_CAUTION') || 'Dow you want to terminate process?',
						buttons: BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
						modal: true,
						onOk: function(messageBox, button, e) {
							f(messageBox, e);
							this._searchControl.documentClickHandler(e);
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
			},
			onPlacamentSearchParams: function(params)
			{
				params["entityTypeName"] = this._entityTypeName;
				params["searchQuery"] = this._searchInput.value;
			},
			onBeforeAppendPlacementItems: function()
			{
				BX.addClass(this._searchControl.getPopupWindow().popupContainer, "client-editor-popup");
			},
			onPlacementEntitySelect: function(data)
			{
				this.onEntitySelect(
					{},
					{
						type: data["entityType"],
						id: data["id"],
						title: data["title"]
					}
				);
			},
			onPlacementSetFoundItems: function(placementItem, results)
			{
				var items = [];
				results.forEach(function(result) {
					items.push({
						id: result.id,
						title: result.name,
						type: this._entityTypeName,
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

				this._searchControl.setItems(items);
			},
			onEntitySelect: function(sender, item)
			{
				if (sender === this._searchControl && item["appSid"] && !item["created"])
				{
					if (!this.creatingItem)
					{
						this.creatingItem = item;
						item._loader = new BX.Loader({
							target: item.node,
							size: 40,
						});
						item.node.classList.add('client-editor-active');
						item.node.parentNode.classList.add('client-editor-inactive');
						item._loader.show();

						BX.onCustomEvent(
							this.detailSearchPlacement,
							"Placements:pick",
							[{appSid: item["appSid"], data: item}]
						);
					}

					return;
				}

				if (this.creatingItem)
				{
					for (var prop in item)
					{
						if (item.hasOwnProperty(prop))
						{
							this.creatingItem[prop] = item[prop];
						}
					}
					this.creatingItem["created"] = true;
					this.creatingItem = null;
					this._searchControl.setItems([]);
				}

				var entityTypeName = BX.prop.getString(item, "type", "");
				var entityId = BX.prop.getInteger(item, "id", 0);
				var title = BX.prop.getString(item, "title", "");

				this.setEntityTypeName(entityTypeName);
				if(entityId <= 0)
				{
					return;
				}

				this.setMode(BX.Crm.EntityEditorClientMode.loading);
				this.adjust();
				this.loadEntityInfo(entityId);

				this._searchInput.value = title;
				if (this._searchControl)
				{
					this._searchControl.destroyPopupWindow();
				}
			},
			onEntityInfoLoad: function(sender, result)
			{
				var entityData = BX.prop.getObject(result, "DATA", null);
				if(entityData)
				{
					this.setEntity(BX.CrmEntityInfo.create(entityData), true);
					if(this._hasLayout)
					{
						var anchor = this._wrapper.nextSibling;
						this.clearLayout();
						this.layout({ anchor: anchor });
					}
				}
			},
			onClientEntityEditorChange: function()
			{
				this._multifieldChangeNotifier.notify();
			},
			ucFirst: function(value)
			{
				return value.substring(0, 1).toUpperCase() + value.substring(1).toLowerCase();
			},
			showNotofication: function(context)
			{
				if (
					context.hasOwnProperty("type")
					&& BX.Type.isString(context["type"])
					&& context["type"].length > 1
				)
				{
					const baseEntityTypeName = this._editor.getEntityTypeName();
					if (BX.Type.isString(baseEntityTypeName) && baseEntityTypeName.length > 1)
					{
						const srcTypeKey = this.ucFirst(context["type"]);
						let dstTypeKey = this.ucFirst(baseEntityTypeName);
						let index = dstTypeKey.indexOf("_");
						while (index >= 0)
						{
							dstTypeKey = dstTypeKey.substring(0, index) + this.ucFirst(dstTypeKey.substring(index + 1));
							index = dstTypeKey.indexOf("_", index + 1);
						}
						const notifyMessage = this.getMessage("notify" + srcTypeKey + "To" + dstTypeKey);
						if (this._searchInput && BX.Type.isStringFilled(notifyMessage))
						{
							const popup = new BX.Main.Popup({
								bindElement: this._searchInput,
								darkMode: true,
								angle: {
									offset: 70,
								},
								content: notifyMessage,
							});
							popup.setBackground("rgba(64,64,64,0.7)");
							popup.show();

							setTimeout(
								function (popup) {
									return function () {
										popup.close();
									}
								}(popup),
								5000
							);
						}
					}
				}
			},
			onEntitySelectExternal: function (event)
			{
				const context = event.getData();
				this.onChangeButtonClick();
				this.onEntitySelect(this, context);
				this.showNotofication(context);
			},
			reset: function()
			{
				this._searchInput.value = "";

				var previousEntityInfo = this._entityInfo;
				this._entityInfo = null;
				this._resetNotifier.notify([ previousEntityInfo ]);

				window.setTimeout(BX.delegate(this.adjust, this), 150);
			},
			loadEntityInfo: function(entityId)
			{
				var loader = BX.prop.getObject(this._loaderConfig, this._entityTypeName, null);
				if(!loader)
				{
					return;
				}
				var loaderParams = {
					'ENTITY_TYPE_NAME': this._entityTypeName,
					'ENTITY_ID': entityId,
					'NORMALIZE_MULTIFIELDS': 'Y'
				};
				if (this._editor)
				{
					loaderParams.ownerEntityTypeId = BX.CrmEntityType.resolveId(this._editor.getEntityTypeName());
					loaderParams.ownerEntityId = this._editor.getEntityId();
				}

				BX.CrmDataLoader.create(
					this._id,
					{
						serviceUrl: loader["url"],
						action: loader["action"],
						params: loaderParams
					}
				).load(BX.delegate(this.onEntityInfoLoad, this));
			},
			getCountryCode: function()
			{
				var phones = this._entityInfo.getPhones();
				if (phones.length === 0)
				{
					return '';
				}

				var extraData = BX.prop.getObject(phones[0], "VALUE_EXTRA", {});

				return BX.prop.getString(extraData, "COUNTRY_CODE", "");
			},
		};
	if(typeof(BX.Crm.EntityEditorClientSearchBox.messages) === "undefined")
	{
		BX.Crm.EntityEditorClientSearchBox.messages = {};
	}
	BX.Crm.EntityEditorClientSearchBox.create = function(id, settings)
	{
		var self = new BX.Crm.EntityEditorClientSearchBox();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityEditorClientLayoutType === "undefined")
{
	BX.Crm.EntityEditorClientLayoutType =
		{
			undefined: 0,
			contactCompany: 1,
			companyContact: 2,
			contact: 3,
			company: 4,

			names:
				{
					contactCompany: "CONTACT_COMPANY",
					companyContact: "COMPANY_CONTACT",
					contact: "CONTACT",
					company: "COMPANY"
				},

			resolveId: function(name)
			{
				name = name.toUpperCase();
				if(this.names.contactCompany === name)
				{
					return this.contactCompany;
				}
				else if(this.names.companyContact === name)
				{
					return this.companyContact;
				}
				else if(this.names.contact === name)
				{
					return this.contact;
				}
				else if(this.names.company === name)
				{
					return this.company;
				}

				return this.undefined;
			}
		};
}

if(typeof BX.Crm.PrimaryClientEditor === "undefined")
{
	BX.Crm.PrimaryClientEditor = function()
	{
		this._id = "";
		this._settings = {};
		this._editor = null;
		this._mode = BX.UI.EntityEditorMode.intermediate;
		this._entityInfo = null;
		this._entityTypeName = "";
		this._container = null;
		this._wrapper = null;

		this._bindingWrapper = null;

		this._externalEventHandler = null;
		this._externalContext = null;

		this._entityBindSelector = null;

		this._searchWrapper = null;
		this._searchInput = null;
		this._searchControl = null;

		this._item = null;
		this._itemBindings = null;
		this._skeleton = null;
		this._loaderConfig = null;
		this._hasLayout = false;
	};
	BX.Crm.PrimaryClientEditor.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};

				this._editor = BX.prop.get(this._settings, "editor");
				this._mode = BX.prop.getInteger(this._settings, "mode", 0);
				this._container = BX.prop.getElementNode(this._settings, "container", null);
				this._entityInfo = BX.prop.get(this._settings, "entityInfo", null);

				if(this._entityInfo)
				{
					this.setEntity(this._entityInfo);
				}
				else
				{
					this._entityTypeName = BX.prop.getString(this._settings, "entityTypeName", "");
				}

				this._loaderConfig = BX.prop.getObject(this._settings, "loaderConfig", {});
			},
			layout: function()
			{
				var isViewMode = this._mode === BX.UI.EntityEditorMode.view;

				this._wrapper = BX.create("div", { props: { className: "crm-entity-widget-clients-container" } });
				this._bindingWrapper = null;

				if(!isViewMode)
				{
					//region Search
					this._searchWrapper = BX.create("div", { props: { className: "crm-entity-widget-content-search-box" } });
					this._wrapper.appendChild(this._searchWrapper);

					this.prepareSearchLayout();
					this.adjustSearchLayout();
					//endregion
				}
				this._wrapper.appendChild(BX.create("div", { style: { clear: "both" } }));

				if(this._item)
				{
					this._item.setContainer(this._wrapper);
					this._item.layout();

					this._bindingWrapper = BX.create("div", { props: { className: "crm-entity-widget-client-block-children" } });
					this._wrapper.appendChild(this._bindingWrapper);

					var bindingInfos = this._editor.getPrimaryEntityBindings();
					this._itemBindings = [];
					var i, length;
					for(i = 0, length = bindingInfos.length; i < length; i++)
					{
						var bindingInfo = bindingInfos[i];
						var binding = BX.Crm.ClientEditorEntityBindingPanel.create(
							this._id +  "_" + bindingInfo.getId().toString(),
							{
								entityInfo: bindingInfo,
								editor: this._editor,
								mode: this._mode,
								container: this._bindingWrapper,
								onChange: BX.delegate(this.onItemBindingChange, this)
							}
						);
						binding.layout();
						this._itemBindings.push(binding);
					}
				}

				var anchor = BX.prop.getElementNode(this._settings, "achor", null);
				if(anchor)
				{
					this._container.insertBefore(this._wrapper, anchor);
				}
				else
				{
					this._container.appendChild(this._wrapper);
				}

				this._hasLayout = true;
			},
			adjustLayout: function()
			{
			},
			clearLayout: function()
			{
				if(this._item)
				{
					this._item.clearLayout();
				}

				if(this._itemBindings)
				{
					for(var i = 0, length = this._itemBindings.length; i < length; i++)
					{
						this._itemBindings[i].clearLayout();
					}
					this._itemBindings = null;
				}

				this._wrapper = BX.remove(this._wrapper);
				this._searchWrapper = null;
				this._bindingWrapper = null;
				this._entityCreateButton = null;

				this._hasLayout = false;
			},
			//region Search
			prepareSearchLayout: function()
			{
				this._searchInput = BX.create(
					"input",
					{
						props:
							{
								id: "dropdown-input",
								className: "crm-entity-widget-content-input crm-entity-widget-content-search-input"
							},
						attrs: { autocomplete: "nope" }
					}
				);
				this._searchWrapper.appendChild(this._searchInput);
				this._searchControl = new BX.UI.Dropdown(
					{
						searchAction: "crm.api.entity.search",
						searchOptions: { types: [ BX.CrmEntityType.names.contact, BX.CrmEntityType.names.company ] },
						autocompleteDelay: 500,
						//TODO: Implement CRM renderer
						searchResultRenderer: null,
						targetElement: this._searchInput,
						items: BX.prop.getArray(this._settings, "lastEntityInfos", []),
						footerItems:
							[
								{
									caption: this.getMessage("create"),
									buttons:
										[
											{
												type: "create",
												caption: BX.CrmEntityType.getCaption(BX.CrmEntityType.enumeration.contact),
												events:
													{
														click: BX.delegate(
															function()
															{
																this.createEntity(BX.CrmEntityType.names.contact);
																this._searchControl.destroyPopupWindow();
															},
															this
														)
													}
											},
											{
												type: "create",
												caption: BX.CrmEntityType.getCaption(BX.CrmEntityType.enumeration.company),
												events:
													{
														click: BX.delegate(
															function()
															{
																this.createEntity(BX.CrmEntityType.names.company);
																this._searchControl.destroyPopupWindow();
															},
															this
														)
													}
											}
										]
								}
							],
						events:
							{
								onSelect: this.onEntitySelect.bind(this),
								onSearch: function(word) {}
							}
					}
				);
			},
			adjustSearchLayout: function()
			{
				if(this._searchWrapper)
				{
					this._searchWrapper.style.display = this._item ? "none" : "";
				}
			},
			//endregion
			getEntityTypeName: function()
			{
				return this._entityTypeName;
			},
			setEntityTypeName: function(entityType)
			{
				if(this._entityTypeName === entityType)
				{
					return;
				}

				this._entityTypeName = entityType;
			},
			setEntity: function(entityInfo)
			{
				if(this._item)
				{
					if(this._hasLayout)
					{
						this._item.clearLayout();
					}
					this._item = null;
				}

				if(!(entityInfo instanceof BX.CrmEntityInfo))
				{
					this._entityInfo = null;
				}
				else
				{
					this._entityInfo = entityInfo;
					this.setEntityTypeName(this._entityInfo.getTypeName());
					this._item = BX.Crm.ClientEditorEntityPanel.create(
						this._id +  "_" + this._entityInfo.getId().toString(),
						{
							editor: this._editor,
							entityInfo: this._entityInfo,
							enableEntityTypeCaption: true,
							enableRequisite: true,
							requisiteBinding: BX.prop.getObject(this._settings, "requisiteBinding", {}),
							mode: this._mode,
							onDelete: BX.delegate(this.onItemDelete, this)
						}
					);

					if(this._hasLayout)
					{
						this._item.setContainer(this._wrapper);
						this._item.layout();
					}
				}

				if(this._itemBindings)
				{
					for(var i = 0, length = this._itemBindings.length; i < length; i++)
					{
						this._itemBindings[i].clearLayout();
					}
					this._itemBindings = null;
				}

				this.adjustSearchLayout();
			},
			setupEntity: function(entityId)
			{
				if(this._entityInfo && this._entityInfo.getId() === entityId)
				{
					return;
				}

				this.setEntity(null);

				var callback = BX.prop.getFunction(this._settings, "onChange");
				if(callback)
				{
					callback(this, this._entityInfo);
				}

				var entityLoader = BX.prop.getObject(this._loaderConfig, this._entityTypeName, null);
				if(entityLoader)
				{
					this.showSkeleton();

					BX.CrmDataLoader.create(
						this._id,
						{
							serviceUrl: entityLoader["url"],
							action: entityLoader["action"],
							params: { "ENTITY_TYPE_NAME": this._entityTypeName, "ENTITY_ID": entityId }
						}
					).load(BX.delegate(this.onEntityInfoLoad, this));
				}
			},
			showSkeleton: function()
			{
				if(!this._skeleton)
				{
					this._skeleton = BX.Crm.ClientEditorEntitySkeleton.create(this._id, { container: this._wrapper });
				}
				this._skeleton.layout();
			},
			hideSkeleton: function()
			{
				if(this._skeleton)
				{
					this._skeleton.clearLayout();
				}
			},
			onEntityInfoLoad: function(sender, result)
			{
				var entityData = BX.prop.getObject(result, "DATA", null);
				if(entityData)
				{
					var hasLayout = this._hasLayout;
					if(hasLayout)
					{
						this.clearLayout();
					}

					this.hideSkeleton();

					var entityInfo = BX.CrmEntityInfo.create(entityData);
					this.setEntity(entityInfo);

					var callback = BX.prop.getFunction(this._settings, "onChange");
					if(callback)
					{
						callback(this, this._entityInfo);
					}

					if(hasLayout)
					{
						this.layout();
					}
				}
			},
			getEntityCreateUrl: function(entityTypeName)
			{
				return this._editor.getEntityCreateUrl(entityTypeName);
			},
			createEntity: function(entityTypeName)
			{
				var url = this.getEntityCreateUrl(entityTypeName);
				if(url === "")
				{
					return "";
				}

				var contextId = this._editor.getContextId();
				url = BX.util.add_url_param(url, { external_context_id: contextId });

				if(!this._externalEventHandler)
				{
					this._externalEventHandler = BX.delegate(this.onExternalEvent, this);
					BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
				}

				if(!this._externalContext)
				{
					this._externalContext = {};
				}
				this._externalContext[contextId] = url;
				BX.Crm.Page.open(url);
			},
			onEntitySelect: function(sender, item)
			{
				this._entityTypeName = BX.prop.getString(item, "type", "");
				var entityId = BX.prop.getInteger(item, "id", 0);
				if(entityId > 0)
				{
					this.setupEntity(entityId);
					this._searchControl.destroyPopupWindow();
				}
			},
			onExternalEvent: function(params)
			{
				if(BX.prop.getString(params, "key", "") !== "onCrmEntityCreate")
				{
					return;
				}

				var value = BX.prop.getObject(params, "value", {});
				var context = BX.prop.getString(value, "context", "");

				if(this._externalContext && typeof(this._externalContext[context]) !== "undefined")
				{
					var entityTypeName = BX.prop.getString(value, "entityTypeName", "");
					var entityId = BX.prop.getInteger(value, "entityId", 0);

					if(this._entityTypeName !== entityTypeName)
					{
						this._entityTypeName = entityTypeName;
					}
					this.setupEntity(entityId);

					BX.Crm.Page.close(this._externalContext[context]);
					delete this._externalContext[context];
				}
			},
			onItemBindingChange: function(item, action)
			{
				if(action === "unbind")
				{
					var callback = BX.prop.getFunction(this._settings, "onBindingRelease");
					if(callback)
					{
						callback(this, item.getEntity());
					}

					this.removeBinding(item);
				}
				else if(action === "delete")
				{
					this.removeBinding(item);
				}
			},
			onItemDelete: function(item)
			{
				var entityInfo = this._entityInfo;

				var hasLayout = this._hasLayout;
				if(hasLayout)
				{
					this.clearLayout();
				}
				this.setEntity(null);

				if(hasLayout)
				{
					this.layout();
				}

				var callback = BX.prop.getFunction(this._settings, "onDelete");
				if(callback)
				{
					callback(this, entityInfo);
				}
			},
			getBindings: function()
			{
				return this._itemBindings;
			},
			createBinding: function(entityInfo)
			{
				return(
					BX.Crm.ClientEditorEntityBindingPanel.create(
						this._id +  "_" + entityInfo.getId().toString(),
						{
							entityInfo: entityInfo,
							editor: this._editor,
							mode: this._mode,
							onChange: BX.delegate(this.onItemBindingChange, this)
						}
					)
				);
			},
			findBindingById: function(entityId)
			{
				for(var i = 0, length = this._itemBindings.length; i < length; i++)
				{
					var item = this._itemBindings[i];
					if(item.getEntity().getId() === entityId)
					{
						return item;
					}
				}

				return null;
			},
			getBindingIndex: function(binding)
			{
				for(var i = 0, length = this._itemBindings.length; i < length; i++)
				{
					if(this._itemBindings[i] === binding)
					{
						return i;
					}
				}

				return -1;
			},
			addBinding: function(item)
			{
				this._itemBindings.push(item);

				if(this._hasLayout)
				{
					item.setContainer(this._bindingWrapper);
					item.layout();
				}

				var callback = BX.prop.getFunction(this._settings, "onBindingAdd");
				if(callback)
				{
					callback(this, item.getEntity());
				}
			},
			removeBinding: function(item)
			{
				var index = this.getBindingIndex(item);
				if(index < 0)
				{
					return;
				}

				item.clearLayout();
				this._itemBindings.splice(index, 1);

				var callback = BX.prop.getFunction(this._settings, "onBindingDelete");
				if(callback)
				{
					callback(this, item.getEntity());
				}
			},
			getBindingEntities: function()
			{
				var results = [];
				if(this._itemBindings)
				{
					for(var i = 0, length = this._itemBindings.length; i < length; i++)
					{
						results.push(this._itemBindings[i].getEntity());
					}
				}
				return results;
			}
		};
	BX.Crm.PrimaryClientEditor.prototype.getMessage = function(name)
	{
		var m = BX.Crm.PrimaryClientEditor.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};

	if(typeof(BX.Crm.PrimaryClientEditor.messages) === "undefined")
	{
		BX.Crm.PrimaryClientEditor.messages = {};
	}
	BX.Crm.PrimaryClientEditor.create = function(id, settings)
	{
		var self = new BX.Crm.PrimaryClientEditor();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.SecondaryClientEditor === "undefined")
{
	BX.Crm.SecondaryClientEditor = function()
	{
		this._id = "";
		this._settings = {};
		this._mode = BX.UI.EntityEditorMode.intermediate;
		this._container = null;
		this._wrapper = null;
		this._entityTypeName = "";
		this._entityInfos = null;
		this._items = null;

		this._externalEventHandler = null;
		this._externalContext = null;

		this._isMultiple = true;

		this._primaryLoaderConfig = null;
		this._secondaryLoaderConfig = null;

		this._editor = null;

		this._searchWrapper = null;
		this._searchInput = null;
		this._searchControl = null;

		this._addButton = null;
		this._addButtonHandler = BX.delegate(this.onAddButtonClick, this);

		this._bindButton = null;
		this._bindButtonClickHandler = BX.delegate(this.onBindButtonClick, this);
		this._bindingSelector = null;
		this._bindingSelectHandler = BX.delegate(this.onBindingSelect, this);

		this._isVisible = true;
		this._hasLayout = false;
	};

	BX.Crm.SecondaryClientEditor.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};
				this._mode = BX.prop.getInteger(this._settings, "mode", 0);
				this._editor = BX.prop.get(this._settings, "editor", null);

				this._container = BX.prop.getElementNode(this._settings, "container", null);
				this._entityTypeName = BX.prop.getString(this._settings, "entityTypeName", "");
				this._entityInfos = BX.prop.getArray(this._settings, "entityInfos", "");
				this._isMultiple = BX.prop.getBoolean(this._settings, "isMultiple", true);

				this._items = [];
				var itemCount = this._entityInfos.length;
				if(!this._isMultiple && itemCount > 1)
				{
					itemCount = 1;
				}
				for(var i = 0; i < itemCount; i++)
				{
					var item = this.createItem(this._entityInfos[i]);
					this._items.push(item);
				}

				this._primaryLoaderConfig = BX.prop.getObject(this._settings, "primaryLoader", {});
				this._secondaryLoaderConfig = BX.prop.getObject(this._settings, "secondaryLoader", {});
			},
			getMessage: function(name)
			{
				var m = BX.Crm.SecondaryClientEditor.messages;
				return m.hasOwnProperty(name) ? m[name] : name;
			},
			getEntityTypeName: function()
			{
				return this._entityTypeName;
			},
			getEntities: function()
			{
				return this._entityInfos;
			},
			setEntities: function(entityInfos)
			{
				this._entityInfos = entityInfos;
				this.clearItems();
				var itemCount = this._entityInfos.length;
				if(!this._isMultiple && itemCount > 1)
				{
					itemCount = 1;
				}
				for(var i = 0; i < itemCount; i++)
				{
					this.addItem(this.createItem(this._entityInfos[i]));
				}
			},
			findItemIndex: function(item)
			{
				for(var i = 0, j = this._items.length; i < j; i++)
				{
					if(this._items[i] === item)
					{
						return i;
					}
				}
				return -1;
			},
			getFirstItem: function()
			{
				return this._items.length > 0 ? this._items[0] : null;
			},
			getItemById: function(id)
			{
				for(var i = 0, length = this._items.length; i < length; i++)
				{
					var item = this._items[i];
					if(item.getEntity().getId() === id)
					{
						return item;
					}
				}
				return null;
			},
			getItems: function()
			{
				return this._items;
			},
			getItemCount: function()
			{
				return this._items.length;
			},
			createItem: function(entityInfo)
			{
				return (
					BX.Crm.ClientEditorEntityPanel.create(
						this._id +  "_" + entityInfo.getId().toString(),
						{
							editor: this._editor,
							entityInfo: entityInfo,
							mode: this._mode,
							onDelete: BX.delegate(this.onItemDelete, this)
						}
					)
				);
			},
			clearItems: function()
			{
				for(var i = 0, length = this._items.length; i < length; i++)
				{
					var item = this._items[i];
					item.clearLayout();
					item.setContainer(null);
				}
				this._items = [];
			},
			addItemById: function(id)
			{
				var entityLoader = BX.prop.getObject(this._primaryLoaderConfig, this._entityTypeName, null);
				if(!entityLoader)
				{
					return;
				}

				BX.CrmDataLoader.create(
					this._id,
					{
						serviceUrl: entityLoader["url"],
						action: entityLoader["action"],
						params: { "ENTITY_TYPE_NAME": this._entityTypeName, "ENTITY_ID": id }
					}
				).load(BX.delegate(this.onEntityInfoLoad, this));
			},
			addItem: function(item)
			{
				var beforeCallback = BX.prop.getFunction(this._settings, "onBeforeAdd");
				if(beforeCallback)
				{
					var eventArgs = { cancel: false };
					beforeCallback(this, item.getEntity(), eventArgs);
					if(eventArgs["cancel"])
					{
						return false;
					}
				}

				if(!this._isMultiple)
				{
					this.clearItems();
				}

				this._items.push(item);
				if(this._hasLayout)
				{
					item.setContainer(this._itemsWrapper);
					item.layout();
				}

				var afterCallback = BX.prop.getFunction(this._settings, "onAdd");
				if(afterCallback)
				{
					afterCallback(this, item.getEntity());
				}

				this.adjustLayout();

				return true;
			},
			removeItem: function(item)
			{
				var index = this.findItemIndex(item);
				if(index < 0)
				{
					return;
				}

				this._items.splice(index, 1);
				if(this._hasLayout)
				{
					item.clearLayout();
					item.setContainer(null);
				}

				var callback = BX.prop.getFunction(this._settings, "onDelete");
				if(callback)
				{
					callback(this, item.getEntity());
				}

				this.adjustLayout();
			},
			reloadEntities: function()
			{
				if(!this._editor)
				{
					return;
				}

				var primaryEntity = this._editor.getPrimaryEntity();
				if(!primaryEntity)
				{
					return;
				}

				var entityLoader = BX.prop.getObject(this._secondaryLoaderConfig, primaryEntity.getTypeName(), null);
				if(entityLoader)
				{
					BX.CrmDataLoader.create(
						this._id,
						{
							serviceUrl: entityLoader["url"],
							action: entityLoader["action"],
							params:
								{
									"PRIMARY_TYPE_NAME": primaryEntity.getTypeName(),
									"PRIMARY_ID": primaryEntity.getId(),
									"SECONDARY_TYPE_NAME": this._entityTypeName,
									"OWNER_TYPE_NAME": this._editor.getOwnerTypeName()
								}
						}
					).load(BX.delegate(this.onEntityInfosReload, this));
				}
			},
			setVisible: function(visible)
			{
				visible = !!visible;
				if(this._isVisible === visible)
				{
					return;
				}

				this._isVisible = visible;
				if(this._wrapper)
				{
					this._wrapper.style.display = visible ? "" : "none";
				}
			},
			layout: function()
			{
				var isViewMode = this._mode === BX.UI.EntityEditorMode.view;

				this._wrapper = BX.create("div", {});
				if(!this._isVisible)
				{
					this._wrapper.style.display = "none";
				}
				this._container.appendChild(this._wrapper);

				var legendText = BX.prop.getString(this._settings, "entityLegend", "");

				this._addButton = null;
				this._bindButton = null;

				if(isViewMode)
				{
					this._wrapper.appendChild(
						BX.create(
							"div",
							{
								props: { className: "crm-entity-widget-content-block-title" },
								children: [
									BX.create(
										"span",
										{
											attrs: { className: "crm-entity-widget-content-block-title-text" },
											text: legendText
										}
									)
								]
							}
						)
					);
				}
				else
				{
					this._bindButton = BX.create('span',
						{
							props: { className: 'crm-entity-widget-actions-btn-bind' },
							text: this.getMessage('bind'),
							events: { click: this._bindButtonClickHandler }
						}
					);

					this._wrapper.appendChild(
						BX.create("div",
							{
								props: { className: "crm-entity-widget-participants-title" },
								children:
									[
										BX.create("div",
											{
												props: { className: "crm-entity-widget-clients-actions-block" },
												children:
													[
														BX.create("span",
															{
																props: { className: "crm-entity-widget-actions-btn-participants" },
																children:
																	[
																		BX.create("span",
																			{
																				props: { className: "crm-entity-widget-participants-title-text" },
																				text: legendText
																			}
																		)
																	]
															}
														),
														this._bindButton
													]
											}
										)
									]
							}
						)
					);
				}

				//region Search
				this._searchWrapper = BX.create("div", { props: { className: "crm-entity-widget-content-search-box" } });
				this._searchWrapper.style.display = "none";

				this._itemsWrapper = BX.create("div", { props: { className: "crm-entity-widget-content-item-container" } });

				this._wrapper.appendChild(this._searchWrapper);

				this._wrapper.appendChild(this._itemsWrapper);

				if(!isViewMode)
				{
					this._addButton = BX.create("span",
						{
							props: { className: "crm-entity-widget-actions-btn-add" },
							text: this.getMessage('addParticipant'),
							events: { click: this._addButtonHandler }
						}
					);
					this._wrapper.appendChild(this._addButton);
				}

				this.prepareSearchLayout();
				//endregion

				for(var i = 0, length = this._items.length; i < length; i++)
				{
					this._items[i].setContainer(this._itemsWrapper);
					this._items[i].layout();
				}

				this.adjustLayout();
				this._hasLayout = true;
			},
			clearLayout: function()
			{
				for(var i = 0, j = this._items.length; i < j; i++)
				{
					this._items[i].clearLayout();
					this._items[i].setContainer(null);
				}

				this._addButton = null;
				this._bindButton = null;

				this._wrapper = BX.remove(this._wrapper);
				this._hasLayout = false;
			},
			adjustLayout: function()
			{
				if(!this._bindButton)
				{
					return;
				}

				this._bindButton.style.display =
					this._editor.getPrimaryEntityTypeName() === BX.CrmEntityType.names.company
					&& BX.util.array_diff(
						this._editor.getSecondaryEntities(),
						this._editor.getPrimaryEntityBindings(),
						BX.CrmEntityInfo.getHashCode
					).length > 0 ? "" : "none";
			},
			//region Search
			prepareSearchLayout: function()
			{
				var entityTypeId = BX.CrmEntityType.resolveId(this._entityTypeName);

				this._searchInput = BX.create(
					"input",
					{
						props:
							{
								id: "dropdown-input",
								className: "crm-entity-widget-content-input crm-entity-widget-content-search-input"
							},
						attrs: { autocomplete: "nope" }
					}
				);
				this._searchWrapper.appendChild(this._searchInput);
				this._searchControl = new BX.UI.Dropdown(
					{
						searchAction: "crm.api.entity.search",
						searchOptions: { types: [ this._entityTypeName ] },
						autocompleteDelay: 500,
						//TODO: Implement CRM renderer
						searchResultRenderer: null,
						targetElement: this._searchInput,
						items: BX.prop.getArray(this._settings, "lastEntityInfos", []),
						footerItems:
							[
								{
									caption: this.getMessage("create"),
									buttons:
										[
											{
												type: "create",
												caption: BX.CrmEntityType.getCaption(entityTypeId),
												events:
													{
														click: BX.delegate(
															function()
															{
																this.createEntity();
																this._searchControl.destroyPopupWindow();
															},
															this
														)
													}
											}
										]
								}
							],
						events:
							{
								onSelect: this.onItemSelect.bind(this),
								onSearch: function(word) {}
							}
					}
				);
			},
			//endregion
			onAddButtonClick: function(e)
			{
				this._searchWrapper.style.display = this._searchWrapper.style.display === "none" ? "" : "none";
			},
			onBindButtonClick: function(e)
			{
				if(this._bindingSelector && this._bindingSelector.isOpened())
				{
					this._bindingSelector.close();
					return;
				}

				if(!this._bindingSelector)
				{
					this._bindingSelector = BX.CmrSelectorMenu.create(this._id, { items: [] });
					this._bindingSelector.addOnSelectListener(this._bindingSelectHandler);
				}

				var bindings = this._editor.getPrimaryEntityBindings();
				var bindingInfos = [];
				var i, length;
				for(i = 0, length = bindings.length; i < length; i++)
				{
					bindingInfos.push(bindings[i]);
				}

				var unboundEntities = BX.util.array_diff(
					this._editor.getSecondaryEntities(),
					bindingInfos,
					BX.CrmEntityInfo.getHashCode
				);

				var items = [];
				for(i = 0, length = unboundEntities.length; i < length; i++)
				{
					var entityInfo = unboundEntities[i];
					items.push({ text: entityInfo.getTitle(), value: entityInfo.getId() });
				}

				this._bindingSelector.setupItems(items);
				this._bindingSelector.open(this._bindButton);
			},
			onBindingSelect: function(sender, item)
			{
				this._editor.onSecondaryEntityBind(this, this._editor.getSecondaryEntityById(item.getValue()));
			},
			onItemSelect: function(sender, item)
			{
				var entityId = BX.prop.getInteger(item, "id", 0);
				if(entityId > 0)
				{
					this.addItemById(entityId);
					this._searchWrapper.style.display = "none";
					this._searchControl.destroyPopupWindow();
				}
			},
			onItemDelete: function(item)
			{
				this.removeItem(item);
			},
			onEntityInfoLoad: function(sender, result)
			{
				var entityData = BX.prop.getObject(result, "DATA", null);
				if(!entityData)
				{
					return;
				}

				var entityInfo = BX.CrmEntityInfo.create(entityData);
				if(this.getItemById(entityInfo.getId()) !== null)
				{
					return;
				}

				this.addItem(this.createItem(entityInfo));
			},
			onEntityInfosReload: function(sender, result)
			{
				var entityData = BX.type.isArray(result['ENTITY_INFOS']) ? result['ENTITY_INFOS'] : [];
				var entityInfos = [];
				for(var i = 0; i < entityData.length; i++)
				{
					entityInfos.push(BX.CrmEntityInfo.create(entityData[i]));
				}
				this.setEntities(entityInfos);
			},
			getEntityCreateUrl: function(entityTypeName)
			{
				return this._editor.getEntityCreateUrl(entityTypeName);
			},
			createEntity: function()
			{
				var url = this.getEntityCreateUrl(this.getEntityTypeName());
				if(url === "")
				{
					return;
				}

				var contextId = this._editor.getContextId();
				url = BX.util.add_url_param(url, { external_context_id: contextId });

				//region add company binding if required
				var ownerTypeName = this._editor.getOwnerTypeName();
				var ownerId = this._editor.getOwnerId();

				if(ownerId > 0 && ownerTypeName === BX.CrmEntityType.names.company)
				{
					url = BX.util.add_url_param(url, { company_id: ownerId });
				}
				//endregion

				if(!this._externalEventHandler)
				{
					this._externalEventHandler = BX.delegate(this.onExternalEvent, this);
					BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
				}

				if(!this._externalContext)
				{
					this._externalContext = {};
				}
				this._externalContext[contextId] = url;
				BX.Crm.Page.open(url);
			},
			onExternalEvent: function(params)
			{
				if(!this._externalContext)
				{
					return;
				}

				var key = BX.type.isNotEmptyString(params["key"]) ? params["key"] : "";
				if(key !== "onCrmEntityCreate")
				{
					return;
				}

				var value = BX.prop.getObject(params, "value", {});
				if(BX.prop.getString(value, "entityTypeName", "") !== this.getEntityTypeName())
				{
					return;
				}

				var entityId = BX.prop.getInteger(value, "entityId", 0);
				var context = BX.prop.getString(value, "context", "");

				if(typeof(this._externalContext[context]) !== "undefined")
				{
					this.addItemById(entityId);
					BX.Crm.Page.close(this._externalContext[context]);
					delete this._externalContext[context];
				}
			}
		};
	if(typeof(BX.Crm.SecondaryClientEditor.messages) === "undefined")
	{
		BX.Crm.SecondaryClientEditor.messages = {};
	}
	BX.Crm.SecondaryClientEditor.create = function(id, settings)
	{
		var self = new BX.Crm.SecondaryClientEditor();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.ClientEditorEntitySkeleton === "undefined")
{
	BX.Crm.ClientEditorEntitySkeleton = function()
	{
		this._id = "";
		this._settings = {};
		this._container = null;
		this._wrapper = null;
		this._hasLayout = false;
	};
	BX.Crm.ClientEditorEntitySkeleton.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};
				this._container = BX.prop.getElementNode(this._settings, "container", null);
			},
			layout: function()
			{
				this._wrapper = BX.create("div",
					{
						props: { className: "crm-entity-widget-client-block crm-entity-widget-client-block-skeleton" },
						children: [ BX.create("div", { props: { className: "crm-entity-widget-client-box" } }) ]
					}
				);
				this._container.appendChild(this._wrapper);
				this._hasLayout = true;
			},
			clearLayout: function()
			{
				this._wrapper = BX.remove(this._wrapper);
				this._hasLayout = false;
			}
		};
	BX.Crm.ClientEditorEntitySkeleton.create = function(id, settings)
	{
		var self = new BX.Crm.ClientEditorEntitySkeleton();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.ClientEditorEntityPanel === "undefined")
{
	BX.Crm.ClientEditorEntityPanel = function()
	{
		this._id = "";
		this._settings = {};
		this._editor = null;
		this._entityInfo = null;
		this._enableCommunications = true;
		this._enableAddress = false;
		this._enableRequisitesTooltip = false;
		this._isRequisiteEnabled = true;
		this._canChangeDefaultRequisite = false;
		this._useExternalRequisiteBinding = false;
		this._requisiteInfo = null;

		this._mode = BX.UI.EntityEditorMode.intermediate;
		this._communicationButtons = null;
		this._deleteButton = null;

		this._container = null;
		this._wrapper = null;
		this._titleIcon = null;
		this._titleLink = null;

		this._clientRequisites = null;

		this._deleteButtonHandler = BX.delegate(this.onDeleteButtonClick, this);
		this._requisiteChangeHandler = BX.delegate(this.onRequisiteChange, this);
		this._selectedRequisiteChangeNotifier = null;
		this._requisiteListChangeNotifier = null;
		this._hasLayout = false;
	};
	BX.Crm.ClientEditorEntityPanel.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};
				this._container = BX.prop.getElementNode(this._settings, "container", null);
				this._editor = BX.prop.get(this._settings, "editor");
				this._entityInfo = BX.prop.get(this._settings, "entityInfo", null);
				this._mode = BX.prop.getInteger(this._settings, "mode", 0);
				this._enableCommunications = BX.prop.getBoolean(this._settings, "enableCommunications", true);
				this._enableAddress = BX.prop.getBoolean(this._settings, "enableAddress", true);
				this._enableRequisitesTooltip = BX.prop.getBoolean(this._settings, "enableTooltip", true);
				this._isRequisiteEnabled = (this._entityInfo.hasRequisites()
					&& BX.prop.getBoolean(this._settings, "enableRequisite", false)
				);
				this._canChangeDefaultRequisite = BX.prop.getBoolean(this._settings, "canChangeDefaultRequisite", false);
				this._useExternalRequisiteBinding = BX.prop.getBoolean(this._settings, "useExternalRequisiteBinding", false);

				var fieldsParams = BX.prop.get(this._settings, "clientEditorFieldsParams", null);
				if (this._entityInfo && fieldsParams)
				{
					var editor = this._editor ? this._editor.getEditor() : null;
					var isReadonly = (editor ? editor.isReadOnly() : true);
					this._clientRequisites = BX.Crm.EntityEditorClientRequisites.create(
						this._entityInfo.getId() + '_rq',
						{
							entityInfo: this._entityInfo,
							fieldsParams: fieldsParams,
							loaderConfig: BX.prop.getObject(this._settings, "loaderConfig", null),
							requisiteBinding: BX.prop.getObject(this._settings, "requisiteBinding", null),
							readonly: isReadonly,
							canChangeDefaultRequisite: !isReadonly && this._canChangeDefaultRequisite,
							requisiteEditUrl: editor ? editor.getRequisiteEditUrl('#requisite_id#') : null,
							formElement: editor ? editor.getFormElement() : null,
							contextId: editor ? editor.getContextId() : null,
							enableAddress: this._enableAddress,
							enableTooltip: this._enableRequisitesTooltip,
							permissionToken: BX.prop.getString(this._settings, 'permissionToken', null),
						});
					BX.Event.EventEmitter.subscribe(this._clientRequisites, 'onSetSelectedRequisite', this._requisiteChangeHandler);
					BX.Event.EventEmitter.subscribe(this._clientRequisites, 'onChangeRequisiteList', this.onChangeRequisiteList.bind(this));
				}

				this._selectedRequisiteChangeNotifier = BX.CrmNotifier.create(this);
				this._requisiteListChangeNotifier = BX.CrmNotifier.create(this);
			},
			getContainer: function()
			{
				return this._container;
			},
			setContainer: function(container)
			{
				this._container = container;
			},
			getTitleLink: function()
			{
				return this._titleLink;
			},
			getEntity: function()
			{
				return this._entityInfo;
			},
			getMode: function()
			{
				return this._mode;
			},
			setMode: function(mode)
			{
				this._mode = mode;
			},
			isRequisiteEnabled: function()
			{
				return this._isRequisiteEnabled;
			},
			addRequisiteChangeListener: function(listener)
			{
				this._selectedRequisiteChangeNotifier.addListener(listener);
			},
			removeRequisiteChangeListener: function(listener)
			{
				this._selectedRequisiteChangeNotifier.removeListener(listener);
			},
			addRequisiteListChangeListener: function(listener)
			{
				this._requisiteListChangeNotifier.addListener(listener);
			},
			removeRequisiteListChangeListener: function(listener)
			{
				this._requisiteListChangeNotifier.removeListener(listener);
			},
			getTitleIcon: function()
			{
				if (!this._titleIcon && this._entityInfo)
				{
					var iconClass = null;
					if (this._entityInfo.getTypeId() === BX.CrmEntityType.enumeration.lead)
					{
						iconClass = 'crm-entity-widget-client-box-icon--lead';
					}
					else if (this._entityInfo.getTypeId() === BX.CrmEntityType.enumeration.deal)
					{
						iconClass = 'crm-entity-widget-client-box-icon--deal';
					}
					if (iconClass)
					{
						this._titleIcon = BX.create("span", {
							props: {
								className: 'crm-entity-widget-client-box-icon ' + iconClass
							}}
						);
					}
				}

				return this._titleIcon;
			},
			layout: function()
			{
				var isViewMode = this._mode === BX.UI.EntityEditorMode.view;

				this._wrapper = BX.create("div", { props: { className: "crm-entity-widget-client-block" } });
				this._container.appendChild(this._wrapper);

				var innerWrapper = BX.create("div", { props: { className: "crm-entity-widget-client-box crm-entity-widget-participants-block" } });
				this._wrapper.appendChild(innerWrapper);

				if(BX.prop.getBoolean(this._settings, "enableEntityTypeCaption", false))
				{
					innerWrapper.appendChild(
						BX.create(
							"div",
							{
								props: { className: "crm-entity-widget-client-box-type" },
								text: this._entityInfo.getTypeCaption()
							}
						)
					);
				}

				this._deleteButton = null;
				if(!isViewMode)
				{
					this._deleteButton = BX.create(
						"div",
						{
							props: { className: "crm-entity-widget-client-block-remove" },
							events: { click: this._deleteButtonHandler }
						}
					);
					innerWrapper.appendChild(this._deleteButton);
				}


				var titleWrapper = BX.create("div",
					{
						props: { className: "crm-entity-widget-client-box-name-container" }
					}
				);
				innerWrapper.appendChild(titleWrapper);

				var buttonWrapper = BX.create("div",
					{ props: { className: "crm-entity-widget-client-actions-container" } }
				);

				var showUrl = this._entityInfo.getShowUrl();
				if(showUrl !== "")
				{
					this._titleLink = BX.create("a",
						{
							props:
								{
									className: "crm-entity-widget-client-box-name",
									href: this._entityInfo.getShowUrl()
								},
							text: this._entityInfo.getTitle()
						}
					);

					BX.Event.bind(this._titleLink, 'mouseenter', this.onTitleMouseEnter.bind(this));
					BX.Event.bind(this._titleLink, 'mouseleave', this.onTitleMouseLeave.bind(this));

					titleWrapper.appendChild(
						BX.create("div",
							{
								props: { className: "crm-entity-widget-client-box-name-row" },
								children: [ this.getTitleIcon(), this._titleLink, buttonWrapper ]
							}
						)
					);
				}
				else
				{
					var titleNone = BX.create("span",
						{
							props:{ className: "crm-entity-widget-client-box-name" },
							text: this._entityInfo.getTitle()
						}
					);

					titleWrapper.appendChild(
						BX.create("div",
							{
								props: { className: "crm-entity-widget-client-box-name-row" },
								children: [ titleNone, buttonWrapper ]
							}
						)
					);
				}

				if(this._enableCommunications)
				{
					this._communicationButtons = [];
					var commTypes = [ "PHONE", "EMAIL", "IM" ];
					for(var i = 0, j = commTypes.length; i < j; i++)
					{
						var commType = commTypes[i];
						var button = BX.Crm.ClientEditorCommunicationButton.create(
							this._id +  "_" + commType,
							{
								entityInfo: this._entityInfo,
								type: commType,
								ownerTypeId: this._editor.getOwnerTypeId(),
								ownerId: this._editor.getOwnerId(),
								container: buttonWrapper
							}
						);
						button.layout();
						this._communicationButtons.push(button);
					}
				}

				var description = this._entityInfo.getDescription();
				if(description !== "")
				{
					innerWrapper.appendChild(
						BX.create("div",
							{
								props: { className: "crm-entity-widget-client-box-position" },
								text: description
							}
						)
					);
				}

				var phones = this._entityInfo.getPhones();
				var emails = this._entityInfo.getEmails();
				if(phones.length > 0 || emails.length > 0)
				{
					var communicationContainer = BX.create("div", { props: { className: "crm-entity-widget-client-contact" } });
					innerWrapper.appendChild(communicationContainer);

					if(phones.length > 0)
					{
						communicationContainer.appendChild(
							BX.create("div",
								{
									props: { className: "crm-entity-widget-client-contact-item crm-entity-widget-client-contact-phone" },
									//HACK: Disable autodetection of phone number for Microsoft Edge
									attrs: { "x-ms-format-detection": "none" },
									text: phones[0]["VALUE_FORMATTED"]
								}
							)
						);
					}

					if(emails.length > 0)
					{
						communicationContainer.appendChild(
							BX.create("div",
								{
									props: { className: "crm-entity-widget-client-contact-item crm-entity-widget-client-contact-email" },
									text: emails[0]["VALUE_FORMATTED"]
								}
							)
						);
					}
				}

				if (this._clientRequisites && this._enableAddress)
				{
					this._clientRequisites.addressLayout(innerWrapper);
				}

				var callback = BX.prop.getFunction(this._settings, "onLayout", null);
				if(callback)
				{
					callback(this, this._wrapper);
				}

				this._hasLayout = true;
			},
			release: function()
			{
				this.clearLayout();
			},
			clearLayout: function()
			{
				if (this._clientRequisites)
				{
					this._clientRequisites.release();
				}

				this._communicationButtons = null;
				this._titleIcon = null;
				this._wrapper = BX.remove(this._wrapper);
				this._hasLayout = false;
			},
			checkOwership: function(element)
			{
				return this._wrapper && BX.isParentForNode(this._wrapper, element);
			},
			onTitleMouseEnter: function()
			{
				if(this._clientRequisites && this._enableRequisitesTooltip)
				{
					this._clientRequisites.showTooltip(this._wrapper);
				}
			},
			onTitleMouseLeave: function()
			{
				if(this._clientRequisites && this._enableRequisitesTooltip)
				{
					this._clientRequisites.closeTooltip();
				}
			},
			onDeleteButtonClick: function(e)
			{
				var callback = BX.prop.getFunction(this._settings, "onDelete");
				if(callback)
				{
					callback(this);
				}
			},
			onRequisiteChange: function(event)
			{
				var data = event.getData();
				var requisiteId = BX.prop.getInteger(data, "requisiteId", 0);
				var bankDetailId = BX.prop.getInteger(data, "bankDetailId", 0);

				if(!this._requisiteInfo
					|| this._requisiteInfo.getRequisiteId() !== requisiteId
					|| this._requisiteInfo.getBankDetailId() !== bankDetailId
				)
				{
					if (this._useExternalRequisiteBinding)
					{
						this._selectedRequisiteChangeNotifier.notify([{requisiteId: requisiteId, bankDetailId: bankDetailId}]);
					}
					else
					{
						BX.ajax.runAction(
							'crm.requisite.settings.setSelectedEntityRequisite',
							{
								data: {
									entityTypeId: this._entityInfo.getTypeId(),
									entityId: this._entityInfo.getId(),
									requisiteId: requisiteId,
									bankDetailId: bankDetailId
								}
							}
						)
					}
				}
			},
			onChangeRequisiteList: function(event)
			{
				this._requisiteListChangeNotifier.notify([ event.getData() ]);
			}
		};
	BX.Crm.ClientEditorEntityPanel.create = function(id, settings)
	{
		var self = new BX.Crm.ClientEditorEntityPanel();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.ClientEditorEntityBindingPanel === "undefined")
{
	BX.Crm.ClientEditorEntityBindingPanel = function()
	{
		this._id = "";
		this._settings = {};
		this._container = null;
		this._entityInfo = null;
		this._editor = null;
		this._mode = BX.UI.EntityEditorMode.intermediate;
		this._item = null;
	};
	BX.Crm.ClientEditorEntityBindingPanel.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};
				this._container = BX.prop.getElementNode(this._settings, "container", null);
				this._editor = BX.prop.get(this._settings, "editor");
				this._entityInfo = BX.prop.get(this._settings, "entityInfo", null);

				this._mode = BX.prop.getInteger(this._settings, "mode", 0);
				this._item = BX.Crm.ClientEditorEntityPanel.create(
					this._id +  "_" + this._entityInfo.getId().toString(),
					{
						editor: this._editor,
						entityInfo: this._entityInfo,
						mode: this._mode,
						onLayout: BX.delegate(this.onItemLayout, this),
						onDelete: BX.delegate(this.onItemDelete, this)
					}
				);
			},
			getEntity: function()
			{
				return this._entityInfo;
			},
			getContainer: function()
			{
				return this._container;
			},
			setContainer: function(container)
			{
				this._container = container;
			},
			layout: function()
			{
				this._button = BX.create("div",
					{
						props: { className: "crm-entity-widget-client-child-link" },
						events: { click: BX.delegate(this.onButtonClick, this) }
					}
				);

				this._item.setContainer(this._container);
				this._item.layout();
			},
			onItemLayout: function(item, wrapper)
			{
				BX.addClass(wrapper, "crm-entity-widget-client-block-child");
				var anchor = wrapper.firstChild;
				if(anchor)
				{
					wrapper.insertBefore(this._button, anchor);
				}
				else
				{
					wrapper.appendChild(this._button);
				}
			},
			clearLayout: function()
			{
				this._item.clearLayout();
			},
			onItemDelete: function(item)
			{
				if(this._mode !== BX.UI.EntityEditorMode.edit)
				{
					return;
				}
				var callback = BX.prop.getFunction(this._settings, "onChange", null);
				if(callback)
				{
					callback(this, "delete");
				}
			},
			onButtonClick: function(e)
			{
				if(this._mode !== BX.UI.EntityEditorMode.edit)
				{
					return;
				}
				var callback = BX.prop.getFunction(this._settings, "onChange", null);
				if(callback)
				{
					callback(this, "unbind");
				}
			}
		};
	BX.Crm.ClientEditorEntityBindingPanel.create = function(id, settings)
	{
		var self = new BX.Crm.ClientEditorEntityBindingPanel();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.ClientEditorCommunicationButton === "undefined")
{
	BX.Crm.ClientEditorCommunicationButton = function()
	{
		this._id = "";
		this._settings = {};
		this._entityInfo = null;
		this._type = "";

		this._items = null;

		this._container = null;
		this._wrapper = null;
		this._menu = null;
	};
	BX.Crm.ClientEditorCommunicationButton.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};
				this._entityInfo = BX.prop.get(this._settings, "entityInfo", null);
				this._type = BX.prop.getString(this._settings, "type", "");

				this._container = BX.prop.getElementNode(this._settings, "container", "");
				if(this._type === "")
				{
					this._type = "PHONE";
				}

				this._items = this._entityInfo.getMultiFieldsByType(this._type);
			},
			layout: function()
			{
				var className;
				if(this._type === "EMAIL")
				{
					className = "crm-entity-widget-client-action-mail";
				}
				else if(this._type === "IM")
				{
					className = "crm-entity-widget-client-action-im";
				}
				else// if(this._type === "PHONE")
				{
					className = "crm-entity-widget-client-action-call";
				}

				if(this._items.length > 0)
				{
					className += " crm-entity-widget-client-action-available";
				}

				this._wrapper = BX.create("a", { props: { className: className } });
				BX.bind(this._wrapper, "click", BX.delegate(this.onClick, this));
				this._container.appendChild(this._wrapper);
			},
			onClick: function(e)
			{
				if(this._items.length === 0)
				{
					return BX.eventReturnFalse(e);
				}

				if(this._items.length === 1)
				{
					var item = this._items[0];
					var value = BX.prop.getString(item, "VALUE");
					if(value !== "")
					{
						if(this._type === "PHONE")
						{
							this.addCall(value);
						}
						else if(this._type === "EMAIL")
						{
							this.addEmail(value);
						}
						else if(this._type === "IM")
						{
							this.openChat(value);
						}
					}
					return BX.eventReturnFalse(e);
				}

				this.toggleMenu();
				BX.eventReturnFalse(e);
			},
			toggleMenu: function()
			{
				if(!this._menu)
				{
					var menuItems = [];
					for(var i = 0, l = this._items.length; i < l; i++)
					{
						var value = BX.prop.getString(this._items[i], "VALUE");
						var formattedValue = BX.prop.getString(this._items[i], "VALUE_FORMATTED");
						var complexName = BX.prop.getString(this._items[i], "COMPLEX_NAME");
						var itemText = (complexName ? complexName + ': ' : '') + (formattedValue ? formattedValue : value);

						if(value !== "")
						{
							menuItems.push({ id: value, text:  itemText });
						}
					}

					this._menu = BX.Crm.ClientEditorMenu.create(
						this._id.toLowerCase() + "_menu",
						{
							anchor: this._wrapper,
							items: menuItems,
							callback: BX.delegate(this.onMenuItemSelect, this)
						}
					);
				}
				this._menu.toggle();
			},
			onMenuItemSelect: function(menu, item)
			{
				if(this._type === "EMAIL")
				{
					this.addEmail(item["id"])
				}
				else if(this._type === "IM")
				{
					this.openChat(item["id"]);
				}
				else// if(this._type === "PHONE")
				{
					this.addCall(item["id"])
				}

				this._menu.close();
			},
			addCall: function(phone)
			{
				if(typeof(window.top['BXIM']) === 'undefined')
				{
					window.alert(this.getMessage("telephonyNotSupported"));
					return;
				}

				var params =
					{
						"ENTITY_TYPE_NAME": this._entityInfo.getTypeName(),
						"ENTITY_ID": this._entityInfo.getId(),
						"AUTO_FOLD": true
					};

				var ownerTypeId = BX.prop.getInteger(this._settings, "ownerTypeId", 0);
				var ownerId = BX.prop.getInteger(this._settings, "ownerId", 0);
				if(ownerTypeId !== this._entityInfo.getTypeId() || ownerId !== this._entityInfo.getId())
				{
					params["BINDINGS"] = [ { "OWNER_TYPE_NAME": BX.CrmEntityType.resolveName(ownerTypeId), "OWNER_ID": ownerId } ];
				}

				window.top['BXIM'].phoneTo(phone, params);
			},
			addEmail: function(email)
			{
				var ownerTypeId = BX.prop.getInteger(this._settings, "ownerTypeId", 0);
				var ownerId = BX.prop.getInteger(this._settings, "ownerId", 0);
				BX.CrmActivityEditor.addEmail(
					{
						ownerID: ownerId,
						ownerType: BX.CrmEntityType.resolveName(ownerTypeId),
						communicationsLoaded: true,
						communications:
							[
								{
									type: "EMAIL",
									entityType: this._entityInfo.getTypeName(),
									entityId: this._entityInfo.getId(),
									value: email
								}
							]
					}
				);
			},
			openChat: function (messengerValue)
			{
				if(typeof(window.top["BXIM"]) === "undefined")
				{
					window.alert(this.getMessage("messagingNotSupported"));
					return;
				}
				window.top["BXIM"].openMessengerSlider(messengerValue, {RECENT: 'N', MENU: 'N'});
			}
		};
	BX.Crm.ClientEditorCommunicationButton.prototype.getMessage = function(name)
	{
		var m = BX.Crm.ClientEditorCommunicationButton.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};

	if(typeof(BX.Crm.ClientEditorCommunicationButton.messages) === "undefined")
	{
		BX.Crm.ClientEditorCommunicationButton.messages = {};
	}
	BX.Crm.ClientEditorCommunicationButton.create = function(id, settings)
	{
		var self = new BX.Crm.ClientEditorCommunicationButton();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.ClientEditorMenu === "undefined")
{
	BX.Crm.ClientEditorMenu = function()
	{
		this._id = null;
		this._settings = {};
		this._items = null;
		this._isOpened = false;
		this._popup = null;
	};
	BX.Crm.ClientEditorMenu.prototype = {
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._items = BX.prop.getArray(this._settings, "items", []);
			for(var i = 0, l = this._items.length; i < l; i++)
			{
				this._items[i]["onclick"] = BX.delegate(this.onItemSelect, this);
			}
		},
		onItemSelect: function(e, item)
		{
			var callback = BX.prop.getFunction(this._settings, "callback", null);
			if(callback)
			{
				callback(this, item);
			}
		},
		isOpened: function()
		{
			return this._isOpened;
		},
		open: function()
		{
			if(this._isOpened)
			{
				return;
			}

			BX.PopupMenu.show(
				this._id,
				BX.prop.getElementNode(this._settings, "anchor", null),
				this._items,
				{
					offsetTop: 0,
					offsetLeft: 0,
					events:
						{
							onPopupShow: BX.delegate(this.onPopupShow, this),
							onPopupClose: BX.delegate(this.onPopupClose, this),
							onPopupDestroy: BX.delegate(this.onPopupDestroy, this)
						}
				}
			);
			this._popup = BX.PopupMenu.currentItem;
		},
		close: function()
		{
			if(!this._isOpened)
			{
				return;
			}

			if(this._popup)
			{
				if(this._popup.popupWindow)
				{
					this._popup.popupWindow.close();
				}
			}
		},
		toggle: function()
		{
			if(!this._isOpened)
			{
				this.open();
			}
			else
			{
				this.close();
			}
		},
		onPopupShow: function()
		{
			this._isOpened = true;
		},
		onPopupClose: function()
		{
			if(this._popup && this._popup.popupWindow)
			{
				this._popup.popupWindow.destroy();
			}
		},
		onPopupDestroy: function()
		{
			this._isOpened = false;
			this._popup = null;

			if(typeof(BX.PopupMenu.Data[this._id]) !== "undefined")
			{
				delete(BX.PopupMenu.Data[this._id]);
			}
		}
	};
	BX.Crm.ClientEditorMenu.create = function(id, settings)
	{
		var self = new BX.Crm.ClientEditorMenu();
		self.initialize(id, settings);
		return self;
	};
}

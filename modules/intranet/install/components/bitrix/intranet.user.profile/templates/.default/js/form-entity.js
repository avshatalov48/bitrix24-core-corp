;(function () {

"use strict";

var namespace = BX.namespace('BX.Intranet.UserProfile');
if (namespace.EntityEditor)
{
	return;
}
namespace.EntityEditor = function(params) {
	this.init(params);
};

namespace.EntityEditor.prototype =
{
	init: function(options)
	{
		var params = options.params;
		this.managerInstance = options.managerInstance;

		this.signedParameters = params.signedParameters;
		this.componentName = params.componentName;
		this.initialFields = params.initialFields || {};
		this.isCloud = params.isCloud === "Y";
		this.isCurrentUserAdmin = params.isCurrentUserAdmin === "Y";
		this.gridId = params.gridId || null;
		this.voximplantEnablePhones = params.voximplantEnablePhones;

		BX.addCustomEvent("BX.UI.EntityEditorControlFactory:onInitialize", BX.proxy(function (params, eventArgs) {
			eventArgs.methods["timezone"] = this.userProfileEntity.bind(this);
			eventArgs.methods["phone"] = this.userProfileEntity.bind(this);
		}, this));

		BX.addCustomEvent("BX.UI.EntityEditorAjax:onSubmit", BX.proxy(function (fields) {
			this.onAfterSubmit(fields);
		}, this));

		if (!this.isCloud && this.isCurrentUserAdmin)
		{
			BX.addCustomEvent(
				window,
				"BX.UI.EntityEditor:onPrepareConfigMenuItems",
				function(sender, items)
				{
					items.push(
						{
							id: "intranet-fields-link",
							text: BX.message("INTRANET_USER_FIILDS_SETTINGS"),
							onclick: function () { this.showFieldsSettings(); }.bind(this),
							className: "menu-popup-item-none"
						}
					);
				}.bind(this)
			);

			BX.addCustomEvent(
				window,
				"BX.UI.EntityEditor:onUserFieldAdd",
				function(sender, userField)
				{
					if (typeof userField !== "object" || !userField)
					{
						return;
					}

					BX.ajax.runComponentAction(this.componentName, "onUserFieldAdd", {
						signedParameters: this.signedParameters,
						mode: 'ajax',
						data: {
							fieldName: userField["FIELD"]
						}
					}).then(function (response) {

					}, function (response) {

					});

				}.bind(this)
			);
		}

		if (document.querySelector('#show-ru-meta-notification'))
		{
			var cb = BX.debounce(function(eventName, editor) {
				var fb = editor._container.querySelector('[data-cid="UF_FACEBOOK"]');
				var isVisible = false;
				if (fb)
				{
					isVisible = fb.style.display !== 'none' && !BX.hasClass(fb, 'ui-entity-card-content-hide');
				}
				document.querySelector('#show-ru-meta-notification').style.display = isVisible ? 'inherit' : 'none';
			}.bind(this), 100);
			[
				"BX.UI.EntityEditor:onRefreshLayout",
				"BX.UI.EntityEditor:onLayout",
				"BX.UI.EntityEditorSection:onLayout",
				"BX.UI.EntityEditorSection:onChildMenuItemSelect",
				"BX.UI.EntityEditorField:onChildMenuItemDeselect"
			].forEach((eventName) => {
				BX.addCustomEvent(eventName, function() {
					cb.apply(this, [eventName, ...arguments]);
				});
			});
		}
	},

	userProfileEntity: function (type, controlId, settings)
	{
		if (type === "timezone")
		{
			return BX.Intranet.UserProfile.EntityEditorTimezone.create(controlId, settings);
		}

		if (type === "phone")
		{
			settings.model._settings["SHOW_PHONE_ICON"] = this.voximplantEnablePhones[controlId] == "Y" ? "Y" : "N";

			return BX.Intranet.UserProfile.EntityEditorPhone.create(controlId, settings);
		}

		return null;
	},

	onAfterSubmit: function (fields)
	{
		if (this.isCloud)
		{
			var personalMobile = BX.prop.getString(fields, "PERSONAL_MOBILE", "");

			if (personalMobile && this.initialFields["PERSONAL_MOBILE"] != personalMobile)
			{
				if (this.managerInstance.showSmsPopup instanceof Function)
				{
					this.managerInstance.showSmsPopup(personalMobile);
				}
				this.managerInstance.personalMobile = personalMobile;
			}
		}

		var name = BX.prop.getString(fields, "NAME", "");
		var lastName = BX.prop.getString(fields, "LAST_NAME", "");

		if (
			name && this.initialFields["NAME"] != name
			|| lastName && this.initialFields["LAST_NAME"] != lastName
		)
		{
			this.changePageTitle(BX.prop.getString(fields, "FULL_NAME", ""));
			(top || window).BX.onCustomEvent('BX.Intranet.UserProfile:Name:changed', [{fullName: BX.prop.getString(fields, "FULL_NAME", "")}]);
		}

		this.reloadGrid();
	},

	reloadGrid: function()
	{
		if (
			!BX.type.isNotEmptyString(this.gridId)
			|| !window.parent.BX.Main.gridManager
		)
		{
			return;
		}

		var grid = window.parent.BX.Main.gridManager.getById(this.gridId);
		if (!grid)
		{
			return;
		}

		let currentPage = grid.instance.getCurrentPage();
		if (BX.Type.isInteger(currentPage) && currentPage > 0)
		{
			grid.instance.reload('?page=page-' + currentPage);
		}
		else
		{
			grid.instance.reload();
		}
	},

	changePageTitle: function(fullName)
	{
		if (!fullName)
		{
			return;
		}

		BX.html(BX("pagetitle"), fullName);

		document.title = fullName;
		if (BX.getClass("BX.SidePanel.Instance.updateBrowserTitle"))
		{
			BX.SidePanel.Instance.updateBrowserTitle();
		}
	},

	showFieldsSettings: function ()
	{
		BX.SidePanel.Instance.open("intranet:user-pfofile-fields-settings", {
			contentCallback: function (slider) {

				return new Promise(function(resolve, reject) {
					top.BX.ajax.runComponentAction(this.componentName, "fieldsSettings", {
						signedParameters: this.signedParameters,
						mode: 'class',
						data: {}
					}).then(function(response) {
						resolve({ html: response.data.html });
					});
				}.bind(this));

			}.bind(this),
			width: 600
		});
	}
};


BX.load(['/bitrix/js/ui/entity-editor/js/editor.js'], function ()
{
	if(typeof BX.Intranet.UserProfile.EntityEditorTimezone === "undefined")
	{
		BX.Intranet.UserProfile.EntityEditorTimezone = function () {
			BX.Intranet.UserProfile.EntityEditorTimezone.superclass.constructor.apply(this);
			this._items = null;
			this._inputAutoTimeZone = null;
			this._inputTimeZone = null;
			this._selectAutoTimeZone = null;
			this._selectTimeZone = null;
			this._selectContainer = null;
			this._selectedValue = "";
			this._selectorClickHandler = BX.delegate(this.onSelectorClick, this);
			this._innerWrapper = null;
			this._isOpened = false;
		};
		BX.Intranet.UserProfile.EntityEditorTimezone.create = function (id, settings) {
			var self = new BX.Intranet.UserProfile.EntityEditorTimezone();
			self.initialize(id, settings);
			return self;
		};

		BX.extend(BX.Intranet.UserProfile.EntityEditorTimezone, BX.UI.EntityEditorField);
		BX.Intranet.UserProfile.EntityEditorTimezone.prototype.layout = function (options) {
			if (this._hasLayout)
			{
				return;
			}

			this.ensureWrapperCreated({classNames: ["ui-entity-card-content-block-field-select"]});
			this.adjustWrapper();

			if (!this.isNeedToDisplay())
			{
				this.registerLayout(options);
				this._hasLayout = true;
				return;
			}

			var name = this.getName();
			var title = this.getTitle();

			var value = this.getValue();
			var timeZoneItem = this.getTimeZoneItemByValue(value.timeZone);
			var autoTimeZoneItem = this.getAutoTimeZoneItemByValue(value.autoTimeZone);
			var isHtmlOption = this.getDataBooleanParam('isHtml', false);
			var containerPropsAutoTimeZone = {};
			var containerPropsTimeZone = {};

			/*	if(!item)
				{
					item = this.getFirstItem();
					if(item)
					{
						value = item["VALUE"];
					}
				}*/
			this._selectedValueAutoTimeZone = value.autoTimeZone;
			this._selectedValueTimeZone = value.timeZone;

			this._selectAutoTimeZone = null;
			this._selectTimeZone = null;
			this._innerWrapper = null;

			if (this._mode === BX.UI.EntityEditorMode.edit)
			{
				this._wrapper.appendChild(this.createTitleNode(title));

				this._inputAutoTimeZone = BX.create("input", {
					attrs: {
						name: "AUTO_TIME_ZONE",
						type: "hidden",
						value: value.autoTimeZone
					}
				});
				this._inputTimeZone = BX.create("input", {
					attrs: {
						name: "TIME_ZONE",
						type: "hidden",
						value: value.timeZone
					}
				});

				this._wrapper.appendChild(this._inputAutoTimeZone);
				this._wrapper.appendChild(this._inputTimeZone);

				containerPropsAutoTimeZone = {props: {className: "ui-ctl-element"}};
				if (isHtmlOption)
				{
					containerPropsAutoTimeZone.html = (autoTimeZoneItem ? autoTimeZoneItem["NAME"] : value);
				}
				else
				{
					containerPropsAutoTimeZone.text = (autoTimeZoneItem ? autoTimeZoneItem["NAME"] : value);
				}

				this._selectAutoTimeZone = BX.create("div", containerPropsAutoTimeZone);
				BX.bind(this._selectAutoTimeZone, "click", BX.proxy(function () {
					this._selectorClickHandler("autoTimeZone");
				}, this));

				containerPropsTimeZone = {props: {className: "ui-ctl-element"}};
				if (isHtmlOption)
				{
					containerPropsTimeZone.html = (timeZoneItem ? timeZoneItem["NAME"] : value);
				}
				else
				{
					containerPropsTimeZone.text = (timeZoneItem ? timeZoneItem["NAME"] : value);
				}

				this._selectTimeZone = BX.create("div", containerPropsTimeZone);
				BX.bind(this._selectTimeZone, "click", BX.proxy(function () {
					this._selectorClickHandler("timeZone");
				}, this));

				var selectIconAutoTimeZone = BX.create("div",
					{
						attrs: {className: "ui-ctl-after ui-ctl-icon-angle"}
					}
				);

				this._selectContainerAutoTimeZone = BX.create("div",
					{
						props: {className: "ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100"},
						children: [
							this._selectAutoTimeZone,
							selectIconAutoTimeZone
						]
					}
				);

				this._selectContainerTimeZone = BX.create("div",
					{
						props: {
							className: "ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100",
							style: "margin-top: 10px;" + (value.autoTimeZone !== "N" ? "display: none;" : "")
						},
						children: [
							this._selectTimeZone,
							BX.clone(selectIconAutoTimeZone)
						]
					}
				);

				this._innerWrapper = BX.create("div",
					{
						props: {className: "ui-entity-card-content-block-inner"},
						children: [this._selectContainerAutoTimeZone, this._selectContainerTimeZone]
					}
				);

				this._wrapper.appendChild(this._innerWrapper);

				if (this.isContextMenuEnabled())
				{
					this._wrapper.appendChild(this.createContextMenuButton());
				}

				this.registerLayout(options);
				this._hasLayout = true;
			}
			else// if(this._mode === BX.UI.EntityEditorMode.view)
			{

			}

		};
		BX.Intranet.UserProfile.EntityEditorTimezone.prototype.getModeSwitchType = function (mode) {
			var result = BX.UI.EntityEditorModeSwitchType.common;
			if (mode === BX.UI.EntityEditorMode.edit)
			{
				result |= BX.UI.EntityEditorModeSwitchType.button | BX.UI.EntityEditorModeSwitchType.content;
			}
			return result;
		};
		BX.Intranet.UserProfile.EntityEditorTimezone.prototype.getContentWrapper = function () {
			return this._innerWrapper;
		};
		BX.Intranet.UserProfile.EntityEditorTimezone.prototype.checkIfNotEmpty = function (value) {
			//0 is value for "Not Selected" item
			return value !== "" && value !== "0";
		};
		BX.Intranet.UserProfile.EntityEditorTimezone.prototype.doRegisterLayout = function () {
		};
		BX.Intranet.UserProfile.EntityEditorTimezone.prototype.doClearLayout = function (options) {
			this.closeMenu();

			this._input = null;
			this._select = null;
			this._innerWrapper = null;
		};
		BX.Intranet.UserProfile.EntityEditorTimezone.prototype.refreshLayout = function () {
			if (!this._hasLayout)
			{
				return;
			}

			if (!this._isValidLayout)
			{
				BX.Intranet.UserProfile.EntityEditorTimezone.superclass.refreshLayout.apply(this, arguments);
				return;
			}

			var value = this.getValue();
			var item = this.getItemByValue(value);
			var text = item ? BX.prop.getString(item, "NAME", value) : value;
			if (this._mode === BX.Intranet.UserProfile.EntityEditorMode.edit)
			{
				this._selectedValue = value;
				if (this._input)
				{
					this._input.value = value;
				}
				if (this._select)
				{
					this._select.innerHTML = this.getDataBooleanParam('isHtml', false) ? text : BX.util.htmlspecialchars(text);
				}
			}
			else if (this._mode === BX.Intranet.UserProfile.EntityEditorMode.view && this._innerWrapper)
			{
				this._innerWrapper.innerHTML = this.getDataBooleanParam('isHtml', false) ? text : BX.util.htmlspecialchars(text);
			}
		};
		BX.Intranet.UserProfile.EntityEditorTimezone.prototype.validate = function (result) {
			if (this._mode !== BX.UI.EntityEditorMode.edit)
			{
				throw "BX.Intranet.UserProfile.EntityEditorTimezone. Invalid validation context";
			}

			if (!this.isEditable())
			{
				return true;
			}

			this.clearError();

			if (this.hasValidators())
			{
				return this.executeValidators(result);
			}

			var isValid = !this.isRequired() || BX.util.trim(this._input.value) !== "";
			if (!isValid)
			{
				result.addError(BX.Intranet.UserProfile.EntityValidationError.create({field: this}));
				this.showRequiredFieldError(this._input);
			}
			return isValid;
		};
		BX.Intranet.UserProfile.EntityEditorTimezone.prototype.showError = function (error, anchor) {
			BX.Intranet.UserProfile.EntityEditorTimezone.superclass.showError.apply(this, arguments);
			if (this._input)
			{
				BX.addClass(this._input, "ui-entity-card-content-error");
			}
		};
		BX.Intranet.UserProfile.EntityEditorTimezone.prototype.clearError = function () {
			BX.Intranet.UserProfile.EntityEditorTimezone.superclass.clearError.apply(this);
			if (this._input)
			{
				BX.removeClass(this._input, "ui-entity-card-content-error");
			}
		};
		BX.Intranet.UserProfile.EntityEditorTimezone.prototype.onSelectorClick = function (itemType) {
			if (!this._isOpened)
			{
				this.openMenu(itemType);
			}
			else
			{
				this.closeMenu(itemType);
			}
		};
		BX.Intranet.UserProfile.EntityEditorTimezone.prototype.openMenu = function (itemType) {
			if (this._isOpened)
			{
				return;
			}

			var menu = [];
			if (itemType == "timeZone")
			{
				var items = this.getTimeZoneItems();
			}
			else
			{
				items = this.getAutoTimeZoneItems();
			}

			for (var i = 0, length = items.length; i < length; i++)
			{
				var item = items[i];
				if (!BX.prop.getBoolean(item, "IS_EDITABLE", true))
				{
					continue;
				}

				var value = BX.prop.getString(item, "VALUE", i);
				var name = BX.prop.getString(item, "NAME", value);
				menu.push(
					{
						text: this.getDataBooleanParam('isHtml', false) ? name : BX.util.htmlspecialchars(name),
						value: value,
						onclick: BX.delegate(itemType == "autoTimeZone" ? this.onAutoTimeZoneItemSelect : this.onTimeZoneItemSelect, this)
					}
				);
			}

			var select = itemType === "timeZone" ? this._selectTimeZone : this._selectAutoTimeZone;

			BX.PopupMenu.show(
				this._id,
				select,
				menu,
				{
					angle: false, width: select.offsetWidth + 'px',
					maxHeight: 300,
					events:
						{
							onPopupShow: BX.delegate(this.onMenuShow, this),
							onPopupClose: BX.delegate(this.onMenuClose, this)
						}
				}
			);
			BX.PopupMenu.currentItem.popupWindow.setWidth(BX.pos(select)["width"]);
		};
		BX.Intranet.UserProfile.EntityEditorTimezone.prototype.closeMenu = function () {
			var menu = BX.PopupMenu.getMenuById(this._id);
			if (menu)
			{
				menu.popupWindow.close();
			}
		};
		BX.Intranet.UserProfile.EntityEditorTimezone.prototype.onMenuShow = function () {
			BX.addClass(this._selectContainer, "ui-ctl-active");
			this._isOpened = true;
		};
		BX.Intranet.UserProfile.EntityEditorTimezone.prototype.onMenuClose = function () {
			BX.PopupMenu.destroy(this._id);

			BX.removeClass(this._selectContainer, "ui-ctl-active");
			this._isOpened = false;
		};
		BX.Intranet.UserProfile.EntityEditorTimezone.prototype.onAutoTimeZoneItemSelect = function (e, item) {
			this.closeMenu();

			this._selectedValueAutoTimeZone = this._inputAutoTimeZone.value = item.value;
			var name = BX.prop.getString(
				this.getAutoTimeZoneItemByValue(this._selectedValueAutoTimeZone),
				"NAME",
				this._selectedValueAutoTimeZone
			);

			this._selectAutoTimeZone.innerHTML = this.getDataBooleanParam('isHtml', false) ? name : BX.util.htmlspecialchars(name);
			this.markAsChanged();
			BX.PopupMenu.destroy(this._id);

			this._selectContainerTimeZone.style.display = (this._selectedValueAutoTimeZone === "N") ? "block" : "none";
		};
		BX.Intranet.UserProfile.EntityEditorTimezone.prototype.onTimeZoneItemSelect = function (e, item) {
			this.closeMenu();

			this._selectedValueTimeZone = this._inputTimeZone.value = item.value;
			var name = BX.prop.getString(
				this.getTimeZoneItemByValue(this._selectedValueTimeZone),
				"NAME",
				this._selectedValueTimeZone
			);

			this._selectTimeZone.innerHTML = this.getDataBooleanParam('isHtml', false) ? name : BX.util.htmlspecialchars(name);
			this.markAsChanged();
			BX.PopupMenu.destroy(this._id);

		};
		BX.Intranet.UserProfile.EntityEditorTimezone.prototype.getTimeZoneItems = function () {
			if (!this._itemsAutoTimeZone)
			{
				this._itemsAutoTimeZone = BX.prop.getArray(this._schemeElement.getData(), "timezone_items", []);
			}
			return this._itemsAutoTimeZone;
		};
		BX.Intranet.UserProfile.EntityEditorTimezone.prototype.getAutoTimeZoneItems = function () {
			if (!this._itemsTimeZone)
			{
				this._itemsTimeZone = BX.prop.getArray(this._schemeElement.getData(), "auto_timezone_items", []);
			}
			return this._itemsTimeZone;
		};
		BX.Intranet.UserProfile.EntityEditorTimezone.prototype.getTimeZoneItemByValue = function (value) {
			var items = this.getTimeZoneItems();

			for (var i = 0, l = items.length; i < l; i++)
			{
				var item = items[i];
				if (value === BX.prop.getString(item, "VALUE", ""))
				{
					return item;
				}
			}
			return null;
		};
		BX.Intranet.UserProfile.EntityEditorTimezone.prototype.getAutoTimeZoneItemByValue = function (value) {
			var items = this.getAutoTimeZoneItems();

			for (var i = 0, l = items.length; i < l; i++)
			{
				var item = items[i];
				if (value === BX.prop.getString(item, "VALUE", ""))
				{
					return item;
				}
			}
			return null;
		};
		BX.Intranet.UserProfile.EntityEditorTimezone.prototype.getFirstItem = function () {
			var items = this.getItems();
			return items.length > 0 ? items[0] : null;
		};
		BX.Intranet.UserProfile.EntityEditorTimezone.prototype.save = function () {
			if (!this.isEditable())
			{
				return;
			}

			var newValue = {timeZone: this._selectedValueTimeZone, autoTimeZone: this._selectedValueAutoTimeZone};
			this._model.setField(this.getName(), newValue);
		};
		BX.Intranet.UserProfile.EntityEditorTimezone.prototype.processModelChange = function (params) {
			if (BX.prop.get(params, "originator", null) === this)
			{
				return;
			}

			if (!BX.prop.getBoolean(params, "forAll", false)
				&& BX.prop.getString(params, "name", "") !== this.getName()
			)
			{
				return;
			}

			this.refreshLayout();
		};
		BX.Intranet.UserProfile.EntityEditorTimezone.prototype.getRuntimeValue = function () {
			return (this._mode === BX.Intranet.UserProfile.EntityEditorMode.edit && this._input
					? this._selectedValue : ""
			);
		};
	}


	if(typeof BX.Intranet.UserProfile.EntityEditorPhone === "undefined")
	{
		BX.Intranet.UserProfile.EntityEditorPhone = function ()
		{
			BX.Intranet.UserProfile.EntityEditorPhone.superclass.constructor.apply(this);
			this._input = null;
			this._innerWrapper = null;
		};
		BX.Intranet.UserProfile.EntityEditorPhone.create = function (id, settings)
		{
			var self = new BX.Intranet.UserProfile.EntityEditorPhone();
			self.initialize(id, settings);
			return self;
		};

		BX.extend(BX.Intranet.UserProfile.EntityEditorPhone, BX.UI.EntityEditorField);

		BX.Intranet.UserProfile.EntityEditorPhone.prototype.getModeSwitchType = function(mode)
		{
			var result = BX.UI.EntityEditorModeSwitchType.common;
			if(mode === BX.UI.EntityEditorMode.edit)
			{
				result |= BX.UI.EntityEditorModeSwitchType.button|BX.UI.EntityEditorModeSwitchType.content;
			}
			return result;
		};
		BX.Intranet.UserProfile.EntityEditorPhone.prototype.getContentWrapper = function()
		{
			return this._innerWrapper;
		};
		BX.Intranet.UserProfile.EntityEditorPhone.prototype.focus = function()
		{
			if(!this._input)
			{
				return;
			}

			BX.focus(this._input);
			BX.UI.EditorTextHelper.getCurrent().setPositionAtEnd(this._input);
		};
		BX.Intranet.UserProfile.EntityEditorPhone.prototype.layout = function (options)
		{
			if(this._hasLayout)
			{
				return;
			}

			this.ensureWrapperCreated({ classNames: [ "ui-entity-card-content-block-field-phone" ] });
			this.adjustWrapper();

			if(!this.isNeedToDisplay())
			{
				this.registerLayout(options);
				this._hasLayout = true;
				return;
			}

			var name = this.getName();
			var title = this.getTitle();
			var value = this.getValue();

			var showPhoneIcon = this.checkPhoneIcon();

			this._input = null;
			this._inputContainer = null;
			this._innerWrapper = null;

			if(this.isDragEnabled())
			{
				this._wrapper.appendChild(this.createDragButton());
			}

			if(this._mode === BX.UI.EntityEditorMode.edit)
			{
				this._wrapper.appendChild(this.createTitleNode(title));

				this._inputContainer = BX.create("div",
					{
						attrs: { className: "ui-ctl ui-ctl-textbox ui-ctl-w100" }
					}
				);

				this._input = BX.create("input",
					{
						attrs:
							{
								name: name,
								className: "ui-ctl-element",
								type: "text",
								value: value,
								id: this._id.toLowerCase() + "_text"
							}
					}
				);

				this._inputContainer.appendChild(this._input);


				if(this.isNewEntity())
				{
					var placeholder = this.getCreationPlaceholder();
					if(placeholder !== "")
					{
						this._input.setAttribute("placeholder", placeholder);
					}
				}

				BX.bind(this._input, "input", this._changeHandler);

				this._innerWrapper = BX.create("div",
					{
						props: { className: "ui-entity-editor-content-block" },
						children: [ this._inputContainer ]
					});
			}
			else// if(this._mode === BX.UI.EntityEditorMode.view)
			{
				this._wrapper.appendChild(this.createTitleNode(title));

				if(this.hasContentToDisplay())
				{
					this._innerWrapper = BX.create("div",
						{
							props: { className: "ui-entity-editor-content-block" },
							children:
								[
									BX.create("div",
										{
											props: { className: "ui-entity-editor-content-block-text" },
											//text: value,
											children: [
												BX.create("span", {text :value}),
												showPhoneIcon ?
													BX.create("div", {
														props: { className: "intranet-user-profile-phone-icon" },
														events: {
															mouseup: function (event) {
																event.preventDefault();
																event.stopPropagation();

																top.BXIM.phoneTo(value);
															}
														}
													}) : ""
											]

										})
								]
						});
				}
				else
				{
					this._innerWrapper = BX.create("div",
						{
							props: { className: "ui-entity-editor-content-block" },
							text: BX.message("UI_ENTITY_EDITOR_FIELD_EMPTY")
						});
				}
			}

			this._wrapper.appendChild(this._innerWrapper);

			if(this.isContextMenuEnabled())
			{
				this._wrapper.appendChild(this.createContextMenuButton());
			}

			if(this.isDragEnabled())
			{
				this.initializeDragDropAbilities();
			}

			this.registerLayout(options);
			this._hasLayout = true;
		};

		BX.Intranet.UserProfile.EntityEditorPhone.prototype.checkPhoneIcon = function (options)
		{
			return this._model._settings["SHOW_PHONE_ICON"] == "Y";
		}
	}
});

})();
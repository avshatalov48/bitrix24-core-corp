(function() {

	"use strict";

	/**
	 * @namespace BX.Disk.Model.ExternalLink
	 */
	BX.namespace("BX.Disk.Model.ExternalLink");

	/**
	 *
	 * @param {object} parameters
	 * @extends {BX.Disk.Model.Item}
	 * @constructor
	 */
	BX.Disk.Model.ExternalLink.Settings = function(parameters)
	{
		BX.Disk.Model.Item.apply(this, arguments);

		this.templateId = this.templateId || 'external-link-setting-popup';

		this.initNewState();
	};

	BX.Disk.Model.ExternalLink.Settings.prototype =
	{
		__proto__: BX.Disk.Model.Item.prototype,
		constructor: BX.Disk.Model.ExternalLink.Settings,

		getDefaultStateValues: function ()
		{
			return {
				defaultValueForTime: 10,
				getFormattedDeathTime: this.getFormattedDeathTime.bind(this)
			};
		},

		initNewState: function ()
		{
			this.newState = {};
			this.newState.deathTimeFactor = 60;
			this.newState.deathTimeTimestamp = Math.floor(Date.now() / 1000) + this.newState.deathTimeFactor * this.state.defaultValueForTime;
		},

		bindEvents: function ()
		{
			var container = this.getContainer();

			var checkboxTimeLimit = this.getEntity(container, 'public-link-setting-checkbox-time-limit');
			var checkboxSetPassword = this.getEntity(container, 'public-link-setting-checkbox-password');
			var canEditPassword = this.getEntity(container, 'public-link-setting-checkbox-can-edit');
			var buttonShowPassword = this.getEntity(container, 'public-link-setting-popup-password-show');
			var accessType = this.getEntity(container, 'public-link-setting-popup-dropdown');
			var inputPassword = this.getEntity(this.getContainer(), 'public-link-setting-popup-input-password');
			var inputDeathTime = this.getEntity(this.getContainer(), 'public-link-setting-popup-input');

			BX.bind(checkboxTimeLimit, 'click', this.handleStateTimeInputBlock.bind(this));
			BX.bind(checkboxSetPassword, 'click', this.handleStatePasswordInputBlock.bind(this));
			BX.bind(canEditPassword, 'click', this.handleStateCanEdit.bind(this));
			BX.bind(buttonShowPassword, 'mousedown', this.handleStatePasswordInput.bind(this));
			BX.bind(buttonShowPassword, 'mouseup', this.handleStatePasswordInput.bind(this));
			BX.bind(accessType, 'click', this.showAccessTypePopup.bind(this));
			BX.bind(inputPassword, 'bxchange', this.handlePasswordValue.bind(this));
			BX.bind(inputDeathTime, 'bxchange', this.handleDeathTimeValue.bind(this));
		},

		/**
		 * @param {?Item~saveCallback} callback
		 */
		save: function (callback)
		{
			var afterSave = function(externalLinkSettings) {
				externalLinkSettings.newState = {};
				externalLinkSettings.initNewState();
				BX.onCustomEvent("Disk.ExternalLink.Settings:onSave", [externalLinkSettings]);
				callback && callback(externalLinkSettings.state, externalLinkSettings);
			};

			if (typeof this.newState.hasDeathTime != 'undefined' && this.newState.hasDeathTime != this.state.hasDeathTime)
			{
				if (!this.newState.hasDeathTime)
				{
					BX.ajax.runAction('disk.api.externalLink.revokeDeathTime', {
						data: {
							externalLinkId: this.state.id
						}
					}).then(function (response) {
						this.state.hasDeathTime = false;
						this.state.deathTimeTimestamp = null;
						this.state.deathTime = null;

						return this;
					}.bind(this)).then(afterSave);
				}
				else
				{
					BX.ajax.runAction('disk.api.externalLink.setDeathTime', {
						data: {
							externalLinkId: this.state.id,
							deathTime: this.newState.deathTimeTimestamp
						}
					}).then(function (response) {
						this.state.hasDeathTime = response.data.externalLink.hasDeathTime;
						this.state.deathTimeTimestamp = response.data.externalLink.deathTimeTimestamp;
						this.state.deathTime = response.data.externalLink.deathTime;

						return this;
					}.bind(this)).then(afterSave);
				}
			}

			if (typeof this.newState.hasPassword != 'undefined' && this.newState.hasPassword != this.state.hasPassword)
			{
				if (!this.newState.hasPassword)
				{
					BX.ajax.runAction('disk.api.externalLink.revokePassword', {
						data: {
							externalLinkId: this.state.id
						}
					}).then(function (response) {
						this.state.hasPassword = false;

						return this;
					}.bind(this)).then(afterSave);
				}
			}

			if (this.newState.newPassword)
			{
				BX.ajax.runAction('disk.api.externalLink.setPassword', {
					data: {
						externalLinkId: this.state.id,
						newPassword: this.newState.newPassword
					}
				}).then(function (response) {
					this.state.hasPassword = true;

					return this;
				}.bind(this)).then(afterSave);
			}

			if (typeof this.newState.canEditDocument != 'undefined' && this.newState.canEditDocument != this.state.canEditDocument)
			{
				if (!this.newState.canEditDocument)
				{
					BX.ajax.runAction('disk.api.externalLink.disallowEditDocument', {
						data: {
							externalLinkId: this.state.id
						}
					}).then(function (response) {
						this.state.canEditDocument = false;

						return this;
					}.bind(this)).then(afterSave);
				}
				else
				{
					BX.ajax.runAction('disk.api.externalLink.allowEditDocument', {
						data: {
							externalLinkId: this.state.id
						}
					}).then(function (response) {
						this.state.canEditDocument = true;

						return this;
					}.bind(this)).then(afterSave);
				}
			}
		},

		handlePasswordValue: function(event)
		{
			var eventTarget = BX.getEventTarget(event);
			if (eventTarget.value)
			{
				this.newState.newPassword = eventTarget.value;
			}
		},

		handleDeathTimeValue: function(event)
		{
			this.updateNewDeathTimestamp(BX.getEventTarget(event).value);
		},

		updateNewDeathTimestamp: function (value)
		{
			value = parseInt(value, 10);
			if (value <= 0)
			{
				this.newState.hasDeathTime = false;

				return;
			}

			var timestamp = Math.floor(Date.now() / 1000);

			this.newState.deathTimeTimestamp = timestamp + (parseInt(value, 10) * this.newState.deathTimeFactor);
			this.newState.hasDeathTime = true;
		},

		getFormattedDeathTime: function()
		{
			if (!this.state.deathTime)
			{
				return '';
			}

			return BX.date.format(
				BX.date.convertBitrixFormat(BX.message('FORMAT_DATETIME')),
				new Date(this.state.deathTime)
			);
		},

		handleStatePasswordInput: function(event)
		{
			var inputPassword = this.getEntity(this.getContainer(), 'public-link-setting-popup-input-password');

			inputPassword.getAttribute('type') === 'password' ?
				inputPassword.setAttribute('type', 'text') :
				inputPassword.setAttribute('type', 'password')
		},

		handleStateTimeInputBlock: function(event)
		{
			var eventTarget = BX.getEventTarget(event);
			var settingNode = this.getEntity(this.getContainer(), 'public-link-setting-time-limit');

			if (eventTarget.checked)
			{
				settingNode.classList.remove('disk-external-link-setting-popup-disabled');
				this.newState.hasDeathTime = true;
			}
			else
			{
				settingNode.classList.add('disk-external-link-setting-popup-disabled');
				this.newState.deathTimeTimestamp = null;
				this.newState.hasDeathTime = false;
			}
		},

		handleStateCanEdit: function(event)
		{
			var eventTarget = BX.getEventTarget(event);
			this.newState.canEditDocument = eventTarget.checked;
		},

		handleStatePasswordInputBlock: function(event)
		{
			var eventTarget = BX.getEventTarget(event);
			var settingNode = this.getEntity(this.getContainer(), 'public-link-setting-password');

			if (eventTarget.checked)
			{
				settingNode.classList.remove('disk-external-link-setting-popup-disabled');
				this.newState.hasPassword = true;
			}
			else
			{
				settingNode.classList.add('disk-external-link-setting-popup-disabled');
				this.newState.hasPassword = false;
			}
		},

		showAccessTypePopup: function (event)
		{
			var self = this;

			var bindElement = BX.getEventTarget(event);
			var zIndex = null;
			if (BX.getClass('BX.SidePanel.Instance') && BX.SidePanel.Instance.isOpen())
			{
				zIndex = BX.SidePanel.Instance.getTopSlider().getZindex();
			}

			var accessType = BX.PopupMenu.create('disk-detail-time-period', bindElement,
				[
					{
						text: BX.message('DISK_JS_EL_SETTINGS_LINK_LABEL_MINUTE'),
						onclick: function(e, item){
							BX.adjust(bindElement, {text: item.text});
							self.newState.deathTimeFactor = 60;

							this.close();
						}
					},
					{
						text: BX.message('DISK_JS_EL_SETTINGS_LINK_LABEL_HOUR'),
						onclick: function(e, item){
							BX.adjust(bindElement, {text: item.text});
							self.newState.deathTimeFactor = 60*60;

							this.close();
						}
					},
					{
						text: BX.message('DISK_JS_EL_SETTINGS_LINK_LABEL_DAY'),
						onclick: function(e, item){
							BX.adjust(bindElement, {text: item.text});
							self.newState.deathTimeFactor = 60*60*24;

							this.close();
						}
					}
				],
				{
					angle: {
						offset: 35
					},
					overlay: {
						backgroundColor: 'rgba(0,0,0,0)'
					},
					autoHide: true,
					className: 'disk-detail-time-period',
					offsetLeft: 50,
					zIndex: zIndex,
					events: {
						onPopupClose: function () {
							var value = this.getEntity(this.getContainer(), 'public-link-setting-popup-input').value;
							this.updateNewDeathTimestamp(value);

							accessType.destroy();
						}.bind(this)
					}
				}
			);

			accessType.show();
		}
	};

})();
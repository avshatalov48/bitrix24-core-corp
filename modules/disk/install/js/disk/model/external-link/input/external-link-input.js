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
	BX.Disk.Model.ExternalLink.Input = function(parameters)
	{
		BX.Disk.Model.Item.apply(this, arguments);

		this.templateId = this.templateId || 'external-link-setting-input';

		this.externalLinkSettings = parameters.models.externalLinkSettings;
		this.externalLinkDescription = parameters.models.externalLinkDescription;
	};

	BX.Disk.Model.ExternalLink.Input.prototype =
	{
		__proto__: BX.Disk.Model.Item.prototype,
		constructor: BX.Disk.Model.ExternalLink.Input,

		getDefaultStateValues: function ()
		{
			return {
				placeholderDescription: this.placeholderDescription.bind(this),
				firstRender: this.getRenderCount() === 0
			};
		},

		renderSubItems: function ()
		{
			this.externalLinkDescription.render();
			var placeholder = this.getEntity(this.getContainer(), 'external-link-placeholderDescription');
			placeholder.parentNode.replaceChild(this.externalLinkDescription.getContainer(), placeholder);
		},

		bindEvents: function ()
		{
			var config = this.getEntity(this.getContainer(), 'public-link-config');
			var copyButton = this.getEntity(this.getContainer(), 'copy-btn');
			var input = this.getEntity(this.getContainer(), 'external-link-input');
			var switcher = this.getEntity(this.getContainer(), 'external-link-switcher');

			BX.bind(config, 'click', this.handleConfigButton.bind(this));
			BX.bind(copyButton, 'click', this.handleCopyLink.bind(this));
			BX.bind(input, 'click', this.handleClickInput);
			BX.bind(switcher, 'click', this.handleSwitcher.bind(this));
		},

		handleSwitcher: function(e)
		{
			var switcher = e.currentTarget;
			var block = this.getEntity(this.getContainer(), 'external-link-block');
			if (BX.hasClass(switcher, 'js-disk-switcher-on'))
			{
				BX.ajax.runAction('disk.api.commonActions.disableExternalLink', {
					data: {
						objectId: this.data.objectId
					}
				});

				this.toggleOnOff(switcher, block);
			}
			else
			{
				BX.ajax.runAction('disk.api.commonActions.generateExternalLink', {
					data: {
						objectId: this.data.objectId
					}
				}).then(function (response) {
					if (!response || response.status !== 'success')
					{
						return;
					}

					this.setState(response.data.externalLink);
					this.toggleOnOff(switcher, block);
					this.render();

				}.bind(this));
			}
		},

		toggleOnOff: function(switcher, block)
		{
			switcher.classList.toggle('js-disk-switcher-on');
			switcher.classList.toggle('js-disk-switcher-off');

			switcher.classList.remove('disk-switcher-on');
			switcher.classList.remove('disk-switcher-off');

			if (switcher.classList.contains('js-disk-switcher-on'))
			{
				switcher.classList.toggle('disk-switcher-animate-to-on');
			}
			else
			{
				switcher.classList.toggle('disk-switcher-animate-to-off');
			}

			block.classList.toggle('disk-detail-sidebar-public-link-block-show');
			block.classList.toggle('disk-detail-sidebar-public-link-block-hide');
			block.classList.toggle('disk-public-link-block-hide');

			setTimeout(function () {
				this.adjustHeight();
			}.bind(this), 0);
		},

		handleClickInput: function()
		{
			BX.focus(this);
			this.setSelectionRange(0, this.value.length);
		},

		handleCopyLink: function (event)
		{
			var element = BX.getEventTarget(event);
			var elementAttr = element.getAttribute('for');
			var copyInput = document.getElementById(elementAttr);
			copyInput.select();
			document.execCommand('copy');
			if (!this.popupOuterLink)
			{
				this.showCopyLinkPopup(copyInput, BX.message('DISK_JS_EL_INPUT_LINK_COPIED'));
			}
		},

		showCopyLinkPopup: function(node, message)
		{
			this.popupOuterLink = new BX.PopupWindow('disk-popup-copy-link', node, {
				className: 'disk-popup-copy-link',
				bindPosition: {
					position: 'top'
				},
				offsetLeft: -10,
				darkMode: true,
				angle: true,
				content: message
			});

			this.popupOuterLink.show();

			setTimeout(function() {
				BX.addClass(BX(this.popupOuterLink.uniquePopupId), 'disk-popup-copy-link-hide');
			}.bind(this), 2000);

			setTimeout(function() {
				this.popupOuterLink.destroy();
				this.popupOuterLink = null;
			}.bind(this), 2200)
		},

		handleConfigButton: function(event)
		{
			var configPlace = this.getEntity(this.getContainer(), 'external-link-config-place');

			this.externalLinkSettings.render();

			if (configPlace)
			{
				configPlace.appendChild(this.externalLinkSettings.getContainer());
				var block = this.getEntity(this.getContainer(), 'external-link-block');
				block.style.height = '';
			}
			else
			{
				var zIndex = null;
				if (BX.getClass('BX.SidePanel.Instance') && BX.SidePanel.Instance.isOpen())
				{
					zIndex = BX.SidePanel.Instance.getTopSlider().getZindex();
				}

				var paramMenu = new BX.PopupWindow(
					'disk-detail-public-link-settings-popup',
					BX.getEventTarget(event),
					{
						className: 'disk-detail-public-link-settings-popup',
						closeByEsc: true,
						offsetTop: -6,
						offsetLeft: 17,
						zIndex: zIndex,
						overlay: {
							backgroundColor: 'rgba(0,0,0,0)'
						},
						autoHide: true,
						angle: true,
						events: {
							onPopupClose: function() {
								this.externalLinkSettings.save(function(state, model) {
									this.setState(state);
								}.bind(this));

								paramMenu.destroy();
							}.bind(this)
						},
						content: this.externalLinkSettings.getContainer()
					}
				);

				paramMenu.show();
			}

			BX.onCustomEvent("Disk.ExternalLink.Input:onShowConfig", []);
		},

		adjustHeight: function ()
		{
			var block = this.getEntity(this.getContainer(), 'external-link-block');
			if (
				block.classList.contains('disk-detail-sidebar-public-link-block-show') ||
				block.classList.contains('disk-detail-properties-public-link-block-show') ||
				block.classList.contains('disk-public-link-block-show')
			)
			{
				var blockWrapper = this.getEntity(this.getContainer(), 'external-link-block-wrapper');
				block.style.height = blockWrapper.offsetHeight + 'px';
				block.style.opacity = '1';
			}
			else
			{
				block.style.opacity = '0';
				block.style.height = '0';
			}

			if (block.classList.contains('disk-detail-sidebar-public-link-block-hide'))
			{
				block.style.opacity = '0';
				block.style.height = '0';
			}
		},

		placeholderDescription: function ()
		{
			return '<span data-entity="external-link-placeholderDescription"></span>';
		}
	};

})();

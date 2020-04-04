;(function(window)
{
	window.toggleHideList = function(event)
	{
		var hiddenList = document.getElementById('hidden-list');

		hiddenList.style.display = (hiddenList.style.display !== 'block') ? 'block' : 'none';
		return false;
	};

	window.showInstagramHelp = function(event)
	{
		if (top.BX.Helper)
		{
			top.BX.Helper.show('redirect=detail&code=8293539');
			event.preventDefault();
		}
	};

	window.popupShowDisconnectImport = function(idForm)
	{
		var popupShowTrue = new BX.PopupWindow('uid' + idForm, null, {
			closeIcon: {right: '5px', top: '5px'},
			titleBar: BX.message('CRM_OIIE_SETTINGS_CONFIRM_DISABLE_TITLE'),
			closeByEsc: true,
			autoHide: true,
			content: '<p class=\"imconnector-popup-text\">' + BX.message('CRM_OIIE_SETTINGS_CONFIRM_DISABLE') + '</p>',
			overlay: {
				backgroundColor: 'black', opacity: '80'
			},
			maxWidth: 500,
			buttons: [
				new BX.PopupWindowButton({
					text: BX.message('CRM_OIIE_SETTINGS_DISABLE'),
					className: 'popup-window-button-decline',
					events: {
						click: function()
						{
							BX.submit(BX('form_delete_' + idForm));
							this.popupWindow.close();
						}
					}
				}),
				new BX.PopupWindowButton({
					text: BX.message('CRM_OIIE_SETTINGS_CONFIRM_DISABLE_BUTTON_CANCEL'),
					className: 'popup-window-button-link',
					events: {
						click: function()
						{
							this.popupWindow.close()
						}
					}
				})
			]
		});
		popupShowTrue.show();
	};

	window.popupShowDisconnectPage = function(idForm)
	{
		var popupShowTrue = new BX.PopupWindow('uid' + idForm, null, {
			closeIcon: {right: '5px', top: '5px'},
			titleBar: BX.message('CRM_OIIE_SETTINGS_CONFIRM_DELETE_PAGE_TITLE'),
			closeByEsc: true,
			autoHide: true,
			content: '<p class=\"imconnector-popup-text\">' + BX.message('CRM_OIIE_SETTINGS_CONFIRM_DELETE_PAGE') + '</p>',
			overlay: {
				backgroundColor: 'black', opacity: '80'
			},
			maxWidth: 500,
			buttons: [
				new BX.PopupWindowButton({
					text: BX.message('CRM_OIIE_SETTINGS_DISABLE'),
					className: 'popup-window-button-decline',
					events: {
						click: function()
						{
							BX.submit(BX('delete_page_' + idForm));
							this.popupWindow.close();
						}
					}
				}),
				new BX.PopupWindowButton({
					text: BX.message('CRM_OIIE_SETTINGS_CONFIRM_DISABLE_BUTTON_CANCEL'),
					className: 'popup-window-button-link',
					events: {
						click: function()
						{
							this.popupWindow.close()
						}
					}
				})
			]
		});
		popupShowTrue.show();
	};

	window.popupShowChangePage = function(idForm)
	{
		var popupShowTrue = new BX.PopupWindow('uid' + idForm, null, {
			closeIcon: {right: '5px', top: '5px'},
			titleBar: BX.message('CRM_OIIE_SETTINGS_CONFIRM_CHANGE_PAGE_TITLE'),
			closeByEsc: true,
			autoHide: true,
			content: '<p class=\"imconnector-popup-text\">' + BX.message('CRM_OIIE_SETTINGS_CONFIRM_CHANGE_PAGE') + '</p>',
			overlay: {
				backgroundColor: 'black', opacity: '80'
			},
			maxWidth: 500,
			buttons: [
				new BX.PopupWindowButton({
					text: BX.message('CRM_OIIE_SETTINGS_TO_CONNECT'),
					className: 'popup-window-button-accept',
					events: {
						click: function()
						{
							BX.submit(BX('change_page_' + idForm));
							this.popupWindow.close();
						}
					}
				}),
				new BX.PopupWindowButton({
					text: BX.message('CRM_OIIE_SETTINGS_CONFIRM_DISABLE_BUTTON_CANCEL'),
					className: 'popup-window-button-link',
					events: {
						click: function()
						{
							this.popupWindow.close()
						}
					}
				})
			]
		});
		popupShowTrue.show();
	};

	window.addPreloader = function(nodeTo)
	{
		var preloader = BX.create("div", {
			props: {
				className: "side-panel-overlay side-panel-overlay-open",
				style: "position: fixed; background-color: rgba(255, 255, 255, .7);"
			},
			children: [
				BX.create("div", {
					props: {
						className: "side-panel-default-loader-container"
					},
					html:
						'<svg class="side-panel-default-loader-circular" viewBox="25 25 50 50">' +
						'<circle ' +
						'class="side-panel-default-loader-path" ' +
						'cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"' +
						'/>' +
						'</svg>'
				})
			]
		});

		nodeTo = BX.type.isDomNode(nodeTo) ? nodeTo : document.body;
		nodeTo.appendChild(preloader);
	};

	BX.ready(function()
	{
		BX.bindDelegate(
			document.body,
			'click',
			{props: {id: 'toggle-list'}},
			toggleHideList
		);
		BX.bindDelegate(
			document.body,
			'click',
			{className: 'show-preloader-button'},
			addPreloader
		);
		BX.bindDelegate(
			document.body,
			'submit',
			{tag: 'form'},
			addPreloader
		);

		if (window.top.BX.SidePanel)
		{
			var createLink = document.querySelector('[data-entity="create-store-link"]');

			if (BX.type.isDomNode(createLink))
			{
				var openSliders = window.top.BX.SidePanel.Instance.getOpenSliders();

				for (var i = 0; i < openSliders.length; i++)
				{
					var context = openSliders[i].getWindow();

					if (context.BX && context.BX.Landing && context.BX.Landing.TemplatePreviewInstance)
					{
						BX.bind(createLink, 'click', function(event)
						{
							BX.addClass(this.createByImportButton, 'ui-btn-wait');
							this.onCreateButtonClick(event);
							window.top.BX.SidePanel.Instance.getTopSlider().close();
						}.bind(context.BX.Landing.TemplatePreviewInstance));

						createLink.style.display = '';
						break;
					}
				}
			}
		}
	});
})(window);
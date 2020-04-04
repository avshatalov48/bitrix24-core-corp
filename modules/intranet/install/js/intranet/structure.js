;(function(){

if (!!BX.IntranetStructure)
	return;

BX.IntranetStructure =
{
	bInit: false,
	popup: null,
	arParams: {}
}

BX.IntranetStructure.Init = function(arParams)
{
	if(arParams)
		BX.IntranetStructure.arParams = arParams;

	if(BX.IntranetStructure.bInit)
		return;

	BX.IntranetStructure.bInit = true;

	BX.ready(BX.delegate(function()
	{
		BX.IntranetStructure.popup = BX.PopupWindowManager.create("BXStructure", null, {
			autoHide: false,
			zIndex: 0,
			offsetLeft: 0,
			offsetTop: 0,
			draggable: {restrict:true},
			closeByEsc: true,
			titleBar: BX.message('INTR_STRUCTURE_TITLE' + (arParams['UF_DEPARTMENT_ID'] > 0 ? '_EDIT' : '')),
			closeIcon: true,
			buttons: [
				new BX.PopupWindowButton({
					text : BX.message('INTR_STRUCTURE_BUTTON'),
					className : "popup-window-button-accept",
					events : { click : function()
					{
						var form = BX('STRUCTURE_FORM');
						var button = this;
						var handler = BX.delegate(function(result)
						{
							BX.removeClass(button.buttonNode, 'popup-window-button-wait');

							if (result == "close")
							{
								BX.IntranetStructure.popup.close();
								if (window.BXSTRUCTURECALLBACK)
									window.BXSTRUCTURECALLBACK.apply(BX.IntranetStructure.popup, [result])
								else
									BX.reload();
							}
							else if (/^error:/.test(result))
							{
								var obErrors = BX.create('DIV', {
									html: '<div class="webform-round-corners webform-error-block" style="margin-top:5px" id="error">\
												<div class="webform-corners-top"><div class="webform-left-corner"></div><div class="webform-right-corner"></div></div>\
												<div class="webform-content">\
													<ul class="webform-error-list">'+result.substring(6, result.length)+'</ul>\
												</div>\
												<div class="webform-corners-bottom"><div class="webform-left-corner"></div><div class="webform-right-corner"></div></div>\
											</div>'
								})

								if (BX.findChild(BX.IntranetStructure.popup.contentContainer, {className: 'webform-error-block'}, true))
								{
									BX.IntranetStructure.popup.contentContainer.replaceChild(obErrors, BX.IntranetStructure.popup.contentContainer.firstChild);
								}
								else
								{
									BX.IntranetStructure.popup.contentContainer.insertBefore(obErrors, BX.IntranetStructure.popup.contentContainer.firstChild);
								}

							}
							else
							{
								BX.IntranetStructure.popup.setContent(result);
								if (window.BXSTRUCTURECALLBACK)
									window.BXSTRUCTURECALLBACK.apply(BX.IntranetStructure.popup, [result])
							}
						});

						if (form && !BX.hasClass(button.buttonNode, 'popup-window-button-wait'))
						{
							BX.addClass(button.buttonNode, 'popup-window-button-wait');

							if (!form.reload)
							{
								BX.ajax.submit(form, handler);
							}
							else
							{
								BX.ajax.get(form.action, handler);
							}
						}
					}}
				}),

				new BX.PopupWindowButtonLink({
					text: BX.message('INTR_CLOSE_BUTTON'),
					className: "popup-window-button-link-cancel",
					events: { click : function()
					{
						this.popupWindow.close();
					}}
				})
			],
			content: '<div style="width:450px;height:230px"></div>',
			events: {
				onAfterPopupShow: function()
				{
					this.setContent('<div style="width:450px;height:230px" id="intr_struct_load">'+BX.message('INTR_LOADING')+'</div>');
					BX.ajax.post(
						'/bitrix/tools/intranet_structure.php',
						{
							lang: BX.message('LANGUAGE_ID'),
							site_id: BX.message('SITE_ID') || '',
							arParams: BX.IntranetStructure.arParams
						},
						BX.delegate(function(result)
						{
							this.setContent(result);
						},
						this)
					);
				}
			}
		});
	}, this));
}

BX.IntranetStructure.ShowForm = function(arParams)
{
	BX.IntranetStructure.Init(arParams);
	BX.IntranetStructure.popup.params.zIndex = (BX.WindowManager? BX.WindowManager.GetZIndex() : 0);
	BX.IntranetStructure.popup.show();
}


})();
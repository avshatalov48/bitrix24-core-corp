(function() {
	'use strict';

	/**
	 * @namespace BX.Config.Order.Property
	 */
	BX.namespace('BX.Config.Order.Property');

	BX.Config.Order.Property = function(options)
	{
		this.params = options.params || {};
		this.signedParamsString = options.signedParamsString;
		this.actionRequestUrl = options.actionRequestUrl;

		this.bindEvents();
	};

	BX.Config.Order.Property.prototype =
		{
			getFileIds: function()
			{
				var fileIds = {};
				var form = BX('crm-order-prop'), el, input;

				if (BX.type.isDomNode(form))
				{
					var index = 0;

					for (var i = 0; i < form.elements.length; i++)
					{
						el = form.elements[i];

						if (el.disabled || !el.type || el.type.toLowerCase() !== 'file')
							continue;

						input = el.previousSibling;

						if (input.type === 'hidden')
						{
							var name = el.name;
							var p = name.indexOf('[');

							if (p > -1)
							{
								name = el.name.substring(0, p);
								index = el.name.substring(p + 1, el.name.indexOf(']'));
							}

							if (!(BX.type.isPlainObject(fileIds[name])))
							{
								fileIds[name] = {};
							}

							fileIds[name][index] = {ID: input.value};
							index++;
						}
					}
				}

				return fileIds;
			},

			reloadAction: function()
			{
				var form = BX('crm-order-prop');
				var that = this;

				this.fadeForm();

				BX.ajax.submitAjax(
					form,
					{
						url: this.actionRequestUrl,
						method: 'POST',
						dataType: 'json',
						data: {
							action: 'reloadFormAjax',
							isAjax: 'Y',
							FILE_IDS: this.getFileIds(),
							signedParamsString: this.signedParamsString
						},
						onsuccess: function(result){
							if (!result.html)
								return;

							var processed = BX.processHTML(result.html);

							form.innerHTML = processed.HTML;
							BX.ajax.processScripts(processed.SCRIPT);

							if (!result.error)
							{
								window.top.BX.SidePanel.Instance.postMessage(
									window,
									'OrderPropertyEdit::onReload',
									{
										property: result.property
									}
								);
							}

							that.showForm();
						}
					}
				);
			},

			saveAction: function()
			{
				var form = BX('crm-order-prop');
				var that = this;

				this.fadeForm();

				BX.ajax.submitAjax(
					form,
					{
						url: this.actionRequestUrl,
						method: 'POST',
						dataType: 'json',
						data: {
							action: 'saveFormAjax',
							isAjax: 'Y',
							FILE_IDS: this.getFileIds(),
							signedParamsString: this.signedParamsString
						},
						onsuccess: function(result){
							that.showForm();

							if (!result)
								return;

							if (result.html)
							{
								var processed = BX.processHTML(result.html);

								form.innerHTML = processed.HTML;
								BX.ajax.processScripts(processed.SCRIPT);
							}

							if (!result.error)
							{
								if (that.params.IFRAME)
								{
									window.top.BX.SidePanel.Instance.postMessage(
										window,
										'OrderPropertyEdit::onSave',
										{
											property: result.property
										}
									);
									window.top.BX.SidePanel.Instance.close();
								}
								else if (BX.type.isNotEmptyString(result.redirect))
								{
									document.location.href = result.redirect;
								}
							}
						}
					}
				);
			},

			applyAction: function()
			{
				var form = BX('crm-order-prop');

				window.top.BX.SidePanel.Instance.postMessage(
					window,
					'OrderPropertyEdit::onApply',
					{
						property: BX.ajax.prepareForm(form).data
					}
				);
				window.top.BX.SidePanel.Instance.close();
			},

			fadeForm: function()
			{
				var form = BX('crm-order-prop');

				form.style.opacity = '0.2';
			},

			showForm: function()
			{
				var form = BX('crm-order-prop');

				form.style.opacity = '';
			},

			closeSlider: function()
			{
				window.top.BX.SidePanel.Instance.close();
			},

			saveClickHandler: function(event)
			{
				if (this.params.LOAD_FROM_REQUEST === 'Y' && parseInt(this.params.PROPERTY_ID) <= 0)
				{
					this.applyAction(event);
				}
				else
				{
					this.saveAction(event);
				}
			},

			bindEvents: function()
			{
				BX.bind(BX('CRM_ORDER_PROPERTY_APPLY_BUTTON'), 'click', BX.proxy(this.saveClickHandler, this));
				BX.bind(BX('CRM_ORDER_PROPERTY_SUBMIT_BUTTON'), 'click', BX.proxy(this.saveClickHandler, this));
				BX.bind(BX('CRM_ORDER_PROPERTY_CANCEL'), 'click', BX.proxy(this.closeSlider, this));
			}
		};
})();

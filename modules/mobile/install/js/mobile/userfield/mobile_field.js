;(function ()
{
	var BX = window.BX;

	var MobileFieldManager = {
		fieldNames: [],
		getInstance: function getInstance() {
			return this;
		}
	};

	BX.namespace("BX.Mobile.Field");
	BX.Mobile.Field.prototype = {
		init: function (params)
		{
			this.gridId = params['gridId'] || '';
			this.formId = params['formId'] || '';
			this.formats = params['formats'] || null;
			this.restrictedMode = params['restrictedMode'];
			this.name = params['name'] || null;
			this.repo = {
				formId: {},
				gridId: {}
			};

			if (this.gridId != '')
			{
				this.repo['gridId'][this.gridId] = this;
			}

			if (this.formId != '')
			{
				this.repo['formId'][this.formId] = this;
			}

			this.apply = BX.delegate(this.apply, this);

			var
				nodes = params['nodes'] || [],
				node,
				obj,
				ff = this.initFf(node, arguments);

			this.bindElements(nodes, params, ff);

			BX.addCustomEvent(
				'onAddMobileUfField',
				BX.delegate(function (command, userFieldType)
				{
					var nodes = BX(command);

					var mobileFieldManager = MobileFieldManager.getInstance();
					var fieldName = nodes.getAttribute('name');

					if (!mobileFieldManager.fieldNames.includes(fieldName))
					{
						mobileFieldManager.fieldNames.push(fieldName);

						if (userFieldType === 'BX.Mobile.Field.Datetime')
						{
							new BX.Mobile.Field.Datetime({
								name: 'BX.Mobile.Field.Datetime',
								nodes: [BX(command)],
								restrictedMode: true,
								formId: this.formId,
								gridId: this.gridId
							});
						}
						else if (userFieldType === 'BX.Mobile.Field.Date')
						{
							new BX.Mobile.Field.Date({
								name: 'BX.Mobile.Field.Date',
								nodes: [BX(command)],
								restrictedMode: true,
								formId: this.formId,
								gridId: this.gridId
							});
						}
						else if (userFieldType === 'BX.Mobile.Field.Money')
						{
							new BX.Mobile.Field.Money({
								name: 'BX.Mobile.Field.Money',
								nodes: [BX(command)],
								restrictedMode: true,
								formId: this.formId,
								gridId: this.gridId
							});
						}
					}
				}, this)
			);
		},
		bindElements: function (nodes, params, ff)
		{
			while ((node = nodes.pop()) && node)
			{
				if ((obj = this.bindElement(BX(node))) && obj)
				{
					if (params["restrictedMode"])
					{
						BX.addCustomEvent(obj, "onChange", this.apply);
					}
					BX.addCustomEvent(obj, "onChange", ff);
				}
			}
		},
		initFf: function (node, params)
		{
			return BX.proxy(function (o, node)
			{
				var res = [this, node, o];
				for (var i = 2; i < params.length; i++)
				{
					res.push(params[i]);
				}
				BX.onCustomEvent(this, "onChange", res);
				window.BXMobileApp.Events.postToComponent(
					'onMobileGridFormDataChange',
					this.getParamsForMobilePostEvent(node, o),
					'tasks.view'
				);
			}, this)
		},
		cancel: function (e)
		{
			if (e)
			{
				BX.PreventDefault(e);
			}
			BX.onCustomEvent(this, 'onCancel', [this, BX(this.formId)]);
			return false;
		},
		click: function (e)
		{
			if (e)
			{
				BX.PreventDefault(e);
			}
			this.save();
			return false;
		},
		apply: function (obj, input, file)
		{
			var res = {
				submit: true
			};
			BX.onCustomEvent(
				this,
				'onSubmitForm',
				[this, BX(this.formId), input, res]
			);
			window.BXMobileApp.onCustomEvent(
				'onSubmitForm',
				[this.gridId, this.formId, (input ? input.id : null)],
				true
			);

			if (res.submit !== false)
			{
				this.submit(true);
			}
		},
		save: function ()
		{
			var res = {
				submit: true
			};
			BX.onCustomEvent(
				this,
				'onSubmitForm',
				[this, BX(this.formId), null, res]
			);
			window.BXMobileApp.onCustomEvent(
				'onSubmitForm',
				[this.gridId, this.formId, null],
				true
			);
			if (res.submit !== false)
			{
				this.submit(false);
			}
		},
		submit: function (ajax)
		{

			if (!BX(this.formId))
			{
				return;
			}

			var options = {
				restricted: 'Y',
				method: BX(this.formId).getAttribute('method'),
				onsuccess: BX.proxy(function ()
				{
					BX.onCustomEvent(this, "onSubmitAjaxSuccess", [this, arguments[0]]);
				}, this),
				onfailure: BX.proxy(function ()
				{
					BX.onCustomEvent(this, "onSubmitAjaxFailure", [this, arguments[0]]);
				}, this),
				onprogress: BX.proxy(function ()
				{
					BX.onCustomEvent(this, "onSubmitAjaxProgress", [this, arguments]);
				}, this)
			};

			if (ajax)
			{
				BX.onCustomEvent(this, "onBeforeSubmitAjax", [this, options]);
			}
			else
			{
				options['restricted'] = 'N';
				options['onsuccess'] = BX.proxy(function ()
				{
					BXMobileApp.UI.Page.LoadingScreen.hide();
					BX.onCustomEvent(this, "onSubmitFormSuccess", [this, arguments[0]]);
				}, this);
				options["onfailure"] = BX.proxy(function ()
				{
					BXMobileApp.UI.Page.LoadingScreen.hide();
					BX.onCustomEvent(this, "onSubmitFormFailure", [this, arguments[0]]);
				}, this);
				options["onprogress"] = BX.proxy(function ()
				{
					BX.onCustomEvent(this, "onSubmitFormProgress", [this, arguments]);
				}, this);
				BX.onCustomEvent(this, "onBeforeSubmitForm", [this, options]);
				BXMobileApp.UI.Page.LoadingScreen.show();
			}

			var save = BX(this.formId).elements['save'];

			if (!BX(save))
			{
				save = BX.create("INPUT", {attrs: {type: "hidden", name: "save"}});
				BX(this.formId).appendChild(save);
			}

			save.value = 'Y';
			BX.ajax.submitAjax(BX(this.formId), options);
		},
		getParamsForMobilePostEvent: function (node, fieldObject)
		{
			return {
				formId: this.formId,
				gridId: this.gridId,
				nodeId: node.id,
				nodeName: node.name,
				nodeValue: node.value
			};
		}
	};
}());
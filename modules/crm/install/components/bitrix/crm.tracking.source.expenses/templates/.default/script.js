;(function ()
{
	var namespace = BX.namespace('BX.Crm.Tracking');
	if (namespace.Expenses)
	{
		return;
	}

	/**
	 * Manager.
	 *
	 */
	function Manager()
	{
	}
	Manager.prototype.init = function (params)
	{
		this.gridId = params.gridId;
		this.componentName = params.componentName;
		this.signedParameters = params.signedParameters;
		this.sourceId = params.sourceId;
		this.sourceName = params.sourceName;
		this.mess = params.mess;

		BX.bind(BX('crm-tracking-expenses-add'), 'click', this.showAddPopup.bind(this));

		var popup = BX('crm-tracking-expenses-popup');
		this.uiNodes = {
			popup: popup,
			actionsCheckbox: this.getNode('actions/checkbox', popup),
			actionsCheckboxData: this.getNode('actions/checkbox/data', popup),
			actionsCont: this.getNode('actions/cont', popup),
			actionsData: this.getNode('actions/data', popup),
			commentData: this.getNode('comment/data', popup),
			currency: this.getNode('currency', popup),
			currencyData: this.getNode('currency/data', popup),
			sum: this.getNode('sum', popup),
			sumData: this.getNode('sum/data', popup),
			fromData: this.getNode('from/data', popup),
			fromView: this.getNode('from/view', popup),
			from: this.getNode('from', popup),
			toData: this.getNode('to/data', popup),
			toView: this.getNode('to/view', popup),
			to: this.getNode('to', popup)
		};
	};
	Manager.prototype.getNode = function(code, context)
	{
		var node = (context || document.body).querySelector('[data-role="crm/tracking/' + code + '"]');
		return node ? node : null;
	};
	Manager.prototype.onAdd = function ()
	{
		if (!this.checkFieldsBeforeAdd())
		{
			return;
		}

		this.request(
			'addExpenses',
			{
				sourceId: this.sourceId,
				from: this.uiNodes.fromData.value,
				to: this.uiNodes.toData.value,
				sum: this.uiNodes.sumData.value,
				currencyId: this.uiNodes.currencyData.value,
				actions: this.uiNodes.actionsData.value,
				comment: this.uiNodes.commentData.value
			},
			function () {
				BX.Main.gridManager.getInstanceById(this.gridId).reload();
				this.popup.close();
			}.bind(this)
		);
	};
	Manager.prototype.showAddPopup = function ()
	{
		if (!this.popup)
		{
			this.popup = new BX.PopupWindow({
				titleBar: this.mess.addTitle.replace('%name%', this.sourceName),
				closeIcon: true,
				overlay: true,
				width: 500,
				content: this.uiNodes.popup,
				buttons: [
					new BX.UI.AddButton({
						events : {
							click: this.onAdd.bind(this)
						}
					}),
					new BX.UI.CancelButton({
						events : {
							click: function() {
								this.getContext().close();
							}
						}
					})
				]
			});

			BX.bind(this.uiNodes.actionsCheckboxData, 'change', function () {
				this.uiNodes.actionsCont.setAttribute(
					'style',
					this.uiNodes.actionsCheckboxData.checked
						? 'display: none !important'
						: ''
				);
				this.uiNodes.actionsData.value = 0;
			}.bind(this));

			BX.bind(this.uiNodes.fromView, 'click', function () {
				BX.calendar({
					node: this.uiNodes.fromView,
					field: this.uiNodes.fromData,
					bTime: false,
					callback_after: this.setPopupDate.bind(this, true)
				});
			}.bind(this));

			BX.bind(this.uiNodes.toView, 'click', function () {
				BX.calendar({
					node: this.uiNodes.toView,
					field: this.uiNodes.toData,
					bTime: false,
					callback_after: this.setPopupDate.bind(this, false)
				});
			}.bind(this));
		}

		this.uiNodes.sumData.value = 0;
		this.uiNodes.actionsData.value = 0;
		this.uiNodes.actionsCheckboxData.checked = true;
		BX.fireEvent(this.uiNodes.actionsCheckboxData, 'change');

		this.setPopupDate(true);
		this.setPopupDate(false);

		this.popup.show();
	};
	Manager.prototype.setPopupDate = function (isFrom, date)
	{
		isFrom = !!(isFrom || false);
		date = date || new Date();

		var dataNode = isFrom ? this.uiNodes.fromData : this.uiNodes.toData;
		var viewNode = isFrom ? this.uiNodes.fromView : this.uiNodes.toView;

		dataNode.setAttribute('data-timestamp', date.getTime());
		dataNode.value = BX.formatDate(date, BX.message('FORMAT_DATE'));
		viewNode.textContent = dataNode.value;

		var anotherDataNode = !isFrom ? this.uiNodes.fromData : this.uiNodes.toData;
		var anotherTime = anotherDataNode.getAttribute('data-timestamp');
		if (anotherTime)
		{
			var isDiffMoreThanZero = (date.getTime() - anotherTime) > 0;
			if (isDiffMoreThanZero === isFrom)
			{
				this.setPopupDate(!isFrom, date);
			}
		}
	};
	Manager.prototype.checkFieldsBeforeAdd = function ()
	{
		var errorClass = 'ui-ctl-danger';
		BX.removeClass(this.uiNodes.to, errorClass);
		BX.removeClass(this.uiNodes.from, errorClass);
		BX.removeClass(this.uiNodes.sum, errorClass);
		BX.removeClass(this.uiNodes.currency, errorClass);

		var isError = false;
		if (!this.uiNodes.fromData.value)
		{
			BX.addClass(this.uiNodes.from, errorClass);
			isError = true;
		}
		if (!this.uiNodes.toData.value)
		{
			BX.addClass(this.uiNodes.to, errorClass);
			isError = true;
		}

		var timestampFrom = this.uiNodes.fromData.getAttribute('data-timestamp');
		var timestampTo = this.uiNodes.toData.getAttribute('data-timestamp');
		if (timestampFrom > timestampTo || ((timestampTo - timestampFrom) / (86400 * 1000)) > 365)
		{
			BX.addClass(this.uiNodes.from, errorClass);
			BX.addClass(this.uiNodes.to, errorClass);
			isError = true;
		}
		if (!this.uiNodes.sumData.value || !parseInt(this.uiNodes.sumData.value))
		{
			BX.addClass(this.uiNodes.sum, errorClass);
			isError = true;
		}
		if (!this.uiNodes.currencyData.value)
		{
			BX.addClass(this.uiNodes.currency, errorClass);
			isError = true;
		}

		return !isError;
	};
	Manager.prototype.remove = function (id)
	{
		this.request(
			'remove',
			{id: id},
			function () {
				BX.Main.gridManager.getInstanceById(this.gridId).reload();
				this.popup.close();
			}.bind(this)
		);
	};
	Manager.prototype.removeSelected = function ()
	{
		var grid = BX.Main.gridManager.getById(this.gridId);
		if (!grid || !grid.instance)
		{
			return;
		}

		this.request(
			'removeList',
			{list: grid.instance.getRows().getSelectedIds()},
			function () {
				BX.Main.gridManager.getInstanceById(this.gridId).reload();
				this.popup.close();
			}.bind(this)
		);
	};
	Manager.prototype.request = function (action, data, callbackSuccess, callbackFailure)
	{
		data = data || {};

		callbackSuccess = callbackSuccess || null;
		callbackFailure = callbackFailure || BX.proxy(this.showErrorPopup, this);

		var self = this;
		BX.ajax.runComponentAction(this.componentName, action, {
			'mode': 'class',
			'signedParameters': this.signedParameters,
			'data': data
		}).then(
			function (response)
			{
				var data = response.data || {};
				if(data.error)
				{
					callbackFailure.apply(self, [data]);
				}
				else if(callbackSuccess)
				{
					callbackSuccess.apply(self, [data]);
				}
			},
			function()
			{
				var data = {'error': true, 'text': ''};
				callbackFailure.apply(self, [data]);
			}
		);
	};
	Manager.prototype.showErrorPopup = function (data)
	{
		data = data || {};
		var text = data.text || this.mess.errorAction;
		var popup = BX.PopupWindowManager.create({
			autoHide: true,
			lightShadow: true,
			closeByEsc: true,
			overlay: {backgroundColor: 'black', opacity: 500}
		});
		popup.setButtons([
			new BX.UI.CloseButton({
				events : {
					click: function() {
						this.getContext().close();
					}
				}
			})
		]);
		popup.setContent('<span class="crm-tracking-expenses-popup-alert">' + text + '</span>');
		popup.show();
	};

	namespace.Expenses = new Manager();

})();
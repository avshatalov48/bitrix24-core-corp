if (typeof (BX.CrmScenarioSelection) === 'undefined')
{
	const ORDER_MODE_HELP_ARTICLE_ID = 13632830;
	BX.namespace('BX.CrmScenarioSelection');
	BX.CrmScenarioSelection = {
		selectedScenario: '',
		initialScenario: '',
		cardNodes: null,
		buttonNodes: null,
		convertActiveCheckbox: null,
		ordersModeInfo: null,
		dealListUrl: null,
		isOrdersInfoAlwaysHidden: false,
		popupConfirm: null,

		init: function(params)
		{
			this.selectedScenario = params.selectedScenario || '';
			this.initialScenario = this.selectedScenario;
			this.cardNodes = params.cardNodes || null;
			this.buttonNodes = params.buttonNodes || null;
			this.convertActiveCheckbox = params.convertActiveCheckbox || null;
			this.ordersModeInfo = params.ordersModeInfo || null;
			this.dealListUrl = params.dealListUrl || null;
			this.isOrdersInfoAlwaysHidden = params.isOrdersInfoAlwaysHidden || false;

			if (this.cardNodes)
			{
				var cardNodes = BX.convert.nodeListToArray(this.cardNodes);
				cardNodes.forEach(function(cardNode) {
					var handlerFunction = this.getSelectScenarioButtonClickHandler();
					cardNode.addEventListener('click', handlerFunction);
				}, this);
			}

			if (this.convertActiveCheckbox)
			{
				this.convertActiveCheckbox.addEventListener('change', function () {
					BX.UI.ButtonPanel.show();
				})
			}

			this.selectScenario(this.selectedScenario);
		},

		selectScenario: function(scenario)
		{
			this.selectedScenario = scenario;
			var cardNodes = BX.convert.nodeListToArray(this.cardNodes);
			var selectedCard = null;
			cardNodes.forEach(function (cardNode)  {
				cardNode.classList.remove('crm-scenario-selection--active');
				var cardScenario = cardNode.dataset.scenario;
				if (this.selectedScenario === cardScenario)
				{
					selectedCard = cardNode;
					selectedCard.classList.add('crm-scenario-selection--active');
				}
			}, this);

			var buttonNodes = BX.convert.nodeListToArray(this.buttonNodes);
			buttonNodes.forEach(function (buttonNode) {
				if (selectedCard.contains(buttonNode))
				{
					buttonNode.textContent = BX.message('CRM_SCENARIO_SELECTION_SELECTED');
				}
				else
				{
					buttonNode.textContent = BX.message('CRM_SCENARIO_SELECTION_SELECT');
				}
			});

			if (this.selectedScenario === 'deal' && this.initialScenario !== 'deal' && !this.isOrdersInfoAlwaysHidden)
			{
				this.ordersModeInfo.style.visibility = 'visible';
			}
			else
			{
				this.ordersModeInfo.style.visibility = 'hidden';
			}
		},

		getSelectScenarioButtonClickHandler: function()
		{
			var selectScenarioReference = this;
			return function(event) {
				var target = event.target;
				if (target.classList.contains('crm-scenario-selection-btn') && this.dataset.scenario !== selectScenarioReference.selectedScenario)
				{
					if (selectScenarioReference.initialScenario === 'order_deal' && this.dataset.scenario === 'deal')
					{
						selectScenarioReference.warnOnDealModeSwitch(
							BX.delegate(function()
							{
								selectScenarioReference.selectScenario(this.dataset.scenario);
								if (this.dataset.scenario === selectScenarioReference.initialScenario)
								{
									BX.UI.ButtonPanel.hide();
								}
								else
								{
									BX.UI.ButtonPanel.show();
								}
							}, this),
							BX.delegate(function()
							{
							}, this)
						);
					}
				}
			}
		},

		saveSelectedScenario: function(event)
		{
			var isConvertActiveDealsEnabled = this.convertActiveCheckbox && this.convertActiveCheckbox.checked ? 'Y' : 'N';
			var params = {
				selectedScenario: this.selectedScenario,
				isConvertActiveDealsEnabled: isConvertActiveDealsEnabled
			}
			var _this = this;
			BX.ajax.runComponentAction('bitrix:crm.scenario_selection', 'saveSelectedScenario', {
				mode: 'class',
				data: {
					params: params
				}
			}).then(function (result) {
				BX.removeClass(event.target, 'ui-btn-wait');
				if (result.data.success)
				{
					_this.closeSlider();
				}
				else
				{
					if (result.data.error)
					{
						BX.UI.Notification.Center.notify({
							content: result.data.error
						});
					}
					else
					{
						BX.UI.Notification.Center.notify({
							content: BX.message('CRM_SCENARIO_SELECTION_SAVE_ERROR')
						});
					}
				}
			});
		},

		warnOnDealModeSwitch: function(onConfirm, onClose)
		{
			var buttons = [
				new BX.PopupWindowButton({
					text: BX.message('CRM_SCENARIO_SELECTION_TO_DEAL_MODE_OK_BUTTON'),
					className: 'ui-btn ui-btn-md ui-btn-primary',
					events: {
						click: function()
						{
							onClose();
							this.popupWindow.destroy();
						}
					}
				}),
				new BX.PopupWindowButton({
					text: BX.message('CRM_SCENARIO_SELECTION_TO_DEAL_MODE_CANCEL_BUTTON'),
					className: 'ui-btn ui-btn-md',
					events: {
						click : function()
						{
							onConfirm();
							this.popupWindow.destroy();
						}}
				})
			];

			if (this.popupConfirm != null)
			{
				this.popupConfirm.destroy();
			}

			var contentNode = document.createElement('div');
			contentNode.innerHTML = '<div>'
				+ BX.message('CRM_SCENARIO_SELECTION_TO_DEAL_MODE_TEXT_1')
				+ '</div><br/><br/><div>'
				+ BX.message('CRM_SCENARIO_SELECTION_TO_DEAL_MODE_TEXT_2')
				+ ' '
				+ '<a onclick="top.BX.Helper.show(\'redirect=detail&code=' + ORDER_MODE_HELP_ARTICLE_ID + '\'); return false;" href="javascript: void();" target="_blank">' + BX.message('CRM_SCENARIO_SELECTION_TO_DEAL_MODE_TEXT_3_PART_1') + '</a>'
				+ ' '
				+ BX.message('CRM_SCENARIO_SELECTION_TO_DEAL_MODE_TEXT_3_PART_2')
				+ '</div>';

			this.popupConfirm = new BX.PopupWindow(
				'bx-popup-documentgenerator-popup',
				null,
				{
					autoHide: true,
					closeByEsc: true,
					buttons: buttons,
					closeIcon: true,
					overlay: true,
					events: {
						onPopupClose: function()
						{
							if (BX.type.isFunction(onClose))
							{
								onClose();
							}
							this.destroy();
						},
						onPopupDestroy: BX.delegate(function()
						{
							this.popupConfirm = null;
						}, this)
					},
					content: BX.create('span', {
						attrs: {
							className: 'bx-popup-crm-scenario-selection-popup-content-text'
						},
						children: [contentNode],
					}),
					titleBar: BX.message('CRM_SCENARIO_SELECTION_TO_DEAL_MODE_TITLE'),
					contentColor: 'white',
					className: 'bx-popup-crm-scenario-selection-popup',
					maxWidth: 800
				}
			);
			this.popupConfirm.show();
		},

		closeSlider: function()
		{
			if(BX.SidePanel.Instance)
			{
				BX.SidePanel.Instance.getTopSlider().close();
				var newUrl = (this.dealListUrl && this.selectedScenario === 'deal')
					? this.dealListUrl
					: this.getParentWindow().location.href;
				newUrl += (newUrl.indexOf('?') !== -1 ? "&" : "?") + '&ncc=1';
				this.getParentWindow().location.href = newUrl;
			}
		},

		getParentWindow: function()
		{
			return BX.SidePanel.Instance.getTopSlider().getWindow().parent;
		}
	};
}
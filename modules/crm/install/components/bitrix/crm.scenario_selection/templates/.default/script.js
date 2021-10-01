if (typeof (BX.CrmScenarioSelection) === 'undefined')
{
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
					selectScenarioReference.selectScenario(this.dataset.scenario);
					if (this.dataset.scenario === selectScenarioReference.initialScenario)
					{
						BX.UI.ButtonPanel.hide();
					}
					else
					{
						BX.UI.ButtonPanel.show();
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
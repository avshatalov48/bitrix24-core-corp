;(function ()
{
	'use strict';
	var namespace = BX.namespace('BX.Crm.Analytics.Report.ViewChart');
	var Instance = null;


	/**
	 * Popup.
	 * @constructor
	 */
	function Popup(options)
	{
		this.content = options.content;
		this.isInit = false;
		this.popup = null;
		Instance = this;
	}
	Popup.instance = function (options)
	{
		if (!Instance)
		{
			return new namespace.Popup(options);
		}

		return Instance;
	};
	Popup.clear = function ()
	{
		if (Instance)
		{
			Instance.hide();
		}

		Instance = null;
	};
	Popup.prototype.init = function ()
	{
		if (this.isInit)
		{
			return;
		}
		this.isInit = true;

		var parameters = {
			width: 200,
			content: this.content,
			noAllPaddings: true,
			//angle: true,
			animationOptions: {
				show: {
					type: "opacity"
				},
				close: {
					type: "opacity"
				}
			},
			bindOptions: {
				position: "top"
			},
			offsetLeft: 30,
			offsetTop: -10
		};
		this.popup = new BX.PopupWindow(parameters);
	};
	Popup.prototype.show = function (item)
	{
		this.init();
		this.popup.setBindElement(item.node);
		/*
		this.popup.setAngle({
			offset: this.content.offsetWidth / 2
		});
		*/

		//debugger;
		var previousItem = item.layer.getPreviousItem(item);
		this.content.style.borderColor = item.source.data.color;
		namespace.Helper
			.getNode('popup-icon', this.content)
			.className = 'crm-report-chart-modal-title-icon ' + item.source.data.iconClass;
		namespace.Helper
			.getNode('popup-icon-color', this.content)
			.style.backgroundColor = item.source.data.color;
		namespace.Helper
			.getNode('popup-caption', this.content)
			.textContent = item.source.data.caption;
		namespace.Helper
			.getNode('popup-quantity', this.content)
			.textContent = item.data.quantityPrint;
		namespace.Helper
			.getNode('popup-quantity-prev', this.content)
			.textContent = previousItem ? previousItem.data.quantityPrint : '';
		namespace.Helper
			.getNode('popup-quantity-arrow', this.content)
			.style.display = previousItem ? '' : 'none';
		namespace.Helper
			.getNode('popup-cost', this.content)
			.innerHTML = item.data.cost ? item.data.costPrint : '-';
		namespace.Helper
			.getNode('popup-cost-changing', this.content)
			.style.display = (item.data.cost && previousItem) ? '' : 'none';


		var costChangingNode = namespace.Helper.getNode('popup-cost-changing-value', this.content);
		costChangingNode.textContent = (item.data.costChanging > 0 ? '+' : '') + item.data.costChanging;
		costChangingNode.style.color = item.data.costChanging > 0 ? '' : 'green';

		var quantityCaptionNode = namespace.Helper.getNode('popup-quantity-caption', this.content);
		quantityCaptionNode.textContent = quantityCaptionNode.getAttribute('data-text' + (previousItem ? '' : '-single'));

		if (this.popup.isShown && this.popup.isShown())
		{
			this.popup.close();
		}

		this.popup.show();
	};
	Popup.prototype.hide = function ()
	{
		if (this.popup)
		{
			this.popup.close();
		}
	};

	namespace.Popup = Popup;
})();
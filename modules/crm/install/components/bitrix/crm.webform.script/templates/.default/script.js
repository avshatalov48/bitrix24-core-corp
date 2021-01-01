function CrmWebFormEditScript(params)
{
	params = params || {};
	this.context = params.context;
	this.init();
}
CrmWebFormEditScript.prototype =
{
	init: function ()
	{
		this.initTabs();
		this.initCopy();
		BX.onCustomEvent(window, 'crm-web-form-edit-script', [this]);
	},

	initTabs: function ()
	{
		this.tabAttribute = 'data-bx-webform-script-tab-cont';
		this.buttonTabAttribute = 'data-bx-webform-script-tab-btn';
		this.buttonAttribute = 'data-bx-webform-script-copy-btn';
		this.copyTextAttribute = 'data-bx-webform-script-copy-text';
		this.selectorAttribute = 'data-bx-webform-script-selector';
		this.kindAttribute = 'data-bx-webform-script-kind';

		this.selectorButton = this.context.querySelector('[' + this.selectorAttribute + ']');
		if (this.selectorButton)
		{
			BX.bind(this.selectorButton, 'change', this.changeScriptKind.bind(this));
		}

		this.tabNodeList = this.context.querySelectorAll('[' + this.tabAttribute + ']');
		this.tabNodeList = BX.convert.nodeListToArray(this.tabNodeList);
		this.currentTabNode = this.tabNodeList[0];

		this.tabBtnNodeList = this.context.querySelectorAll('[' + this.buttonTabAttribute + ']');
		this.tabBtnNodeList = BX.convert.nodeListToArray(this.tabBtnNodeList);
		this.tabBtnNodeList.forEach(this.bindTabButtonClick, this);

		this.copyButtonList = this.context.querySelectorAll('[' + this.buttonAttribute + ']');
		this.copyButtonList = BX.convert.nodeListToArray(this.copyButtonList);
		this.copyButtonScriptList = [];
	},

	changeScriptKind: function ()
	{
		var isOld = !this.selectorButton || this.selectorButton.checked;
		var list = this.context.querySelectorAll('[' + this.kindAttribute + ']');
		list = BX.convert.nodeListToArray(list);
		list.forEach(function (node) {
			var isNodeOld = node.getAttribute(this.kindAttribute) === 'old';
			var show = (isNodeOld && isOld) || (!isNodeOld && !isOld);
			node.style.display = show ? '' : 'none';
		}, this);
	},

	bindTabButtonClick: function (tabBtnNode)
	{
		var _this = this;
		BX.bind(tabBtnNode, 'click', function(){
			var id = this.getAttribute(_this.buttonTabAttribute);
			_this.showTab(id);
		});
	},

	showTab: function (id)
	{
		this.tabBtnNodeList.forEach(function(tabBtnNode){
			var iteratedId = tabBtnNode.getAttribute(this.buttonTabAttribute);
			if(id == iteratedId)
			{
				BX.addClass(tabBtnNode, 'crm-webform-script-tab-item-active');
			}
			else
			{
				BX.removeClass(tabBtnNode, 'crm-webform-script-tab-item-active');
			}
		}, this);

		this.tabNodeList.forEach(function(tabNode){
			var iteratedId = tabNode.getAttribute(this.tabAttribute);
			tabNode.style.display = (id == iteratedId ? 'block' : 'none');
			if (id == iteratedId)
			{
				this.currentTabNode = tabNode;
			}
		}, this);

		this.copyButtonScriptList.forEach(function(buttonNode){
			var iteratedId = buttonNode.getAttribute(this.buttonAttribute);
			var btnId = 'SCRIPT_' + id;
			buttonNode.style.display = (btnId == iteratedId ? '' : 'none');
		}, this);
	},

	getCurrentCopyText: function ()
	{
		var selector = '[' + this.kindAttribute + ']';
		var parentNode = this.currentTabNode.querySelector(selector);
		var copyTextNode = parentNode.querySelector('[' + this.copyTextAttribute + ']');
		if (copyTextNode)
		{
			return copyTextNode.innerText;
		}
		else
		{
			return '';
		}
	},

	hideCopyTextButtons: function (params)
	{
		this.copyButtonScriptList[0].parentNode.style.display = 'none';
	},

	initCopy: function ()
	{
		this.copyButtonList.forEach(function(buttonNode){
			if (!BX.clipboard.isCopySupported())
			{
				buttonNode.style.display = 'none';
				return;
			}

			var id = buttonNode.getAttribute(this.buttonAttribute);
			if (id.substr(0, 6) === 'SCRIPT')
			{
				this.copyButtonScriptList.push(buttonNode);
			}

			BX.bind(buttonNode, 'click', function () {
				var selector = '[' + this.kindAttribute + ']';
				var copyButtonText = this.context.querySelector(selector + ' [' + this.copyTextAttribute + '="' + id + '"]');
				if(!copyButtonText)
				{
					return;
				}
				BX.clipboard._onCopyClick(
					BX.util.getRandomString(5),
					buttonNode,
					copyButtonText,
					{offsetLeft: 30}
				);
			}.bind(this));
		}, this);
	}
};
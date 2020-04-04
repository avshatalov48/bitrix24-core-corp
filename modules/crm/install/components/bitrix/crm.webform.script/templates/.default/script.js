function CrmWebFormEditScript(params)
{
	params = params || {};
	this.context = params.context;
	this.init();
}
CrmWebFormEditScript.prototype =
{
	init: function (params)
	{
		this.initTabs(params);
		this.initCopy(params);
		BX.onCustomEvent(window, 'crm-web-form-edit-script', [this]);
	},

	initTabs: function (params)
	{
		this.tabAttribute = 'data-bx-webform-script-tab-cont';
		this.buttonTabAttribute = 'data-bx-webform-script-tab-btn';
		this.buttonAttribute = 'data-bx-webform-script-copy-btn';
		this.copyTextAttribute = 'data-bx-webform-script-copy-text';

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
		var copyTextNode = this.currentTabNode.querySelector('[' + this.copyTextAttribute + ']');
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

	initCopy: function (params)
	{
		this.copyButtonList.forEach(function(buttonNode){
			var id = buttonNode.getAttribute(this.buttonAttribute);

			if (id.substr(0, 6) == 'SCRIPT')
			{
				this.copyButtonScriptList.push(buttonNode);
			}

			var copyButtonText = this.context.querySelector('[' + this.copyTextAttribute + '="' + id + '"]');
			if(!copyButtonText)
			{
				return;
			}

			BX.clipboard.bindCopyClick(buttonNode, {text: copyButtonText, offsetLeft: 30});
		}, this);
	}
};
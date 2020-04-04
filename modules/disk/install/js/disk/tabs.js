BX.namespace("BX.Disk");
BX.Disk.TabsClass = (function ()
{
	var TabsClass = function (parameters)
	{
		parameters = parameters || {};
		this.headerContainer = parameters.headerContainer || null;
		this.contentContainer = parameters.contentContainer || null;

		this.currentActiveTab = null;

		this.setEvents();
		this.workWithLocationHash();
	};

	TabsClass.prototype.setEvents = function ()
	{
		BX.bind(window, 'hashchange', BX.proxy(this.onHashChange, this));
		BX.bindDelegate(this.headerContainer, "click", {className: 'disk-tab'}, BX.proxy(this.onClickTab, this));
	};

	TabsClass.prototype.workWithLocationHash = function()
	{
		setTimeout(BX.delegate(function(){
			this.onHashChange();
		}, this), 150);
	};

	TabsClass.prototype.onHashChange = function()
	{
		var matches = document.location.hash.match(/tab-(\w+)/);
		if(!matches)
			return;

		var possibleCurrent = this.findTabByAttribute(matches[1]);
		if(!possibleCurrent)
			return;

		this.setActiveTab(possibleCurrent);
	};

	TabsClass.prototype.setCurrentActiveTab = function(node)
	{
		this.currentActiveTab = node;
	};

	TabsClass.prototype.findActiveTab = function()
	{
		if(!!this.currentActiveTab)
		{
			return this.currentActiveTab;
		}
		var tabs = BX.findChildren(this.headerContainer, {
			className: 'disk-tab'
		}, true);

		for (var i in tabs) {
			if (!tabs.hasOwnProperty(i)) {
				continue;
			}

			if(BX.hasClass(tabs[i], 'disk-tab-active'))
			{
				this.setCurrentActiveTab(tabs[i]);

				return this.currentActiveTab;
			}
		}

		return null;
	};

	TabsClass.prototype.findTabContentByAttribute = function(attribute)
	{
		return BX.findChild(this.contentContainer, {
			className: 'disk-tab-content',
			attribute: {
				'bx-disk-tab': attribute
			}
		}, true);
	};

	TabsClass.prototype.findTabByAttribute = function(attribute)
	{
		return BX.findChild(this.headerContainer, {
			className: 'disk-tab',
			attribute: {
				'bx-disk-tab': attribute
			}
		}, true);
	};

	TabsClass.prototype.setInActiveTab = function(node)
	{
		if(!node)
		{
			return;
		}

		BX.removeClass(node, 'disk-tab-active');
		var tabContent = this.findTabContentByAttribute(node.getAttribute('bx-disk-tab'));
		BX.removeClass(tabContent, 'active');
	};

	TabsClass.prototype.setActiveTab = function(node)
	{
		if(BX.hasClass(node, 'disk-tab-active'))
		{
			return;
		}

		if(!node)
		{
			return;
		}

		this.setInActiveTab(this.findActiveTab());
		this.setCurrentActiveTab(node);

		BX.addClass(node, 'disk-tab-active');
		var tabContent = this.findTabContentByAttribute(node.getAttribute('bx-disk-tab'));
		BX.addClass(tabContent, 'active');

		BX.onCustomEvent(this, 'onChangeTab', [node.getAttribute('bx-disk-tab')]);
	};

	TabsClass.prototype.onClickTab = function(e)
	{
		var target = e.target || e.srcElement;
		this.setActiveTab(target);
	};

	return TabsClass;
})();

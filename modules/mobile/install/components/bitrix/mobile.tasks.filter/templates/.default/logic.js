BX.namespace('BX.Mobile.Tasks');

BX.Mobile.Tasks.filter = function(opts, nf){

	this.parentConstruct(BX.Mobile.Tasks.filter, opts);

	BX.merge(this, {
		sys: {
			classCode: 'filter',
		},
		vars: {
			prevFilterId: false
		},
		opts: {
			chosenFilter: false
		}
	});

	this.handleInitStack(nf, BX.Mobile.Tasks.filter, opts);
}
BX.extend(BX.Mobile.Tasks.filter, BX.Mobile.Tasks.page);

// the following functions can be overrided with inheritance
BX.merge(BX.Mobile.Tasks.filter.prototype, {

	// member of stack of initializers, must be defined even if do nothing
	init: function()
	{
		var ctx = this;

		if(typeof BXMobileApp != 'undefined' && typeof app != 'undefined')
		{
			BX.bindDelegate(this.scope(), 'click', {attribute: {'data-bx-id': this.classCode()+'-variant'}}, function(){
				ctx.selectFilter(BX.data(this, 'filter'));
			});

			this.addFastClick(this.scope());
		}

		var container = this.controlR('filters');

		if(typeof this.opts.customFilters != 'undefined' && BX.type.isArray(this.opts.customFilters) && this.opts.customFilters.length > 0)
		{
			for(var k = 0; k < this.opts.customFilters.length; k++)
			{
				var node = this.createNodesByTemplate('custom-preset', this.opts.customFilters[k], true);
				if(BX.type.isElementNode(node[0]))
					BX.append(node[0], container);
			}
		}
	},

	selectFilter: function(filterId)
	{
		// after refactoring of the task list component
		// here will be loadPageBlank() that leads to the task list bulk page and inits data reload on it

		var filter = {};
		if(filterId == parseInt(filterId))
			filter['F_FILTER_SWITCH_PRESET'] = parseInt(filterId);
		else
			filter['F_STATE'] = [filterId];

		this.highlightFilter(filterId);

		BXMobileApp.onCustomEvent(
			'onTasksFilterChange',
			{
				module_id:	'tasks',
				emitter:	'tasks filter',
				filter:		filter,
				user_id:	this.option('userId')
			}
		,true);

		app.closeController();
	},

	highlightFilter: function(filterId)
	{
		var items = this.control('variant', false, {all:true});

		for(var i = 0; i < items.length; i++)
		{
			var fId = BX.data(items[i], 'filter');

			if(this.vars.prevFilterId !== false && fId == this.vars.prevFilterId)
			{
				BX.removeClass(items[i], 'selected');
			}

			if(fId == filterId)
			{
				BX.addClass(items[i], 'selected');
			}
		}

		this.vars.prevFilterId = filterId;
	},

	pageOpenHandler: function(data)
	{
		try
		{
			if(BX.type.isNotEmptyString(data.currentFilter))
				this.highlightFilter(data.currentFilter);
			else if(this.opts.chosenFilter !== false)
				this.highlightFilter(this.opts.chosenFilter);
		}
		catch(e)
		{
		}
	}
});
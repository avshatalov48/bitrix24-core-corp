BX.namespace('BX.Mobile.Tasks.View.Bitrix');

BX.Mobile.Tasks.roles = function(opts, nf){

	this.parentConstruct(BX.Mobile.Tasks.roles, opts);

	BX.merge(this, {
		sys: {
			classCode: 'roles'
		},
		vars: {
			userId: false
		},
		opts: {
		}
	});

	BX.merge(opts, { siteId : BX.message('SITE_ID'), usePull : true } );
	this.handleInitStack(nf, BX.Mobile.Tasks.roles, opts);
};
BX.extend(BX.Mobile.Tasks.roles, BX.Mobile.Tasks.page);

// the following functions can be overrided with inheritance
BX.merge(BX.Mobile.Tasks.roles.prototype, {

	// member of stack of initializers, must be defined even if do nothing
	init: function()
	{
	},

	////////// CLASS-SPECIFIC: free to modify in a child class

	dynamicActionsCustom: function(data)
	{
		var instRoles = this.instance('taskgroups');

		if(typeof instRoles != 'undefined')
		{
			instRoles.setCounters(data.counters);
			instRoles.setUserId(data.userId);

			// ready device here?
			this.resetMenu(this.getMenu());
		}
	},

	pullHandler: function(data)
	{
		try
		{
			if(data.command == 'user_counter' && data.module_id == 'main' && BX.type.isPlainObject(data.params[this.option('siteId')]))
			{
				var counters = data.params[this.option('siteId')];

				if(BX.type.isPlainObject(this.option('counterToRole')))
				{
					var c = {};
					var i = 0;
					for(var k in this.option('counterToRole'))
					{
						if(typeof counters[k] != 'undefined')
						{
							c[this.option('counterToRole')[k]] = parseInt(counters[k]);
							i++;
						}
					}

					if(i > 0)
						this.instance('taskgroups').setCounters(c, true);
				}
			}
		}
		catch(e)
		{
			BX.debug('Counters has not been updated');
			BX.debug(e);
		}
	},

	getMenu: function()
	{
		return [
			{
				name: BX.message('MB_TASKS_ROLES_TASK_ADD'),
				arrowFlag: false,
				icon: 'add',
				action: BX.delegate(function() {
					BX.Mobile.Tasks.createWindow(this.getUser());
				}, this)
			}
		];
	}
});

BX.Mobile.Tasks.View.Bitrix.taskgroups = function(opts, nf){

	this.parentConstruct(BX.Mobile.Tasks.View.Bitrix.taskgroups, opts);

	BX.merge(this, {
		sys: {
			classCode: 'taskgroups'
		},
		vars: {
			userId: 0
		}
	});

	this.handleInitStack(nf, BX.Mobile.Tasks.View.Bitrix.taskgroups, opts);
}
BX.extend(BX.Mobile.Tasks.View.Bitrix.taskgroups, BX.Mobile.Tasks.widget);

// the following functions can be overrided with inheritance
BX.merge(BX.Mobile.Tasks.View.Bitrix.taskgroups.prototype, {

	// member of stack of initializers, must be defined even if do nothing
	init: function()
	{
		if(typeof window.BXMobileApp != 'undefined')
		{
			BX.bindDelegate(this.getScope(), 'click', {attribute: {'data-bx-id': this.classCode()+'-group-url'}}, this.passCtx(this.follow, this));
			BX.bindDelegate(this.getScope(), 'click', {attribute: {'data-bx-id': this.classCode()+'-subgroup'}}, this.passCtx(this.follow, this));
			BX.bindDelegate(this.getScope(), 'touchstart', {attribute: {'data-bx-id': this.classCode()+'-group-url'}}, this.passCtx(this.touch, this));
			BX.bindDelegate(this.getScope(), 'touchstart', {attribute: {'data-bx-id': this.classCode()+'-subgroup'}}, this.passCtx(this.touch, this));
		}
	},
	touch : function() {
		BX.addClass(this, "hover");
		var _this = this;
		setTimeout(function(){ BX.removeClass(_this, "hover"); }, 1000);
	},
	follow: function(ctx, e)
	{
		BX.eventCancelBubble(e);
		var url = BX.data(this, 'url');
		if(ctx.vars.userId > 0) // we are not allowed to follow untill userId is set
		{
			url = url.replace('%USER_ID%', ctx.vars.userId).replace('%user_id%', ctx.vars.userId);

			if(BX.type.isNotEmptyString(url))
			{
				window.BXMobileApp.PageManager.loadPageUnique({url: url, bx24ModernStyle: true});
			}
		}
		return BX.PreventDefault(e);
	},

	setUserId: function(id)
	{
		this.vars.userId = parseInt(id);
	},

	setCounters: function(counters, keepAbsent)
	{
		if(BX.type.isPlainObject(counters))
		{
			var groupControls = this.controlR('group', {all:true}),
				groupId,
				counter,
				cs,
				counterId,
				k, i;
			for (k = 0; k < groupControls.length; k++)
			{
				groupId = BX.data(groupControls[k], 'group-id');

				if (BX.type.isElementNode(groupControls[k]))
				{
					// Update counters
					cs = this.control('counter', false, {all : true, scope: groupControls[k], noCache: true});
					for (i = 0; i < cs.length; i++)
					{
						counter = cs[i];
						if (BX.type.isElementNode(counter))
						{
							counterId = BX.data(counter, 'counter-id');
							if (BX.type.isNotEmptyString(groupId) && typeof counters[groupId] != 'undefined' && counters[groupId] &&
								counters[groupId][counterId] && parseInt(counters[groupId][counterId]['VALUE']) > 0)
							{
								counter.innerHTML = parseInt(counters[groupId][counterId]['VALUE']);
								if (counterId == 'TOTAL')
									BX.removeClass(counter, 'hidden');
								else
									BX.removeClass(counter.parentNode, 'hidden');
							}
							else if(!keepAbsent)
							{
								if (counterId == 'TOTAL')
									BX.addClass(counter, 'hidden');
								else
									BX.addClass(counter.parentNode, 'hidden');
							}
						}
					}
				}
			}
		}
	}
});
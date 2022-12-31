BX.namespace('BX.Mobile.Tasks');

BX.Mobile.Tasks.roles = function(opts, nf){

	this.parentConstruct(BX.Mobile.Tasks.roles, opts);

	BX.merge(this, {
		sys: {
			classCode: 'roles',
		},
		vars: {
			userId: false
		},
		opts: {
		}
	});

	this.handleInitStack(nf, BX.Mobile.Tasks.roles, opts);
}
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
			BX.debug('Counters update UNsuccessful');
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
				action: BX.delegate(function()
				{
					var uid = this.getUser();
					var url = this.option('path').taskCreate.replace('#USER_ID#', uid).replace('#user_id#', uid);

					app.showModalDialog({
						url: url
					});
				}, this)
			}
		];
	}
});
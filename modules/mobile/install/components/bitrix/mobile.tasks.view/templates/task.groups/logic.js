BX.namespace('BX.Mobile.Tasks.View.Bitrix');

BX.Mobile.Tasks.View.Bitrix.taskgroups = function(opts, nf){

	this.parentConstruct(BX.Mobile.Tasks.View.Bitrix.taskgroups, opts);

	BX.merge(this, {
		sys: {
			classCode: 'taskgroups',
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
		if(typeof BXMobileApp != 'undefined')
		{
			BX.bindDelegate(this.getScope(), 'click', {attribute: {'data-bx-id': this.classCode()+'-group'}}, this.passCtx(this.follow, this));
		}
	},

	follow: function(ctx)
	{
		var url = BX.data(this, 'url');

		if(ctx.vars.userId > 0) // we are not allowed to follow untill userId is set
		{
			url = url.replace('%USER_ID%', ctx.vars.userId).replace('%user_id%', ctx.vars.userId);
			url += '&t='+((new Date()).getTime()); // remove this when implement single-page feature for task list

			if(BX.type.isNotEmptyString(url))
			{
				BXMobileApp.PageManager.loadPageUnique({url: url, bx24ModernStyle: true});
			}
		}
	},

	setUserId: function(id)
	{
		this.vars.userId = parseInt(id);
	},

	setCounters: function(counters, keepAbsent)
	{
		if(BX.type.isPlainObject(counters))
		{
			var groupControls = this.controlR('group', {all:true});

			for(var k = 0; k < groupControls.length; k++)
			{
				var groupId = BX.data(groupControls[k], 'group-id');

				if(BX.type.isElementNode(groupControls[k]))
				{
					var counter = this.control('counter', false, {scope: groupControls[k], noCache: true});

					if(BX.type.isElementNode(counter))
					{
						if(BX.type.isNotEmptyString(groupId) && typeof counters[groupId] != 'undefined' && parseInt(counters[groupId]) > 0)
						{
							counter.innerHTML = parseInt(counters[groupId]);
							BX.removeClass(counter, 'hidden');
						}
						else if(!keepAbsent)
						{
							BX.addClass(counter, 'hidden');
						}
					}
				}
			}
		}
	}
});
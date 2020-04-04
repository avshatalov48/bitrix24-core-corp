'use strict';

BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.TasksSysLog != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TasksSysLog = BX.Tasks.Component.extend({
		sys: {
			code: 'syslog'
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Component);
			},

			bindEvents: function()
			{
			}
		}
	});

}).call(this);
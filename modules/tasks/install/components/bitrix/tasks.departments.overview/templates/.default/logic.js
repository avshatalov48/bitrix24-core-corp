'use strict';

BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.TasksDepartmentsOverview != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TasksDepartmentsOverview = BX.Tasks.Component.extend({
		sys: {
			code: 'departments-overview'
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Component);

				// create sub-instances through this.subInstance(), do some initialization, etc

				// do ajax call, like
				// this.callRemote('this.sampleCreateTask', {data: {TITLE: 'Sample Task'}}).then(function(result){ ... });
				// dont care about CSRF, SITE_ID and LANGUAGE_ID: it will be sent and checked automatically
			},

			bindEvents: function()
			{
				// do some permanent event bindings here, like i.e.
				/*
				this.bindControlPassCtx('some-div', 'click', this.showAddPopup);
				this.bindControlPassCtx('some-div', 'click', this.showActionPopup);
				this.bindControlPassCtx('some-div', 'click', this.showUnHideFieldPopup);
				this.bindDelegateControl('some-div', 'keypress', this.jamEnter, this.control('new-item-place'));
				*/
			}

			// add more methods, then call them like this.methodName()
		}
	});

	// may be some sub-controllers here...

}).call(this);
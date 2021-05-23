'use strict';

BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.TasksWidgetTimeEstimate != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TasksWidgetTimeEstimate = BX.Tasks.Component.extend({
		sys: {
			code: 'timeestimate'
		},
		methods: {

			bindEvents: function()
			{
				var times = this.controlAll('time');
				for(var k = 0; k < times.length; k++)
				{
					BX.Tasks.Util.bindInstantChange(times[k], this.onChange.bind(this));
				}

				this.bindDelegateControl('flag', 'click', this.passCtx(this.onToggleFlag));
			},

			onChange: function()
			{
				var hControl = 	this.control('hour');
				var mControl = 	this.control('minute');
				var sControl =	this.control('second');

				if(!BX.type.isElementNode(sControl))
				{
					return;
				}

				var hour = 0;
				if(BX.type.isElementNode(hControl))
				{
					var value = parseInt(hControl.value);

					if(!isNaN(value))
					{
						hour = value;
					}
				}

				var minute = 0;
				if(BX.type.isElementNode(mControl))
				{
					var value = parseInt(mControl.value);

					if(!isNaN(value))
					{
						minute = value;
					}
				}

				// seconds
				sControl.value = minute * 60 + hour * 3600;
			},

			onToggleFlag: function(node)
			{
				var target = BX.data(node, 'target');
				if(typeof target != 'undefined' && BX.type.isNotEmptyString(target))
				{
					var flagNode = this.control(target);

					var yesValue = BX.data(node, 'yes-value') || 'Y';
					var noValue = BX.data(node, 'no-value') || 'N';

					if(BX.type.isElementNode(flagNode))
					{
						flagNode.value = node.checked ? yesValue : noValue;
					}

					BX.Tasks.Util.fadeToggleByClass(this.control('inputs'), 200);
				}
			}
		}
	});

}).call(this);
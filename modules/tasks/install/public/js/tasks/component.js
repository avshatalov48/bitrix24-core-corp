BX.namespace('Tasks');

var prevNameSpace = null;
if(typeof BX.Tasks.Component != 'undefined' && BX.type.isPlainObject(BX.Tasks.Component))
{
	prevNameSpace = BX.Tasks.Component;
}

BX.Tasks.Component = BX.Tasks.Util.Widget.extend({
	sys: {
		code: 'bx-comp'
	},
	options: {
		url: '',
		registerDispatcher: true,
		controlBind: 'class',
		componentId: '',
		componentClassName: '',
		modulesAvailable: {}
	},
	methods: {
		construct: function()
		{
			this.callConstruct(BX.Tasks.Util.Widget);

			this.bindEvents();
			this.disableDisposableHints();
		},

		bindEvents: function()
		{
			// do some event binding
		},

		disableDisposableHints: function()
		{
			try
			{
				BX.Tasks.Util.hintManager.disableSeveral(this.option('hintState'));
			}
			catch(e)
			{
			}
		}
	}
});

if(prevNameSpace)
{
	for(var k in prevNameSpace)
	{
		if(prevNameSpace.hasOwnProperty(k))
		{
			BX.Tasks.Component[k] = prevNameSpace[k];
		}
	}
}

BX.namespace("BX.Crm");

//region EDITOR MODE
if(typeof BX.Crm.EntityEditorMode === "undefined")
{
	BX.Crm.EntityEditorMode =
		{
			intermediate: 0,
			edit: 1,
			view: 2,
			names: { view: "view",  edit: "edit" },
			getName: function(id)
			{
				if(id === this.edit)
				{
					return this.names.edit;
				}
				else if(id === this.view)
				{
					return this.names.view;
				}
				return "";
			},
			parse: function(str)
			{
				str = str.toLowerCase();
				if(str === this.names.edit)
				{
					return this.edit;
				}
				else if(str === this.names.view)
				{
					return this.view;
				}
				return this.intermediate;
			}
		};
}
//endregion

//region EDITOR MODE OPTIONS
if(typeof BX.Crm.EntityEditorModeOptions === "undefined")
{
	BX.Crm.EntityEditorModeOptions =
		{
			none: 0,
			exclusive:  0x1,
			individual: 0x2,
			saveOnExit: 0x40,
			check: function(options, option)
			{
				return((options & option) === option);
			}
		};
}
//endregion

//region EDITOR CONTROL OPTIONS
if(typeof BX.Crm.EntityEditorControlOptions === "undefined")
{
	BX.Crm.EntityEditorControlOptions =
		{
			none: 0,
			showAlways: 1,
			check: function(options, option)
			{
				return((options & option) === option);
			}
		};
}
//endregion

//region EDITOR PRIORITY
if(typeof BX.Crm.EntityEditorPriority === "undefined")
{
	BX.Crm.EntityEditorPriority =
		{
			undefined: 0,
			normal: 1,
			high: 2
		};
}
//endregion

//region EDITOR MODE SWITCH TYPE
if(typeof BX.Crm.EntityEditorModeSwitchType === "undefined")
{
	BX.Crm.EntityEditorModeSwitchType =
		{
			none:       0x0,
			common:     0x1,
			button:     0x2,
			content:    0x4,
			check: function(options, option)
			{
				return((options & option) === option);
			}
		};
}
//endregion

//region FILE STORAGE TYPE
if(typeof BX.Crm.EditorFileStorageType === "undefined")
{
	BX.Crm.EditorFileStorageType =
		{
			undefined: 0,
			file: 1,
			webdav: 2,
			diskfile: 3
		};
}
//endregion

import {Reflection, Type} from 'main.core';
import {Manager} from 'rpa.manager';

const namespace = Reflection.namespace('BX.Rpa');

class ItemEditorComponent
{
	editor = null;
	id;
	typeId;

	constructor(editorId, options)
	{
		if(Type.isString(editorId))
		{
			this.editorId = editorId;
			this.editor = BX.UI.EntityEditor.get(this.editorId);

			if(Type.isPlainObject(options))
			{
				if(options.id)
				{
					this.id = parseInt(options.id);
				}
				if(options.typeId)
				{
					this.typeId = parseInt(options.typeId);
					if(!this.id)
					{
						this.id = 0;
					}
				}
			}
		}
	}

	init()
	{
		Manager.addEditor(this.typeId, this.id, this.editor);
	}
}

namespace.ItemEditorComponent = ItemEditorComponent;
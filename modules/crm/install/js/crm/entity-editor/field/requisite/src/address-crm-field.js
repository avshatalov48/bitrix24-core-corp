import {EntityEditorAddressField} from "crm.entity-editor.field.address";
import {EventEmitter} from "main.core.events";

export class EntityEditorRequisiteAddressField extends EntityEditorAddressField
{
	initialize(id, settings)
	{
		super.initialize(id, settings);
		EventEmitter.emit(this.getEditor(), 'onFieldInit', {field: this});
	}

	rollback()
	{
		// rollback will be executed in requisite controller
	}

	reset()
	{
		// reset will be executed in requisite controller
	}

	onAddressListUpdate(event)
	{
		super.onAddressListUpdate(event);
		EventEmitter.emit(this, 'onAddressListUpdate', event);
	}

	static create(id, settings)
	{
		let self = new this(id, settings);
		self.initialize(id, settings);
		return self;
	}
}

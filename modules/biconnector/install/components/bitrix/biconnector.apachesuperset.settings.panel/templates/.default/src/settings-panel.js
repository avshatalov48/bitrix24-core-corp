import { ControllerFactory } from './controllers/controller-factory';
import { FieldFactory } from './fields/field-factory';

export class SettingsPanel
{
	static registerFieldFactory(entityEditorControlFactory): void
	{
		new FieldFactory(entityEditorControlFactory);
	}

	static registerControllerFactory(entityEditorControllerFactory): void
	{
		new ControllerFactory(entityEditorControllerFactory);
	}
}

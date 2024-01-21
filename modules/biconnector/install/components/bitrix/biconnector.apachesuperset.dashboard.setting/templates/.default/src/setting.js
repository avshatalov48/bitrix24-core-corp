import { SettingsDateFilterFieldFactory } from 'biconnector.entity-editor.field.settings-date-filter';
import { Factory } from './editor-controller/factory';

export class Setting
{
	static registerFieldFactory(entityEditorControlFactory)
	{
		new SettingsDateFilterFieldFactory(entityEditorControlFactory);
	}

	static registerControllerFactory(entityEditorControllerFactory)
	{
		new Factory(entityEditorControllerFactory);
	}
}

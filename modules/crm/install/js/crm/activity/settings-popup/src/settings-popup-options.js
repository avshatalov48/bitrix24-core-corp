import { SectionSettings } from 'crm.activity.todo-editor';

export type SettingsPopupOptions = {
	onSettingsChange: Function,
	onSave: Function,
	sections: Array<SectionSettings>,
	fetchSettingsPath: ?String,
	ownerTypeId: ?Number,
	ownerId: ?Number,
	id: ?number,
	settings: ?Object,
};

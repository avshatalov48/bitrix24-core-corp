import { EntitySelectorEntity } from 'tasks.v2.const';
import type { TextEditorOptions } from 'ui.text-editor';

export const DefaultEditorOptions: TextEditorOptions = Object.freeze({
	toolbar: [],
	floatingToolbar: [
		'bold', 'italic', 'underline', 'strikethrough', '|',
		'numbered-list', 'bulleted-list', '|',
		'link',
	],
	removePlugins: ['BlockToolbar'],
	visualOptions: {
		borderWidth: 0,
		blockSpaceInline: 0,
		colorBackground: 'transparent',
	},
	mention: {
		dialogOptions: {
			entities: [
				{
					id: EntitySelectorEntity.User,
					options: {
						emailUsers: true,
						inviteEmployeeLink: false,
					},
					itemOptions: {
						default: {
							link: '',
							linkTitle: '',
						},
					},
				},
				{
					id: EntitySelectorEntity.StructureNode,
					options: {
						selectMode: 'usersOnly',
						allowFlatDepartments: false,
					},
				},
			],
		},
	},
	copilot: {
		copilotOptions: {},
	},
	paragraphPlaceholder: 'auto',
});

import { ChatTextarea } from 'im.v2.component.textarea';

import './css/textarea.css';

// @vue/component
export const OpenLinesTextarea = {
	name: 'OpenLinesTextarea',
	components: { ChatTextarea },
	props:
	{
		dialogId: {
			type: String,
			default: '',
		},
	},
	template: `
		<ChatTextarea
			:dialogId="dialogId"
			:key="dialogId"
			:withAudioInput="false"
		>
		</ChatTextarea>
	`,
};

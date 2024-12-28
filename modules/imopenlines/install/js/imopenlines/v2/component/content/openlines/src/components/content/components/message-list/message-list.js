import { MessageList } from 'im.v2.component.message-list';

import { OpenLinesMessageMenu } from './classes/message-menu';

// @vue/component
export const OpenLinesMessageList = {
	name: 'OpenLinesMessageList',
	components: { MessageList },
	props:
	{
		dialogId: {
			type: String,
			required: true,
		},
	},
	computed:
	{
		OpenLinesMessageMenu: () => OpenLinesMessageMenu,
	},
	template: `
		<MessageList :dialogId="dialogId" :messageMenuClass="OpenLinesMessageMenu" />
	`,
};

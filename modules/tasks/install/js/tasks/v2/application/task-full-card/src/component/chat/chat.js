import { EventEmitter } from 'main.core.events';
import { Messenger } from 'im.public';
import 'im.v2.application.integration.task';

import { Core } from 'tasks.v2.core';
import { Model } from 'tasks.v2.const';
import type { TaskModel } from 'tasks.v2.model.tasks';

import './chat.css';

// @vue/component
export const Chat = {
	name: 'TaskFullCardChat',
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
	},
	computed: {
		task(): TaskModel
		{
			return this.$store.getters[`${Model.Tasks}/getById`](this.taskId);
		},
		isEdit(): boolean
		{
			return Number.isInteger(this.taskId) && this.taskId > 0;
		},
	},
	watch: {
		taskId(): void
		{
			void this.openChat();
		},
	},
	mounted(): void
	{
		void this.openChat();
	},
	methods: {
		async openChat(): Promise<void>
		{
			this.app?.bitrixVue.unmount();
			this.app ??= await Messenger.initApplication('task');

			if (this.isEdit)
			{
				void this.app.mount({
					rootContainer: this.$el,
					chatId: this.task.chatId,
					taskId: this.taskId,
					type: Core.getParams().chatType,
				});
			}
			else
			{
				await this.app.mountPlaceholder({
					rootContainer: this.$el,
					taskId: `'${this.taskId}'`,
				});

				EventEmitter.emit('tasks:card:onMembersCountChange', {
					taskId: this.taskId,
					userCounter: 1,
				});
			}
		},
	},
	template: `
		<div class="tasks-full-card-chat"></div>
	`,
};

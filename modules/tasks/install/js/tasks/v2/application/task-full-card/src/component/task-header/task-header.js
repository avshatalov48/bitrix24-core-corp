import { Type } from 'main.core';
import 'im.v2.application.integration.task';

import { Title as TitleField } from 'tasks.v2.component.fields.title';
import { Importance } from 'tasks.v2.component.fields.importance';

import { BurgerMenu } from './burger-menu/burger-menu';
import './task-header.css';

// @vue/component
export const TaskHeader = {
	name: 'TaskFullCardHeader',
	components: {
		TitleField,
		Importance,
		BurgerMenu,
	},
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
	},
	computed: {
		isEdit(): boolean
		{
			return Type.isNumber(this.taskId) && this.taskId > 0;
		},
	},
	template: `
		<div class="tasks-full-card-header">
			<TitleField :taskId />
			<Importance :taskId />
			<BurgerMenu v-if="isEdit" :taskId/>
		</div>
	`,
};

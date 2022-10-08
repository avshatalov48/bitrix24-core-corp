import 'ui.design-tokens';
import { BitrixVue } from 'ui.vue';
import { Text } from 'main.core';
import { Theme } from '../../../mixin/theme';
import 'ui.forms';

import './question.css';

const QuestionState = Object.freeze({
	DEFAULT: 'default',
	EDIT: 'edit',
});

export const Question = BitrixVue.localComponent('imbot-support24-question-component-question-list-question',{
	directives:
	{
		focus:
		{
			inserted(element, params)
			{
				element.focus();
			}
		}
	},
	mixins: [Theme],
	props: {
		id: Number,
		title: String,
	},
	data: function() {
		return {
			state: QuestionState.DEFAULT,
			newTitle: this.title,
		}
	},
	computed: {
		QuestionState: () => QuestionState,
		questionClass()
		{
			return this.getClassWithTheme('bx-imbot-support24-question-list-question');
		},
		titleClass()
		{
			return this.getClassWithTheme('bx-imbot-support24-question-list-question-title');
		},
		inputClass()
		{
			return this.getClassWithTheme('bx-imbot-support24-question-list-question-input');
		},
	},
	methods: {
		callMethod(method, params)
		{
			return this.$Bitrix.RestClient.get().callMethod(method, params);
		},
		click()
		{
			if (this.state === QuestionState.EDIT)
			{
				return;
			}

			this.$emit('click', this.id);
		},
		edit(event: Event)
		{
			event.stopPropagation();

			this.state = QuestionState.EDIT;
		},
		rename()
		{
			if (this.title === this.newTitle || this.newTitle.trim() === '')
			{
				this.state = QuestionState.DEFAULT;
				return;
			}

			const oldTitle =  this.title;

			this.setTitleById(this.id, this.newTitle).then(() => {
				this.setRecentListTitleById(this.id, this.newTitle);
				this.state = QuestionState.DEFAULT;
			});

			this.callMethod('im.chat.updateTitle', {
				CHAT_ID: this.id,
				TITLE: this.newTitle,
			}).catch(() => {
				this.setRecentListTitleById(this.id, oldTitle);
				this.setTitleById(this.id, oldTitle);
			});
		},
		setTitleById(id, title)
		{
			return this.$store.dispatch('question/setQuestionTitleById', {
				id,
				title,
			});
		},
		setRecentListTitleById(id, title)
		{
			if (BXIM && BXIM.messenger && BXIM.messenger.chat && BXIM.messenger.chat[id])
			{
				BXIM.messenger.chat[id].name = Text.encode(title);
				BX.MessengerCommon.recentListRedraw();
			}
		},
		inputClick(event: Event)
		{
			event.stopPropagation();
		}
	},
	// language=Vue
	template: `
		<div :class="questionClass" @click="click">
			<template v-if="state === QuestionState.DEFAULT">
				<div 
					class="
						bx-imbot-support24-question-list-question-icon
						bx-imbot-support24-question-list-question-icon-chat
					"/>

				<div :class="titleClass">
					{{ title }}
				</div>

				<div
					class="
						bx-imbot-support24-question-list-question-icon
						bx-imbot-support24-question-list-question-icon-edit
					"
					@click="edit"
				/>
			</template>

			<template v-else-if="state === QuestionState.EDIT">
				<div 
					class="ui-ctl ui-ctl-textbox ui-ctl-block ui-ctl-w100 ui-ctl-sm"
					:class="inputClass"
				>
					<input
						class="ui-ctl-element"
						type="text"
						:class="inputClass"
						v-model="newTitle"
						v-focus
						@keydown.enter="rename"
						@blur="rename"
						@click="inputClick"
					>
				</div>
			</template>
		</div>
	`
});
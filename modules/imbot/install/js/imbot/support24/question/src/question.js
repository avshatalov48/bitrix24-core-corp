import { BitrixVue } from 'ui.vue';
import { VuexBuilder } from 'ui.vue.vuex';
import { Loc, Dom } from 'main.core';

import { QuestionModel } from './model/question';
import { QuestionList } from './component/question-list/question-list';

export type QuestionOptions = {
	nodeId: string,
	popupContext?: any,
};

export class Question
{
	#viewModel;

	constructor(options: QuestionOptions)
	{
		this.rootNode = document.getElementById(options.nodeId);
		this.popupContext = options.popupContext ? options.popupContext : null;

		this.createStorage()
			.then(builder => {
				const store = builder.store;

				this.createApplication(store);
			});
	}

	createStorage(): Promise
	{
		const model =
			QuestionModel
				.create()
				.useDatabase(true)
		;

		const databaseConfig = {
			name: 'imbot-support24-question',
			type: VuexBuilder.DatabaseType.indexedDb,
			siteId: Loc.getMessage('SITE_ID'),
			userId: Loc.getMessage('USER_ID'),
		};

		return new VuexBuilder()
			.addModel(model)
			.setDatabaseConfig(databaseConfig)
			.build()
		;
	}

	createApplication(store)
	{
		Dom.clean(this.rootNode);
		Dom.append(Dom.create('div'), this.rootNode);

		const applicationContext = this;
		const popupContext = this.popupContext;

		this.#viewModel = BitrixVue.createApp({
			store,
			components:
			{
				QuestionList,
			},
			beforeCreate()
			{
				this.$bitrix.Application.set(applicationContext);
				this.$bitrix.Data.set('popupContext', popupContext);
			},
			template: `
				<QuestionList/>
			`,
		}).mount(this.rootNode.firstChild);
	}
}
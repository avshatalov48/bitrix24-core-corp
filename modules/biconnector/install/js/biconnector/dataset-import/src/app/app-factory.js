import { BitrixVue } from 'ui.vue3';
import type { AppStateOptions } from '../types/app-state-options';
import type { DataFormatTemplate } from '../types/data-types';
import { CsvApp } from './csv-app';
import { ExternalConnectionApp } from './external-connection-app';
import { Store } from './store';

const codeMap = {
	csv: CsvApp,
	'1c': ExternalConnectionApp,
};

type AppParams = {
	dataFormatTemplates: DataFormatTemplate,
	encodings: Array,
	separator: Array,
};

export class AppFactory
{
	static getApp(code: string, stateOptions: AppStateOptions = {}, appParams: AppParams = {})
	{
		const defaultStructure = {
			previewData: {},
			config: {
				fileProperties: {},
				dataFormats: {},
				datasetProperties: {},
				fieldsSettings: [],
			},
		};

		const state = BX.util.objectMerge(defaultStructure, stateOptions);

		let app = null;

		const appComponent = codeMap[code];

		if (appComponent)
		{
			app = BitrixVue.createApp({
				name: 'DatasetImport',
				data()
				{
					return {
						appParams,
					};
				},
				components: {
					appComponent,
				},
				computed: {
					componentName()
					{
						return appComponent;
					},
				},
				// language=Vue
				template: `
					<component :is="componentName" :app-params="appParams" />
				`,
			});
		}

		if (app)
		{
			const store = Store.buildStore(state);

			app.use(store);
		}

		return app;
	}
}

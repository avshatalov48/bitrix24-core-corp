/* eslint-disable no-param-reassign */
import { createStore } from 'ui.vue3.vuex';
import type { AppStateOptions, ConnectionProperties } from '../types/app-state-options';

export class Store
{
	static buildStore(defaultValues: AppStateOptions)
	{
		return createStore({
			state()
			{
				return defaultValues;
			},
			mutations: {
				setFileProperties(state, fileProperties)
				{
					state.config.fileProperties = fileProperties;
				},
				setConnectionProperties(state, connectionProperties)
				{
					state.config.connectionProperties = connectionProperties;
				},
				setDatasetProperties(state, datasetProperties)
				{
					state.config.datasetProperties = datasetProperties;
				},
				toggleRowVisibility(state, rowIndex)
				{
					state.config.fieldsSettings[rowIndex].visible = !state.config.fieldsSettings[rowIndex].visible;
				},
				setAllRowsVisible(state)
				{
					state.config.fieldsSettings = state.config.fieldsSettings.map((field) => {
						field.visible = true;

						return field;
					});
				},
				setAllRowsInvisible(state)
				{
					state.config.fieldsSettings = state.config.fieldsSettings.map((field) => {
						field.visible = false;

						return field;
					});
				},
				setFieldRowSettings(state, payload)
				{
					state.config.fieldsSettings[payload.index] = payload.settings;
				},
				setDataFormats(state, formats)
				{
					state.config.dataFormats = formats;
				},
				setPreviewData(state, data)
				{
					state.previewData.rows = data;
				},
				setFieldsSettings(state, settings)
				{
					state.config.fieldsSettings = settings;
				},
			},
			getters: {
				isEditMode(state)
				{
					return state.config.datasetProperties.id > 0;
				},
				areAllRowsVisible(state)
				{
					return state.config.fieldsSettings.filter((field) => !(field.visible)).length === 0;
				},
				areNoRowsVisible(state)
				{
					return state.config.fieldsSettings.filter((field) => (field.visible)).length === 0;
				},
				areSomeRowsVisible(state, getters)
				{
					return !getters.areAllRowsVisible && !getters.areNoRowsVisible;
				},
				columnVisibilityMap(state)
				{
					const map = [];
					state.config.fieldsSettings.forEach((field) => {
						map.push(Boolean(field.visible));
					});

					return map;
				},
				previewHeaders(state)
				{
					return state.config.fieldsSettings.map((item) => item.name);
				},
				hasData(state)
				{
					return state.previewData?.rows?.length > 0;
				},
				connectionProperties(state): ConnectionProperties
				{
					return state.config?.connectionProperties;
				},
				datasetProperties(state)
				{
					return state.config?.datasetProperties;
				},
			},
		});
	}
}

import { ToolbarComponent } from 'crm.toolbar-component';
import { ajax as Ajax, Runtime, Type } from 'main.core';
import { FeaturePromotersRegistry } from 'ui.info-helper';
import { Error } from './index';
import { normalizeId, normalizeTitle } from './validation';

export default {
	setState: (store, stateToSet: StateShape): void => {
		store.commit('setState', stateToSet);
	},

	setErrors: (store, errors: Error[]): void => {
		store.commit('setErrors', errors);
	},
	removeError: (store, error: Error): void => {
		store.commit('removeError', error);
	},

	setTitle: (store, title: string): void => {
		store.commit('setTitle', title);
	},

	addTypeId: (store, typeId: number): void => {
		store.commit('addTypeId', typeId);
	},
	removeTypeId: (store, typeId: number): void => {
		store.commit('removeTypeId', typeId);
	},

	save: (store): Promise<void, string[]> => {
		let savePromise: ?Promise = null;

		const fields = Runtime.clone(store.state.automatedSolution);
		if (fields.typeIds.length <= 0)
		{
			// we cant send an empty array in form data
			fields.typeIds = false;
		}

		if (store.getters.isNew)
		{
			savePromise = runAjaxAction('crm.automatedsolution.add', {
				data: {
					fields,
				},
			});
		}
		else
		{
			savePromise = runAjaxAction('crm.automatedsolution.update', {
				data: {
					id: store.state.automatedSolution.id,
					fields,
				},
			});
		}

		return savePromise
			.then(({ data }) => {
				store.dispatch('setState', {
					automatedSolution: data.automatedSolution,
				});
				emitUpdateEventToOutsideWorld(data.automatedSolution);
			})
			.catch((response) => {
				// eslint-disable-next-line no-console
				console.warn('could not save automated solution', { response, state: Runtime.clone(store.state) });

				const { errors } = response;

				let wasErrorHandled = false;
				for (const error of errors)
				{
					if (Type.isStringFilled(error.customData?.sliderCode))
					{
						FeaturePromotersRegistry.getPromoter({ code: error.customData.sliderCode }).show();
						wasErrorHandled = true;
					}
				}

				if (!wasErrorHandled)
				{
					// to show errors in ui
					store.dispatch('setErrors', errors);
				}

				throw errors;
			})
		;
	},

	delete: (store): Promise<void, string[]> => {
		return runAjaxAction('crm.automatedsolution.delete', {
			data: {
				id: store.state.automatedSolution.id,
			},
		})
			.then(() => {
				store.dispatch('setState', {});
				emitUpdateEventToOutsideWorld({ id: store.state.automatedSolution.id });
			})
			.catch((response) => {
				// eslint-disable-next-line no-console
				console.warn('could not delete automated solution', { response, state: Runtime.clone(store.state) });

				store.dispatch('setErrors', response.errors);

				throw response.errors;
			})
		;
	},
};

function runAjaxAction(...ajaxRunActionArgs): Promise
{
	// vuex don't understand BX.Promise. 'this.$store.dispatch.then' and 'subscribeAction({after})' won't work
	// wrap it in native Promise to fix it

	return new Promise((resolve, reject) => {
		Ajax.runAction(...ajaxRunActionArgs)
			.then(resolve)
			.catch(reject)
		;
	});
}

function emitUpdateEventToOutsideWorld({ id, title, intranetCustomSectionId }): void
{
	const data = {
		id: normalizeId(id),
		title: normalizeTitle(title),
		intranetCustomSectionId: normalizeId(intranetCustomSectionId),
	};

	ToolbarComponent.Instance.emitAutomatedSolutionUpdatedEvent(data);
}

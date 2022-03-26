import {VuexBuilderModel} from 'ui.vue.vuex';
import {rest as Rest} from 'rest.client';

export class DocumentSelectorModel extends VuexBuilderModel
{
	/**
	 * @inheritDoc
	 */
	getName()
	{
		return 'documentSelector';
	}

	getActions()
	{
		return {
			addDocument({commit, dispatch}, {document})
			{
				commit('addDocument', {document});
				dispatch('setBoundDocumentId', {boundDocumentId: document.id});
			},
			setBoundDocumentId({state, commit}, {boundDocumentId})
			{
				if (state.paymentId > 0)
				{
					Rest.callMethod('crm.documentgenerator.document.bindToPayment', {
						id: boundDocumentId,
						paymentId: state.paymentId,
					}).then(() => {
						commit('setBoundDocumentId', {boundDocumentId});
					}).catch((response) => {
						console.error(response);
					});
				}
				else
				{
					commit('setBoundDocumentId', {boundDocumentId});
				}
			},
			loadTemplates({state, commit})
			{
				if (Number(state.entityTypeId) <= 0 || Number(state.entityId) <= 0)
				{
					commit('setTemplates', {templates: []});
					return;
				}
				Rest.callMethod('crm.documentgenerator.template.listForItem', {
					entityTypeId: state.entityTypeId,
					entityId: state.entityId,
				}).then((response) => {
					if (response.answer.result.templates)
					{
						commit('setTemplates', {templates: response.answer.result.templates});
					}
				}).catch((response) => {
					console.error(response);
				});
			}
		}
	}

	getState()
	{
		return {
			entityTypeId: null,
			entityId: null,
			paymentId: null,
			documents: [],
			templates: [],
			boundDocumentId: null,
			selectedTemplateId: null,
		}
	}

	getGetters()
	{
		return {
			getTemplates: (state) => {
				return state.templates;
			},
			getDocuments: (state) => {
				return state.documents;
			},
			getBoundDocumentId: (state) => {
				return state.boundDocumentId;
			},
			getSelectedTemplateId: (state) => {
				return state.selectedTemplateId;
			},
		}
	}

	getMutations()
	{
		return {
			fillState: (state, payload) => {
				state.entityTypeId = payload.entityTypeId;
				state.entityId = payload.entityId;
				state.paymentId = payload.paymentId;
				state.documents = payload.documents;
				state.templates = payload.templates;
				state.boundDocumentId = payload.boundDocumentId;
				state.selectedTemplateId = payload.selectedTemplateId;
			},
			setTemplates: (state, payload) => {
				if(typeof payload.templates === 'object')
				{
					state.templates = payload.templates;
				}
			},
			setBoundDocumentId: (state, payload) => {
				if(typeof payload.boundDocumentId === 'number')
				{
					state.boundDocumentId = payload.boundDocumentId;
				}
			},
			setSelectedTemplateId: (state, payload) => {
				if(typeof payload.selectedTemplateId === 'number')
				{
					state.selectedTemplateId = payload.selectedTemplateId;
					state.boundDocumentId = null;
				}
			},
			addDocument: (state, payload) => {
				if(typeof payload.document === 'object')
				{
					const newDocument = payload.document;
					if (!newDocument.id)
					{
						return;
					}
					const documents = state.documents || [];
					let isUpdated = false;
					for (const index in documents)
					{
						if (documents[index].id === newDocument.id)
						{
							documents[index] = newDocument;
							isUpdated = true;
							break;
						}
					}
					if (!isUpdated)
					{
						documents.unshift(newDocument);
					}
					state.documents = documents;
				}
			},
		}
	}
}

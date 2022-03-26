import {Loc, Uri} from 'main.core';
import {StatusTypes as Status} from 'salescenter.component.stage-block';
import {MenuManager} from "main.popup";
import {Manager} from 'salescenter.manager';
import {Block} from 'salescenter.component.stage-block';
import {StageMixin} from "./stage-mixin";
import "ui.icons.disk";
import type {Document, Template} from "../../app";
import {Vuex} from "ui.vue.vuex";

const DocumentSelector = {
	props: {
		counter: {
			type: Number,
			required: true
		},
		templateAddUrl: {
			type: String,
			required: false,
		},
	},
	mixins: [StageMixin],
	components: {
		'stage-block-item':	Block,
	},
	computed: {
		status()
		{
			if (
				this.model
				&& (
					this.model.templates
					&& this.model.templates.length
				)
				|| (
					this.model.documents
					&& this.model.documents.length
				)
			)
			{
				return Status.complete;
			}

			if(
				this.templateAddUrl
				&& this.templateAddUrl.length
			)
			{
				return Status.current;
			}

			return Status.disabled;
		},
		configForBlock()
		{
			return {
				counter: this.counter,
				titleItems: [],
				installed: true,
				collapsible: false,
				checked: this.counterCheckedMixin,
				showHint: false,
				initialCollapseState: false,
			}
		},
		getDocumentTitle(): ?string
		{
			const currentDocument = this.getCurrentDocument();
			if (currentDocument)
			{
				return currentDocument.title;
			}

			return Loc.getMessage('SALESCENTER_DOCUMENT_SELECTOR_BLOCK_CREATE_NEW_TEMPLATE');
		},
		withStamps(): string
		{
			let isWithStamps = '';
			const currentDocument = this.getCurrentDocument();
			if (currentDocument)
			{
				if(currentDocument.isWithStamps)
				{
					isWithStamps = Loc.getMessage('SALESCENTER_DOCUMENT_SELECTOR_BLOCK_WITH_SIGNS');
				}
				else
				{
					isWithStamps = Loc.getMessage('SALESCENTER_DOCUMENT_SELECTOR_BLOCK_WITHOUT_SIGNS');
				}
			}

			return isWithStamps;
		},
		hasData()
		{
			const document = this.getCurrentDocument();

			return (document && document.detailUrl);
		},
		...Vuex.mapState({
			model: state => state.documentSelector,
		})
	},
	methods: {
		handleDocumentClick({target})
		{
			if (this.getCurrentDocument() !== null)
			{
				this.openSelectorMenu(target);
			}
			else
			{
				this.openTemplatesList();
			}
		},
		openSelectorMenu(bindElement)
		{
			MenuManager.show({
				id: 'payment-document-selector',
				bindElement,
				items: this.prepareSelectorMenuItems(),
				closeByEsc: true,
				cacheable: false,
			});
		},
		closeSelectorMenu()
		{
			MenuManager.destroy('payment-document-selector');
		},
		prepareSelectorMenuItems()
		{
			const items = [];
			if (this.model.documents)
			{
				for (const document of this.model.documents)
				{
					items.push({
						text: document.title,
						onclick: () => {
							this.closeSelectorMenu();
							this.$store.dispatch('documentSelector/setBoundDocumentId', {boundDocumentId: document.id});
						}
					});
				}
			}
			const templateListItem = {
				text: Loc.getMessage('SALESCENTER_DOCUMENT_SELECTOR_BLOCK_CREATE_NEW_DOCUMENT'),
				items: [],
			};
			if (this.model.templates)
			{
				if (items.length > 0)
				{
					items.push({delimiter: true});
				}
				for (const template of this.model.templates)
				{
					templateListItem.items.push({
						text: template.title,
						onclick: () => {
							this.closeSelectorMenu();
							this.$store.commit('documentSelector/setSelectedTemplateId', {selectedTemplateId: template.id});
						}
					})
				}
			}
			if (this.templateAddUrl)
			{
				if (templateListItem.items.length > 0)
				{
					templateListItem.items.push({delimiter: true});
				}
				templateListItem.items.push({
					text: Loc.getMessage('SALESCENTER_DOCUMENT_SELECTOR_BLOCK_CREATE_NEW_TEMPLATE'),
					onclick: () => {
						this.closeSelectorMenu();
						this.openTemplatesList();
					}
				})
			}
			if (templateListItem.items.length > 0)
			{
				items.push(templateListItem);
			}

			return items;
		},
		openTemplatesList()
		{
			Manager.openSlider(this.templateAddUrl, {
				width: 930,
			}).then(() => {
				this.$store.dispatch('documentSelector/loadTemplates');
			});
		},
		handleEditDocumentClick()
		{
			const currentDocument = this.getCurrentDocument();
			if (currentDocument.detailUrl)
			{
				Manager.openSlider(currentDocument.detailUrl, {
					width: 980,
				}).then((slider) => {
					const document = slider.getData().get('document');
					if (document)
					{
						this.$store.dispatch('documentSelector/addDocument', {document});
					}
				});
			}
		},
		getCurrentDocument(): ?Document
		{
			let document = null;
			if (this.model.boundDocumentId > 0)
			{
				document = this.getDocumentById(this.model.boundDocumentId);
			}
			if (!document && this.model.selectedTemplateId > 0)
			{
				document = this.getStubDocumentByTemplate(this.model.selectedTemplateId);
			}
			if (!document)
			{
				const documents = this.model.documents;
				if (documents && documents[0])
				{
					document = documents[0];
					this.$store.dispatch('documentSelector/setBoundDocumentId', {boundDocumentId: document.id});
				}
			}
			if (!document)
			{
				const templates = this.model.templates;
				if (templates && templates[0])
				{
					document = this.getStubDocumentByTemplate(templates[0].id);
					this.$store.commit('documentSelector/setSelectedTemplateId', {selectedTemplateId: templates[0].id});
				}
			}

			return document;
		},
		getDocumentById(id: number): ?Document
		{
			for (const document: Document of this.model.documents)
			{
				if (document.id === id)
				{
					return document;
				}
			}

			return null;
		},
		getTemplateById(id: number): ?Template
		{
			for (const template: Template of this.model.templates)
			{
				if (template.id === id)
				{
					return template;
				}
			}

			return null;
		},
		getStubDocumentByTemplate(templateId: number): ?Document
		{
			const template = this.getTemplateById(templateId);
			if (!template)
			{
				return null;
			}

			const paymentId = this.$root.$app.options.paymentId || 0;
			let title = null;
			let detailUrl = null;
			if (paymentId > 0)
			{
				title = Loc.getMessage('SALESCENTER_DOCUMENT_SELECTOR_BLOCK_DOCUMENT_NEW_SUFFIX', {
					'#TITLE#': template.title
				});
				detailUrl = Uri.addParam(template.documentCreationUrl, {
					values: {
						_paymentId: paymentId,
					}
				});
			}
			else
			{
				title = Loc.getMessage('SALESCENTER_DOCUMENT_SELECTOR_BLOCK_DOCUMENT_CREATED_LATER_SUFFIX', {
					'#TITLE#': template.title
				});
				detailUrl = null;
			}
			return {
				id: 0,
				title,
				detailUrl,
				isWithStamps: template.isWithStamps,
			}
		},
	},
	// language=Vue
	template: `
		<stage-block-item
			:class="statusClassMixin"
			:config="configForBlock"
		>
			<template v-slot:block-title-title>${Loc.getMessage('SALESCENTER_DOCUMENT_SELECTOR_BLOCK_TITLE')}</template>
			<template v-slot:block-container>
				<div :class="containerClassMixin">										
					<div class="salescenter-app-payment-item-container-document-selector">
						<div class="salescenter-app-payment-item-container-document-selector-selector">
							<div class="salescenter-app-payment-item-container-document-selector-selector-file">
								<div class="ui-icon ui-icon-lg ui-icon-file-pdf"><i></i></div>
							</div>
							<div class="salescenter-app-payment-item-container-document-selector-title">
								<div 
									ref="selectorNode"
									class="salescenter-app-payment-item-container-document-selector-title-button"
									@click="handleDocumentClick"
								>{{getDocumentTitle}}</div>
								<div class="salescenter-app-payment-item-container-document-selector-title-sign">{{withStamps}}</div>
							</div>
						</div>
						<div
							v-if="hasData"
							class="salescenter-app-payment-item-container-document-selector-edit"
						>
							<div 
								class="salescenter-app-payment-item-container-document-selector-edit-button"
								@click="handleEditDocumentClick"
							>
                              <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M11.4359 0.03479L13.9865 2.61221L4.00879 12.563L1.45822 9.98561L11.4359 0.03479ZM0.0256074 13.6726C0.00148857 13.7639 0.0273302 13.8603 0.0927957 13.9275C0.159984 13.9947 0.25646 14.0205 0.347767 13.9947L3.19896 13.2265L0.793965 10.8223L0.0256074 13.6726Z" fill="#525C69"/>
                              </svg>
                              ${Loc.getMessage('SALESCENTER_RIGHT_ACTION_EDIT')}</div>
						</div>
					</div>
				</div>
			</template>
		</stage-block-item>
	`
};

export
{
	DocumentSelector
}
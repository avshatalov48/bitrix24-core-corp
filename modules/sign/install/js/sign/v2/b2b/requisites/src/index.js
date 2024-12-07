import { Dom, Tag, Loc, ajax, Runtime, Reflection, Text, Event } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Loader } from 'main.loader';
import { Api } from 'sign.v2.api';
import { Hint } from 'sign.v2.helper';
import './style.css';

const crmEditorSettings = {
	entityTypeId: 36,
	guid: 'sign_entity_editor',
	params: {
		ENABLE_CONFIGURATION_UPDATE: 'N',
		ENABLE_PAGE_TITLE_CONTROLS: false,
		ENABLE_MODE_TOGGLE: true,
		IS_EMBEDDED: 'N',
		forceDefaultConfig: 'Y',
		enableSingleSectionCombining: 'N',
	},
};
const companyEntity = 'company';
const contactEntity = 'contact';
const events = [
	'onCrmEntityUpdate',
	'BX.Crm.EntityEditor:onFailedValidation',
	'onCrmEntityUpdateError',
	'BX.Crm.EntityEditor:onEntitySaveFailure',
];

export class Requisites extends EventEmitter
{
	#requisitesNode: HTMLElement;
	#initiatorNode: HTMLElement;
	#api: API;
	#editors: { [key: string]: BX.Crm.EntityEditor };
	#members;
	documentData;

	constructor()
	{
		super();
		this.setEventNamespace('BX.Sign.V2.Requisites');
		this.#requisitesNode = Tag.render`
			<div class="sign-wizard__requisites"></div>
		`;
		this.#initiatorNode = Tag.render`
			<input type="text" class="ui-ctl-element" />
		`;
		this.#api = new Api();
		this.documentData = {};
		this.#members = {};
		this.#editors = {};
	}

	getLayout(): HTMLElement
	{
		this.#initiatorNode.value = Text.encode(this.documentData.initiator);
		Event.bind(this.#initiatorNode, 'change', ({ target }) => {
			this.emit('changeInitiator', { initiator: target.value });
		});
		const preparingNode = Tag.render`
			<div class="sign-wizard__preparing">
				<div class="sign-wizard__first-party_responsible">
					<span class="sign-wizard__first-party_responsible-title">
						${Loc.getMessage('SIGN_PARTY_RESPONSIBLE_TITLE')}
						<span data-hint="${Loc.getMessage('SIGN_PARTY_RESPONSIBLE_HINT')}"></span>
					</span>
					<div class="ui-ctl ui-ctl-textbox sign-wizard__first-party_responsible-name">
						${this.#initiatorNode}
					</div>
				</div>
				${this.#requisitesNode}
			</div>
		`;
		Hint.create(preparingNode);
		this.#showEntityEditor();

		return preparingNode;
	}

	async #loadEntityEditor(): Promise<string>
	{
		const loader = new Loader({
			target: this.#requisitesNode,
			size: 80,
		});

		try
		{
			loader.show();
			const response = await ajax.runAction('crm.api.item.getEditor', {
				data: { ...crmEditorSettings, id: this.documentData.entityId },
			});
			loader.destroy();

			return response?.data?.html || '';
		}
		catch (e)
		{
			console.error(e);
			loader.destroy();

			return '';
		}
	}

	checkInitiator(initiator: string): boolean
	{
		const parentNode = this.#initiatorNode.parentNode;
		if (!initiator)
		{
			Dom.addClass(parentNode, 'ui-ctl-warning');
			this.#initiatorNode.focus();

			return false;
		}

		Dom.removeClass(parentNode, 'ui-ctl-warning');

		return true;
	}

	async #showEntityEditor()
	{
		Dom.clean(this.#requisitesNode);
		const documentId = this.documentData.entityId;
		if (this.#editors[documentId])
		{
			const editorNode = this.#editors[documentId].getContainer();
			this.#requisitesNode.appendChild(editorNode);
		}
		else
		{
			const editorHtml = await this.#loadEntityEditor();
			await Runtime.html(this.#requisitesNode, editorHtml);
			const Editor = Reflection.getClass('BX.Crm.EntityEditor');
			this.#editors = {
				...this.#editors,
				[documentId]: Editor.defaultInstance,
			};
		}

		this.#toggleCompany();
		this.#toggleContact();
	}

	async processMembers()
	{
		const entityData = await this.#saveEditor();
		if (!entityData)
		{
			return null;
		}

		try
		{
			const { uid: documentUid = '' } = this.documentData;
			if (!this.#members[documentUid])
			{
				await this.#loadMembers(documentUid);
			}

			const {
				MYCOMPANY_ID_INFO: { COMPANY_DATA: [companyData] },
				CLIENT_INFO: { CONTACT_DATA: [contactData] },
				REQUISITE_BINDING: { REQUISITE_ID: selectedContactRequisite },
			} = entityData;

			const companyEntityId = companyData.id;
			const contactEntityId = contactData.id;
			const companyRequisiteData = this.#getRequisiteData(companyData);
			const contactRequisiteData = this.#getRequisiteData(contactData, selectedContactRequisite);
			const documentMembers = this.#members[documentUid];
			const itemsForRemove = documentMembers?.filter((member, index) => {
				const entityId = member.entityId;
				if (index === 0)
				{
					return entityId !== companyEntityId;
				}

				return entityId !== contactEntityId;
			});
			const membersData = this.#getMembersData(companyData, contactData);
			const result = {
				company: {
					...membersData[0],
					...companyRequisiteData,
					part: 1,
				},
				contact: {
					...membersData[1],
					...contactRequisiteData,
					part: 2,
				},
			};

			if (documentMembers)
			{
				if (itemsForRemove.length === 0)
				{
					const [company, contact] = documentMembers;
					result.company.uid = company.uid;
					result.contact.uid = contact.uid;

					return result;
				}

				await this.#removeMembers(itemsForRemove);
			}

			const [companyUid, contactUid] = await this.#addMembers(documentUid, {
				companyPresetId: companyRequisiteData.presetId,
				contactPresetId: contactRequisiteData.presetId,
				companyEntityId,
				contactEntityId,
			});
			result.company.uid = companyUid;
			result.contact.uid = contactUid;

			return result;
		}
		catch
		{
			return null;
		}
	}

	#getMembersData(companyData, contactData)
	{
		return [
			companyData,
			contactData,
		].map((party) => {
			const { id, title, url, advancedInfo, type } = party;
			const multiFields = advancedInfo?.multiFields ?? [];

			return { id, title, url, type, multiFields };
		});
	}

	async #saveEditor()
	{
		if (!this.#editors[this.documentData.entityId])
		{
			return null;
		}

		const editor = this.#editors[this.documentData.entityId];
		const clientControl = editor.getControlById('CLIENT');
		if (clientControl?.isInViewMode() && !clientControl.hasContentToDisplay())
		{
			return null;
		}

		const promise = new Promise((resolve) => {
			const [successFullEvent, ...errorEvents] = events;
			EventEmitter.subscribeOnce(successFullEvent, (event) => {
				const [{ entityData }] = event.data;
				resolve(entityData);
			});
			errorEvents.forEach((event) => {
				EventEmitter.subscribeOnce(event, () => resolve(null));
			});
		});
		editor.save();
		const entityData = await promise;
		events.forEach((event) => EventEmitter.unsubscribeAll(event));

		return entityData;
	}

	async #addMembers(documentUid: string, entityData)
	{
		const {
			companyEntityId,
			contactEntityId,
			companyPresetId,
			contactPresetId,
		} = entityData;
		const documentMembers = this.#members[documentUid] ?? [];
		const sameCompany = documentMembers[0]?.entityId === companyEntityId;
		const sameContact = documentMembers[1]?.entityId === contactEntityId;
		const companyPromise = sameCompany
			? Promise.resolve({ uid: documentMembers[0].uid })
			: this.#api.addMember(
				documentUid,
				companyEntity,
				companyEntityId,
				1,
				companyPresetId,
			);
		const contactPromise = sameContact
			? Promise.resolve({ uid: documentMembers[1].uid })
			: this.#api.addMember(
				documentUid,
				contactEntity,
				contactEntityId,
				2,
				contactPresetId,
			);
		const [companyUid, contactUid] = await Promise.all([
			companyPromise,
			contactPromise,
		]);
		this.#members = {
			...this.#members,
			[documentUid]: [
				{
					entityId: companyEntityId,
					uid: companyUid.uid,
				},
				{
					entityId: contactEntityId,
					uid: contactUid.uid,
				},
			],
		};

		return [companyUid.uid, contactUid.uid];
	}

	async #removeMembers(removeItems = [])
	{
		await Promise.all([
			removeItems.map((removeItem) => {
				return this.#api.removeMember(removeItem.uid);
			}),
		]);
	}

	async #loadMembers(documentUid: string)
	{
		const membersData = await this.#api.loadMembers(documentUid);
		membersData.forEach((memberData) => {
			this.#members[documentUid] = [
				...this.#members[documentUid] ?? [],
				{
					entityId: memberData.entityId,
					uid: memberData.uid,
				},
			];
		});
	}

	#getRequisiteData(memberData, selectedRequisiteId = null): number
	{
		const { requisiteData } = memberData.advancedInfo;
		const selectedItem = requisiteData.find((item) => {
			if (selectedRequisiteId !== null)
			{
				return selectedRequisiteId === item.requisiteId;
			}

			return item.selected === true;
		}) ?? {};
		const { entityTypeId = 0, presetId = 0 } = selectedItem;

		return { entityTypeId, presetId };
	}

	#toggleCompany()
	{
		const crmEditor = this.#editors[this.documentData.entityId];
		const companyField = crmEditor.getControlById('MYCOMPANY_ID');
		const companySection = crmEditor.getControlById('myCompany');
		if (!companyField)
		{
			return;
		}

		this.#toggleEntity(companyField, companySection);
	}

	#toggleContact()
	{
		const crmEditor = this.#editors[this.documentData.entityId];
		const contactsField = crmEditor.getControlById('CLIENT');
		const clientSection = crmEditor.getControlById('client');
		if (!contactsField)
		{
			return;
		}

		this.#toggleEntity(contactsField, clientSection);
	}

	#toggleEntity(field, section)
	{
		const switchToSingleEditMode = field.switchToSingleEditMode;
		const crmEditor = this.#editors[this.documentData.entityId];
		field.isRequired = () => true;
		field.switchToSingleEditMode = (...args) => {
			if (section?.getMode() === BX.UI.EntityEditorMode.view)
			{
				crmEditor.switchControlMode(section, BX.UI.EntityEditorMode.edit);
			}

			switchToSingleEditMode.apply(field, args);
		};
		const layout = field.layout;
		field.layout = (...args) => {
			layout.apply(field, args);
			Dom.remove(field._addContactButton);
		};
		const switchToViewMode = field.getId() === 'CLIENT'
			? field.hasContacts() : field.hasCompanies();
		if (switchToViewMode && section?.getMode() === BX.UI.EntityEditorMode.edit)
		{
			crmEditor.switchControlMode(section, BX.UI.EntityEditorMode.view);

			return;
		}

		section?.enableToggling(false);
		Dom.remove(field._addContactButton);
	}
}

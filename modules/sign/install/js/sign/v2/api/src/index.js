import { post } from './request';
import { TemplateApi } from './template/template-api';
import type {
	B2eCompanyList,
	BlockData,
	Communication,
	LoadedBlock,
	LoadedDocumentData,
	HcmLinkMultipleVacancyEmployeesLoadData,
	EmployeeSaveData,
} from './type';
import { CountMember, SetupMember } from './type';
import type { MemberStatusType } from 'sign.type';

export * from './type';
export * from './template/type';

export class Api
{
	template: TemplateApi = new TemplateApi();

	#post(endpoint: string, data: Object | null = null, notifyError: boolean = true): $Call<typeof post>
	{
		return post(endpoint, data, notifyError);
	}

	register(
		blankId: string,
		scenarioType: string | null = null,
		asTemplate: boolean = false,
		chatId: number = 0,
	): Promise<{
		uid: string,
		templateUid: string | null,
		templateId: number | null,
		chatId: number,
	}>
	{
		return this.#post('sign.api_v1.document.register', { blankId, scenarioType, asTemplate, chatId });
	}

	upload(uid: string): Promise<[]>
	{
		return this.#post('sign.api_v1.document.upload', { uid });
	}

	getPages(uid: string): Promise<Array<{ url: string; }>>
	{
		return this.#post('sign.api_v1.document.pages.list', { uid }, false);
	}

	loadBlanks(page: number, scenario: string | null = null, countPerPage: number | null = null): Promise<Array<{ title: string; id: number }>>
	{
		return this.#post('sign.api_v1.document.blank.list', { page, scenario, countPerPage });
	}

	createBlank(files: Array<string>, scenario: string | null = null, forTemplate: boolean = false): Promise<{
		id: number;
	}>
	{
		return this.#post('sign.api_v1.document.blank.create', { files, scenario, forTemplate });
	}

	saveBlank(documentUid: string, blocks: []): Promise<[]>
	{
		return this.#post('sign.api_v1.document.blank.block.save', { documentUid, blocks }, false);
	}

	loadBlocksData(documentUid: string, blocks: []): Promise<BlockData>
	{
		return this.#post('sign.api_v1.document.blank.block.loadData', { documentUid, blocks });
	}

	changeDocument(uid: string, blankId: number): Promise<{ uid: string; }>
	{
		return this.#post('sign.api_v1.document.changeBlank', { uid, blankId });
	}

	changeDocumentLanguages(uid: string, lang: string): Promise
	{
		return this.#post('sign.api_v1.document.changeDocumentLanguages', { uid, lang });
	}

	changeRegionDocumentType(uid: string, type: string): Promise<{ status: string; data: []; errors: string[]; }>
	{
		return this.#post('sign.api_v1.document.modifyRegionDocumentType', { uid, type });
	}

	changeSenderDocumentType(uid: string, initiatedByType: string): Promise<
		{ status: string; data: []; errors: string[]; }>
	{
		return this.#post('sign.api_v1.document.modifyInitiatedByType', { uid, initiatedByType });
	}

	changeExternalId(uid: string, id: string): Promise<{ status: string; data: []; errors: string[]; }>
	{
		return this.#post('sign.api_v1.document.modifyExternalId', { uid, id });
	}

	changeExternalDate(uid: string, externalDate: string): Promise<{ status: string; data: []; errors: string[]; }>
	{
		return this.#post('sign.api_v1.document.modifyExternalDate', { uid, externalDate });
	}

	changeIntegrationId(uid: string, integrationId: number | null = null): Promise<{
		status: string;
		data: [];
		errors: string[];
	}>
	{
		return this.#post('sign.api_v1.document.modifyIntegrationId', { uid, integrationId });
	}

	loadDocument(uid: string): Promise<LoadedDocumentData>
	{
		return this.#post('sign.api_v1.document.load', { uid });
	}

	loadDocumentById(id: number): Promise<LoadedDocumentData>
	{
		return this.#post('sign.api_v1.document.loadById', { id });
	}

	configureDocument(uid: string): Promise<[]>
	{
		return this.#post('sign.api_v1.document.configure', { uid });
	}

	configureDocumentGroup(groupId: number): Promise<[]>
	{
		return this.#post('sign.api_v1.b2e.document.group.configure', { groupId });
	}

	loadBlocksByDocument(documentUid: string): Promise<Array<LoadedBlock>>
	{
		return this.#post('sign.api_v1.document.blank.block.loadByDocument', {
			documentUid,
		});
	}

	startSigning(uid: string): Promise<[]>
	{
		return this.#post('sign.api_v1.document.signing.start', { uid });
	}

	addMember(
		documentUid: string,
		entityType: string,
		entityId: number,
		party: number,
		presetId: number,
	): Promise<{ uid: string; }>
	{
		return this.#post('sign.api_v1.document.member.add', {
			documentUid,
			entityType,
			entityId,
			party,
			presetId,
		});
	}

	removeMember(uid: string): Promise<[]>
	{
		return this.#post('sign.api_v1.document.member.remove', { uid });
	}

	loadMembers(documentUid: string): Promise<Array<{ entityId: number; uid: string; }>>
	{
		return this.#post('sign.api_v1.document.member.load', { documentUid });
	}

	modifyCommunicationChannel(
		uid: string,
		channelType: string,
		channelValue: string,
	): Promise<[]>
	{
		return this.#post('sign.api_v1.document.member.modifyCommunicationChannel', {
			uid,
			channelType,
			channelValue,
		});
	}

	loadCommunications(uid: String): Promise<Array<Communication>>
	{
		return this.#post('sign.api_v1.document.member.loadCommunications', { uid });
	}

	modifyTitle(uid: string, title: string): Promise<{ blankTitle: string }>
	{
		return this.#post('sign.api_v1.document.modifyTitle', {
			uid,
			title,
		});
	}

	modifyInitiator(uid: string, initiator: string): Promise<[]>
	{
		return this.#post('sign.api_v1.document.modifyInitiator', {
			uid,
			initiator,
		});
	}

	modifyLanguageId(uid: string, langId: string): Promise
	{
		return this.#post('sign.api_v1.document.modifyLangId', {
			uid,
			langId,
		});
	}

	modifyReminderTypeForMemberRole(documentUid: string, memberRole: string, reminderType: string): Promise
	{
		return this.#post('sign.api_v1.b2e.member.reminder.set', {
			documentUid,
			memberRole,
			type: reminderType,
		});
	}

	loadLanguages(): Promise
	{
		return this.#post('sign.api_v1.document.loadLanguage');
	}

	refreshEntityNumber(documentUid: string): Promise<[]>
	{
		return this.#post('sign.api_v1.document.refreshEntityNumber', {
			documentUid,
		});
	}

	changeDomain(): Promise
	{
		return this.#post('sign.api_v1.portal.changeDomain');
	}

	loadRestrictions(): Promise<{ smsAllowed: boolean; }>
	{
		return this.#post('sign.api_v1.portal.hasRestrictions');
	}

	saveStamp(memberUid: String, fileId: string): Promise<{ id: number; srcUri: string; }>
	{
		return this.#post('sign.api_v1.document.member.saveStamp', {
			memberUid, fileId,
		});
	}

	setupB2eParties(
		documentUid: string,
		representativeId: number,
		members: Array<SetupMember>,
	): Promise
	{
		return this.#post('sign.api_v1.document.member.setupB2eParties', {
			documentUid, representativeId, members,
		});
	}

	syncB2eMembersWithDepartments(
		documentUid: string,
		currentParty: number,
	): Promise<{ syncFinished: boolean }>
	{
		return this.#post('sign.api_v1.document.member.syncB2eMembersWithDepartments', {
			documentUid, currentParty,
		});
	}

	getUniqUserCountForMembers(
		members: Array<CountMember>,
	): Promise<{ count: number }>
	{
		return this.#post('sign.api_v1.document.member.getUniqSignersCount', {
			members,
		});
	}

	getUniqUserCountForDocument(
		documentUid: string,
	): Promise<{ count: number }>
	{
		return this.#post('sign.api_v1.document.member.getUniqSignersCountForDocument', {
			documentUid,
		});
	}

	getDepartmentsForDocument(
		documentUid: string,
		page: number,
		pageSize: number,
	): Promise<{ departments: [{ id: number, name: string }] }>
	{
		return this.#post('sign.api_v1.document.member.getDepartmentsForDocument', {
			documentUid, page, pageSize,
		});
	}

	getMembersForDocument(
		documentUid: string,
		page: number,
		pageSize: number,
	): Promise<{ members: [{ memberId: number, userId: number, name: string, avatar: ?string, profileUrl: string }] }>
	{
		return this.#post('sign.api_v1.document.member.getMembersForDocument', {
			documentUid, page, pageSize,
		});
	}

	updateChannelTypeToB2eMembers(
		membersUids: Array<string>,
		channelType: string,
	): Promise
	{
		return this.#post('sign.api_v1.b2e.member.communication.updateMembersChannelType', {
			members: membersUids,
			channelType,
		});
	}

	loadB2eCompanyList(
		forDocumentInitiatedByType: string | null = null,
	): Promise<B2eCompanyList>
	{
		return this.#post('sign.api_v1.integration.crm.b2ecompany.list', { forDocumentInitiatedByType });
	}

	modifyB2eCompany(documentUid: string, companyUid: string): Promise
	{
		return this.#post('sign.api_v1.document.modifyCompany', {
			documentUid, companyUid,
		});
	}

	modifyB2eDocumentScheme(uid: string, scheme: string): Promise
	{
		return this.#post('sign.api_v1.document.modifyScheme', {
			uid, scheme,
		});
	}

	loadB2eAvaialbleSchemes(documentUid: string): Promise
	{
		return this.#post('sign.api_v1.b2e.scheme.load', {
			documentUid,
		});
	}

	deleteB2eCompany(id: string): Promise
	{
		return this.#post('sign.api_v1.integration.crm.b2ecompany.delete', {
			id,
		});
	}

	getLinkForSigning(memberId: number, notifyError: boolean = true): Promise<{
		uri: string,
		requireBrowser: boolean,
		mobileAllowed: boolean,
		employeeData: Object
	}>
	{
		return this.#post('sign.api_v1.b2e.member.link.getLinkForSigning', {
			memberId,
		}, notifyError);
	}

	memberLoadReadyForMessageStatus(memberIds: Array<number>): Promise
	{
		return this.#post('sign.api_v1.document.send.getMembersForResend', {
			memberIds,
		});
	}

	memberResendMessage(memberIds: Array<number>): Promise
	{
		return this.#post('sign.api_v1.document.send.resendMessage', {
			memberIds,
		});
	}

	getBlankById(id: number): Promise<{ id: number, title: string, scenario: string }>
	{
		return this.#post('sign.api_v1.document.blank.getById', { id });
	}

	registerB2eCompany(
		providerCode: string,
		taxId: string,
		companyId: string,
		externalProviderId: ?string,
	): Promise<{ id: number }>
	{
		return this.#post('sign.api_v1.integration.crm.b2ecompany.register', {
			providerCode, taxId, companyId, externalProviderId,
		});
	}

	setDecisionToSesB2eAgreement(): Promise<{ decision: string }>
	{
		return this.#post('sign.api_v1.b2e.member.communication.setAgreementDecision', {});
	}

	createDocumentChat(chatType: number, documentId: number, isEntityId: boolean): Promise<{ chatId: number }>
	{
		return this.#post('sign.api_v1.integration.im.groupChat.createDocumentChat', {
			chatType, documentId, isEntityId,
		});
	}

	getDocumentFillAndStartProgress(uid: string): Promise<{ completed: boolean, progress: Number }>
	{
		return this.#post('sign.api_v1.document.getFillAndStartProgress', { uid });
	}

	getDocumentGroupFillAndStartProgress(groupId: number): Promise<{ completed: boolean, progress: Number }>
	{
		return this.#post('sign.api_v1.b2e.document.group.getFillAndStartProgress', { groupId });
	}

	getMember(uid: string): Promise<{ id: number, uid: string, status: MemberStatusType }>
	{
		return this.#post('sign.api_v1.document.member.get', { uid });
	}

	createDocumentsGroup(): Promise<{ groupId: number }>
	{
		return this.#post('sign.api_v1.b2e.document.group.create');
	}

	removeDocument(uid: string): Promise<Array>
	{
		return this.#post('sign.api_v1.document.remove', { uid });
	}

	attachGroupToDocument(documentUid: string, groupId: number): Promise<Array>
	{
		return this.#post('sign.api_v1.b2e.document.group.attach', { documentUid, groupId });
	}

	getDocumentListInGroup(groupId: string): Promise
	{
		return this.#post('sign.api_v1.b2e.document.group.documentList', { groupId });
	}

	changeTemplateVisibility(templateId: number, visibility: string): Promise<Object>
	{
		return this.#post('sign.api_v1.b2e.document.template.changeVisibility', { templateId, visibility });
	}

	deleteTemplate(templateId: number): Promise<void>
	{
		return this.#post('sign.api_v1.b2e.document.template.delete', { templateId });
	}

	copyTemplate(templateId: number): Promise<void>
	{
		return this.#post('sign.api_v1.b2e.document.template.copy', { templateId });
	}

	checkCompanyHrIntegration(id: number): Promise<Array<{ id: number, title: string }>>
	{
		return this.#post('sign.api_v1.integration.humanresources.hcmLink.checkCompany', { id });
	}

	checkNotMappedMembersHrIntegration(
		documentUid: string,
	): Promise<{ integrationId: number, userIds: Array<number>, allUserIds: Array<number> }>
	{
		return this.#post('sign.api_v1.integration.humanresources.hcmLink.loadNotMappedMembers', { documentUid });
	}

	getMultipleVacancyMemberHrIntegration(documentUid: string): Promise<HcmLinkMultipleVacancyEmployeesLoadData>
	{
		return this.#post('sign.api_v1.integration.humanresources.hcmLink.loadMultipleVacancyEmployee', { documentUid });
	}

	saveEmployeesForSignProcess(data: EmployeeSaveData): Promise<[]>
	{
		return this.#post('sign.api_v1.integration.humanresources.hcmLink.saveSelectedEmployees', data);
	}
}

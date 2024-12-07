import { post } from './request';
import { TemplateApi } from './template/template-api';
import type { BlockData, Communication, LoadedBlock, LoadedDocumentData, MemberStatusType } from './type';
import { SetupMember } from './type';

export * from './type';
export * from './template/type';

export class Api
{
	template: TemplateApi = new TemplateApi();

	#post(endpoint: string, data: Object | null = null, notifyError: boolean = true): $Call<typeof post>
	{
		return post(endpoint, data, notifyError);
	}

	register(blankId: string, scenarioType: string | null = null, asTemplate: boolean = false): Promise<{
		uid: string,
		templateUid: string | null
	}>
	{
		return this.#post('sign.api_v1.document.register', { blankId, scenarioType, asTemplate });
	}

	upload(uid: string): Promise<[]>
	{
		return this.#post('sign.api_v1.document.upload', { uid });
	}

	getPages(uid: string): Promise<Array<{ url: string; }>>
	{
		return this.#post('sign.api_v1.document.pages.list', { uid }, false);
	}

	loadBlanks(page: number, scenario: string | null = null): Promise<Array<{ title: string; id: number }>>
	{
		return this.#post('sign.api_v1.document.blank.list', { page, scenario });
	}

	createBlank(files: Array<string>, scenario: string | null = null, forTemplate: boolean = false): Promise<{ id: number; }>
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

	changeExternalId(uid: string, id: string): Promise<{ status: string; data: []; errors: string[]; }>
	{
		return this.#post('sign.api_v1.document.modifyExternalId', { uid, id });
	}

	changeExternalDate(uid: string, externalDate: string): Promise<{ status: string; data: []; errors: string[]; }>
	{
		return this.#post('sign.api_v1.document.modifyExternalDate', { uid, externalDate });
	}

	loadDocument(uid: string): Promise<LoadedDocumentData>
	{
		return this.#post('sign.api_v1.document.load', { uid });
	}

	configureDocument(uid: string): Promise<[]>
	{
		return this.#post('sign.api_v1.document.configure', { uid });
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

	loadB2eCompanyList(): Promise
	{
		return this.#post('sign.api_v1.integration.crm.b2ecompany.list');
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
			providerCode, taxId, companyId, externalProviderId
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

	getMember(uid: string): Promise<{ id: number, uid: string, status: MemberStatusType }>
	{
		return this.#post('sign.api_v1.document.member.get', { uid });
	}
}

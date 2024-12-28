/**
 * @module im/messenger/lib/element/dialog/message/banner/banners/sign/configuration
 */

jn.define('im/messenger/lib/element/dialog/message/banner/banners/sign/configuration', (require, exports, module) => {
	const { Loc } = require('loc');
	const {
		Await,
		Success,
		Failure,
		ImageName,
	} = require('im/messenger/lib/element/dialog/message/banner/banners/sign/type');
	const { ButtonDesignType } = require('im/messenger/lib/element/dialog/message/banner/const/type');
	const { ButtonSize } = require('ui-system/form/buttons/button');
	const { inAppUrl } = require('in-app-url');

	/**
	 * @type {SignMetaData}
	 */
	const SignMetaData = {
		[Await.inviteCompany]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_COMPANY_TITLE'),
				imageName: ImageName.docAwaitSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_COMPANY_DESCRIPTION'),
				buttons: [
					{
						id: Await.inviteCompany,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_COMPANY_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Await.inviteCompanyWithInitiator]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_COMPANY_TITLE_INITIATOR'),
				imageName: ImageName.docAwaitSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_COMPANY_DESCRIPTION_INITIATOR'),
				buttons: [
					{
						id: Await.inviteCompanyWithInitiator,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_COMPANY_BUTTON_TEXT_INITIATOR'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Await.inviteEmployeeSes]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_EMPLOYEE_SES_TITLE'),
				imageName: ImageName.docAwaitSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_EMPLOYEE_SES_DESCRIPTION_MSGVER_1'),
				buttons: [
					{
						id: Await.inviteEmployeeSes,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_EMPLOYEE_SES_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Await.inviteEmployeeSesWithInitiator]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_EMPLOYEE_SES_TITLE'),
				imageName: ImageName.docAwaitSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_EMPLOYEE_SES_DESCRIPTION_INITIATOR_MSGVER_1'),
				buttons: [
					{
						id: Await.inviteEmployeeSesWithInitiator,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_EMPLOYEE_SES_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Await.inviteEmployeeGosKey]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_EMPLOYEE_GOS_KEY_TITLE'),
				imageName: ImageName.docAwaitSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_EMPLOYEE_GOS_KEY_DESCRIPTION'),
				button: null,
			}
		},
		[Await.inviteEmployeeGosKeyV2]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_EMPLOYEE_GOS_KEY_TITLE'),
				imageName: ImageName.docAwaitSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_EMPLOYEE_GOS_KEY_V2_DESCRIPTION'),
				buttons: [
					{
						id: Await.inviteEmployeeGosKeyV2,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_EMPLOYEE_GOS_KEY_V2_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Await.inviteEmployeeGosKeyWithInitiator]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_EMPLOYEE_GOS_KEY_TITLE'),
				imageName: ImageName.docAwaitSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_EMPLOYEE_GOS_KEY_V2_INITIATOR_DESCRIPTION'),
				buttons: [
					{
						id: Await.inviteEmployeeGosKeyWithInitiator,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_EMPLOYEE_GOS_KEY_V2_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Await.inviteReviewer]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_REVIEWER_TITLE'),
				imageName: ImageName.docAwaitSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_REVIEWER_DESCRIPTION'),
				buttons: [
					{
						id: Await.inviteReviewer,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_REVIEWER_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Await.inviteReviewerWithInitiator]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_REVIEWER_TITLE_INITIATOR'),
				imageName: ImageName.docAwaitSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_REVIEWER_DESCRIPTION_INITIATOR'),
				buttons: [
					{
						id: Await.inviteReviewerWithInitiator,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_REVIEWER_BUTTON_TEXT_INITIATOR'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Await.inviteEditor]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_EDITOR_TITLE'),
				imageName: ImageName.docAwaitSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_EDITOR_DESCRIPTION'),
				buttons: [
					{
						id: Await.inviteEditor,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_EDITOR_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Await.inviteEditorWithInitiator]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_EDITOR_TITLE_INITIATOR'),
				imageName: ImageName.docAwaitSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_EDITOR_DESCRIPTION_INITIATOR'),
				buttons: [
					{
						id: Await.inviteEditorWithInitiator,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_EDITOR_BUTTON_TEXT_INITIATOR'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Await.byEmployeeInviteCompany]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_INVITE_COMPANY_TITLE'),
				imageName: ImageName.docAwaitSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_INVITE_COMPANY_DESCRIPTION'),
				buttons: [
					{
						id: Await.byEmployeeInviteCompany,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_INVITE_COMPANY_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.filled,
					},
				],
			},
		},
		[Await.byEmployeeInviteReviewer]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_INVITE_REVIEWER_TITLE'),
				imageName: ImageName.docAwaitSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_INVITE_REVIEWER_DESCRIPTION'),
				buttons: [
					{
						id: Await.byEmployeeInviteReviewer,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_INVITE_REVIEWER_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.filled,
					},
				],
			},
		},
		[Await.byEmployeeInviteEmployee]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_INVITE_EMPLOYEE_TITLE'),
				imageName: ImageName.docAwaitSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_INVITE_EMPLOYEE_DESCRIPTION'),
				buttons: [
					{
						id: Await.byEmployeeInviteEmployee,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_INVITE_EMPLOYEE_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.filled,
					},
				],
			},
		},
		[Await.byEmployeeSignedByEmployee]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_SIGNED_EMPLOYEE_TITLE'),
				imageName: ImageName.docAwaitSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_SIGNED_EMPLOYEE_DESCRIPTION'),
				buttons: [
					{
						id: Await.byEmployeeSignedByEmployee,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_SIGNED_EMPLOYEE_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Await.inviteB2bDocumentSigning]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_B2B_DOCUMENT_SIGNING_TITLE'),
				imageName: ImageName.docSuccessSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_INVITE_B2B_DOCUMENT_SIGNING_DESCRIPTION'),
				button: null,
			},
		},
		[Success.doneCompany]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DONE_COMPANY_TITLE'),
				imageName: ImageName.docSuccessSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DONE_COMPANY_DESCRIPTION'),
				buttons: [
					{
						id: Success.doneCompany,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DONE_COMPANY_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Success.doneEmployee]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DONE_EMPLOYEE_TITLE'),
				imageName: ImageName.docSuccessSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DONE_EMPLOYEE_DESCRIPTION_MSGVER_1'),
				buttons: [
					{
						id: Success.doneEmployee,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DONE_EMPLOYEE_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Success.doneEmployeeGosKey]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DONE_EMPLOYEE_GOS_KEY_TITLE'),
				imageName: ImageName.docSuccessSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DONE_EMPLOYEE_GOS_KEY_DESCRIPTION_MSGVER_1'),
				buttons: [
					{
						id: Success.doneEmployeeGosKey,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DONE_EMPLOYEE_GOS_KEY_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Success.doneFromAssignee]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DONE_FROM_ASSIGNEE_TITLE'),
				imageName: ImageName.docSuccessSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DONE_FROM_ASSIGNEE_DESCRIPTION'),
				button: null,
			},
		},
		[Success.doneFromEditor]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DONE_FROM_EDITOR_TITLE'),
				imageName: ImageName.docSuccessSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DONE_FROM_EDITOR_DESCRIPTION'),
				button: null,
			},
		},
		[Success.doneFromReviewer]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DONE_FROM_REVIEWER_TITLE'),
				imageName: ImageName.docSuccessSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DONE_FROM_REVIEWER_DESCRIPTION'),
				button: null,
			},
		},
		[Success.doneB2bDocumentSigning]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DONE_B2B_DOCUMENT_SIGNING_TITLE'),
				imageName: ImageName.docSuccessSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DONE_B2B_DOCUMENT_SIGNING_DESCRIPTION'),
				button: null,
			},
		},
		[Success.byEmployeeDoneEmployee]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_DONE_EMPLOYEE_TITLE'),
				imageName: ImageName.docSuccessSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_DONE_EMPLOYEE_DESCRIPTION'),
				buttons: [
					{
						id: Success.byEmployeeDoneEmployee,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_DONE_EMPLOYEE_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Success.byEmployeeDoneEmployeeM]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_DONE_EMPLOYEE_TITLE'),
				imageName: ImageName.docSuccessSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_DONE_EMPLOYEE_DESCRIPTIONM'),
				buttons: [
					{
						id: Success.byEmployeeDoneEmployeeM,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_DONE_EMPLOYEE_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Success.byEmployeeDoneEmployeeF]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_DONE_EMPLOYEE_TITLE'),
				imageName: ImageName.docSuccessSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_DONE_EMPLOYEE_DESCRIPTIONF'),
				buttons: [
					{
						id: Success.byEmployeeDoneEmployeeF,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_DONE_EMPLOYEE_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Success.byEmployeeDoneCompany]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_DONE_COMPANY_TITLE'),
				imageName: ImageName.docSuccessSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_DONE_COMPANY_DESCRIPTION'),
			},
		},
		[Failure.refusedCompanyV2]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_REFUSED_COMPANY_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_REFUSED_COMPANY_DESCRIPTION_1'),
				buttons: [
					{
						id: Failure.refusedCompanyV2,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_REFUSED_COMPANY_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Failure.refusedCompanyV2M]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_REFUSED_COMPANY_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_REFUSED_COMPANY_DESCRIPTIONM'),
				buttons: [
					{
						id: Failure.refusedCompanyV2M,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_REFUSED_COMPANY_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Failure.refusedCompanyV2F]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_REFUSED_COMPANY_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_REFUSED_COMPANY_DESCRIPTIONF'),
				buttons: [
					{
						id: Failure.refusedCompanyV2F,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_REFUSED_COMPANY_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Failure.stoppedToEmployee]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_STOPPED_TO_EMPLOYEE_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_STOPPED_TO_EMPLOYEE_TITLE_DESCRIPTION'),
			},
		},
		[Failure.stoppedToEmployeeM]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_STOPPED_TO_EMPLOYEE_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_STOPPED_TO_EMPLOYEE_TITLE_DESCRIPTIONM'),
			},
		},
		[Failure.stoppedToEmployeeF]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_STOPPED_TO_EMPLOYEE_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_STOPPED_TO_EMPLOYEE_TITLE_DESCRIPTIONF'),
			},
		},
		[Failure.byEmployeeStoppedToEmployee]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_STOPPED_TO_EMPLOYEE_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_STOPPED_TO_EMPLOYEE_TITLE_DESCRIPTION'),
				buttons: [
					{
						id: Failure.byEmployeeStoppedToEmployee,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_STOPPED_TO_EMPLOYEE_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Failure.byEmployeeStoppedToEmployeeM]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_STOPPED_TO_EMPLOYEE_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_STOPPED_TO_EMPLOYEE_TITLE_DESCRIPTIONM'),
				buttons: [
					{
						id: Failure.byEmployeeStoppedToEmployeeM,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_STOPPED_TO_EMPLOYEE_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Failure.byEmployeeStoppedToEmployeeF]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_STOPPED_TO_EMPLOYEE_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_STOPPED_TO_EMPLOYEE_TITLE_DESCRIPTIONF'),
				buttons: [
					{
						id: Failure.byEmployeeStoppedToEmployeeF,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_BY_EMPLOYEE_STOPPED_TO_EMPLOYEE_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Failure.employeeStoppedToCompanyV2]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_EMPLOYEE_STOPPED_TO_COMPANY_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_EMPLOYEE_STOPPED_TO_COMPANY_DESCRIPTION_1'),
				buttons: [
					{
						id: Failure.employeeStoppedToCompanyV2,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_EMPLOYEE_STOPPED_TO_COMPANY_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Failure.employeeStoppedToCompanyV2M]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_EMPLOYEE_STOPPED_TO_COMPANY_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_EMPLOYEE_STOPPED_TO_COMPANY_DESCRIPTIONM'),
				buttons: [
					{
						id: Failure.employeeStoppedToCompanyV2M,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_EMPLOYEE_STOPPED_TO_COMPANY_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Failure.employeeStoppedToCompanyV2F]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_EMPLOYEE_STOPPED_TO_COMPANY_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_EMPLOYEE_STOPPED_TO_COMPANY_DESCRIPTIONF'),
				buttons: [
					{
						id: Failure.employeeStoppedToCompanyV2F,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_EMPLOYEE_STOPPED_TO_COMPANY_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Failure.documentStoppedToAssignee]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_STOPPED_TO_ASSIGNEE_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_STOPPED_TO_ASSIGNEE_DESCRIPTION'),
			},
		},
		[Failure.documentStoppedToAssigneeM]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_STOPPED_TO_ASSIGNEE_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_STOPPED_TO_ASSIGNEE_DESCRIPTIONM'),
			},
		},
		[Failure.documentStoppedToAssigneeF]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_STOPPED_TO_ASSIGNEE_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_STOPPED_TO_ASSIGNEE_DESCRIPTIONF'),
			},
		},
		[Failure.documentStoppedToReviewer]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_STOPPED_TO_REVIEWER_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_STOPPED_TO_REVIEWER_DESCRIPTION'),
			},
		},
		[Failure.documentStoppedToReviewerM]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_STOPPED_TO_REVIEWER_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_STOPPED_TO_REVIEWER_DESCRIPTIONM'),
			},
		},
		[Failure.documentStoppedToReviewerF]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_STOPPED_TO_REVIEWER_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_STOPPED_TO_REVIEWER_DESCRIPTIONF'),
			},
		},
		[Failure.documentStoppedToEditor]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_STOPPED_TO_EDITOR_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_STOPPED_TO_EDITOR_DESCRIPTION'),
			},
		},
		[Failure.documentStoppedToEditorM]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_STOPPED_TO_EDITOR_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_STOPPED_TO_EDITOR_DESCRIPTIONM'),
			},
		},
		[Failure.documentStoppedToEditorF]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_STOPPED_TO_EDITOR_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_STOPPED_TO_EDITOR_DESCRIPTIONF'),
			},
		},
		[Failure.documentStoppedToInitiator]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_STOPPED_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_STOPPED_DESCRIPTION_1'),
				buttons: [
					{
						id: Failure.documentStoppedToInitiator,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_STOPPED_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Failure.documentStoppedToInitiatorM]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_STOPPED_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_STOPPED_DESCRIPTIONM'),
				buttons: [
					{
						id: Failure.documentStoppedToInitiatorM,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_STOPPED_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Failure.documentStoppedToInitiatorF]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_STOPPED_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_STOPPED_DESCRIPTIONF'),
				buttons: [
					{
						id: Failure.documentStoppedToInitiatorF,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_STOPPED_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Failure.refusedCompany]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_REFUSED_COMPANY_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_REFUSED_COMPANY_DESCRIPTION'),
			},
		},
		[Failure.employeeStoppedToCompany]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_EMPLOYEE_STOPPED_TO_COMPANY_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_EMPLOYEE_STOPPED_TO_COMPANY_DESCRIPTION'),
			},
		},
		[Failure.documentStopped]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_STOPPED_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_STOPPED_DESCRIPTION'),
			},
		},
		[Failure.documentCancelled]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_CANCELLED_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_CANCELLED_DESCRIPTION'),
				buttons: [
					{
						id: Failure.documentCancelled,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_CANCELLED_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Failure.signingError]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_SIGNING_ERROR_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_SIGNING_ERROR_DESCRIPTION'),
				buttons: [
					{
						id: Failure.signingError,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_SIGNING_ERROR_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
		[Failure.repeatSigning]: {
			banner: {
				title: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_REPEAT_TITLE'),
				imageName: ImageName.docFailureSign,
				description: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_REPEAT_DESCRIPTION'),
				buttons: [
					{
						id: Failure.repeatSigning,
						text: Loc.getMessage('IMMOBILE_MESSAGE_SIGN_DOCUMENT_REPEAT_BUTTON_TEXT'),
						height: ButtonSize.S.getName(),
						callback,
						design: ButtonDesignType.outlineAccent2,
					},
				],
			},
		},
	};

	/**
	 * @protected
	 * @param {SignMessageRestParams} data
	 */
	function callback(data)
	{
		const url = data.document?.link;

		if (url)
		{
			inAppUrl.open(url);
		}
	}

	module.exports = {
		SignMetaData,
	};
});

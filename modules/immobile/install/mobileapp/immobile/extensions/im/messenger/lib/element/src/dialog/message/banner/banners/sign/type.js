/**
 * @module im/messenger/lib/element/dialog/message/banner/banners/sign/type
 */
jn.define('im/messenger/lib/element/dialog/message/banner/banners/sign/type', (require, exports, module) => {
	const Await = Object.freeze({
		inviteCompany: 'inviteCompany',
		inviteCompanyWithInitiator: 'inviteCompanyWithInitiator',
		inviteEmployeeSes: 'inviteEmployeeSes',
		inviteEmployeeSesWithInitiator: 'inviteEmployeeSesWithInitiator',
		inviteEmployeeGosKey: 'inviteEmployeeGosKey',
		inviteEmployeeGosKeyV2: 'inviteEmployeeGosKeyV2',
		inviteEmployeeGosKeyWithInitiator: 'inviteEmployeeGosKeyWithInitiator',
		inviteReviewer: 'inviteReviewer',
		inviteReviewerWithInitiator: 'inviteReviewerWithInitiator',
		inviteEditor: 'inviteEditor',
		inviteEditorWithInitiator: 'inviteEditorWithInitiator',
		inviteB2bDocumentSigning: 'inviteB2bDocumentSigning',

		// initiated by employee
		byEmployeeInviteCompany: 'byEmployeeInviteCompany',
		byEmployeeInviteReviewer: 'byEmployeeInviteReviewer',
		byEmployeeInviteEmployee: 'byEmployeeInviteEmployee',
		byEmployeeSignedByEmployee: 'byEmployeeSignedByEmployee',
	});

	const Success = Object.freeze({
		doneCompany: 'doneCompany',
		doneEmployee: 'doneEmployee',
		doneEmployeeGosKey: 'doneEmployeeGosKey',
		doneFromAssignee: 'doneFromAssignee',
		doneFromEditor: 'doneFromEditor',
		doneFromReviewer: 'doneFromReviewer',
		doneB2bDocumentSigning: 'doneB2bDocumentSigning',

		// initiated by employee
		byEmployeeDoneEmployee: 'byEmployeeDoneEmployee',
		byEmployeeDoneEmployeeM: 'byEmployeeDoneEmployeeM',
		byEmployeeDoneEmployeeF: 'byEmployeeDoneEmployeeF',
		byEmployeeDoneCompany: 'byEmployeeDoneCompany',
	});

	const Failure = Object.freeze({
		refusedCompanyV2: 'refusedCompanyV2',
		refusedCompanyV2M: 'refusedCompanyV2M',
		refusedCompanyV2F: 'refusedCompanyV2F',
		employeeStoppedToCompanyV2: 'employeeStoppedToCompanyV2',
		employeeStoppedToCompanyV2M: 'employeeStoppedToCompanyV2M',
		employeeStoppedToCompanyV2F: 'employeeStoppedToCompanyV2F',
		documentStoppedToInitiator: 'documentStoppedToInitiator',
		documentStoppedToInitiatorM: 'documentStoppedToInitiatorM',
		documentStoppedToInitiatorF: 'documentStoppedToInitiatorF',
		documentStoppedToAssignee: 'documentStoppedToAssignee',
		documentStoppedToAssigneeM: 'documentStoppedToAssigneeM',
		documentStoppedToAssigneeF: 'documentStoppedToAssigneeF',
		documentStoppedToReviewer: 'documentStoppedToReviewer',
		documentStoppedToReviewerM: 'documentStoppedToReviewerM',
		documentStoppedToReviewerF: 'documentStoppedToReviewerF',
		documentStoppedToEditor: 'documentStoppedToEditor',
		documentStoppedToEditorM: 'documentStoppedToEditorM',
		documentStoppedToEditorF: 'documentStoppedToEditorF',
		refusedCompany: 'refusedCompany',
		employeeStoppedToCompany: 'employeeStoppedToCompany',
		documentStopped: 'documentStopped',
		documentCancelled: 'documentCancelled',
		stoppedToEmployee: 'stoppedToEmployee',
		stoppedToEmployeeM: 'stoppedToEmployeeM',
		stoppedToEmployeeF: 'stoppedToEmployeeF',
		signingError: 'signingError',
		repeatSigning: 'repeatSigning',

		// initiated by employee
		byEmployeeStoppedToEmployee: 'byEmployeeStoppedToEmployee',
		byEmployeeStoppedToEmployeeM: 'byEmployeeStoppedToEmployeeM',
		byEmployeeStoppedToEmployeeF: 'byEmployeeStoppedToEmployeeF',
	});

	const ImageName = Object.freeze({
		docFailureSign: 'doc_failure_sign',
		docSuccessSign: 'doc_success_sign',
		docAwaitSign: 'doc_await_sign',
	});

	const SignType = {
		...Await,
		...Success,
		...Failure,
	};

	module.exports = {
		Await,
		Success,
		Failure,
		SignType,
		ImageName,
	};
});

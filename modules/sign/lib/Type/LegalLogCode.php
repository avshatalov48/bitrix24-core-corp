<?php

namespace Bitrix\Sign\Type;

class LegalLogCode
{
	/**
	 * Document send to signing to signsafe service
	 */
	public const DOCUMENT_START = 'documentStart';
	/**
	 * Document signing stopped
	 */
	public const DOCUMENT_STOP = 'documentStop';
	/**
	 * Document signing completed
	 */
	public const DOCUMENT_DONE = 'documentDone';
	/**
	 * Invite to sign/review/edit is successfully delivered to bitrix chat
	 */
	public const CHAT_INVITE_DELIVERED = 'chatInviteDelivered';
	/**
	 * Invite to sign/review/edit not delivered to chat because of chat disable
	 */
	public const CHAT_INVITE_NOT_DELIVERED = 'chatInviteNotDelivered';
	/**
	 * Reviewer accepted document
	 */
	public const REVIEWER_ACCEPT = 'reviewerAccept';
	/**
	 * Editor completed document and member fields filling and verifying
	 */
	public const EDITOR_ACCEPT = 'editorAccept';
	/**
	 * Privileged member (reviewer/editor/assignee) initiated signing stop with some member,
	 * initiator member there
	 */
	public const INITIATE_MEMBER_STOP = 'initiateMemberStop';
	/**
	 * Signing with employee stopped by privileged member,
	 * stopped member there
	 */
	public const MEMBER_STOPPED = 'memberStopped';
	/**
	 * Assignee signed document with employee,
	 * employee member there
	 */
	public const ASSIGNEE_SIGNED_MEMBER = 'assigneeSignedMember';
	/**
	 * Signer signed document
	 */
	public const SIGNER_SIGN = 'signerSign';
	/**
	 * Signer refused to sign document
	 */
	public const SIGNER_REFUSE = 'signerRefuse';
	/**
	 * Signer processing to sign document
	 */
	public const SIGNER_PROCESSING = 'signerProcessing';
	/**
	 * An error occurred on provider side in employee signing process (e.g. goskey)
	 */
	public const SIGN_ERROR = 'signError';
	/**
	 * Final archive with pdf and sign saved for member
	 */
	public const MEMBER_FILE_SAVED = 'memberFileSaved';
}

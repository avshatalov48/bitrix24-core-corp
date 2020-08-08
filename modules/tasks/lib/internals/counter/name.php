<?php

namespace Bitrix\Tasks\Internals\Counter;

class Name
{
	const TOTAL = 'total';

	const MY = 'my';
	const MY_NOT_VIEWED = 'my_not_viewed';
	const MY_EXPIRED = 'my_expired';
	const MY_EXPIRED_SOON = 'my_expired_soon';
	const MY_WITHOUT_DEADLINE = 'my_without_deadline';
	const MY_NEW_COMMENTS = 'my_new_comments';

	const ACCOMPLICES = 'accomplices';
	const ACCOMPLICES_NOT_VIEWED = 'accomplices_not_viewed';
	const ACCOMPLICES_EXPIRED = 'accomplices_expired';
	const ACCOMPLICES_EXPIRED_SOON = 'accomplices_expired_soon';
	const ACCOMPLICES_NEW_COMMENTS = 'accomplices_new_comments';

	const AUDITOR = 'auditor';
	const AUDITOR_EXPIRED = 'auditor_expired';
	const AUDITOR_NEW_COMMENTS = 'auditor_new_comments';

	const ORIGINATOR = 'originator';
	const ORIGINATOR_EXPIRED = 'originator_expired';
	const ORIGINATOR_WITHOUT_DEADLINE = 'originator_without_deadline';
	const ORIGINATOR_WAIT_CONTROL = 'originator_wait_ctrl';
	const ORIGINATOR_NEW_COMMENTS = 'originator_new_comments';

	const OPENED = 'opened';
	const CLOSED = 'closed';
	const NEW_COMMENTS = 'new_comments';
	const EXPIRED = 'expired';

	const EFFECTIVE = 'effective';
}
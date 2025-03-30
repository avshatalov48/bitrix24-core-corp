/**
 * @module sign/connector
 */
jn.define('sign/connector', (require, exports, module) => {

	/**
	 * @function getTemplateListPromise
	 * @returns {Promise}
	 */
	function getTemplateListPromise()
	{
		return BX.ajax.runAction('sign.api_v1.b2e.document.template.list', {
			json: true,
		});
	}

	/**
	 * @function getFieldsPromise
	 * @returns {Promise}
	 */
	function getFieldsPromise(uid)
	{
		return BX.ajax.runAction('signmobile.api.template.getFields', {
			json: {
				uid
			},
		});
	}

	/**
	 * @function isSendDocumentByEmployeeEnabled
	 * @returns {Promise}
	 */
	function isSendDocumentByEmployeeEnabled()
	{
		return BX.ajax.runAction('signmobile.api.document.isE2bAvailable', {
			json: true,
		});
	}

	/**
	 * @function sendTemplate
	 * @returns {Promise}
	 */
	function sendTemplate(uid, fields)
	{
		return BX.ajax.runAction('sign.api_v1.b2e.document.template.send', {
			json: {
				uid,
				fields,
			},
		});
	}

	/**
	 * @function getMember
	 * @returns {Promise}
	 */
	function getMember(uid)
	{
		return BX.ajax.runAction('sign.api_v1.document.member.get', {
			json: {
				uid,
			},
		});
	}

	/**
	 * @function getSigningLinkPromise
	 * @returns {Promise}
	 */
	function getSigningLinkPromise(memberId)
	{
		return BX.ajax.runAction('signmobile.api.document.getSigningLink', {
			data: {
				memberId: Number(memberId),
			},
		});
	}

	/**
	 * @function reviewAccept
	 *
	 * @param memberId
	 * @returns {Promise}
	 */
	function reviewAccept(memberId)
	{
		return BX.ajax.runAction('signmobile.api.document.reviewAccept', {
			data: {
				memberId: Number(memberId),
			},
		});
	}

	/**
	 * @function signingAccept
	 *
	 * @param memberId
	 * @returns {Promise}
	 */
	function signingAccept(memberId)
	{
		return BX.ajax.runAction('signmobile.api.document.signingAccept', {
			data: {
				memberId: Number(memberId),
			},
		});
	}

	/**
	 * @function getExternalUrl
	 *
	 * @param memberId
	 * @returns {Promise<{ data: {url: string } }>}
	 */
	function getExternalUrl(memberId)
	{
		return BX.ajax.runAction('signmobile.api.document.getExternalUrl', {
			data: {
				memberId: Number(memberId),
			},
		});
	}

	/**
	 * @function confirmationAccept
	 *
	 * @param memberId
	 * @returns {Promise}
	 */
	function confirmationAccept(memberId)
	{
		return BX.ajax.runAction('signmobile.api.document.confirmationAccept', {
			data: {
				memberId: Number(memberId),
			},
		});
	}

	/**
	 * @function rejectConfirmation
	 */
	function rejectConfirmation(memberId)
	{
		return BX.ajax.runAction('signmobile.api.document.signingReject', {
			data: {
				memberId: Number(memberId),
			},
		});
	}

	/**
	 * @function confirmationPostpone
	 */
	function confirmationPostpone(memberId)
	{
		return BX.ajax.runAction('signmobile.api.document.confirmationPostpone', {
			data: {
				memberId: Number(memberId),
			},
		});
	}

	module.exports = {
		getTemplateListPromise,
		getFieldsPromise,
		isSendDocumentByEmployeeEnabled,
		sendTemplate,
		getMember,
		getSigningLinkPromise,
		signingAccept,
		reviewAccept,
		confirmationAccept,
		rejectConfirmation,
		confirmationPostpone,
		getExternalUrl,
	};
});

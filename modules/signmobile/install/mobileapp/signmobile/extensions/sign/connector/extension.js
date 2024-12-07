/**
 * @module sign/connector
 */
jn.define('sign/connector', (require, exports, module) => {

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
		getSigningLinkPromise,
		signingAccept,
		confirmationAccept,
		rejectConfirmation,
		confirmationPostpone,
		getExternalUrl,
	};
});

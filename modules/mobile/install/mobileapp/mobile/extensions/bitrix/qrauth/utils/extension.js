/**
 * @module qrauth/utils
 */
jn.define('qrauth/utils', (require, exports, module) => {
	const { Loc } = require('loc');
	const { openManager } = require('qrauth/utils/src/manager');

	const qrauth = {
		urlTemplate: 'https://b24.to/a/',
		open: openManager,
		listenUniversalLink: () => {
			const handler = (data) => {
				if (!data.url || !String(data.url).startsWith('https://b24.to/a/'))
				{
					return;
				}

				void qrauth.open({
					urlData: data,
					external: true,
					title: Loc.getMessage('QR_EXTERNAL_AUTH'),
				});
			};
			const unhandled = Application.getUnhandledUniversalLink();
			if (unhandled)
			{
				handler(unhandled);
			}
			Application.on('universalLinkReceived', handler);
		},
		authorizeByUrl(url, redirectUrl = '')
		{
			return new Promise((resolve, reject) => {
				if (url && url.startsWith(qrauth.urlTemplate))
				{
					const path = url.replace(qrauth.urlTemplate, '');
					const [siteId, uniqueId, channelTag] = path.split('/');
					BX.ajax.runAction(
						'main.qrcodeauth.pushToken',
						{
							data: {
								channelTag, siteId, uniqueId, redirectUrl,
							},
						},
					).then(({ status, errors }) => {
						if (status === 'success')
						{
							resolve();
						}
						else
						{
							reject(errors[0]);
						}
					}).catch(({ errors }) => {
						if (errors.length > 0)
						{
							reject(errors[0]);
						}
					});
				}
				else
				{
					reject({ message: Loc.getMessage('WRONG_QR') });
				}
			});
		},
	};

	module.exports = { qrauth };
});

(function() {
	const require = (ext) => jn.require(ext);
	const { qrauth } = require('qrauth/utils');

	jnexport([qrauth, 'qrauth']);
})();

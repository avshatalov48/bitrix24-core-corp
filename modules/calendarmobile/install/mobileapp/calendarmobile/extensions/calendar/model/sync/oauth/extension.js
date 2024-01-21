/**
 * @module calendar/model/sync/oauth
 */
jn.define('calendar/model/sync/oauth', (require, exports, module) => {
	const { OAuthSession } = require('native/oauth');
	const { Feature } = require('feature');

	class Oauth
	{
		constructor(props)
		{
			this.connectionLink = props.connectionLink;
		}

		run()
		{
			if (!Feature.isOAuthSupported())
			{
				Feature.showDefaultUnsupportedWidget();

				return;
			}

			// eslint-disable-next-line consistent-return
			return (new OAuthSession(this.connectionLink)).start();
		}
	}

	module.exports = { Oauth };
});
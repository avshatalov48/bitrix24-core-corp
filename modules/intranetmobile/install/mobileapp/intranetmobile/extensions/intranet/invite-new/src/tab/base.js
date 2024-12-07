/**
 * @module intranet/invite-new/src/tab/base
 */
jn.define('intranet/invite-new/src/tab/base', (require, exports, module) => {
	const { makeLibraryImagePath } = require('asset-manager');
	const { Indent } = require('tokens');

	/**
	 * @abstract
	 */
	class BaseTab
	{
		constructor(props)
		{
			this.props = props;
		}

		renderTabContent()
		{
			return View();
		}

		renderButton()
		{
			return View();
		}

		renderGraphics(type = 'link')
		{
			const fileName = type === 'admin-email-not-confirmed' ? 'admin-email-not-confirmed.svg' : `invite-by-${type}.svg`;
			const uri = makeLibraryImagePath(fileName, 'invite', 'intranet');

			return View(
				{
					style: {
						width: '100%',
						height: 140,
						justifyContent: 'center',
						alignItems: 'center',
						marginVertical: Indent.XL3.toNumber(),
					},
				},
				Image({
					style: {
						width: 148,
						height: 110,
					},
					svg: {
						resizeMode: 'contain',
						uri,
					},
				}),
			);
		}
	}

	module.exports = { BaseTab };
});

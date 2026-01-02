/**
 * @module im/messenger/controller/sidebar-v2/tabs/audio/src/ui/owner
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/audio/src/ui/owner', (require, exports, module) => {
	const { ChatAvatar } = require('im/messenger/lib/ui/avatar');
	const { Text6 } = require('ui-system/typography/text');
	const { Color, Indent } = require('tokens');

	/**
	 * @param {number} id
	 * @param {string} name
	 * @param {string} testId
	 * @returns {View}
	 */
	function Owner(id, name, testId)
	{
		return View(
			{
				style: {
					flexDirection: 'row',
					alignItems: 'center',
					alignContent: 'center',
					justifyContent: 'flex-start',
					marginTop: Indent.XS2.toNumber(),
				},
			},
			ChatAvatar({
				testId: `${testId}-user-avatar`,
				dialogId: id,
				size: 18,
				isNotes: false,
			}),
			Text6({
				testId: `${testId}-user-name`,
				text: name,
				color: Color.base4,
				ellipsize: 'end',
				style: {
					marginLeft: Indent.XS.toNumber(),
				},
			}),
		);
	}

	module.exports = {
		Owner,
	};
});

/**
 * @module text-editor/entity-selector
 */
jn.define('text-editor/entity-selector', (require, exports, module) => {
	const { Type } = require('type');
	const { Mention } = require('text-editor/entities/mention');

	class EntitySelector
	{
		constructor(props = {})
		{
			/**
			 * @private
			 */
			this.props = { ...props };
		}

		/**
		 * Shows entity selector dialog
		 * @returns {
		 * 		Promise<
		 *     		Array<Mention>
		 * 		>
		 * }
		 */
		show()
		{
			const recipientList = new RecipientList(
				['users', 'groups', 'departments'],
			);

			const typesMap = {
				user: 'user',
				group: 'project',
				department: 'department',
			};

			recipientList.ui.on('close', this.props?.onClose);

			return recipientList
				.open({ returnShortFormat: false })
				.then((recipients) => {
					return Object.entries(recipients)
						.reduce((acc, [recipientScopeId, scopeRecipients]) => {
							if (Type.isArrayFilled(scopeRecipients))
							{
								const type = typesMap[recipientScopeId.slice(0, -1)];
								scopeRecipients.forEach((data) => {
									acc.push(
										new Mention({ type, data }),
									);
								});
							}

							return acc;
						}, []);
				});
		}
	}

	module.exports = {
		EntitySelector,
	};
});

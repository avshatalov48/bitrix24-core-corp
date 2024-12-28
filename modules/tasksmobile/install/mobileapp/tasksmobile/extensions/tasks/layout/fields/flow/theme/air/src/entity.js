/**
 * @module tasks/layout/fields/flow/theme/air/src/entity
 */
jn.define('tasks/layout/fields/flow/theme/air/src/entity', (require, exports, module) => {
	const { AvatarEntityType } = require('ui-system/blocks/avatar');
	const { AirThemeEntity } = require('layout/ui/fields/entity-selector/theme/air');

	/**
	 * @class FlowAirThemeEntity
	 */
	class FlowAirThemeEntity extends AirThemeEntity
	{
		getAvatarParams()
		{
			return {
				...super.getAvatarParams(),
				testId: 'flow-avatar',
				withRedux: false,
				entityType: AvatarEntityType.GROUP,
			};
		}
	}

	module.exports = {
		FlowAirThemeEntity,
	};
});

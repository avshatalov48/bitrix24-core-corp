/**
 * @module ui-system/blocks/avatar/src/enums/entity-type-enum
 */
jn.define('ui-system/blocks/avatar/src/enums/entity-type-enum', (require, exports, module) => {
	const { Type } = require('type');
	const { isNil } = require('utils/type');
	const { BaseEnum } = require('utils/enums/base');
	const { AvatarShape } = require('ui-system/blocks/avatar/src/enums/shape-enum');
	const { AvatarAccentGradient } = require('ui-system/blocks/avatar/src/enums/accent-gradient-enum');

	/**
	 * @class AvatarEntityType
	 * @template TAvatarEntityType
	 * @extends {BaseEnum<AvatarEntityType>}
	 */
	class AvatarEntityType extends BaseEnum
	{
		static COLLAB = new AvatarEntityType('COLLAB', {
			shape: AvatarShape.HEXAGON,
			emptyAvatar: 'person_green.svg',
			accentGradient: AvatarAccentGradient.GREEN,
		});

		static GROUP = new AvatarEntityType('GROUP', {
			emptyAvatar: 'person.svg',
			shape: AvatarShape.CIRCLE,
			accentGradient: AvatarAccentGradient.BLUE,
		});

		static USER = new AvatarEntityType('USER', {
			emptyAvatar: 'person.svg',
			shape: AvatarShape.CIRCLE,
			accentGradient: AvatarAccentGradient.BLUE,
		});

		static EXTRANET = new AvatarEntityType('EXTRANET', {
			emptyAvatar: 'person.svg',
			shape: AvatarShape.CIRCLE,
		});

		static resolveType(value, defaultEnum)
		{
			if (isNil(value))
			{
				return AvatarEntityType.resolve(defaultEnum, AvatarEntityType.USER);
			}

			if (AvatarEntityType.has(value))
			{
				return AvatarEntityType.resolve(value, defaultEnum);
			}

			const enumKey = AvatarEntityType.getKeys().find((key) => {
				return Type.isStringFilled(value) && key.toLowerCase() === value.toLowerCase();
			});

			return AvatarEntityType.resolve(AvatarEntityType.getEnum(enumKey), AvatarEntityType.USER);
		}
	}

	module.exports = { AvatarEntityType };
});

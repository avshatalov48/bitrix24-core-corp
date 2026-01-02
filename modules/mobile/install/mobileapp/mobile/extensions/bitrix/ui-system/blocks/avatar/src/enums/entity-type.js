/**
 * @module ui-system/blocks/avatar/src/enums/entity-type
 */
jn.define('ui-system/blocks/avatar/src/enums/entity-type', (require, exports, module) => {
	const { Type } = require('type');
	const { Color } = require('tokens');
	const { isNil } = require('utils/type');
	const { BaseEnum } = require('utils/enums/base');
	const { EmptyAvatar } = require('ui-system/blocks/avatar/src/enums/empty-avatar');
	const { AvatarShape } = require('ui-system/blocks/avatar/src/enums/shape');
	const { AvatarAccentGradient } = require('ui-system/blocks/avatar/src/enums/accent-gradient');

	/**
	 * @class AvatarEntityType
	 * @template TAvatarEntityType
	 * @extends {BaseEnum<AvatarEntityType>}
	 */
	class AvatarEntityType extends BaseEnum
	{
		static COLLAB = new AvatarEntityType('COLLAB', {
			accent: true,
			shape: AvatarShape.CIRCLE,
			placeholder: {
				emptyAvatar: {
					uri: 'person_green.svg',
					named: EmptyAvatar.COLLAB_USER,
				},
				backgroundColor: Color.collabAccentPrimary,
			},
			accentGradient: AvatarAccentGradient.GREEN,
		});

		static GROUP = new AvatarEntityType('GROUP', {
			placeholder: {
				emptyAvatar: {
					uri: 'person.svg',
					named: EmptyAvatar.DEFAULT_USER,
				},
			},
			shape: AvatarShape.CIRCLE,
			accentGradient: AvatarAccentGradient.BLUE,
		});

		static USER = new AvatarEntityType('USER', {
			placeholder: {
				emptyAvatar: {
					uri: 'person.svg',
					named: EmptyAvatar.DEFAULT_USER,
				},
			},
			shape: AvatarShape.CIRCLE,
			accentGradient: AvatarAccentGradient.BLUE,
		});

		static EXTRANET = new AvatarEntityType('EXTRANET', {
			accent: true,
			placeholder: {
				emptyAvatar: {
					uri: 'person_orange.svg',
					named: EmptyAvatar.EXTRANET_USER,
				},
				backgroundColor: Color.accentMainWarning,
			},
			shape: AvatarShape.CIRCLE,
			accentGradient: AvatarAccentGradient.ORANGE,
		});

		static resolveType(value)
		{
			const defaultEnum = AvatarEntityType.USER;

			if (isNil(value))
			{
				return defaultEnum;
			}

			if (AvatarEntityType.has(value))
			{
				return AvatarEntityType.resolve(value, defaultEnum);
			}

			const enumKey = AvatarEntityType.getKeys().find((key) => {
				return Type.isStringFilled(value) && key.toLowerCase() === value.toLowerCase();
			});

			return AvatarEntityType.resolve(AvatarEntityType.getEnum(enumKey), defaultEnum);
		}

		isCollab()
		{
			return this.equal(AvatarEntityType.COLLAB);
		}

		isExtranet()
		{
			return this.equal(AvatarEntityType.EXTRANET);
		}
	}

	module.exports = { AvatarEntityType };
});

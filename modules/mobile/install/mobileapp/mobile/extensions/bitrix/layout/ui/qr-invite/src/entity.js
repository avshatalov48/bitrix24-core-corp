/**
 * @module layout/ui/qr-invite/src/entity
 */
jn.define('layout/ui/qr-invite/src/entity', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');
	const { Loc } = require('loc');
	const { Color } = require('tokens');
	const { AvatarEntityType, AvatarShape } = require('ui-system/blocks/avatar');

	class QrEntity extends BaseEnum
	{
		static COLLAB = new QrEntity('COLLAB', {
			entityType: AvatarEntityType.COLLAB,
			shape: AvatarShape.HEXAGON,
			textColor: Color.collabAccentPrimary,
			accent: true,
			borderColor: {
				start: Color.collabAccentLess2.toHex(),
				middle: Color.collabAccentLess2.toHex(),
				end: Color.collabAccentLess2.toHex(),
				angle: 65,
			},
			entityName: Loc.getMessage('M_UI_QR_INVITE_ENTITY_GROUP_COLLAB'),
			bottomText: Loc.getMessage('M_UI_QR_INVITE_DESCRIPTION_COLLAB'),
		});

		static GROUP_CHAT = new QrEntity('GROUP_CHAT', {
			entityType: AvatarEntityType.GROUP,
			shape: AvatarShape.CIRCLE,
			textColor: Color.accentMainPrimaryalt,
			accent: false,
			borderColor: {
				start: '#75E4BD',
				middle: '#2CAAF5',
				end: '#0B83FF',
				angle: 65,
			},
			entityName: Loc.getMessage('M_UI_QR_INVITE_ENTITY_GROUP_CHAT'),
			bottomText: Loc.getMessage('M_UI_QR_INVITE_DESCRIPTION_GROUP_CHAT'),
		});

		static DEPARTMENT = new QrEntity('DEPARTMENT', {
			entityType: AvatarEntityType.GROUP,
			shape: AvatarShape.SQUARE,
			textColor: Color.accentMainPrimaryalt,
			accent: false,
			borderColor: {
				start: '#75E4BD',
				middle: '#2CAAF5',
				end: '#0B83FF',
				angle: 65,
			},
			entityName: Loc.getMessage('M_UI_QR_INVITE_ENTITY_GROUP_DEPARTMENT'),
			bottomText: Loc.getMessage('M_UI_QR_INVITE_DESCRIPTION_DEPARTMENT'),
		});

		static COMPANY = new QrEntity('COMPANY', {
			entityType: AvatarEntityType.GROUP,
			shape: AvatarShape.SQUARE,
			textColor: Color.accentMainPrimaryalt,
			accent: false,
			borderColor: {
				start: '#75E4BD',
				middle: '#2CAAF5',
				end: '#0B83FF',
				angle: 65,
			},
			entityName: Loc.getMessage('M_UI_QR_INVITE_ENTITY_GROUP_COMPANY'),
			bottomText: Loc.getMessage('M_UI_QR_INVITE_DESCRIPTION_DEPARTMENT'),
		});
	}

	module.exports = { QrEntity };
});

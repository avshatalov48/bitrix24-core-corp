import { BaseField } from './base-field';
import { Dom } from 'main.core';
import { AvatarRound, AvatarRoundGuest, AvatarRoundExtranet } from 'ui.avatar';

export type PhotoFieldType = {
	photoUrl: string,
	role: string,
}

export class PhotoField extends BaseField
{
	render(params: PhotoFieldType): void
	{
		const avatarOptions = {
			size: 40,
			userpicPath: params.photoUrl ? params.photoUrl : null,
		};

		let avatar = null;

		if (params.role === 'collaber')
		{
			avatar = new AvatarRoundGuest(avatarOptions);
		}
		else if (params.role === 'extranet')
		{
			avatar = new AvatarRoundExtranet(avatarOptions);
		}
		else
		{
			avatar = new AvatarRound(avatarOptions);
		}

		avatar?.renderTo(this.getFieldNode());

		Dom.addClass(this.getFieldNode(), 'user-grid_user-photo');
	}
}
import type { UserModel } from 'tasks.v2.model.users';
import type { UserDto } from './types';

export function mapDtoToModel(userDto: UserDto): UserModel
{
	return {
		id: userDto.id,
		name: userDto.name,
		image: userDto.image?.src,
		type: userDto.type,
	};
}

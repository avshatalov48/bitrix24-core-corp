import {AvatarDetail} from "../../element/types/chat-avatar";

export interface RecentCarouselItem
{
	id: `user/${number}`;
	type: 'info';
	params: {
		id: number,
		externalAuthId: string,
	};
	title: string,
	imageUrl: string,
	color: string,
	shortTitle: string,
	subtitle: string,
	styles: {
		title: {
			font: {
				color: '#ca8600',
			}
		}
	}
	avatar: AvatarDetail
}


type RecentCarouselItemUser = {
	id: number,
	externalAuthId: string,
	firstName: string,
	avatar: string,
	lastActivityDate: string,
	color: string,
	name: string,
	workPosition: string,
	extranet: boolean,
}

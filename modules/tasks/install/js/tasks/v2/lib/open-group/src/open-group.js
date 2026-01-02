import { hrefClick } from 'tasks.v2.lib.href-click';
import { groupService } from 'tasks.v2.provider.service.group-service';

export const openGroup = async (id: number, type: string): Promise<void> => {
	const href = await groupService.getUrl(id, type);

	hrefClick(href);
};

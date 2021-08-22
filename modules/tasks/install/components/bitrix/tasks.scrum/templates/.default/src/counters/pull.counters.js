import {Filter} from '../service/filter';

type Params = {
	filterService: Filter,
	userId: number,
	groupId: number
}

export class PullCounters
{
	constructor(params: Params)
	{
		this.filterService = params.filterService;
		this.userId = params.userId;
		this.groupId = params.groupId;
	}

	getModuleId()
	{
		return 'tasks';
	}

	getMap()
	{
		return {
			comment_read_all: this.onCommentsReadAll.bind(this)
		};
	}

	onCommentsReadAll(data)
	{
		const groupId = data.GROUP_ID;

		console.log(data);
		console.log(groupId && groupId === this.groupId);
		if (groupId && groupId === this.groupId)
		{
			this.filterService.applyFilter();
		}
	}
}
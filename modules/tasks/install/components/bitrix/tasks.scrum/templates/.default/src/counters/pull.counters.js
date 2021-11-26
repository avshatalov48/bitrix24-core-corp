import {Filter} from '../service/filter';
import {Item} from '../item/item';
import {RequestSender} from '../utility/request.sender';
import {EntityStorage} from '../utility/entity.storage';

type Params = {
	requestSender: RequestSender,
	entityStorage: EntityStorage,
	filterService: Filter,
	userId: number,
	groupId: number
}

export class PullCounters
{
	constructor(params: Params)
	{
		this.requestSender = params.requestSender;
		this.entityStorage = params.entityStorage;
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
			task_view: this.onTaskView.bind(this),
			project_read_all: this.onCommentsReadAll.bind(this)
		};
	}

	onTaskView(data)
	{
		const inputTaskId = parseInt(data.TASK_ID, 10);
		const inputUserId = parseInt(data.USER_ID, 10);

		if (inputUserId !== this.userId)
		{
			return;
		}

		const item = this.entityStorage.findItemBySourceId(inputTaskId);

		if (item)
		{
			this.requestSender.getCurrentState({
				taskId: item.getSourceId()
			}).then((response) => {
				item.updateYourself(new Item(response.data.itemData));
			}).catch((response) => {
				this.requestSender.showErrorAlert(response);
			});
		}
	}

	onCommentsReadAll(data)
	{
		const groupId = data.GROUP_ID;

		if (groupId && groupId === this.groupId)
		{
			this.filterService.applyFilter();
		}
	}
}
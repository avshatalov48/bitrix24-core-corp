import {Kanban} from 'main.kanban';
import createStub from './internal/create-stub';

export default class DropZone extends Kanban.DropZone
{
	getContainer()
	{
		return createStub();
	}
}
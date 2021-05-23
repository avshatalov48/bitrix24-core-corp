import {Kanban} from 'main.kanban';
import createStub from './internal/create-stub';

export default class DropZoneArea extends Kanban.DropZoneArea
{
	getContainer()
	{
		return createStub();
	}
}
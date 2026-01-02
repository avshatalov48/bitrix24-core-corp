import { RelatedEntityData } from '../../../dialog-opener/dialog-opener';
import { DialoguesModelState } from '../../../../model/dialogues/src/types';
import { IMessageMenuMessage, IMessageMenuView } from '../../../../controller/dialog/lib/message-menu/types';

export type MessageMenuContext = {
	getDialog: () => DialoguesModelState;
	relatedEntity: RelatedEntityData;
}

export interface IMessageContextMenu {
	getDialog: () => DialoguesModelState;
	relatedEntity: RelatedEntityData;
	getActions(): Record<string, (menu: IMessageMenuView, message: IMessageMenuMessage) => void>,
	getActionHandlers(): Record<string, (message: IMessageMenuMessage) => void>,
	getOrderedActions(message: IMessageMenuMessage): Promise<string[]>,
}

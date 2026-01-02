import { DialoguesModelState } from '../../../../model/dialogues/src/types';
import { RelatedEntityData } from '../../../dialog-opener/dialog-opener';

export interface IDialogHeaderTitle {
	getDialog: () => DialoguesModelState;
	relatedEntity: RelatedEntityData;
	createTitleParams(): JNWidgetTitleParams;
}

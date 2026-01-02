import { DialoguesModelState } from '../../../../model/dialogues/src/types';
import { RelatedEntityData } from '../../../dialog-opener/dialog-opener';
import { DialogHeaderButton } from '../../../../controller/dialog/lib/header/buttons/buttons/extension';

export interface IDialogHeaderButtons {
	getDialog: () => DialoguesModelState;
	relatedEntity: RelatedEntityData;
	getButtons(): Array<DialogHeaderButton>;
	tapHandler(buttonId: string): void;
}
import {DialogType} from "../../../model/types/dialogues";

declare type ListByDialogTypeFilter = {
	dialogTypes?: Array<DialogType>,
	exceptDialogTypes?: Array<DialogType>,
	lastActivityDate?: string,
	limit: number,
}

declare type PinnedListByDialogTypeFilter = {
	dialogTypes?: Array<DialogType>,
	exceptDialogTypes?: Array<DialogType>,
}

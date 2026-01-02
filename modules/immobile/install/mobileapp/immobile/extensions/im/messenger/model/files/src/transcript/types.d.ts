import { MessengerModel, PayloadData } from '../../../base';

declare type FileId = number;

declare type TranscriptModelState = {
	fileId: FileId,
	messageId: number,
	chatId: number,
	text: string,
	status: TranscriptStatus
};

declare type TranscriptStatus = 'ready' | 'progress' | 'expanded' | 'error';

declare type TranscriptModelCollection = {
	collection: Record<FileId, TranscriptModelState>
}

declare type TranscriptMessengerModel = MessengerModel<TranscriptModelCollection>;

declare type TranscriptModelActions = 'filesModel/transcriptModel/set'
	| 'filesModel/transcriptModel/toggleText'
	| 'filesModel/transcriptModel/setFromLocalDatabase'
	| 'filesModel/transcriptModel/delete'
	| 'filesModel/transcriptModel/deleteByChatId';

declare type TranscriptModelMutation = 'filesModel/transcriptModel/add'
	| 'filesModel/transcriptModel/update'
	| 'filesModel/transcriptModel/delete'
	| 'filesModel/transcriptModel/deleteByChatId';

declare type TranscriptAddActions = 'set';
declare interface TranscriptAddData extends PayloadData
{
	transcriptList: TranscriptModelState[]
}

declare type TranscriptUpdateActions = 'set' | 'toggleText';
declare interface TranscriptUpdateData extends PayloadData
{
	transcriptList: TranscriptModelState[]
}

declare type TranscriptDeleteActions = 'delete';
declare interface TranscriptDeleteData extends PayloadData
{
	fileId: FileId
}

declare type TranscriptDeleteByChatIdActions = 'deleteByChatId';
declare interface TranscriptDeleteByChatIdData extends PayloadData
{
	chatId: number
}

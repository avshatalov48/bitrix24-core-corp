import {
	TodoEditorBlocksAddress,
	TodoEditorBlocksCalendar,
	TodoEditorBlocksClient,
	TodoEditorBlocksFile,
	TodoEditorBlocksLink,
} from './block/index';

export default class BlockFactory
{
	static getInstance(id: string)
	{
		if (id === TodoEditorBlocksCalendar.methods.getId())
		{
			return TodoEditorBlocksCalendar;
		}

		if (id === TodoEditorBlocksClient.methods.getId())
		{
			return TodoEditorBlocksClient;
		}

		if (id === TodoEditorBlocksLink.methods.getId())
		{
			return TodoEditorBlocksLink;
		}

		if (id === TodoEditorBlocksFile.methods.getId())
		{
			return TodoEditorBlocksFile;
		}

		if (id === TodoEditorBlocksAddress.methods.getId())
		{
			return TodoEditorBlocksAddress;
		}

		throw new Error(`Unknown block id: ${id}`);
	}
}

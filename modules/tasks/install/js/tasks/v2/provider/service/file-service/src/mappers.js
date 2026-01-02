import type { UploaderFileInfo } from 'ui.uploader.core';
import type { FileDto } from './types';

export function mapDtoToModel(fileDto: FileDto): UploaderFileInfo
{
	return {
		serverFileId: fileDto.id,
		serverId: fileDto.serverId,
		type: fileDto.type,
		name: fileDto.name,
		size: fileDto.size,
		width: fileDto.width,
		height: fileDto.height,
		isImage: fileDto.isImage,
		isVideo: fileDto.isVideo,
		treatImageAsFile: fileDto.treatImageAsFile,
		downloadUrl: fileDto.downloadUrl,
		serverPreviewUrl: fileDto.serverPreviewUrl,
		serverPreviewWidth: fileDto.serverPreviewWidth,
		serverPreviewHeight: fileDto.serverPreviewHeight,
		customData: fileDto.customData,
		viewerAttrs: fileDto.viewerAttrs,
	};
}

<?php declare(strict_types=1);

namespace Bitrix\AI\Controller;

use Bitrix\AI\Parameter\DefaultParameter;
use Bitrix\AI\Services\ImageService;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\BFile;
use Bitrix\Main\Engine\ActionFilter\Authentication;

class ImageController extends Controller
{
	public function getDefaultPreFilters(): array
	{
		return [
			new Authentication(),
		];
	}

	public function getAutoWiredParameters(): array
	{
		return [
			new DefaultParameter()
		];
	}

	public function getImgAction(int $id, string $hashId, ImageService $imageService): ?BFile
	{
		return $imageService->getImg($id, $hashId);
	}
}

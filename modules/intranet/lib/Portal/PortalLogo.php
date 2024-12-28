<?php

namespace Bitrix\Intranet\Portal;

use Bitrix\Intranet\Service\PortalSettings;
use Bitrix\Main\Error;
use Bitrix\Intranet\Portal\Settings\LogoSettings;

class PortalLogo
{
	public const LOGO_PRESETS = [
		'logo' => [222, 55], // default
		'2x' => [444, 110] // retina
	];
	private LogoSettings $logoSettings;
	private ?string $logoId;
	private ?string $logoRetinaId;

	public function __construct(PortalSettings $settingsService)
	{
		$this->logoSettings = $settingsService->logoSettings();

		$this->logoId = $this->logoSettings->getLogoId();
		$this->logoRetinaId = $this->logoSettings->getLogoRetinaId();
	}

	public function getLogo(): ?array
	{
		$result = null;

		if ($this->logoId && $image = \CFile::_GetImgParams($this->logoId))
		{
			$result = [
				'id' => $this->logoId,
				'src' => $image['SRC'],
				'width' => $image['WIDTH'],
				'height' => $image['HEIGHT'],
			];

			if ($this->logoRetinaId && $image = \CFile::_GetImgParams($this->logoRetinaId))
			{
				$result['srcset'] = $image['SRC'];
			}
		}

		return $result;
	}

	/**
	 * @param array $files from Request
	 * @return void
	 */
	public function saveLogo(array $files): void
	{
		if ($this->logoSettings->canCurrentUserEdit())
		{
			$file = array_combine(array_keys($files), array_column($files, 'logo_file'));
			$result = $this->saveLogoFile($file);

			if ($result->isSuccess())
			{
				$this->setLogoSettings(...array_values($result->getId()));
			}
		}
	}

	private function setLogoSettings(int $logo, ?int $logo2x = null)
	{
		$this->removeLogo();
		$this->logoId = $logo;
		$this->logoSettings->setLogoId($logo);

		if (!empty($logo2x))
		{
			$this->logoRetinaId = $logo2x;
			$this->logoSettings->setLogoRetinaId($logo2x);
		}
	}

	public function removeLogo(): void
	{
		if ($this->logoSettings->canCurrentUserEdit())
		{
			\CFile::Delete($this->logoId);
			\CFile::Delete($this->logoRetinaId);

			$this->logoId = null;
			$this->logoRetinaId = null;

			$this->logoSettings->setLogoId(0);
			$this->logoSettings->setLogoRetinaId(0);
		}
	}

	private function saveLogoFile(array $file): \Bitrix\Main\ORM\Data\AddResult
	{
		$result = new \Bitrix\Main\Entity\AddResult();

		$file['MODULE_ID'] = 'bitrix24';
		$ids = [];
		foreach (self::LOGO_PRESETS as $presetId => [$width, $height])
		{
			$saveFile = $file;
			$saveFile['name'] = 'logo_' . \Bitrix\Main\Security\Random::getString(10) . '.png';
			$enough = true;

			if (\CFile::CheckImageFile($saveFile, 0, $width, $height) !== null)
			{
				$enough = false;
				\CFile::ResizeImage($saveFile, ['width' => $width, 'height' => $height]);
			}

			if (!($id = (int)\CFile::SaveFile($saveFile, 'bitrix24')))
			{
				global $APPLICATION;
				$exception = $APPLICATION->GetException();
				$result->addError(new Error($exception ? $exception->GetString() : ''));
				break;
			}
			else
			{
				$ids[$presetId] = $id;
			}

			if ($enough)
			{
				break;
			}
		}

		if ($result->isSuccess())
		{
			$result->setId($ids);
		}
		else
		{
			foreach ($ids as $id)
			{
				\CFile::Delete($id);
			}
		}

		return $result;
	}
}
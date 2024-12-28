<?php

namespace Bitrix\Intranet\User\Grid\Row\Assembler\Field\JsFields;

use Bitrix\Intranet\User\Grid\Row\Assembler\Field\CustomUserFieldAssembler;
use Bitrix\Intranet\User\Grid\Settings\UserSettings;
use Bitrix\Main\Grid\Settings;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Web\Json;

abstract class JsExtensionFieldAssembler extends CustomUserFieldAssembler
{
	private string $extensionClassName;
	private string $extensionName;

	public function __construct(array $columnIds, ?Settings $settings = null)
	{
		parent::__construct($columnIds, $settings);
		$this->extensionClassName = $this->getExtensionClassName();
		$this->extensionName = $settings?->getExtensionName();
	}

	abstract protected function getExtensionClassName(): string;
	abstract protected function getRenderParams($rawValue): array;

	protected function prepareColumn($value): mixed
	{
		if (!$this->extensionName)
		{
			return $value;
		}

		$renderParams = Json::encode($this->getRenderParams($value));
		$fieldId = Random::getString('6');
		$extensionParams = [
			'fieldId' => $fieldId,
			'gridId' => $this->getSettings()->getID(),
		];
		$extensionParams = Json::encode($extensionParams);

		$extension = $this->extensionName;
		$className = $this->extensionClassName;

		$script = "(new BX.$extension.$className($extensionParams)).render($renderParams)";

		return "<div class='user-grid_custom-field-container' id='$fieldId'></div><script>$script</script>";
	}
}
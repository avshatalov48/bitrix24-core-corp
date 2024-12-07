<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

trait SignUiComponent
{
	/**
	 * Includes error's component by template's code.
	 * @param string $errorCode Error code.
	 * @param int|null $limit Restrict errors count.
	 * @return void
	 */
	public function includeError(string $errorCode, ?int $limit = null): void
	{
		$componentName = 'bitrix:sign.ui';
		$uiCmp = new \CBitrixComponent();
		$uiCmp->initComponent($componentName);
		$uiCmp->arResult = $this->arResult;
		$uiCmp->arResult['ERRORS'] = \Bitrix\Sign\Error::getInstance()->getErrors();

		if ($uiCmp->arResult['ERRORS'] && $limit)
		{
			$uiCmp->arResult['ERRORS'] = array_slice($uiCmp->arResult['ERRORS'], 0, $limit);
		}

		$uiCmp->includeComponentTemplate($errorCode);
	}

	/**
	 * Adds to exist page property additional value.
	 * @param string $code Property code.
	 * @param string $value Property added value.
	 * @return void
	 */
	public function addToPageProperty(string $code, string $value): void
	{
		$app = \Bitrix\Sign\Main\Application::getInstance();
		$currValue = $app->getPageProperty($code);
		$app->setPageProperty($code, ($currValue ? $currValue . ' ' : '') . $value);
	}

	/**
	 * Registers urls to open in sidepanel.
	 * @param string[] $urls Array of urls.
	 * @param array $options Sidepanel's options.
	 * @throws \Bitrix\Main\LoaderException
	 * @return void
	 */
	public function registerSidePanels(array $urls, array $options = [])
	{
		static $register = false;

		if (!$register)
		{
			$register = true;
			\Bitrix\Main\UI\Extension::load(['sidepanel']);
		}

		$urls = array_filter($urls, fn ($url) => !empty($url));
		if (!$urls)
		{
			return;
		}

		$options['allowChangeHistory'] = false;
		$options['cacheable'] = false;
		$urls = preg_replace(['/(#[^#]+#)/', '/\?/'], ['(\d+)', '\?'], $urls);

		$options = json_encode($options);
		$encodedUrls = json_encode($urls);
		$stopParameters = json_encode(['IFRAME']);

		echo <<<HTML
			<script>
				BX.SidePanel.Instance.bindAnchors({
					rules: [
						{
							condition: $encodedUrls,
							options: $options,
							stopParameters: $stopParameters
						}
					]
				})
			</script>
		HTML;
	}
}

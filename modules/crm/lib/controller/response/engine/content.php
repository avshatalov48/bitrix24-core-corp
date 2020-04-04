<?php
namespace Bitrix\Crm\Controller\Response\Engine;

use Bitrix\Main\Engine\Response\Json;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Errorable;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Page\AssetMode;
use Bitrix\Crm\Controller\Response\Entity\ContentInterface;

/**
 * Response type for rendering ajax html content from action
 */
final class Content extends Json implements Errorable
{
	const STATUS_SUCCESS = 'success';
	const STATUS_DENIED  = 'denied';
	const STATUS_ERROR   = 'error';

	private $jsPathList = array();
	private $cssPathList = array();
	private $stringPathList = array();

	/**
	 * @var string
	 */
	private $status;

	/**
	 * @var ErrorCollection
	 */
	private $errorCollection;


	/**
	 * Component constructor.
	 * @param ContentInterface $content
	 * @param string $status
	 * @param ErrorCollection|null $errorCollection
	 * @param array $additionalResponseParams
	 */
	public function __construct(ContentInterface $content, $status = self::STATUS_SUCCESS, ErrorCollection $errorCollection = null, array $additionalResponseParams = [])
	{
		global $APPLICATION;

		$this->status = $status?: self::STATUS_SUCCESS;
		$this->errorCollection = $errorCollection?: new ErrorCollection;

		$APPLICATION->ShowAjaxHead();
		$html = $content->getHtml();
		$this->collectAssetsPathList();

		$this->setData([
			'status' => $this->status,
			'data' => [
				'html' => $html,
				'assets' => [
					'css' => $this->getCss(),
					'js' => $this->getJsList(),
					'string' => $this->getStringList()
				]
			],
			'additionalParams' => $additionalResponseParams,
			'errors' => $this->getErrorsToResponse(),
		]);
	}

	private function collectAssetsPathList()
	{
		Asset::getInstance()->getJs();
		$this->jsPathList = Asset::getInstance()->getTargetList('JS');
	}

	/**
	 * @return array
	 */
	private function getJsList()
	{
		$jsList = array();

		foreach($this->jsPathList as $targetAsset)
		{
			$assetInfo = Asset::getInstance()->getAssetInfo($targetAsset['NAME'], AssetMode::ALL);
			if (!empty($assetInfo['JS']))
			{
				$jsList = array_merge($jsList, $assetInfo['JS']);
			}
		}

		return $jsList;
	}

	/**
	 * @return string
	 */
	private function getCss()
	{
		return Asset::getInstance()->getCss();
	}

	/**
	 * @return array
	 */
	private function getStringList()
	{
		$stringList = array();
		foreach($this->cssPathList as $targetAsset)
		{
			$assetInfo = Asset::getInstance()->getAssetInfo($targetAsset['NAME'], AssetMode::ALL);
			if (!empty($assetInfo['STRINGS']))
			{
				$stringList = array_merge($stringList, $assetInfo['STRINGS']);
			}
		}

		foreach($this->jsPathList as $targetAsset)
		{
			$assetInfo = Asset::getInstance()->getAssetInfo($targetAsset['NAME'], AssetMode::ALL);
			if (!empty($assetInfo['STRINGS']))
			{
				$stringList = array_merge($stringList, $assetInfo['STRINGS']);
			}
		}
		return $stringList;
	}

	/**
	 * @return array
	 */
	protected function getErrorsToResponse()
	{
		$errors = array();
		foreach ($this->errorCollection as $error)
		{
			/** @var Error $error */
			$errors[] = array(
				'message' => $error->getMessage(),
				'code' => $error->getCode(),
			);
		}

		return $errors;
	}

	/**
	 * Getting array of errors.
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * Getting once error with the necessary code.
	 * @param string $code Code of error.
	 * @return Error
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}
}
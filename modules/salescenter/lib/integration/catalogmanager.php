<?php

namespace Bitrix\SalesCenter\Integration;

use Bitrix\Catalog;
use Bitrix\Catalog\ProductCompilationTable;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Error;
use Bitrix\Main\Web\Json;

/**
 * Class CatalogManager
 * @package Bitrix\SalesCenter\Integration
 */
class CatalogManager extends Base
{
	protected const COMPILATION_POSTFIX = '_compilation';

	/**
	 * @return string
	 */
	protected function getModuleName()
	{
		return 'catalog';
	}

	public function getLinkToProductCompilation(int $compilationId, array $productIds): Result
	{
		$result = new Result();

		$compilationHashString = $this->encodeCompilationId($compilationId)->getData()['COMPILATION_HASH'];
		$compilationUrlInfo = LandingManager::getInstance()->getCollectionPublicUrlInfo([
			'compilationId' => $compilationHashString,
		]);

		$resultData = [
			'id' => $compilationUrlInfo['landingId'] ?? null,
			'link' => $compilationUrlInfo['url'] ?? null,
			'productIds' => $productIds,
		];
		$result->setData($resultData);

		return $result;
	}

	public function getLinkToProductCompilationPreview(array $productIds): Result
	{
		$result = new Result();

		$compilationUrlInfo = LandingManager::getInstance()->getCollectionPublicUrlInfo([
			'isPreviewCompilationMode' => 'Y',
		]);

		$resultData = [
			'id' => $compilationUrlInfo['landingId'] ?? null,
			'link' => $compilationUrlInfo['url'] ?? null,
			'productIds' => $productIds,
		];
		$result->setData($resultData);

		return $result;
	}

	public function getProductVariations($productIds): array
	{
		$skus = [];

		foreach ($productIds as $productId)
		{
			$sku =
				Catalog\v2\IoC\ServiceContainer::getRepositoryFacade()
					->loadVariation((int)$productId)
			;

			if (!$sku)
			{
				continue;
			}

			$skus[] = $sku;
		}

		return $skus;
	}

	public function createCompilationForDeal(int $dealId, array $productIds, ?int $chatId = null): int
	{
		$productIds = $this->prepareProductIds($productIds);

		return Catalog\ProductCompilationTable::add([
			'DEAL_ID' => $dealId,
			'PRODUCT_IDS' => Json::encode(
				array_unique(
					$productIds
				)
			),
			'CREATION_DATE' => new DateTime(),
			'CHAT_ID' => $chatId,
		])->getId();
	}

	public function getCompilationById(int $compilationId): ?array
	{
		$compilation = ProductCompilationTable::getById($compilationId)->fetch();
		if (!$compilation)
		{
			return null;
		}
		$compilation['PRODUCT_IDS'] = Json::decode($compilation['PRODUCT_IDS']);

		return $compilation;
	}

	public function getCompilationByQueueId(int $queueId): array
	{
		return ProductCompilationTable::getList([
			'filter' => [
				'=QUEUE_ID' => $queueId,
			]
		])->fetch();
	}

	public function setCompilationQueueId(int $compilationId, int $queueId): void
	{
		Catalog\ProductCompilationTable::update($compilationId, ['QUEUE_ID' => $queueId]);
	}

	public function setCompilationProducts(int $compilationId, array $productIds): void
	{
		$productIds = $this->prepareProductIds($productIds);
		Catalog\ProductCompilationTable::update(
			$compilationId,
			[
				'PRODUCT_IDS' => Json::encode(
					array_unique(
						$productIds
					)
				)
			]
		);
	}

	private function prepareProductIds(array $productIds): array
	{
		return array_map(static function($productId) {
			return (int)$productId;
		}, $productIds);
	}

	public function encodeCompilationId(int $compilationId): Result
	{
		$result = new Result();

		$compilationHash = base64_encode($compilationId . self::COMPILATION_POSTFIX);
		$result->setData(['COMPILATION_HASH' => $compilationHash]);

		return $result;
	}

	public function decodeCompilationId(string $compilationHash): Result
	{
		$result = new Result();

		$compilationDecodeHash = base64_decode($compilationHash);
		$postfixLen = mb_strlen(self::COMPILATION_POSTFIX);

		$hashPostfix = substr($compilationDecodeHash, -$postfixLen);
		if ($hashPostfix === self::COMPILATION_POSTFIX)
		{
			$compilationId = substr($compilationDecodeHash, 0, (mb_strlen($compilationDecodeHash) - $postfixLen));
			if (is_numeric($compilationId) && $compilationId > 0)
			{
				$result->setData(['COMPILATION_ID' => (int)$compilationId]);
			}
			else
			{
				$result->addError(new Error('Invalid compilation ID'));
			}
		}
		else
		{
			$result->addError(new Error('Invalid compilation ID hash structure'));
		}

		return $result;
	}
}

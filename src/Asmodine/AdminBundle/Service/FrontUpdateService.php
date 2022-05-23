<?php

namespace Asmodine\AdminBundle\Service;

use Asmodine\AdminBundle\Repository\BrandRepository;
use Asmodine\AdminBundle\Repository\CategoryRepository;
use Asmodine\CommonBundle\Api\Client;
use Asmodine\CommonBundle\Exception\ApiException;
use Buzz\Message\MessageInterface;
use Buzz\Message\Response;
use Psr\Log\LoggerInterface;

/**
 * Class UpdateFrontService.
 */
class FrontUpdateService
{
    /** @var Client */
    private $api;

    /** @var BrandRepository */
    private $brandRepository;

    /** @var CategoryRepository */
    private $categoryRepository;

    /** @var LoggerInterface */
    private $logger;

    /**
     * FrontUpdateService constructor.
     *
     * @param Client             $api
     * @param BrandRepository    $brandRepository
     * @param CategoryRepository $categoryRepository
     * @param LoggerInterface    $logger
     */
    public function __construct(Client $api, BrandRepository $brandRepository, CategoryRepository $categoryRepository, LoggerInterface $logger)
    {
        $this->api = $api;
        $this->brandRepository = $brandRepository;
        $this->categoryRepository = $categoryRepository;
        $this->logger = $logger;
    }

    /**
     * Update Brands.
     *
     * @return MessageInterface
     */
    public function updateBrands(): MessageInterface
    {
        $brands = $this->brandRepository->findAll();
        $url = $this->api->prepareUrl('front_update_brands');

        return $this->update($url, ['brands' => $brands]);
    }

    /**
     * Update Categories.
     *
     * @return MessageInterface
     */
    public function updateCategories(): MessageInterface
    {
        $categories = $this->categoryRepository->findAll();
        $url = $this->api->prepareUrl('front_update_categories');

        return $this->update($url, ['categories' => $categories]);
    }

    /**
     * @param string $url
     * @param array  $datas
     *
     * @return MessageInterface
     *
     * @throws ApiException
     */
    private function update(string $url, array $datas): MessageInterface
    {
        /** @var Response $response */
        $response = $this->api->submit($url, $datas);
        if ($response->getStatusCode() >= 300) {
            $this->logger->error(
                $response->getContent(),
                ['url' => $url, 'status_code' => $response->getStatusCode(), 'datas' => json_encode($datas)]
            );
            throw new ApiException($response->getContent(), $response->getStatusCode());
        }

        return $response;
    }
}

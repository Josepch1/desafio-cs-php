<?php

namespace Contatoseguro\TesteBackend\Controller;

use Contatoseguro\TesteBackend\Model\Product;
use Contatoseguro\TesteBackend\Service\CategoryService;
use Contatoseguro\TesteBackend\Service\ProductService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ProductController
{
    private ProductService $service;
    private CategoryService $categoryService;

    public function __construct()
    {
        $this->service = new ProductService();
        $this->categoryService = new CategoryService();
    }

    public function getAll(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $adminUserId = $request->getHeader('admin_user_id')[0];

        $stm = $this->service->getAll($adminUserId);
        $products = $stm->fetchAll();

        foreach ($products as $product) {
            $product->category = explode(',', $product->category);
        }


        $response->getBody()->write(json_encode($products));
        return $response->withStatus(200);
    }

    public function getOne(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $stm = $this->service->getOne($args['id']);
        $product = Product::hydrateByFetch($stm->fetch());

        $adminUserId = $request->getHeader('admin_user_id')[0];
        $productCategory = $this->categoryService->getProductCategory($product->id)->fetchAll();

        $productCategories = [];

        foreach ($productCategory as $category) {
            $fetchedCategory = $this->categoryService->getOne($adminUserId, $category->id)->fetch();
            $productCategories[] = $fetchedCategory->title;
        }

        $product->setCategory($productCategories);

        $response->getBody()->write(json_encode($product));
        return $response->withStatus(200);
    }

    public function insertOne(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $body = $request->getParsedBody();
        $adminUserId = $request->getHeader('admin_user_id')[0];

        if ($this->service->insertOne($body, $adminUserId)) {
            return $response->withStatus(200);
        } else {
            return $response->withStatus(404);
        }
    }

    public function updateOne(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $body = $request->getParsedBody();
        $adminUserId = $request->getHeader('admin_user_id')[0];

        if ($this->service->updateOne($args['id'], $body, $adminUserId)) {
            return $response->withStatus(200);
        } else {
            return $response->withStatus(404);
        }
    }

    public function deleteOne(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $adminUserId = $request->getHeader('admin_user_id')[0];

        if ($this->service->deleteOne($args['id'], $adminUserId)) {
            return $response->withStatus(200);
        } else {
            return $response->withStatus(404);
        }
    }

    public function getInactive(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $adminUserId = $request->getHeader('admin_user_id')[0];

        $stm = $this->service->getInactive($adminUserId);
        $products = $stm->fetchAll();

        foreach ($products as $product) {
            $product->category = explode(',', $product->category);
        }

        $response->getBody()->write(json_encode($products));
        return $response->withStatus(200);
    }

    public function getActive(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $adminUserId = $request->getHeader('admin_user_id')[0];

        $stm = $this->service->getActive($adminUserId);
        $products = $stm->fetchAll();

        foreach ($products as $product) {
            $product->category = explode(',', $product->category);
        }

        $response->getBody()->write(json_encode($products));
        return $response->withStatus(200);
    }

    public function getByCategory(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $adminUserId = $request->getHeader('admin_user_id')[0];

        $stm = $this->service->getByCategory($args['id'], $adminUserId);
        $products = $stm->fetchAll();

        foreach ($products as $product) {
            $product->category = explode(',', $product->category);
        }

        $response->getBody()->write(json_encode($products));
        return $response->withStatus(200);
    }

    public function getOrderBy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $adminUserId = $request->getHeader('admin_user_id')[0];
        $order = isset($args['order']) ? strtolower($args['order']) : 'asc';

        if (!in_array($order, ['asc', 'desc'])) {
            $order = 'asc';
        }

        $stm = $this->service->getOrderBy($adminUserId, $order);
        $products = $stm->fetchAll();

        foreach ($products as $product) {
            $product->category = explode(',', $product->category);
        }

        $response->getBody()->write(json_encode($products));
        return $response->withStatus(200);
    }
}

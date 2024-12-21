<?php

namespace Contatoseguro\TesteBackend\Controller;

use Contatoseguro\TesteBackend\Service\CompanyService;
use Contatoseguro\TesteBackend\Service\ProductService;
use DateTime;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ReportController
{
    private ProductService $productService;
    private CompanyService $companyService;

    public function __construct()
    {
        $this->productService = new ProductService();
        $this->companyService = new CompanyService();
    }

    public function generate(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $adminUserId = $request->getHeader('admin_user_id')[0];

        $data = [[
            'Id do produto',
            'Nome da Empresa',
            'Nome do Produto',
            'Valor do Produto',
            'Categorias do Produto',
            'Data de Criação',
            'Logs de Alterações'
        ]];
        $stm = $this->productService->getAll($adminUserId);
        $products = $stm->fetchAll();

        foreach ($products as $i => $product) {
            $data[$i + 1] = $this->getProductData($product);
        }

        $report = $this->generateReportTable($data);

        $response->getBody()->write($report);
        return $response->withStatus(200)->withHeader('Content-Type', 'text/html');
    }

    public function generateByProduct(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $adminUserId = $request->getHeader('admin_user_id')[0];
        $productId = $args['id'];

        $data = [[
            'Id do produto',
            'Nome da Empresa',
            'Nome do Produto',
            'Valor do Produto',
            'Categorias do Produto',
            'Data de Criação',
            'Logs de Alterações'
        ]];
        $stm = $this->productService->getOne($productId, $adminUserId);
        $product = $stm->fetch();

        $data[1] = $this->getProductData($product);

        $report = $this->generateReportTable($data);

        $response->getBody()->write($report);
        return $response->withStatus(200)->withHeader('Content-Type', 'text/html');
    }

    private function generateReportTable(array $data): string
    {
        $report = "<table style='font-size: 10px;'>";
        foreach ($data as $row) {
            $report .= "<tr>";
            foreach ($row as $column) {
                $report .= "<td>{$column}</td>";
            }
            $report .= "</tr>";
        }
        $report .= "</table>";

        return $report;
    }

    private function formatProductLogs(array $productLogs): string
    {
        if (empty($productLogs)) {
            return 'Nenhum log de alteração';
        }

        $logEntries = array_map(function ($log) {
            $formattedTimestamp = new DateTime($log->timestamp);
            return "(" . ucfirst($log->name) . ", " . ucfirst($log->action) . ", " . $formattedTimestamp->format('d/m/Y H:i:s') . ")";
        }, $productLogs);

        return implode(', ', $logEntries);
    }

    private function getProductData($product): array
    {
        $companyStm = $this->companyService->getNameById($product->company_id);
        $companyName = $companyStm->fetch()->name;

        $productLogsStm = $this->productService->getLog($product->id);
        $productLogs = $productLogsStm->fetchAll();
        $productLogsString = $this->formatProductLogs($productLogs);

        $categoryList = str_replace(',', ', ', $product->category);

        $createdAt = new DateTime($product->created_at);
        $createdAtFormatted = $createdAt->format('d/m/Y H:i:s');

        return [
            $product->id,
            $companyName,
            $product->title,
            $product->price,
            $categoryList,
            $createdAtFormatted,
            $productLogsString
        ];
    }
}

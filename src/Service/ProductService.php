<?php

namespace Contatoseguro\TesteBackend\Service;

use Contatoseguro\TesteBackend\Config\DB;

class ProductService
{
    private \PDO $pdo;
    public function __construct()
    {
        $this->pdo = DB::connect();
    }

    public function getAll($adminUserId)
    {
        $query = "
            SELECT 
                p.*,
                GROUP_CONCAT(c.title) AS category
            FROM 
                product p
            INNER JOIN 
                product_category pc ON pc.product_id = p.id
            INNER JOIN 
                category c ON c.id = pc.cat_id
            WHERE 
                p.company_id = {$adminUserId}
            GROUP BY 
                p.id;
        ";

        $stm = $this->pdo->prepare($query);

        $stm->execute();

        return $stm;
    }

    public function getOne($id)
    {
        $stm = $this->pdo->prepare("
            SELECT 
                p.*, 
                GROUP_CONCAT(c.title) AS category
            FROM 
                product p
            INNER JOIN 
                product_category pc ON pc.product_id = p.id
            INNER JOIN 
                category c ON c.id = pc.cat_id
            WHERE 
                p.id = {$id}
            GROUP BY 
                p.id;
        ");
        $stm->execute();

        return $stm;
    }

    public function insertOne($body, $adminUserId)
    {
        $stm = $this->pdo->prepare("
            INSERT INTO product (
                company_id,
                title,
                price,
                active
            ) VALUES (
                {$body['company_id']},
                '{$body['title']}',
                {$body['price']},
                {$body['active']}
            )
        ");
        if (!$stm->execute())
            return false;

        $productId = $this->pdo->lastInsertId();

        $stm = $this->pdo->prepare("
            INSERT INTO product_category (
                product_id,
                cat_id
            ) VALUES (
                {$productId},
                {$body['category_id']}
            );
        ");
        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("
            INSERT INTO product_log (
                product_id,
                admin_user_id,
                `action`
            ) VALUES (
                {$productId},
                {$adminUserId},
                'create'
            )
        ");

        return $stm->execute();
    }

    public function updateOne($id, $body, $adminUserId)
    {
        $stm = $this->pdo->prepare("
            UPDATE product
            SET company_id = {$body['company_id']},
                title = '{$body['title']}',
                price = {$body['price']},
                active = {$body['active']}
            WHERE id = {$id}
        ");
        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("
            UPDATE product_category
            SET cat_id = {$body['category_id']}
            WHERE product_id = {$id}
        ");
        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("
            INSERT INTO product_log (
                product_id,
                admin_user_id,
                `action`
            ) VALUES (
                {$id},
                {$adminUserId},
                'update'
            )
        ");

        return $stm->execute();
    }

    public function deleteOne($id, $adminUserId)
    {
        $stm = $this->pdo->prepare("
            DELETE FROM product_category WHERE product_id = {$id}
        ");
        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("DELETE FROM product WHERE id = {$id}");
        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("
            INSERT INTO product_log (
                product_id,
                admin_user_id,
                `action`
            ) VALUES (
                {$id},
                {$adminUserId},
                'delete'
            )
        ");

        return $stm->execute();
    }

    public function getLog($id)
    {
        $stm = $this->pdo->prepare("
            SELECT pl.*, au.name
            FROM product_log pl
			INNER JOIN admin_user au ON au.id = pl.admin_user_id
			WHERE pl.product_id = {$id}
        ");
        $stm->execute();

        return $stm;
    }

    public function getInactive($adminUserId)
    {
        $stm = $this->pdo->prepare("
            SELECT 
                p.*,
                GROUP_CONCAT(c.title) AS category
            FROM 
                product p
            INNER JOIN 
                product_category pc ON pc.product_id = p.id
            INNER JOIN 
                category c ON c.id = pc.cat_id
            WHERE 
                p.company_id = {$adminUserId}
                AND p.active = 0
            GROUP BY 
                p.id;
        ");
        $stm->execute();

        return $stm;
    }

    public function getActive($adminUserId)
    {
        $stm = $this->pdo->prepare("
            SELECT 
                p.*,
                GROUP_CONCAT(c.title) AS category
            FROM 
                product p
            INNER JOIN 
                product_category pc ON pc.product_id = p.id
            INNER JOIN 
                category c ON c.id = pc.cat_id
            WHERE 
                p.company_id = {$adminUserId}
                AND p.active = 1
            GROUP BY 
                p.id;
        ");
        $stm->execute();

        return $stm;
    }

    public function getByCategory($id, $adminUserId)
    {
        $stm = $this->pdo->prepare("
            SELECT 
                p.*,
                GROUP_CONCAT(c.title) AS category
            FROM 
                product p
            INNER JOIN 
                product_category pc ON pc.product_id = p.id
            INNER JOIN 
                category c ON c.id = pc.cat_id
            WHERE 
                p.company_id = {$adminUserId}
                AND c.id = {$id}
            GROUP BY 
                p.id;
        ");
        $stm->execute();

        return $stm;
    }

    public function getOrderBy($adminUserId, $order)
    {
        $stm = $this->pdo->prepare("
            SELECT 
                p.*,
                GROUP_CONCAT(c.title) AS category
            FROM 
                product p
            INNER JOIN 
                product_category pc ON pc.product_id = p.id
            INNER JOIN 
                category c ON c.id = pc.cat_id
            WHERE 
                p.company_id = {$adminUserId}
            GROUP BY 
                p.id
            ORDER BY 
                p.created_at {$order};
        ");
        $stm->execute();

        return $stm;
    }
}

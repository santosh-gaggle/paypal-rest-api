<?php
/**
 * Copyright © @Gaggle_PaypalApi All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Gaggle\PaypalApi\Api;

interface CreatePaypalExpressTokenManagementInterface
{

    /**
     * POST for createPaypalExpressToken api
     * @param string $cart_id
     * @param string $return_url
     * @param string $cancel_url
     * @param string $customer_id
     * @return array
     */
    public function createPaypalExpressToken($cart_id, $return_url, $cancel_url, $customer_id = null);
}


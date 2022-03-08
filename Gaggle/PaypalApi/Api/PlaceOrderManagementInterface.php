<?php
/**
 * Copyright © @Gaggle_PaypalApi All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Gaggle\PaypalApi\Api;

interface PlaceOrderManagementInterface
{

    /**
     * POST for placeOrder api
     * @param string $cart_id
     * @param string $customer_id
     * @return string
     */
    public function placeOrder($cart_id, $customer_id = null);
}


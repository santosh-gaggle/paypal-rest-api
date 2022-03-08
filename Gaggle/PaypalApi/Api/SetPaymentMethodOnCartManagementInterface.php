<?php
/**
 * Copyright © @Gaggle_PaypalApi All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Gaggle\PaypalApi\Api;

interface SetPaymentMethodOnCartManagementInterface
{

    /**
     * POST for setPaymentMethodOnCart api
     * @param string $cart_id
     * @param string $payment_method
     * @param string $customer_id
     * @param string $payer_id
     * @param string $token
     * @return array
     */
    public function setPaymentMethodOnCart($cart_id = false, $payment_method = false, $customer_id = null, $payer_id, $token);
}


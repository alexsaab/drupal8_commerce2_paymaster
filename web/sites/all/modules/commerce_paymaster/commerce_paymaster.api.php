<?php

/**
 * @file
 * Hook for Paymaster module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provides a ability to alter additional parameters send to Paymaster.
 *
 * Use it only when you fully understand what are you doing. This can brake
 * order registration or further payment errors.
 *
 * @param array $params
 *   An array with all additional params send to register order. For more
 *   information.
 * @param array $context
 *   An array with additional information.
 *   - payment: PaymentInterface object for current request.
 *
 * @see https://securepayments.sberbank.ru/wiki/doku.php/integration:api:rest:requests:register_cart_credit
 */
function hook_commerce_paymaster_register_order_alter(&$params, $context) {
  $params['sessionTimeoutSecs'] = 1200;
}

/**
 * @} End of "addtogroup hooks".
 */

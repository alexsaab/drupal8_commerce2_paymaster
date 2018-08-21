<?php

namespace Drupal\commerce_paymaster\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_order\Entity\Order;

/**
 * Provides the Paymaster payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "paymaster",
 *   label = @Translation("Paymaster"),
 *   display_label = @Translation("Paymaster"),
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_paymaster\PluginForm\OffsiteRedirect\PaymasterForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "maestro", "mastercard", "visa", "mir",
 *   },
 * )
 */
class Paymaster extends OffsitePaymentGatewayBase
{

    /**
     * {@inheritdoc}
     */
    public function defaultConfiguration()
    {
        return [
                'merchant_id' => '',
                'secret' => '',
                'hash_method' => '',
                'vat_product' => '',
                'vat_shipping' => '',
            ] + parent::defaultConfiguration();
    }

    /**
     * Setup configuration
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        $form['merchant_id'] = [
            '#type' => 'textfield',
            '#title' => $this->t("Merchant ID"),
            '#description' => $this->t("Visit merchant interface in Paymaster site and copy data from 'Merchant id' field"),
            '#default_value' => $this->configuration['merchant_id'],
            '#required' => TRUE,
        ];

        $form['secret'] = [
            '#type' => 'textfield',
            '#title' => $this->t("Secret word"),
            '#description' => $this->t("Visit merchant interface in Paymaster site set and copy data from 'Secret word' field"),
            '#default_value' => $this->configuration['secret'],
            '#required' => TRUE,
        ];


        $form['hash_method'] = [
            '#type' => 'select',
            '#title' => $this->t("Hash method"),
            '#description' => $this->t("Visit merchant interface in Paymaster site set and copy data from 'Hash method' field"),
            '#options' => array(
                'md5' => $this->t('MD5'),
                'sha1' => $this->t('SHA1'),
                'sha256' => $this->t('SHA256'),
            ),
            '#default_value' => $this->configuration['hash_method'],
            '#required' => TRUE,
        ];

        $form['description'] = [
            '#type' => 'textfield',
            '#title' => $this->t("Order description"),
            '#description' => $this->t("Order description on Paymaster interface"),
            '#default_value' => $this->configuration['description'],
            '#required' => TRUE,
        ];


        $form['vat_product'] = [
            '#type' => 'select',
            '#title' => $this->t("Vat rate for products"),
            '#description' => $this->t("Set vat rate for products"),
            '#options' => array(
                'vat18' => $this->t('VAT 18%'),
                'vat10' => $this->t('VAT 10%'),
                'vat118' => $this->t('VAT formula 18/118'),
                'vat110' => $this->t('VAT formula 10/110'),
                'vat0' => $this->t('VAT 0%'),
                'no_vat' => $this->t('No VAT'),
            ),
            '#default_value' => $this->configuration['vat_product'],
            '#required' => TRUE,
        ];

        $form['vat_shipping'] = [
            '#type' => 'select',
            '#title' => $this->t("Vat rate for shipping"),
            '#description' => $this->t("Set vat rate for shipping"),
            '#options' => array(
                'vat18' => $this->t('VAT 18%'),
                'vat10' => $this->t('VAT 10%'),
                'vat118' => $this->t('VAT formula 18/118'),
                'vat110' => $this->t('VAT formula 10/110'),
                'vat0' => $this->t('VAT 0%'),
                'no_vat' => $this->t('No VAT'),
            ),
            '#default_value' => $this->configuration['vat_shipping'],
            '#required' => TRUE,
        ];

        return $form;
    }

    /**
     * Validation of form
     * {@inheritdoc}
     */
    public function validateConfigurationForm(array &$form, FormStateInterface $form_state)
    {
        $values = $form_state->getValue($form['#parents']);
    }

    /**
     *
     * {@inheritdoc}
     */
    public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
    {
        // Parent method will reset configuration array and further condition will
        // fail.
        parent::submitConfigurationForm($form, $form_state);
        if (!$form_state->getErrors()) {
            $values = $form_state->getValue($form['#parents']);
            $this->configuration['merchant_id'] = $values['merchant_id'];
            $this->configuration['secret'] = $values['secret'];
            $this->configuration['hash_method'] = $values['hash_method'];
            $this->configuration['decription'] = $values['description'];
            $this->configuration['vat_product'] = $values['vat_product'];
            $this->configuration['vat_shipping'] = $values['vat_shipping'];
        }
    }

    /**
     * Notity payment callback
     * @param Request $request
     * @return null|\Symfony\Component\HttpFoundation\Response|void
     */
    public function onNotify(Request $request)
    {

        // try to get values
        $LMI_MERCHANT_ID = self::getRequest('LMI_MERCHANT_ID');
        $LMI_PAYMENT_NO = self::getRequest('LMI_PAYMENT_NO');
        $LMI_SYS_PAYMENT_ID = self::getRequest('LMI_SYS_PAYMENT_ID');
        $LMI_SYS_PAYMENT_DATE = self::getRequest('LMI_SYS_PAYMENT_DATE');
        $LMI_PAYMENT_AMOUNT = self::getRequest('LMI_PAYMENT_AMOUNT');
        $LMI_CURRENCY = self::getRequest('LMI_CURRENCY');
        $LMI_PAID_AMOUNT = self::getRequest('LMI_PAID_AMOUNT');
        $LMI_PAID_CURRENCY = self::getRequest('LMI_PAID_CURRENCY');
        $LMI_PAYMENT_SYSTEM = self::getRequest('LMI_PAYMENT_SYSTEM');
        $LMI_SIM_MODE = self::getRequest('LMI_SIM_MODE');
        $SECRET = $this->configuration['secret'];
        $hash_method = $this->configuration['hash_method'];

        $LMI_HASH = self::getRequest('LMI_HASH');
        $LMI_SIGN = self::getRequest('SIGN');

        $LMI_PAYMENT_NO = 1;

        // gert order
        if (!$LMI_PAYMENT_NO)
            die('Order load fail!');
        $order = Order::load($LMI_PAYMENT_NO);
        $order_total = self::getOrderTotalAmount($order->getTotalPrice());
        $order_currency = self::getOrderCurrencyCode($order->getTotalPrice());


        print_r($order_currency, false);

        // Check callback for pre-request
        if (self::getRequest("LMI_PREREQUEST")) {
            if (($LMI_MERCHANT_ID == $this->configuration['merchant_id']) && ($LMI_PAYMENT_AMOUNT == $order_total) && ($LMI_PAID_CURRENCY == $order_currency)) {
                echo 'YES';
                exit;
            } else {
                echo 'FAIL';
                exit;
            }
        }

        $hash = self::getHash($LMI_MERCHANT_ID, $LMI_PAYMENT_NO, $LMI_SYS_PAYMENT_ID, $LMI_SYS_PAYMENT_DATE,
            $LMI_PAYMENT_AMOUNT, $LMI_CURRENCY, $LMI_PAID_AMOUNT, $LMI_PAID_CURRENCY, $LMI_PAYMENT_SYSTEM,
            $LMI_SIM_MODE, $SECRET, $hash_method);

        $sign = self::getSign($LMI_MERCHANT_ID, $LMI_PAYMENT_NO, $LMI_PAID_AMOUNT, $LMI_PAID_CURRENCY,
            $SECRET, $hash_method);

        if ($hash === $LMI_HASH && $sign === $LMI_SIGN) {

        } else {
            echo 'FAIL';
            exit;
        }

    }


    /**
     * Callback function
     * {@inheritdoc}
     */
    public function onReturn(OrderInterface $order, Request $request)
    {
        // Get order_id in callback
        $order_id = $request->query->get('LMI_PAYMENT_NO');
        $order = Order::load($order_id);
        $order_total = number_format($order->getTotalPrice()->getNumber(), 2, '.', '');

        print($order_total);

        // Check callback for prerequest
        if ($request->query->get("LMI_PREREQUEST")) {
            if (($request->query->get("LMI_MERCHANT_ID") == $this->configuration['merchant_id']) && ($request->query->get("LMI_PAYMENT_AMOUNT") == $order_total)) {
                echo 'YES';
                exit;
            } else {
                echo 'FAIL';
                exit;
            }
        }


    }


    /**
     * Get hash
     * @param $LMI_MERCHANT_ID
     * @param $LMI_PAYMENT_NO
     * @param $LMI_SYS_PAYMENT_ID
     * @param $LMI_SYS_PAYMENT_DATE
     * @param $LMI_PAYMENT_AMOUNT
     * @param $LMI_CURRENCY
     * @param $LMI_PAID_AMOUNT
     * @param $LMI_PAID_CURRENCY
     * @param $LMI_PAYMENT_SYSTEM
     * @param $LMI_SIM_MODE
     * @param $SECRET
     * @param string $hash_method
     * @return string
     */
    public static function getHash($LMI_MERCHANT_ID, $LMI_PAYMENT_NO, $LMI_SYS_PAYMENT_ID, $LMI_SYS_PAYMENT_DATE, $LMI_PAYMENT_AMOUNT, $LMI_CURRENCY, $LMI_PAID_AMOUNT, $LMI_PAID_CURRENCY, $LMI_PAYMENT_SYSTEM, $LMI_SIM_MODE, $SECRET, $hash_method = 'md5')
    {
        $string = $LMI_MERCHANT_ID . ";" . $LMI_PAYMENT_NO . ";" . $LMI_SYS_PAYMENT_ID . ";" . $LMI_SYS_PAYMENT_DATE . ";" . $LMI_PAYMENT_AMOUNT . ";" . $LMI_CURRENCY . ";" . $LMI_PAID_AMOUNT . ";" . $LMI_PAID_CURRENCY . ";" . $LMI_PAYMENT_SYSTEM . ";" . $LMI_SIM_MODE . ";" . $SECRET;
        $hash = base64_encode(hash($hash_method, $string, true));
        return $hash;
    }


    /**
     * Get sign
     * @param $merchant_id
     * @param $order_id
     * @param $amount
     * @param $lmi_currency
     * @param $secret_key
     * @param string $sign_method
     * @return string
     */
    public static function getSign($merchant_id, $order_id, $amount, $lmi_currency, $secret_key, $hash_method = 'md5')
    {
        $plain_sign = $merchant_id . $order_id . $amount . $lmi_currency . $secret_key;
        $sign = base64_encode(hash($hash_method, $plain_sign, true));
        return $sign;
    }

    /**
     * Get post or get method
     * @param null $param
     */
    public static function getRequest($param = null)
    {
        $post = \Drupal::request()->request->get($param);
        $get = \Drupal::request()->query->get($param);
        if ($post) {
            return $post;
        }
        if ($get) {
            return $get;
        } else {
            return null;
        }
    }


    /**
     * Get order amount
     * @param \Drupal\commerce_price\Price $price
     * @return string
     */
    public static function getOrderTotalAmount(\Drupal\commerce_price\Price $price)
    {
        return number_format($price->getNumber(), 2, '.', '');
    }


    /**
     * Get order currency
     * @param \Drupal\commerce_price\Price $price
     * @return string
     */
    public static function getOrderCurrencyCode(\Drupal\commerce_price\Price $price)
    {
        return $price->getCurrencyCode();
    }


}

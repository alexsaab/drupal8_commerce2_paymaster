<?php

namespace Drupal\commerce_paymaster\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_paymaster\Plugin\Commerce\PaymentGateway\Paymaster as PM;

/**
 * Order registration and redirection to payment URL.
 */
class PaymasterForm extends BasePaymentOffsiteForm
{


    /** @var string payment url for redirect on paymaster  */
    private $payment_url = 'https://paymaster.ru/Payment/Init';

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
        $payment = $this->entity;
        /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
        $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
        $configs = $payment_gateway_plugin->getConfiguration();

        $order        = $payment->getOrder();
        $total_price  = $order->getTotalPrice();

        $data = [
            'LMI_MERCHANT_ID' => $configs['merchant_id'],
            'LMI_PAYMENT_AMOUNT' =>  $total_price ? $total_price->getNumber() : '0.00',
            'LMI_PAYMENT_DESC' => 'Платеж по заказу №'.$payment->getOrderId(),
            'LMI_PAYMENT_NO' => $payment->getOrderId(),
            'LMI_CURRENCY'=>$total_price->getCurrencyCode(),
            'LMI_PAYMENT_NOTIFICATION_URL' => $this->getNotifyUrl(),
            'LMI_SUCCESS_URL' => $form['#return_url'],
            'LMI_FAILURE_URL' => $form['#cancel_url'],
            'SIGN' => PM::getSign($configs['merchant_id'], $payment->getOrderId(), $total_price ? $total_price->getNumber() : '0.00',  $total_price->getCurrencyCode(), $configs['secret'], $configs['hash_method'])
        ];

        return $this->buildRedirectForm($form, $form_state, $this->payment_url, $data, 'post');
    }



    /**
     * {@inheritdoc}
     */
    public function getNotifyUrl() {
        return Url::fromRoute('commerce_payment.notify', [
            'commerce_payment_gateway' => $this->entityId,
        ], ['absolute' => TRUE]);
    }

}

<?php

namespace Drupal\commerce_reddotpayment\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class RedDotPaymentRedirectForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $config = $payment_gateway_plugin->getConfiguration();

    // Determine correct endpoint
    $rdp_endpoint = 'https://secure.reddotpayment.com/service/payment-api';
    if ($config['mode'] == 'test') {
      $rdp_endpoint = 'https://secure-dev.reddotpayment.com/service/payment-api';
    }

    $order = $payment->getOrder();
    // Simulate an API call failing and throwing an exception, for test purposes.
    // See PaymentCheckoutTest::testFailedCheckoutWithOffsiteRedirectGet().
    if ($order->getBillingProfile()->get('address')->family_name == 'FAIL') {
      throw new PaymentGatewayException('Could not get the redirect URL.');
    }
    $redirect_url = Url::fromRoute('commerce_reddotpayment.redirect_302', [], ['absolute' => TRUE])->toString();

    $data = [
      'return' => $form['#return_url'],
      'cancel' => $form['#cancel_url'],
      'total' => $payment->getAmount()->getNumber(),
    ];

    $form = $this->buildRedirectForm($form, $form_state, $redirect_url, $data);

    return $form;
  }

}

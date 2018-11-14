<?php

namespace Drupal\commerce_reddotpayment\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "reddotpayment_redirect",
 *   label = "Red Dot Payment Redirect",
 *   display_label = "Red Dot Payment Redirect",
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_reddotpayment\PluginForm\OffsiteRedirect\RedDotPaymentRedirectForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 * )
 */
class RedDotPaymentRedirect extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'merchant_id' => '',
      'secret_key' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['merchant_id'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Merchant ID'),
      '#description' => $this->t('Red Dot Payment Merchant ID given to you when registering for RDP account.'),
      '#default_value' => $this->configuration['merchant_id'],
    ];

    $form['secret_key'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Secret key'),
      '#description' => $this->t('Red Dot Payment secret key given to you when registering for RDP account.'),
      '#default_value' => $this->configuration['secret_key'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['merchant_id'] = $values['merchant_id'];
      $this->configuration['secret_key'] = $values['secret_key'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onCancel(OrderInterface $order, Request $request) {
    parent::onCancel($order, $request);
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    parent::onNotify($request);

  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    parent::onReturn($order, $request);

  }

  /**
   * @param $secret_key
   * @param $params
   * @return string
   */
  static public function signGeneric($secret_key, $params) {
    unset($params['signature']);

    $data_to_sign = "";
    self::recursiveGenericArraySign($params, $data_to_sign);
    $data_to_sign .= $secret_key;

    return hash('sha512', $data_to_sign);
  }

  /**
   * @param $params
   * @param $data_to_sign
   */
  static public function recursiveGenericArraySign(&$params, &$data_to_sign) {
    ksort($params);
    foreach ($params as $v) {
      if (is_array($v)) {
        self::recursiveGenericArraySign($v, $data_to_sign);
      }
      else {
        $data_to_sign .= $v;
      }
    }
  }

}

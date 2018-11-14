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
    $order = $payment->getOrder();
    $current_language = \Drupal::languageManager()->getCurrentLanguage();

    /** @var \Drupal\address\AddressInterface $billing_address */
    $billing_address = $order->getBillingProfile()->get('address')->first();

    // Basic validation steps
    if (empty($config['merchant_id'])) {
      throw new PaymentGatewayException('Merchant ID not provided.');
    }

    if (empty($config['secret_key'])) {
      throw new PaymentGatewayException('Client secret not provided.');
    }

    // Determine correct endpoint
    $rdp_endpoint = 'https://secure.reddotpayment.com/service/payment-api';
    if ($config['mode'] == 'test') {
      $rdp_endpoint = 'https://secure-dev.reddotpayment.com/service/payment-api';
    }

    // Prepare the first phase request object
    $request_fields = array(
      'mid' => $config['merchant_id'],
      'api_mode' => 'redirection_hosted',
      'payment_type' => 'S', // TODO: Make configurable, please
      'order_id' => $order->getOrderNumber(),
      'store_code' => $order->getStoreId(),
      'ccy' => $order->getTotalPrice()->getCurrencyCode(),
      'amount' => $order->getTotalPrice()->getNumber(), // TODO: Handle IDR correctly, please as per "...IDR (Indonesia Rupiah) Should be sent without digits behind comma"
      'multiple_method_page' => '1', // TODO: Make configurable, please
      'back_url' => $form['#cancel_url'],
      'redirect_url' => $form['#return_url'],
      'notify_url' => $payment_gateway_plugin->getNotifyUrl()->toString(),
      'locale' => in_array($current_language->getId(), array('en', 'id', 'es', 'fr', 'de')) ? $current_language->getId() : 'en',
      'payer_id' => $order->getEmail(),
      'payer_email' => $order->getEmail(),
      'bill_to_forename' => $billing_address->getGivenName(),
      'bill_to_surname' => $billing_address->getFamilyName(),
      'bill_to_address_city' => $billing_address->getLocality(),
      'bill_to_address_line1' => $billing_address->getAddressLine1(),
      'bill_to_address_line2' => $billing_address->getAddressLine2(),
      'bill_to_address_country' => $billing_address->getCountryCode(),
      'bill_to_address_state' => $billing_address->getAdministrativeArea(),
      'bill_to_address_postal_code' => $billing_address->getPostalCode(),
      'ship_to_forename' => $billing_address->getGivenName(),
      'ship_to_surname' => $billing_address->getFamilyName(),
      'ship_to_address_city' => $billing_address->getLocality(),
      'ship_to_address_line1' => $billing_address->getAddressLine1(),
      'ship_to_address_line2' => $billing_address->getAddressLine2(),
      'ship_to_address_country' => $billing_address->getCountryCode(),
      'ship_to_address_state' => $billing_address->getAdministrativeArea(),
      'ship_to_address_postal_code' => $billing_address->getPostalCode(),
    );

    // Create request signature.
    $fields_for_signature = array('mid', 'order_id', 'payment_type', 'amount', 'ccy', 'payer_id');
    $aggregated_fields = "";
    foreach ($fields_for_signature as $f) {
      $aggregated_fields .= trim($request_fields[$f]);
    }
    $aggregated_fields .= $config['secret_key'];
    $request_fields['signature'] = hash('sha512', $aggregated_fields);

    // TODO: Call a service

    // TODO: Set up payment, please. Add remote ID from transaction.

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

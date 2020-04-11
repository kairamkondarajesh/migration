<?php
/**
 * @file
 * Contains \Drupal\node_stripe\Form\PaymentForm.
 */
namespace Drupal\node_stripe\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Stripe\Stripe;

class paymentForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'payment_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $current_path = \Drupal::service('path.current')->getPath();
    $path_args = explode('/', $current_path);
    $node = \Drupal\node\Entity\Node::load($path_args[2]);
    $amount = $node->get('field_amount')->getValue();
    $title = $node->getTitle();
    $form['card_number'] = array(
      '#type' => 'textfield',
      '#title' => t('CARD NUMBER'),
      '#required' => TRUE,
    );

    $form['card_exp_month'] = array(
      '#type' => 'textfield',
      '#title' => t('EXPIRY MONTH'),
      '#required' => TRUE,
    );
    $form['card_exp_year'] = array(
      '#type' => 'textfield',
      '#title' => t('EXPIRY YEAR'),
      '#required' => TRUE,
    );
    $form['card_cvc'] = array(
      '#type' => 'textfield',
      '#title' => t('CVC CODE'),
      '#required' => TRUE,
    );
    $form['stripeToken'] = array(
      '#type' => 'hidden',
      '#id' => 'stripeToken', 
      '#attributes' => array('class' => array('stripeToken')), 
   );
   $form['nid'] = array(
     '#type' => 'hidden',
     '#id' => 'nid', 
     '#value' => $path_args[2],
  );
  $form['title'] = array(
    '#type' => 'hidden',
    '#id' => 'title', 
    '#value' => $title,
 );
  $form['amount'] = array(
    '#type' => 'hidden',
    '#id' => 'nid', 
    '#value' => $amount[0]['value'],
  );
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit Payment'),
      '#button_type' => 'primary',
    );
    //$form['#attached']['library'][] = 'node_stripe/paymentform';
    //$form['#attached']['library'][] = 'node_stripe/paymentstripe';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues(); 
    \Stripe\Stripe::setApiKey("sk_test_wErEd9NiAIqV9X1hJxmU7a5P00FF54WyY4");

    $charge = \Stripe\Charge::create(
      array(
          'amount' => $values['amount'],
          'currency' => 'usd',
          'source' => $values['stripeToken'],
          "description" => $values['title'],
          "metadata" => ["nid" => $values['nid']]
      )
    );
    // Retrieve charge details 
    $chargeJson = $charge->jsonSerialize();
    if($chargeJson['amount_refunded'] == 0 && empty($chargeJson['failure_code']) && $chargeJson['paid'] == 1 && $chargeJson['captured'] == 1){ 
      // Order details  
      $transactionID = $chargeJson['balance_transaction']; 
      $paidAmount = $chargeJson['amount']; 
      $paidCurrency = $chargeJson['currency']; 
      $payment_status = $chargeJson['status']; 
      $uid = \Drupal::currentUser()->id();
      $request_time = \Drupal::time()
      ->getCurrentTime();

      //$new_datetime = DateTime::createFromFormat ("Y-m-d\TH:i:sT", $request_time);


      \Drupal::database()->insert('node_stripe')
        ->fields([
          'uid',
          'nid',
          'card_number',
          'card_exp_month',
          'card_exp_year',
          'amount',
          'txn_id',
          'payment_status',
          'subscription_date',
          'currency',
        ])
        ->values(array(
          $uid,
          $values['nid'],
          $values['card_number'],
          $values['card_exp_month'],
          $values['card_exp_year'],
          $paidAmount,
          $transactionID,
          $payment_status,
          '2020-04-01 01:03:09',
          $paidCurrency
        ))
      ->execute();
      }
  
/* 
    print_r($charge);
    exit; */
    drupal_set_message($this->t('@emp_name ,Your application is being submitted!', array('@emp_name' => $form_state->getValue('employee_name'))));

  }
}
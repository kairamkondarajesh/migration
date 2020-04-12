<?php

/**
 * @file
 * Contains \Drupal\node_stripe\Form\PaymentForm.
 */

namespace Drupal\node_stripe\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node_stripe\StripePayment;
use Drupal\Core\Database\Connection;

class PaymentForm extends FormBase
{

    /**
     * PaymentForm class constructor
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManagerInterface,
        StripePayment $payment,
        CurrentPathStack $currentPathStack,
        AccountProxyInterface $currentUser,
        TimeInterface $time,
        Connection $connection
    ) {
        $this->entityTypeManager = $entityTypeManagerInterface;
        $this->paymentServivce = $payment;
        $this->currentPathStack = $currentPathStack;
        $this->currentUser = $currentUser;
        $this->time = $time;
        $this->database = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('entity_type.manager'),
            $container->get('stripe_payment'),
            $container->get('path.current'),
            $container->get('current_user'),
            $container->get('datetime.time'),
            $container->get('database')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'payment_form';
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $current_path = $this->currentPathStack->getPath();
        $path_args = explode('/', $current_path);
        
        if (isset($path_args[2])) {
            $node = $this->entityTypeManager->getStorage('node')->load($path_args[2]);
            if ($node->hasField('field_amount')) {
                $amount = $node->field_amount->value;
            }
            $form['card_number'] = [
                '#type' => 'textfield',
                '#title' => $this->t('CARD NUMBER'),
                '#required' => true,
            ];
            $form['card_exp_month'] = [
                '#type' => 'textfield',
                '#title' => $this->t('EXPIRY MONTH'),
                '#required' => true,
            ];
            $form['card_exp_year'] = [
                '#type' => 'textfield',
                '#title' => t('EXPIRY YEAR'),
                '#required' => true,
            ];
            $form['card_cvc'] = [
                '#type' => 'textfield',
                '#title' => $this->t('CVC CODE'),
                '#required' => true,
            ];
            $form['stripeToken'] = [
                '#type' => 'hidden',
                '#id' => 'stripeToken', 
                '#attributes' => [ 
                    'class' => ['stripeToken']
                ], 
            ];
            $form['nid'] = [
                '#type' => 'hidden',
                '#id' => 'nid', 
                '#value' => $node->id(),
            ];
            $form['title'] = [
                '#type' => 'hidden',
                '#id' => 'title', 
                '#value' => $node->getTitle(),
            ];
            $form['amount'] = [
                '#type' => 'hidden',
                '#id' => 'nid', 
                '#value' => $amount ?? null,
            ];
            $form['actions']['#type'] = 'actions';
            $form['actions']['submit'] = [
                '#type' => 'submit',
                '#value' => $this->t('Submit Payment'),
                '#button_type' => 'primary',
            ];
        }
        //$form['#attached']['library'][] = 'node_stripe/paymentform';
        //$form['#attached']['library'][] = 'node_stripe/paymentstripe';
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $values = $form_state->getValues();
        
        $amount = $values['amount'];
        $stripeToken = $values['stripeToken'];
        $title = $values['title'];
        $nid = $values['nid'];
        $card_number = $values['card_number'];
        $card_exp_month = $values['card_exp_month'];
        $card_exp_year = $values['card_exp_year'];

        $charge_json = $this->paymentServivce->processPayment(
            $amount,
            'usd',
            $stripeToken,
            $title,
            ['nid' => $nid]
        );

        if (!$charge_json) {
            $this->messenger()->addError('Unable process your payment. Please try later.');
        } elseif ($charge_json['amount_refunded'] == 0 
            && empty($charge_json['failure_code']) 
            && $charge_json['paid'] == 1 
            && $charge_json['captured'] == 1
        ) {
            
            // Order details  
            $transaction_id = $charge_json['balance_transaction']; 
            $paid_amount = $charge_json['amount']; 
            $paidCurrency = $charge_json['currency']; 
            $payment_status = $charge_json['status']; 
            $uid = $this->currentUser->id();
            $request_time = $this->time->getCurrentTime();
            $purchase_date = DrupalDateTime::createFromFormat("Y-m-d\TH:i:sT", $request_time);
            
            // Save the order details.
            $this->saveOrderDetails(
                [
                $uid, $nid, $card_number, $card_exp_month,
                $card_exp_year, $paid_amount, $transaction_id, $payment_status,
                $purchase_date, $paidCurrency
                ]
            );
            $this->messenger()->addMessage(
                $this->t(
                    '@emp_name ,Your application is being submitted!',
                    ['@emp_name' => $values['employee_name']]
                )
            );
        }
    }

    /**
     * Saves the order information in custom table.
     */
    protected function saveOrderDetails($order_details)
    {
        $id = $this->database->insert('node_stripe')
            ->fields(
                [
                'uid', 'nid', 'card_number', 'card_exp_month', 'card_exp_year',
                'amount', 'txn_id', 'payment_status', 'subscription_date', 'currency',
                ]
            )
            ->values($order_details)
            ->execute();
        return $id;
    }
}
<?php
/**
 * @file
 * Contains \Drupal\resume\Form\WorkForm.
 
 */
namespace Drupal\resume\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Stripe\Stripe;

class workForm extends FormBase
{
    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'resume_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {

        $form['card_number'] = array(
        '#type' => 'textfield',
        '#title' => t('CARD NUMBER'),
        '#required' => true,
        );

        $form['card_exp_month'] = array(
        '#type' => 'textfield',
        '#title' => t('EXPIRY MONTH'),
        '#required' => true,
        );
        $form['card_exp_year'] = array(
        '#type' => 'textfield',
        '#title' => t('EXPIRY YEAR'),
        '#required' => true,
        );
        $form['card_cvc'] = array(
        '#type' => 'textfield',
        '#title' => t('CVC CODE'),
        '#required' => true,
        );
        $form['stripeToken'] = array(
        '#type' => 'hidden',
        '#id' => 'stripeToken', 
        '#attributes' => array('class' => array('stripeToken')), 
        );

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Submit Payment'),
        '#button_type' => 'primary',
        );
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $values = $form_state->getValues(); 
        /* echo "<pre>";
        print_r($values);
        exit; */
        \Stripe\Stripe::setApiKey("sk_test_wErEd9NiAIqV9X1hJxmU7a5P00FF54WyY4");

        $charge = \Stripe\Charge::create(
            array(
            'amount' => 2000,
            'currency' => 'usd',
            'source' => $values['stripeToken'],
            )
        );
        print_r($charge);
        exit;
        drupal_set_message($this->t('@emp_name ,Your application is being submitted!', array('@emp_name' => $form_state->getValue('employee_name'))));

    }
}
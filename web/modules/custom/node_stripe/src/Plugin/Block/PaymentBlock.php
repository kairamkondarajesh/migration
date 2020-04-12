<?php
/**
 * @file
 * Contains \Drupal\node_stripe\Plugin\Block\PaymentBlock.
 */

namespace Drupal\node_stripe\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormInterface;

/**
 * Provides a 'payment' block.
 *
 * @Block(
 *   id = "payment_block",
 *   admin_label = @Translation("Payment block"),
 *   category = @Translation("Custom payment block")
 * )
 */
class PaymentBlock extends BlockBase
{

    /**
     * {@inheritdoc}
     */
    public function build()
    {
        $form = \Drupal::formBuilder()->getForm('Drupal\node_stripe\Form\paymentForm');
        return $form;
    }
}
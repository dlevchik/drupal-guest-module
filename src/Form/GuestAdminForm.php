<?php

namespace Drupal\levchik\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\levchik\Controller\LevchikController as LevchikController;

/**
 * Provides a levchik administer guests page form.
 */
class GuestAdminForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'levchik_guests_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $guests_rows = [];
    $guests = LevchikController::getGuests();
    $renderer = \Drupal::service('renderer');
    foreach ($guests as $guest) {
      $contacts_list = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => [
          $guest['name'],
          $guest['email'],
          $guest['phone'],
        ],
      ];
      $avatar = [
        '#theme' => 'image',
        '#uri' => $guest['avatar_src'],
        '#alt' => $this->t("Guest avatar"),
        '#height' => 75,
        '#width' => 75,
      ];
      $picture = $guest['picture_fid'] != 0 ? [
        '#theme' => 'image',
        '#uri' => $guest['picture_src'],
        '#alt' => $this->t("Feedback picture"),
        '#height' => 75,
        '#width' => 75,
      ] : ['#markup' => 'No picture'];
      $buttons = [
        '#theme' => 'levchik_guest_button',
        '#id' => $guest['id'],
      ];
      $guests_rows[$guest['id']] = [
        $renderer->render($contacts_list),
        $renderer->render($avatar),
        $guest['message'],
        $renderer->render($picture),
        $renderer->render($buttons),
      ];
    }
    $form['guests'] = $guests_rows ? [
      '#type' => 'tableselect',
      '#caption' => $this->t('Your <a href="@url">guests</a> feedback', ["@url" => \Drupal::urlGenerator()->generateFromRoute(LevchikController::getRouteName())]),
      '#header' => [
        $this->t('User info'),
        $this->t('User avatar'),
        $this->t('Feedback message'),
        $this->t('Feedback picture'),
        $this->t('Actions'),
      ],
      '#options' => $guests_rows,
    ] : [
      '#markup' => "<h2>Sorry. No Guests Feedback found here:(</h2>",
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#states' => [
        'visible' => [
          'form' => ['filled' => 'true'],
        ],
      ],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete this guests'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = array_filter($form_state->getValues()['guests']);
    LevchikController::deleteGuest(array_keys($values));
    $form_state->setRedirect(LevchikController::getRouteName());
  }

}

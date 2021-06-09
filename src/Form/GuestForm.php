<?php

namespace Drupal\levchik\Form;

use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Url;
use Drupal\levchik\Controller\LevchikController as LevchikController;

/**
 * Provides a Levchik form.
 */
class GuestForm extends FormBase {

  /**
   * ID of the item to edit.
   *
   * @var int
   */
  protected $id;
  /**
   * Guest data array.
   *
   * @var array
   */
  protected $guest;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'levchik_guest';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $id = NULL) {
    $this->id = $id;
    if (!is_null($id)) {
      if (isset(LevchikController::getGuests($this->id)[0])) {
        $this->guest = LevchikController::getGuests($this->id)[0];
      }
      else {
        $form['message'] = [
          '#type' => 'markup',
          '#markup' => '<h2>Sorry, no guest with this id was found</h2>',
        ];
        return $form;
      }
    }

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your name:'),
      '#required' => TRUE,
      '#description' => $this->t('Min name length: 2, max: 100'),
      '#maxlength' => 100,
      '#default_value' => !is_null($this->guest) ? $this->guest['name'] : "",
      '#attributes' => [
        'class' => ['name'],
      ],
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Your email:'),
      '#required' => TRUE,
      '#description' => $this->t('Valid email looks like example@mail.site.'),
      '#default_value' => !is_null($this->guest) ? $this->guest['email'] : "",
    ];

    $form['phone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your phone:'),
      '#required' => TRUE,
      '#maxlength' => 13,
      '#description' => $this->t('Valid phone looks like (+)(38)0123456789.'),
      '#default_value' => !is_null($this->guest) ? $this->guest['phone'] : "",
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#required' => TRUE,
      '#default_value' => !is_null($this->guest) ? $this->guest['message'] : "",
    ];

    $form['avatar'] = [
      '#type' => 'managed_file',
      '#name' => 'avatar',
      '#title' => t("Your avatar:"),
      '#required' => FALSE,
      '#upload_validators' => [
        'file_validate_is_image' => [],
        'file_validate_size' => [2097152],
        'file_validate_extensions' => ['gif jpg jpeg'],
      ],
      '#upload_location' => 'public://levchik/avatars/',
      '#default_value' => (!is_null($this->guest) && $this->guest['avatar_fid'] != 0) ? [$this->guest['avatar_fid']] : "",
    ];

    $form['picture'] = [
      '#type' => 'managed_file',
      '#name' => 'picture',
      '#title' => t("Your feedback picture:"),
      '#required' => FALSE,
      '#upload_validators' => [
        'file_validate_is_image' => [],
        'file_validate_size' => [5242880],
        'file_validate_extensions' => ['gif jpg jpeg'],
      ],
      '#upload_location' => 'public://levchik/pictures/',
      '#default_value' => (!is_null($this->guest) && $this->guest['picture_fid'] != 0) ? [$this->guest['picture_fid']] : "",
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send'),
      '#ajax' => [
        // 'event' => 'change',
        'effect' => 'fade',
        'wrapper' => 'my_form_wrapper',
        'callback' => '::ajaxValidation',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
    ];
    $form['#attached']['library'][] = 'levchik/guests-form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->clearErrors();
    $form_state->set('clearElements', []);
    if (mb_strlen($form_state->getValue('name')) < 2 || mb_strlen($form_state->getValue('name')) > 100) {
      $errText = $this->t("Invalid name.");
      $form_state->setErrorByName('name', $errText);
    }
    else {
      $clearElements = $form_state->get('clearElements');
      $clearElements[] = 'name';
      $form_state->set('clearElements', $clearElements);
    }

    if (!filter_var($form_state->getValue('email'), FILTER_VALIDATE_EMAIL)) {
      $errText = $this->t("Invalid email.");
      $form_state->setErrorByName('email', $errText);
    }
    else {
      $clearElements = $form_state->get('clearElements');
      $clearElements[] = 'email';
      $form_state->set('clearElements', $clearElements);
    }

    if (!preg_match('/^\+?3?8?(0\d{9})$/i', $form_state->getValue('phone'))) {
      $errText = $this->t("Invalid phone number.");
      $form_state->setErrorByName('phone', $errText);
    }
    else {
      $clearElements = $form_state->get('clearElements');
      $clearElements[] = 'phone';
      $form_state->set('clearElements', $clearElements);
    }
  }

  /**
   * Function for ajax validation message display using
   * ajaxErrorByName function. clearElements is array of fields without
   * errors and necesarry to clear errors messages after user has corrected
   * his input.
   */
  public function ajaxValidation(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    if (!$form_state->hasAnyErrors()) {
      $response->addCommand(
        new HtmlCommand(
          '.block-system-main-block',
          $this->t("Thanks for your submission! Page will be reloaded automatically!"),
        ),
      );
      $url = Url::fromRoute(LevchikController::getRouteName());
      $response->addCommand(
        new RedirectCommand($url->toString())
      );
      return $response;
    }
    $errors = $form_state->getErrors();
    foreach ($errors as $name => $errText) {
      $this->ajaxErrorByName($form, $form_state, $response, $name, $errText);
    }
    $clearElements = $form_state->get('clearElements');
    foreach ($clearElements as $clearElement) {
      $this->ajaxErrorByName($form, $form_state, $response, $clearElement);
    }
    return $response;
  }

  /**
   * Function displays message from setErrorByName() in form fields
   * description. Also it add's class 'error' on fields with error.
   * If setErrorByName() has no message in it function just sets
   * form field description as default field description value.
   */
  public function ajaxErrorByName(array &$form, FormStateInterface $form_state, &$response, string $name, string $errText = "") {
    if (!empty($errText)) {
      $response->addCommand(
        new InvokeCommand(
          '.form-item-' . $name,
          'addClass',
          ['error'],
        ),
      );
      $response->addCommand(
        new HtmlCommand(
          '#edit-' . $name . '--description',
          $errText,
        )
      );
    }
    else {

      $response->addCommand(
        new InvokeCommand(
          '.form-item-' . $name,
          'removeClass',
          ['error'],
        ),
      );
      $response->addCommand(
        new HtmlCommand(
          '#edit-' . $name . '--description',
          $form[$name]['#description'],
        ),
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $guest = new \stdClass();
    $guest->name = $form_state->getValue('name');
    $guest->email = $form_state->getValue('email');
    $guest->phone = $form_state->getValue('phone');
    $guest->picture_fid = $form_state->getValue('picture')[0];
    $guest->avatar_fid = $form_state->getValue('avatar')[0];
    $guest->message = $form_state->getValue('message');
    if (!is_null($this->id)) {
      $guest->id = $this->id;
      LevchikController::editGuest($guest);
    }
    else {
      LevchikController::saveGuest($guest);
    }
    $form_state->setRedirectUrl(Url::fromRoute(LevchikController::getRouteName()));
  }

}

<?php

namespace Drupal\levchik\Form;

use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\levchik\Controller\LevchikController as LevchikController;

/**
 * Defines a confirmation form to confirm deletion of gurst by id.
 */
class ConfirmDeleteForm extends ConfirmFormBase {

  /**
   * ID of the item to delete.
   *
   * @var int
   */
  protected $id;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $id = NULL) {
    $this->id = $id;
    if (!LevchikController::guestExists($this->id)) {
      $form = [];
      $form['message'] = [
        '#type' => 'markup',
        '#markup' => '<h2>This Guest is already deleted.</h2>',
      ];
      return $form;
    }
    $form['actions']['submit']['#ajax'] = [
      'callback' => '::ajaxFunc',
      'progress' => [
        'type' => 'throbber',
        'message' => NULL,
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    LevchikController::deleteGuest([$this->id]);
    $form_state->setRedirectUrl(Url::fromRoute(LevchikController::getRouteName()));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "confirm_delete_form";
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url(LevchikController::getRouteName());
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('You sure you want to delete this feedback?');
  }

  /**
   * Redirect user after successful deletion.
   */
  public function ajaxFunc(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    if (!$form_state->hasAnyErrors()) {
      $response->addCommand(
        new HtmlCommand(
          '.confirm-delete-form',
          $this->t("Cat has been deleted! Page will be reloaded automatically."),
        ),
      );
      $url = Url::fromRoute(LevchikController::getRouteName());
      $response->addCommand(
        new RedirectCommand($url->toString())
      );
    }
    return $response;
  }

}

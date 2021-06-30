<?php

namespace Drupal\levchik\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File as File;
use Drupal\Core\Url as Url;

/**
 * Returns responses for levchik routes.
 */
class LevchikController extends ControllerBase {

  /**
   * Builds the response for guest book page.
   */
  public function build() {
    $guests = LevchikController::getGuests();
    foreach ($guests as &$guest) {
      $guest['buttons'] = [
        '#theme' => 'levchik_guest_button',
        '#id' => $guest['id'],
      ];
    }
    $build = [
      'header' => [
        '#markup' => $this->t('Greetings, our dear guest. Here you can publish your feedback about this site.'),
      ],
      'form' => \Drupal::formBuilder()->getForm('\Drupal\levchik\Form\GuestForm'),
      'guests' => [
        '#theme' => 'levchik_guests',
        '#guests' => $guests,
        '#attached' => [
          'library' => [
            'levchik/guests-styling',
          ],
        ],
      ],
    ];
    return $build;
  }

  /**
   * Get name of main guest book route.
   *
   * @return string
   *   name of main guest book route
   */
  public static function getRouteName() {
    return 'levchik.guestbook';
  }

  /**
   * Searches guests or one particular guest in db.
   *
   * @param string $id
   *   ID of the guest to search in db.
   *
   * @return array
   *   Guests objects array(May be only one particular guest).
   */
  public static function getGuests(string $id = NULL) {
    $database = \Drupal::database();
    $query = $database->select('levchik', 'lv');
    $query->fields('lv');
    if (!is_null($id)) {
      $query->condition('id', $id, "IN");
    }
    $query->orderBy('created', 'DESC');

    $result = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    foreach ($result as &$item) {
      $avatar_fid = $item['avatar_fid'] ? $item['avatar_fid'] : 0;
      if (isset($avatar_fid) && $avatar_fid != 0) {
        $file = File::load($avatar_fid);
        $avatar_uri = $file->getFileUri();
        $avatar_url = Url::fromUri(file_create_url($avatar_uri))->toString();
      }
      else {
        $avatar_url = '/' . drupal_get_path('module', 'levchik') . "/img/download.jpeg";
      }
      $item['avatar_src'] = $avatar_url;

      $picture_fid = $item['picture_fid'] ? $item['picture_fid'] : 0;
      if (isset($picture_fid) && $picture_fid != 0) {
        $file = File::load($picture_fid);
        $picture_uri = $file->getFileUri();
        $picture_url = Url::fromUri(file_create_url($picture_uri))->toString();
      }
      $item['picture_src'] = isset($picture_url) ? $picture_url : "";
    }

    return $result;
  }

  /**
   * Check if this guest exist in db.
   *
   * @param int $id
   *   Id of guest.
   */
  public static function guestExists(int $id) {
    $connection = \Drupal::service('database');
    $query = $connection->select('levchik', 'lv');
    $query->condition('id', $id);
    $query->fields('lv', ['id']);
    return (bool) $query->range(0, 1)->execute()->fetch();
  }

  /**
   * Deletes guest data from db.
   *
   * @param array $id
   *   Array with guests id's to delete.
   */
  public static function deleteGuest(array $id = []) {
    if (empty($id)) {
      return;
    }
    $connection = \Drupal::service('database');
    $query = $connection->select('levchik', 'lv');
    $query->condition('id', $id, 'IN');
    $query->fields('lv', ['picture_fid', 'avatar_fid']);
    $result = $query->execute()->fetchAll();
    foreach ($result as $item) {
      $picture_fid = $item->picture_fid;
      if ($picture_fid != "0") {
        $file = File::load($picture_fid);
        $file->delete();
      }
      $avatar_fid = $item->avatar_fid;
      if ($avatar_fid != "0") {
        $file = File::load($avatar_fid);
        $file->delete();
      }
    }
    $cat_deleted = $connection->delete('levchik')
      ->condition('id', $id, 'IN')
      ->execute();
  }

  /**
   * Updates Guest data from db.
   *
   * @param object $guest
   *   Object with guest data.
   */
  public static function editGuest(\stdClass $guest) {
    $connection = \Drupal::service('database');
    $picture_fid = $guest->picture_fid;
    if ($picture_fid) {
      LevchikController::fileSavePermanent($picture_fid);
    }
    $avatar_fid = $guest->avatar_fid;
    if ($avatar_fid) {
      LevchikController::fileSavePermanent($avatar_fid);
    }
    $connection->update('levchik')
      ->condition('id', $guest->id)
      ->fields([
        'name' => $guest->name,
        'email' => $guest->email,
        'phone' => $guest->phone,
        'picture_fid' => $picture_fid ? $picture_fid : 0,
        'avatar_fid' => $avatar_fid ? $avatar_fid : 0,
        'message' => $guest->message,
      ])
      ->execute();
  }

  /**
   * Saves guest data to db.
   *
   * @param object $guest
   *   Object with guest data.
   */
  public static function saveGuest(\stdClass $guest) {
    $connection = \Drupal::service('database');
    $picture_fid = $guest->picture_fid;
    $avatar_fid = $guest->avatar_fid;
    if ($picture_fid) {
      LevchikController::fileSavePermanent($picture_fid);
    }
    if ($avatar_fid) {
      LevchikController::fileSavePermanent($avatar_fid);
    }
    $connection->insert('levchik')
      ->fields([
        'name',
        'created',
        'email',
        'phone',
        'picture_fid',
        'avatar_fid',
        'message',
      ])
      ->values([
        'name' => $guest->name,
        'created' => \Drupal::time()->getRequestTime(),
        'email' => $guest->email,
        'phone' => $guest->phone,
        'picture_fid' => $picture_fid ? $picture_fid : 0,
        'avatar_fid' => $avatar_fid ? $avatar_fid : 0,
        'message' => $guest->message,
      ])
      ->execute();
  }

  /**
   * Function to make fresh downloaded file permanent to drupal.
   *
   * @param int $fid
   *   File id to make permanent.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function fileSavePermanent(int $fid) {
    $file = File::load($fid);
    $file->setPermanent();
    $file->save();
  }

}

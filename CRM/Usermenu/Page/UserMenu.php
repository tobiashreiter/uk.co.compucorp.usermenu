<?php

class CRM_Usermenu_Page_UserMenu extends CRM_Core_Page {

  /**
   * @const string
   */
  const DEFAULT_USER_IMAGE_PATH = '%{base}/images/profile-default.png';

  /**
   * The contact data used to build the menu
   *
   * @var array
   */
  private $contactData = [];

  /**
   * Replaces the placholders with actual data and then returns
   * the processed markup
   *
   * @return string
   */
  public function run() {
    $this->contactData();

    $this->assign('menuItems', $this->getMenuItems());
    $this->assign('username', $this->getUserName());
    $this->assign('image', $this->getUserImagePath());

    return parent::run();
  }

  /**
   * Returns the default user image URL path.
   *
   * @return string
   */
  private function getDefaultImagePath() {
    $modulePath = (new CRM_Extension_System())
      ->getFullContainer()
      ->getResUrl('uk.co.compucorp.usermenu');

    return str_replace('%{base}', $modulePath, self::DEFAULT_USER_IMAGE_PATH);
  }

  /**
   * Returns the user menu navigation items.
   *
   * @return Array
   */
  private function getMenuItems() {
    $menuItems = civicrm_api3('Navigation', 'get', [
      'is_active' => 1,
      'parent_id' => 'user-menu-ext__user-menu',
      'options' => [ 'sort' => 'weight ASC']
    ]);

    return $menuItems['values'];
  }

  /**
   * Returns the contact data, or fetches it from the api if
   * it's not yet available
   *
   * @return array
   */
  private function contactData() {
    if (empty($this->contactData)) {
      $this->contactData = $this->getContactDataFromApi();
    }

    return $this->contactData;
  }

  /**
   * Fetches the contact data from the API and then
   * normalizes the response
   *
   * @return array
   */
  private function getContactDataFromApi() {
    $rawContactData = civicrm_api3('Contact', 'getsingle', [
      'return' => ['id', 'display_name', 'image_URL'],
      'id' => CRM_Core_Session::getLoggedInContactID(),
      'api.User.getsingle' => ['contact_id' => '$value.contact_id']
    ]);

    return $this->normalizeContactDataAPIResponse($rawContactData);
  }

  /**
   * Normalizes the given contact data, removing any odd structure
   * related to the API response
   *
   * @param array $rawData
   *
   * @return array
   */
  private function normalizeContactDataAPIResponse($rawData) {
    $rawData['cmsId'] = $rawData['api.User.getsingle']['id'];
    unset($rawData['api.User.getsingle']);

    return $rawData;
  }

  /**
   * Returns the user name, falling back to a generic name if the user
   * doesn't have one
   *
   * @return string
   */
  private function getUserName() {
    if (!empty($this->contactData()['display_name'])) {
      return $this->contactData()['display_name'];
    }

    return 'Anonymous';
  }

  /**
   * Returns the path of the user's image, falling back to the CMS's default
   * image if the user doesn't have one
   *
   * @return string
   */
  private function getUserImagePath() {
    if (!empty($this->contactData()['image_URL'])) {
      return $this->contactData()['image_URL'];
    }

    return $this->getDefaultImagePath();
  }
}

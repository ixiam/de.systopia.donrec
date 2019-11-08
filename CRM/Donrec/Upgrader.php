<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

/**
 * Collection of upgrade steps.
 */
class CRM_Donrec_Upgrader extends CRM_Donrec_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Install hook
   */
  public function install() {
    // nothing to do
  }

  /**
   * Uninstall hook
   */
  public function uninstall() {
    // nothing to do
  }

  /**
   * Make sure all the data structures are there when the module is enabled
   */
  public function enable() {
    // create snapshot database tables
    $this->executeSqlFile('sql/donrec.sql', true); 

    // create/update custom groups
    CRM_Donrec_DataStructure::update();

    // rename the custom fields according to l10.
    // FIXME: this is a workaround: if you do this before, the table name change,
    //         BUT we should not be working with static table names
    CRM_Donrec_DataStructure::translateCustomGroups();

    // make sure the template is there
    CRM_Donrec_Logic_Template::getDefaultTemplateID();
  }

  /**
   * Example: Run a simple query when a module is disabled.
   */
  public function disable() {
    // delete the snapshot-table
    $this->executeSqlFile('sql/donrec_uninstall.sql', true); 
  }

  /**
   * Upgrade to 1.6.6:
   *  - Add custom field for register export type
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0166() {
    // Add custom field
    $result = civicrm_api3('CustomField', 'create', [
      'custom_group_id' => "zwb_donation_receipt",
      'label' => "exporters",
      'data_type' => "String",
      'html_type' => "Text",
      "is_searchable": 1,
    ]);

    // Add custom field
    $result = civicrm_api3('CustomField', 'create', [
      'custom_group_id' => "zwb_donation_receipt_item",
      'label' => "exporters",
      'data_type' => "String",
      'html_type' => "Text",
      "is_searchable": 1,
    ]);

    return TRUE;
  }

  /**
   * Upgrade to 1.4:
   *  - update to new, prefixed, settings names
   *  - (on 4.6) if no profile exists, create default (from legacy indivudual values)
   *  - (on 4.6) migrate all profiles into a "settings bag"
   *
   * REMARK: using the CRM_Core_BAO_Setting::getItem in order to evaluate the group_name
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0140() {
    $OLD_SETTINGS_GROUP = 'Donation Receipt Profiles';

    // STEP 1: Migrate old general settings to prefixed ones
    $settings_migration = array(
      'default_profile'  => 'donrec_default_profile',
      'packet_size'      => 'donrec_packet_size',
      'pdfinfo_path'     => 'donrec_pdfinfo_path',
      );

    $migrated_values = array();
    foreach ($settings_migration as $old_key => $new_key) {
      $new_value = CRM_Core_BAO_Setting::getItem($OLD_SETTINGS_GROUP, $new_key);
      if ($new_value === NULL) {
        $old_value = CRM_Core_BAO_Setting::getItem($OLD_SETTINGS_GROUP, $old_key);
        if ($old_value !== NULL) {
          $migrated_values[$new_key] = $old_value;
        }
      }
    }

    if (!empty($migrated_values)) {
      civicrm_api3('Setting', 'create', $migrated_values);
    }

    
    // Migrate profiles 
    //  (only works on 4.6. With 4.7 the group_name was dropped, and we cannot find the profiles any more)
    $existing_profiles = civicrm_api3('Setting', 'getvalue', array('name' => 'donrec_profiles'));
    if (empty($existing_profiles) && version_compare(CRM_Utils_System::version(), '4.6', '<=')) {

      // FIXME: is there a better way than a SQL query?
      $profiles = array();
      $query = CRM_Core_DAO::executeQuery("SELECT name FROM civicrm_setting WHERE group_name = '$OLD_SETTINGS_GROUP'");
      while ($query->fetch()) {
        $profile_data = CRM_Core_BAO_Setting::getItem($OLD_SETTINGS_GROUP, $query->name);
        if (is_array($profile_data)) {
          $profiles[$query->name] = $profile_data;
        } else {
          $this->ctx->log->warn('Profile "{$query->name}" seems to be broken and is lost.');
        }
      }

      // if there is no default profile, create one and copy legacy (pre 1.3) values
      if (empty($profiles['Default'])) {
        $default_profile = new CRM_Donrec_Logic_Profile('Default');
        $profile_data    = $default_profile->getData();

        foreach (array_keys($profile_data) as $field_name) {
          $legacy_value = CRM_Core_BAO_Setting::getItem(CRM_Donrec_Logic_Settings::$SETTINGS_GROUP, $field_name);
          if ($legacy_value !== NULL) {
            $profile_data[$field_name] = $legacy_value;
          }
        }
        $legacy_contribution_types = CRM_Core_BAO_Setting::getItem(CRM_Donrec_Logic_Settings::$SETTINGS_GROUP, 'contribution_types');
        if ($legacy_contribution_types !== NULL && $legacy_contribution_types != 'all') {
          $profile_data['financial_types'] = explode(',', $legacy_contribution_types);
        }

        $profiles['Default'] = $profile_data;
        $this->ctx->log->warn('Created default profile.');
      }

      CRM_Donrec_Logic_Profile::setAllData($profiles);

      $profiles_migrated = count($profiles);
      $this->ctx->log->info('Migrated {$profiles_migrated} profiles.');
    }

    return TRUE;
  }
}

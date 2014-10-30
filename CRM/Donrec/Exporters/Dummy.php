<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2014 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
+--------------------------------------------------------*/

/**
 * This is a dummy exporter, for testing purposes
 */
class CRM_Donrec_Exporters_Dummy extends CRM_Donrec_Logic_Exporter {

  /**
   * @return the display name
   */
  static function name() {
    return ts("Don't generate files");
  }

  /**
   * @return a html snippet that defines the options as form elements
   */
  static function htmlOptions() {
    return '';
  }

  /**
   * @return the ID of this importer class
   */
  public function getID() {
    return 'Dummy';
  }


  /**
   * export this chunk of individual items
   *
   * @return array:
   *          'is_error': set if there is a fatal error
   *          'log': array with keys: 'type', 'timestamp', 'message'
   */
  public function exportSingle($chunk, $snapshotId, $is_test) {
    $reply = array();

    // edit the process information
    foreach ($chunk as $chunk_id => $chunk_item) {
      $this->setProcessInformation($chunk_id, array('test' => 'Dummy was here!'));
    }

    usleep(300);

    // add a log entry
    CRM_Donrec_Logic_Exporter::addLogEntry($reply, 'Dummy processed ' . count($chunk) . ' items.', CRM_Donrec_Logic_Exporter::LOG_TYPE_INFO);
    return $reply;
  }

  /**
   * bulk-export this chunk of items
   *
   * @return array:
   *          'is_error': set if there is a fatal error
   *          'log': array with keys: 'type', 'level', 'timestamp', 'message'
   */
  public function exportBulk($chunk, $snapshotId, $is_test) {
    $reply = array();

    usleep(500);

    CRM_Donrec_Logic_Exporter::addLogEntry($reply, 'Dummy bulk-processed ' . count($chunk) . ' items.', CRM_Donrec_Logic_Exporter::LOG_TYPE_INFO);
    return $reply;
}

  /**
   * generate the final result
   *
   * @return array:
   *          'is_error': set if there is a fatal error
   *          'log': array with keys: 'type', 'level', 'timestamp', 'message'
   *          'download_url: URL to download the result
   *          'download_name: suggested file name for the download
   */
  public function wrapUp($chunk, $is_test, $is_bulk) {
    $reply = array();
    CRM_Donrec_Logic_Exporter::addLogEntry($reply, 'Dummy process ended.', CRM_Donrec_Logic_Exporter::LOG_TYPE_INFO);
    return $reply;
  }
}

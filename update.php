<?php

/**
 * Class schemaUpdate
 *
 * Schema update utility methods
 *
 * @author Chris Seufert <chris@seufert.id.au>
 * @package schema
 */
class schemaUpdate {

  const RISK_NONE = 0x00;
  const RISK_LOW  = 0x02;
  const RISK_MED  = 0x08;
  const RISK_HIGH = 0x20;
  /**
   * Find all schema tables
   * @return schemaTable[] List of Table Schemas
   */
  static function getAllSchema() {
    pluginHook_Util::loadAllPHP();
    $sC = new ReflectionClass("schemaColumn");
    $sT = new ReflectionClass("schemaTable");
    $tables = array();
    foreach(get_declared_classes() as $class) {
      $rc = new ReflectionClass($class);
      $doc = $rc->getDocComment();
      $m = array();
      if(preg_match('/@dbTable ([a-z0-9]+)/', $doc, $m)) {
        $tables[] = schemaTable::loadComment($doc);
      }
    }
    return $tables;
  }

  /**
   * Compare schema between PHP Doc Schema and Live Database
   */
  static function compareSchema() {
    $risk = self::RISK_NONE;
    $sql = array();
    $tables = self::getAllSchema();
    foreach($tables as $table) {
      foreach($table->sqlDiff($risk) as $q) $sql[] = $q;
    }
    var_dump($risk, $sql);
  }

}
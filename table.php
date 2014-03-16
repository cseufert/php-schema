<?php

/**
 * Class schemaTable
 *
 * Schema Table mapping class
 *
 * @author Chris Seufert <chris@seufert.id.au>
 * @pckage schema
 */
class schemaTable {
  /** @var string Name of Table */
  protected $name;
  /** @var schemaColumn[] List of Columns  */
  protected $cols = array();
  /** @var schemaIndex[] List of Indexes  */
  protected $idx = array();

  /**
   * Create new Table Definition
   * @param string $name Name Of Table
   */
  function __construct($name) {
    $this->name = $name;
    $params = func_get_args();
    array_shift($params);
    foreach($params as $col) {
      /** @var $col schemaColumn */
      $this->cols[$col->getName()] = $col;
    }
  }

  /**
   * Load a Table from current Database
   * @param string $table Name of Table to Load
   * @return schemaTable Schema table loaded from database
   */
  static function loadDb($table) {
    $tab = new self($table);
    $cols = stdDB::selectArray("SHOW COLUMNS FROM `$table`");
    foreach($cols as $col)
      $tab->cols[$col['Field']] = new schemaColumn($col['Field'],$col['Type'],
                $col['Null'], $col['Default'], $col['Extra']);
    $rows = stdDB::selectArray("SHOW INDEX FROM `$table`");
    $idxrow = array(); $idxs = array();
    foreach($rows as $row){
      $idxrow[$row["Key_name"]][] = $row["Column_name"];
      $idxs[$row["Key_name"]] = $row;
    }
    foreach($idxs as $k=>$row) {
      $tab->idx[$k] = new schemaIndex($k, $idxrow[$k],
                              $row['Non_unique'], $row['Sub_part']);
    }
    return $tab;
  }

  /**
   * Load schema from PHP Doc Comment Block
   *
   * @param string $comment PHP Doc Block
   * @return schemaTable Schema table loaded from comment block
   */
  static function loadComment($comment) {
    if(!preg_match('/@dbTable ([a-z0-9]+)/', $comment, $mName))
      throw new Exception("Unable to find Table Name");
    $tbl = new self($mName[1]);
    $sC = new ReflectionClass("schemaColumn");
    if(!preg_match_all('/@dbColumn ([^\n]+)\n/', $comment, $mCol,
                                                    PREG_OFFSET_CAPTURE))
      throw new Exception("Unable to find a single Column");
    if($mCol) foreach($mCol[1] as $col) {
      $col = explode("|",$col[0]);
      foreach($col as $k => $v)
        $col[$k] = trim($v);
      $tbl->cols[$col[0]] = $sC->newInstanceArgs($col);
    }
    if(preg_match_all('/@dbIndex ([^\n]+)\n/', $comment, $mIdx,
                                                    PREG_OFFSET_CAPTURE)) {
      $sI = new ReflectionClass("schemaIndex");
      foreach($mIdx[1] as $idx) {
        $idx = explode("|",$idx[0]);
        $idxCols = explode(",",$idx[1]);
        foreach($idx as $k=>$v) $idx[$k] = trim($v);
        foreach($idxCols as $k=>$v) $idxCols[$k] = trim($v);
        $idx[1] = $idxCols;
        $tbl->idx[$idx[0]] = $sI->newInstanceArgs($idx);
      }
    }
    return $tbl;
  }

  /**
   * Get table name
   * @return string Table Name
   */
  function getName() { return $this->name; }

  /**
   * @param int $risk Risk Level for changes
   * @return array List of SQL Queries to make changes
   */
  function sqlDiff(&$risk) {
    $sql = array();
    try {
      $liveTable = self::loadDb($this->name);
      $myCols = array_keys($this->cols);
      $liveCols = array_keys($liveTable->cols);
      foreach(array_diff($myCols,$liveCols) as $addCol) {
        $sql[] = "ALTER TABLE `{$this->name}` ADD COLUMN ".
            $this->cols[$addCol]->getColDef();
        $risk = max($risk, schemaUpdate::RISK_LOW);
      }
      foreach(array_diff($liveCols,$myCols) as $delCol) {
        $sql[] = "ALTER TABLE `{$this->name}` DROP COLUMN `$delCol`";
        $risk = max($risk, schemaUpdate::RISK_HIGH);
      }
      foreach(array_intersect($myCols, $liveCols) as $col) {
        $change = $this->cols[$col]->compare($liveTable->cols[$col]);
        if($change) {
          $sql[] = "ALTER TABLE `{$this->name}` ".$change;
          $risk = max($risk, schemaUpdate::RISK_MED);
        }
      }
      $myIdx = array_keys($this->idx);
      $liveIdx = array_keys($liveTable->idx);
      foreach(array_diff($myIdx, $liveIdx) as $addIdx) {
        $sql[] = "ALTER TABLE `{$this->name}` ADD ".
                          $this->idx[$addIdx]->getIdxDef();
        $risk = max($risk, schemaUpdate::RISK_LOW);
      }
      foreach(array_diff($liveIdx, $myIdx) as $delIdx) {
        $sql[] = "ALTER TABLE `{$this->name}` DROP KEY `$delIdx`";
        $risk = max($risk, schemaUpdate::RISK_MED);
      }
      foreach(array_intersect($myIdx, $liveIdx) as $idx) {
        $change = $this->idx[$idx]->compare($liveTable->idx[$idx]);
        if($change) {
          $sql[] = "ALTER TABLE `{$this->name}` DROP KEY `$idx`";
          $sql[] = "ALTER TABLE `{$this->name}` ADD KEY ".
                                          $this->idx[$idx]->getIdxDef();
          $risk = max($risk, schemaUpdate::RISK_MED);
        }
      }
      return $sql;
    } catch (stdDB_ExQuery $e) {
      //create table cause its missing
      $cols = array();
      foreach($this->cols as $col) $cols[] = $col->getColDef();
      foreach($this->idx as $index) $cols[] = $index->getIdxDef();
      return array("CREATE TABLE ".$this->name." (".implode(",",$cols).")");
    }
  }

}
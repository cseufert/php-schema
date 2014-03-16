<?php

/**
 * Class schemaIndex
 *
 * Represents schema index
 *
 * @author Chris Seufert <chris@seufert.id.au>
 * @package schema
 */
class schemaIndex {
  /** @var string Name of Index */
  protected $name;
  /** @var array List of Columns in Index */
  protected $col = array();
  /** @var bool Not Unique flag */
  protected $notUnique;
  /** @var int|null size of index  */
  protected $size;

  /**
   * @param string $name Name of Index
   * @param string $cols List of Columns in Index
   * @param bool $notUnique Not Unique Flag
   * @param null|int $size Size of Index
   */
  function __construct($name, $cols, $notUnique = true, $size = NULL) {
    $this->name = $name;
    $this->rows = $cols;
    $this->notUnique = ($this->name == "PRIMARY")?FALSE:$notUnique && true;
    $this->size = ($size == "NULL")?0:$size;
  }

  /**
   * Get Index Name
   * @return string Name of Index
   */
  function getName() {
    return $this->name;
  }

  /**
   * Get Index Denifinition
   * @return string SQL INDEX Definition
   */
  function getIdxDef() {
    $len = $this->size?"({$this->size})":"";
    $uni = $this->notUnique?"":"UNIQUE";
    if($this->name == "PRIMARY")
      return "PRIMARY KEY (`".implode("`, `",$this->rows)."`)";
    else
      return "$uni KEY `{$this->name}` (`".implode("`, `",$this->rows)."`$len)";
  }

  /**
   * Compare this Index to another Index
   * @param schemaIndex $idx Index to compare with
   * @return string SQL Alter KEY string
   */
  function compare(schemaIndex $idx) {
    $alter = false;
    foreach(array("name","notUnique","size") as $k) {
      if(0 != strcasecmp($this->$k,$idx->$k))
        $alter = true;
    }
    if(array_diff($this->rows, $idx->rows) ||
                                    array_diff($idx->rows, $this->rows))
      $alter = true;
    if($alter)
      return $this->getIdxDef();
    return "";
  }

}
<?php

/**
 * Class schemaColumn
 *
 * Schema column matching
 *
 * @author Chris Seufert <chris@seufert.id.au>
 * @package schema
 */
class schemaColumn {
  /** @var string Name of Column */
  protected $name;
  /** @var string SQL Type of Column */
  protected $type;
  /** @var string Fields holds null value YES or NO */
  protected $null;
  /** @var string Default value for column */
  protected $default;
  /** @var string Extra parameters like auto_increment */
  protected $extra;

  /**
   * Define a new column
   * @param string $name Column Name
   * @param string $type Column Data Type
   * @param string $null Column can hold NULL
   * @param string $default Column default value
   * @param string $extra Column extra params (like auto_increment)
   */
  function __construct($name, $type, $null = "NO",
                                              $default = NULL, $extra = "") {
    $this->name = $name;
    $this->type = $type;
    $this->null = $null;
    $this->default = is_null($default)?"NULL":$default;
    $this->extra = $extra;
  }

  /**
   * Compare this column with another column
   * @param schemaColumn $col Comlumn to compare me with
   * @return string Returns string if changes are required with change statement
   */
  function compare(schemaColumn $col) {
    $alter = false;
    foreach(array("name","type","null","default","extra") as $k)
      if(0 != strcasecmp($this->$k,$col->$k))
        $alter = true;
    if($alter) {
      return "CHANGE `{$this->name}` ".$this->getColDef();
    }
    return "";
  }

  /**
   * Get a column definition
   * @return string SQL Create table definition
   */
  function getColDef() {
    $null = (0 == strcasecmp($this->null,"YES"))?"NULL":"NOT NULL";
    $default = $this->default != "NULL"?"DEFAULT '{$this->default}'":"";
    return "`{$this->name}` {$this->type} {$null} {$default} {$this->extra}";
  }

  /**
   * Retrieve Column Name
   * @return string Name of Column
   */
  function getName() {
    return $this->name;
  }
}
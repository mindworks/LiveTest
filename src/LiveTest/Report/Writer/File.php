<?php

namespace LiveTest\Report\Writer;

use LiveTest\Exception;

class File implements Writer
{
  private $filename;
  
  public function __construct(\Zend_Config $config)
  {
    $this->filename = $config->filename;    
  }
  
  public function write($formatedText)
  {          
    file_put_contents($this->filename, $formatedText);
  }
}

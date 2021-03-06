<?php

namespace LiveTest\Extensions;

use LiveTest\TestRun\Information;
use Base\Http\ConnectionStatus;

use LiveTest\TestRun\Properties;

use Base\Http\Response;

use LiveTest\Extensions\Extention;
use LiveTest\TestRun\Test;
use LiveTest\TestRun\Result\Result;

class RunInfoHeader implements Extension
{
  public function __construct($runId,\Zend_Config $config = null, $arguments = null)
  {
  }
  
  public function preRun(Properties $properties)
  {
    echo "  Default Domain  : " . $properties->getDefaultDomain()->toString()."\n";
    echo "  Start Time      : " . date( 'Y-m-d H:i:s' )."\n\n";
    echo "  Number of URIs  : " . count($properties->getTestSets())."\n";  
    echo "  Number of Tests : " . $this->getTotalTestCount($properties)."\n\n";
  }
  
  private function getTotalTestCount(Properties $properties)
  {
    $count = 0;
    foreach ($properties->getTestSets() as $testSet)
    {
      $count += $testSet->getTestCount();
    }
    return $count;
  }
  
  public function handleResult(Result $result, Response $response)
  {
  }
  
  public function handleConnectionStatus(ConnectionStatus $status)
  {
    
  }
  
  public function postRun(Information $information)
  {
  }
}
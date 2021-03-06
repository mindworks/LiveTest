<?php

namespace LiveTest\TestRun;

use Base\Http\HttpClient;
use Base\Timer\Timer;

use Base\Http\ConnectionStatus;

use Base\Www\Uri;

use LiveTest\Extensions\Extension;
use LiveTest\TestCase\Exception;
use LiveTest\TestRun\Result\ResultSet;
use LiveTest\TestRun\Result\Result;

use Base\Http\Response;
use Base\Http\Client;

class Run
{
  /**
   * All properties for the test run
   * 
   * @var LiveTest\TestRun\Properties
   */
  private $properties;
  private $httpClient = null;
  
  private $extensions = array();
  
  public function addExtension(Extension $extension)
  {
    $this->extensions[] = $extension;
  }
  
  public function __construct(Properties $properties, HttpClient $httpClient)
  {
    $this->httpClient = $httpClient;
    // @todo is the properties object needed? TestSet would work as well
    $this->properties = $properties;
  }
  
  private function extensionsPostRun(Information $information)
  {
    foreach ($this->extensions as $extension)
    {
      $extension->postRun($information);
    }
  }
  
  private function handleResult(Result $result, Response $response)
  {
    foreach ($this->extensions as $extension)
    {
      $extension->handleResult($result, $response);
    }
  }
  
  private function handleConnectionStatus(ConnectionStatus $status)
  {
    foreach ($this->extensions as $extension)
    {
      $extension->handleConnectionStatus($status);
    }
  }
  
  private function extensionsPreRun()
  {
    foreach ($this->extensions as $extension)
    {
      if ($extension->preRun($this->properties) === false)
      {
        return false;
      }
    }
    return true;
  }
  
  private function runTests(TestSet $testSet, Response $response)
  {
    foreach ($testSet->getTests() as $test)
    {
      $testCaseName = $test->getClassName();
      try
      {
        $testCaseObject = new $testCaseName($test->getParameter());
        $testCaseObject->test($response, new Uri($testSet->getUrl()));
        $result = new Result($test, Result::STATUS_SUCCESS, '', $testSet->getUrl());
      }
      catch (\LiveTest\TestCase\Exception $e )
      {
        $result = new Result($test, Result::STATUS_FAILED, $e->getMessage(), $testSet->getUrl());
      }
      catch ( \Exception $e )
      {
        $result = new Result($test, Result::STATUS_ERROR, $e->getMessage(), $testSet->getUrl());
      }
      $this->handleResult($result, $response);
    }
  }
  
  private function getHttpClient()
  {
    return $this->httpClient;
  }
  
  public function run()
  {
    
    $continueRun = $this->extensionsPreRun();
    if ($continueRun)
    {
      $timer = new Timer();
      $testSets = $this->properties->getTestSets();
      $client = $this->getHttpClient();
      
      foreach ($testSets as $testSet)
      {
        try
        {
          $client->setUri($testSet->getUrl());
          $response = $client->request();
          $this->handleConnectionStatus(new ConnectionStatus(ConnectionStatus::SUCCESS, new Uri($testSet->getUrl())));
        }
        catch (\Zend_Http_Client_Adapter_Exception $e )
        {
          $this->handleConnectionStatus(new ConnectionStatus(ConnectionStatus::ERROR, new Uri($testSet->getUrl()), $e->getMessage()));
          continue;
        }
        catch (\Zend_Http_Client_Exception $e )
        {
          $this->handleConnectionStatus(new ConnectionStatus(ConnectionStatus::ERROR, new Uri($testSet->getUrl()), $e->getMessage()));
        }
        $this->runTests($testSet, $response);
      }
      $timer->stop();
      $information = new Information($timer->getElapsedTime(), $this->properties->getDefaultDomain());
      $this->extensionsPostRun($information);
    }
  }
}

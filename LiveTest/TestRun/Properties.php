<?php

// @fixme Diese Datei muss gerefactored werden!!!
//        - Harte Kopplung an yaml
//        - Lange Methoden
//        - doppelter Code bei Page Und PageList
//        - Config Merge vll. Teil einer anderen Klasse

// @todo PageLists auf TestCase-Ebene
// @todo Urls auf G�ltigkeit testen 


namespace LiveTest\TestRun;

use Base\Config\Yaml;

use Base\Www\Uri;
use Base\Config\Config;

class Properties
{
  private $config;
  private $pages;
  private $defaultDomain;
  
  private $configPath;
  
  private $testSets = array();
  
  public function __construct(Config $config, $defaultDomain)
  {
    $this->configPath = dirname($config->getFilename());
    
    if (is_null($config->TestSuite))
    {
      throw new Exception('The mandatory "TestSuite" root element is missing.');
    }
    $this->defaultDomain = $defaultDomain;
    $this->config = $config->TestSuite;
    $this->extractConfig();
  }
  
  private function extractConfig()
  {
    $this->extractStandardUrls();
    $pages = $this->getStandardUrls();
    foreach ($this->config->TestCases as $testEntityName => $testCase)
    {
      if (!is_null($testCase->Pages))
      {
        $testCasePages = $this->getPageArray($testCase->Pages);
      }
      elseif ((!is_null($testCase->RemovedPages)) || !is_null($testCase->AdditionalPages))
      {
        $testCasePages = $pages;
        if (!is_null($testCase->RemovedPages))
        {
          $removedPages = $this->getPageArray($testCase->RemovedPages);
          $testCasePages = array_diff($testCasePages, $removedPages);
        }
        if (!is_null($testCase->AdditionalPages))
        {
          $additionalPages = $this->getPageArray($testCase->AdditionalPages);
          $testCasePages = array_merge($testCasePages, $additionalPages);
        }
      }
      else
      {
        $testCasePages = $pages;
      }
      
      foreach ($testCasePages as $testCasePage)
      {
        if (!array_key_exists($testCasePage, $this->testSets))
        {
          $this->testSets[$testCasePage] = new TestSet($testCasePage);
        }
        $parameter = $testCase->Parameter;
        $this->testSets[$testCasePage]->addTest(new Test($testEntityName, $testCase->TestCase, $parameter));
      }
    }
  }
  
  private function getStandardPages()
  {
    $config = $this->config;
    
    $pageConfig = $config->Pages;
    if ($pageConfig == null)
    {
      $pageConfig = array();
    }
    
    $pages = array();
    $domain = new Uri($this->defaultDomain);
    
    foreach ($pageConfig as $page)
    {
      $pages[] = $domain->concatUri((string)$page);
    }
    return $pages;
  }
  
  private function getPageLists()
  {
    $config = $this->config->PageLists;
    if ($config == null)
    {
      $config = array();
    }
    $pages = array();
    $pagesListPage = array();
    $domain = new Uri($this->defaultDomain);
    foreach ($config as $pageList)
    {
      $pageListConfig = new Yaml($this->configPath . '/' . (string)$pageList);
      $pageList = $pageListConfig->Pages->toArray();
      foreach ($pageList as $page)
      {
        $pageListPage[] = $domain->concatUri((string)$page);
      }
      $pages = array_merge($pages, $pageListPage);
    }
    return $pages;
  }
  
  private function getPageArray()
  {
    $pages = array_merge($this->getStandardPages(), $this->getPageLists());
    return $pages;
  }
  
  private function extractStandardUrls()
  {
    $this->pages = $this->getPageArray();
  }
  
  private function getStandardUrls()
  {
    return $this->pages;
  }
  
  public function getDefaultDomain( )
  {
    return $this->defaultDomain;
  }
  
  public function getTestSets()
  {
    return $this->testSets;
  }
}
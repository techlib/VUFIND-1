<?php

require_once "sys/Recommend/Interface.php";
require_once "sys/Logger.php";


class PSH implements RecommendationInterface
{
  private $baseSettings;
  private $searchObject;
     
  public function __construct($searchObject, $params) 
  {
    $this->logger = new Logger();
    $this->searchObject = $searchObject;
    
    $iniFile = isset($params[1]) ? $params[1] : "facets";
    $config = getExtraConfigArray($iniFile);
    $this->baseSettings = array(
      "cols" => $config["Results_Settings"]["top_cols"]
    );
  }
  
  public function init() {}

  public function process()
  {
    global $interface;
    
    $indexEngine = $this->searchObject->getIndexEngine();

    // Získat query string
    $search = $this->searchObject->getSearchTerms();
    $lookfor = isset($search[0]["lookfor"]) ? $search[0]["lookfor"] : "";
    
    // Validate query
    $query = $indexEngine->validateInput($lookfor);
    if (empty($query)) {
      return array();
    }
    
    $this->searchObject = SearchObjectFactory::initSearchObject("SolrAuth");
    
    $this->searchObject->setQueryString("heading:".$query);
    $result = $this->searchObject->processSearch(true);
    $this->searchObject->close();
    
    // Zpracovat si $result do $pshSet 
    if (isset($result["response"]["docs"])) {
      $result = $result["response"]["docs"][0];
      
      $this->logger->log(json_encode($result), PEAR_LOG_ERR);
  
      $interface->assign("see_also", isset($result["see_also"]) ? $result["see_also"] : array());
      $interface->assign("broader", isset($result["broader"]) ? $result["broader"] : array());
      $interface->assign("narrower", isset($result["narrower"]) ? $result["narrower"] : array());
      
      $interface->assign('topFacetSettings', $this->baseSettings);
    }
    else {
      return array();
    }

  }
    
  /* getTemplate
   *
   * This method provides a template name so that recommendations can be displayed
   * to the end user.  It is the responsibility of the process() method to
   * populate all necessary template variables.
   *
   * @access  public
   * @return  string      The template to use to display the recommendations.
   */
  public function getTemplate() 
  {
    return "Search/Recommend/PSH.tpl";
  }
}
?>

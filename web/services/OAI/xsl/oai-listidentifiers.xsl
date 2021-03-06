<?xml version="1.0"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                              xmlns:php="http://php.net/xsl"
                              xsl:extension-element-prefixes="php">

  <xsl:output method="xml" indent="yes"/>
  
  <xsl:template match="/">
    <OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/" 
             xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
             xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/
             http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">  
      <responseDate><xsl:value-of select="php:function('date', 'Y-m-d\TH:i:s\Z')"/></responseDate>
      <request verb="ListIdentifiers">http://digital.library.villanova.edu/OAIServer.php</request>
      <ListIdentifiers>
        <xsl:for-each select="//doc">
          <header>
            <identifier><xsl:value-of select="concat('oai:', 'digital.library.villanova.edu:', substring(parent::node()/@name, string-length('/db/DigitalLibrary/ ')), '/', node())"/></identifier>
            <datestamp><xsl:value-of select="php:function('getISODate', string(@modified))"/></datestamp>
            <setSpec><xsl:value-of select="concat('collection:', translate(substring(parent::node()/@name, string-length('/db/DigitalLibrary/ ')), '/', ':'))"/></setSpec>
          </header>
        </xsl:for-each>
      </ListIdentifiers>
    </OAI-PMH>
  </xsl:template>

  <xsl:template match="doc">  
  </xsl:template>
  
</xsl:stylesheet>
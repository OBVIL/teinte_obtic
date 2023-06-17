<?xml version="1.0" encoding="UTF-8"?>
<xsl:transform xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0"
  xmlns="http://www.tei-c.org/ns/1.0" 
  xmlns:epub="http://www.idpf.org/2007/ops"
  
  xmlns:html="http://www.w3.org/1999/xhtml"
  xmlns:teinte="https://oeuvres.github.io/teinte"
  xmlns:opf="http://www.idpf.org/2007/opf"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:dcterms="http://purl.org/dc/terms/"
  
  exclude-result-prefixes="dc dcterms html epub opf teinte"
  xmlns:exslt="http://exslt.org/common"
  xmlns:date="http://exslt.org/dates-and-times"
  xmlns:php="http://php.net/xsl"
  extension-element-prefixes="date exslt php"
  >

  <xsl:output indent="yes" encoding="UTF-8" method="xml" omit-xml-declaration="yes"/>
  <xsl:variable name="lf" select="'&#10;'"/>
  <!-- 1234567890 -->
  <xsl:variable name="ÂBC">ABCDEFGHIJKLMNOPQRSTUVWXYZÀÂÄÆÇÉÈÊËÎÏÑÔÖŒÙÛÜ _-,</xsl:variable>
  <xsl:variable name="âbc">abcdefghijklmnopqrstuvwxyzàâäæçéèêëîïñôöœùûü </xsl:variable>
  <xsl:variable name="abc">abcdefghijklmnopqrstuvwxyzaaaeceeeeiinooeuuu _-</xsl:variable>
  <!-- A key maybe used on styles for perfs -->
  <xsl:variable name="sheet" select="document('styles.xml', document(''))"/>
  <xsl:key name="class" match="teinte:class" use="@name"/>
  <xsl:key name="id" match="*[@id]" use="@id"/>
  <xsl:template match="html:*">
    <xsl:element name="{local-name()}">
      <xsl:apply-templates select="node() | @*"/>
    </xsl:element>
  </xsl:template>
  <xsl:template match="node()">
    <xsl:copy>
      <xsl:apply-templates select="node() | @*"/>
    </xsl:copy>
  </xsl:template>
  <xsl:template match="@*">
    <xsl:copy/>
  </xsl:template>
  <xsl:template match="html:link | html:script | html:style"/>
  <xsl:template match="html:meta[@http-equiv]"/>
<!--
STRUCTURE
-->
  <xsl:template match="html:html">
    <xsl:call-template name="procs"/>
    <TEI>
      <xsl:apply-templates select="node() | @*"/>
    </TEI>
  </xsl:template>
  
  <xsl:template name="procs">
    <xsl:processing-instruction name="xml-model"> href="http://oeuvres.github.io/teinte/teinte.rng" type="application/xml" schematypens="http://relaxng.org/ns/structure/1.0"</xsl:processing-instruction>
    <xsl:processing-instruction name="xml-stylesheet"> type="text/xsl" href="https://oeuvres.github.io/teinte_xsl/tei_html.xsl"</xsl:processing-instruction>
  </xsl:template>
  
  <xsl:template match="/html:article | /html:section | html:div">
    <xsl:call-template name="procs"/>
    <TEI>
      <text>
        <body>
          <xsl:apply-templates select="node() | @*"/>
        </body>
      </text>
    </TEI>
  </xsl:template>
  <xsl:template match="html:head">
    <teiHeader>
      <xsl:apply-templates/>
    </teiHeader>
  </xsl:template>
  <xsl:template match="html:body">
    <text>
      <xsl:apply-templates select="@*"/>
      <body>
        <xsl:apply-templates/>
      </body>
    </text>
  </xsl:template>
  
  <xsl:template name="mixed">
    <xsl:variable name="text">
      <xsl:for-each select="text()">
        <!-- tested, normalize is faster here -->
        <xsl:value-of select="translate(normalize-space(.), ' ', '')"/>
      </xsl:for-each>
    </xsl:variable>
    <xsl:value-of select="$text"/>
  </xsl:template>

  <xsl:template match="html:section | html:article">
    <div type="{local-name()}">
      <xsl:apply-templates select="@*"/>
      <xsl:variable name="mixed">
        <xsl:call-template name="mixed"/>
      </xsl:variable>
      <xsl:choose>
        <xsl:when test="$mixed = '' and count(*) = 1 and html:div">
          <xsl:apply-templates select="html:div/node()"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:apply-templates/>
        </xsl:otherwise>
      </xsl:choose>
    </div>
  </xsl:template>
  <xsl:template match="html:img">
    <graphic>
      <xsl:copy-of select="@*"/>
    </graphic>
  </xsl:template>
  
  <xsl:template name="map_xml">
    <xsl:param name="map_class"/>
      <xsl:comment>map_xml, $map_class='<xsl:value-of select="$map_class"/>'</xsl:comment>
    <xsl:if test="normalize-space($map_class) = ''">
      <xsl:message terminate="yes">map_xml, dev error $map_class='<xsl:value-of select="$map_class"/>'</xsl:message>
    </xsl:if>
    <xsl:variable name="map_tei">
      <xsl:for-each select="$sheet">
        <xsl:value-of select="key('class', $map_class)[1]/@tei"/>
      </xsl:for-each>
    </xsl:variable>
    <xsl:if test="normalize-space($map_tei) = ''">
      <xsl:message terminate="yes">map_xml, dev error no mapping found for $map_class='<xsl:value-of select="$map_class"/>'</xsl:message>
    </xsl:if>
    <xsl:element name="{$map_tei}">
      <xsl:variable name="map_rend">
        <xsl:for-each select="$sheet">
          <xsl:value-of select="key('class', $map_class)[1]/@rend"/>
        </xsl:for-each>
      </xsl:variable>
      <xsl:if test="$map_rend != ''">
        <xsl:attribute name="rend">
          <xsl:value-of select="$map_rend"/>
        </xsl:attribute>
      </xsl:if>
      <xsl:variable name="map_type">
        <xsl:for-each select="$sheet">
          <xsl:value-of select="key('class', $map_class)[1]/@type"/>
        </xsl:for-each>
      </xsl:variable>
      <xsl:if test="$map_type != ''">
        <xsl:attribute name="type">
          <xsl:value-of select="$map_type"/>
        </xsl:attribute>
      </xsl:if>
      <xsl:apply-templates/>
    </xsl:element>
  </xsl:template>
  
  <!-- 
  Test if div is a hierarchical grouping a false para
  -->
  <xsl:template match="html:div">
    <xsl:variable name="class" select="translate(@class, $ÂBC, $abc)"/>
    <xsl:variable name="id" select="translate(@id, $ÂBC, $abc)"/>
    <xsl:variable name="map_class">
      <xsl:call-template name="map_class"/>
    </xsl:variable>
    <xsl:variable name="mixed">
      <xsl:call-template name="mixed"/>
    </xsl:variable>
    <xsl:choose>
      <!-- epub type -->
      <xsl:when test="@epub:type='title'">
        <head>
          <xsl:apply-templates select="@*|node()"/>
        </head>
      </xsl:when>
      <!-- spacer -->
      <xsl:when test="$mixed = '' and not(*)">
        <space quantity="1" unit="line"/>
      </xsl:when>
      <!-- image container -->
      <xsl:when test="$mixed = '' and count(*) = 1 and html:img">
        <figure>
          <xsl:apply-templates/>
        </figure>
      </xsl:when>
      <!-- class name with mapped element -->
      <xsl:when test="$map_class != ''">
        <xsl:call-template name="map_xml">
          <xsl:with-param name="class" select="$map_class"/>
        </xsl:call-template>
      </xsl:when>
      <!-- probably paragraph -->
      <xsl:when test="$mixed != ''">
        <p>
          <xsl:apply-templates select="@*"/>
          <xsl:apply-templates/>
        </p>
      </xsl:when>
      <xsl:when test="$mixed = '' and count(*) = 1 and html:i">
        <p>
          <xsl:apply-templates select="@*"/>
          <xsl:call-template name="rend">
            <xsl:with-param name="suffix">i</xsl:with-param>
          </xsl:call-template>
          <xsl:apply-templates select="html:i/node()"/>
        </p>
      </xsl:when>
      <!-- no sibling paras, strip ? -->
      <xsl:when test="not(../html:p)">
        <xsl:apply-templates/>
      </xsl:when>
      <xsl:otherwise>
        <quote>
          <xsl:apply-templates select="@*"/>
          <xsl:attribute name="type">div</xsl:attribute>
          <xsl:apply-templates/>
        </quote>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  <!-- Wikisource -->
  <xsl:template match="
    html:div[@class='tableItem']
    ">
    <!-- strip -->
  </xsl:template>
  <xsl:template match="html:ol[@class='references']">
    <div type="notes">
      <xsl:apply-templates/>
    </div>
  </xsl:template>
  <xsl:template match="html:ol[@class='references']/html:li">
    <note>
      <xsl:attribute name="xml:id">
        <xsl:choose>
          <xsl:when test="starts-with(@id, 'cite_note-')">
            <xsl:text>fn</xsl:text>
            <xsl:value-of select="substring-after(@id, 'cite_note-')"/>
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="@id"/>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:attribute>
      <xsl:apply-templates/>
    </note>
  </xsl:template>
<!--
BLOCKS
-->
  <!-- Hierarchical headers is not sure -->
  <xsl:template match="html:h1 | html:h2 | html:h3 | html:h4 | html:h5 | html:h6">
    <!--
    <xsl:text disable-output-escaping="yes">
&lt;/div>
&lt;div>
</xsl:text>
-->
    <head>
      <xsl:apply-templates select="@*"/>
      <xsl:attribute name="type">
        <xsl:value-of select="local-name()"/>
      </xsl:attribute>
      <xsl:apply-templates/>
    </head>
  </xsl:template>
  <!--
  <xsl:template match="html:h1//text() | html:h2//text() | html:h3//text() | html:h4//text() | html:h5//text() | html:h6//text()">
    <xsl:value-of select="translate(., $ABC, $abc)"/>
  </xsl:template>
  -->
  <xsl:template match="html:ul | html:ol">
    <list>
      <xsl:apply-templates select="@*"/>
      <xsl:attribute name="type">
        <xsl:value-of select="local-name()"/>
      </xsl:attribute>
      <xsl:apply-templates/>
    </list>
  </xsl:template>
  <xsl:template match="html:li">
    <item>
      <xsl:apply-templates select="@*"/>
      <xsl:apply-templates/>
    </item>
  </xsl:template>
  <xsl:template match="html:blockquote">
    <quote>
      <xsl:apply-templates select="node() | @*"/>
    </quote>
  </xsl:template>
  <xsl:template match="html:p">
    <xsl:variable name="class" select="translate(@class, $ÂBC, $abc)"/>
    <xsl:variable name="id" select="translate(@id, $ÂBC, $abc)"/>
    <xsl:variable name="map_class">
      <xsl:call-template name="map_class"/>
    </xsl:variable>
    <xsl:variable name="mixed">
      <xsl:call-template name="mixed"/>
    </xsl:variable>
    <xsl:choose>
      <!-- spacer -->
      <xsl:when test="$mixed = '' and not(*)">
        <space quantity="1" unit="line"/>
      </xsl:when>
      <!-- ??
      <xsl:when test="$mixed = '' and normalize-space(.) = '' and not(*[local-name != 'img'])">
        <xsl:apply-templates/>
      </xsl:when>
      -->
      <xsl:when test="$mixed = '' and count(*) = 1 and *[@class='pagenum']">
        <xsl:apply-templates/>
      </xsl:when>
      <!-- class name with mapped element -->
      <xsl:when test="$map_class != ''">
        <xsl:call-template name="map_xml">
          <xsl:with-param name="class" select="$map_class"/>
        </xsl:call-template>
      </xsl:when>
      <!-- bad practice but seen -->
      <xsl:when test="ancestor::html:div[@class='poetry' or @class='stanza' or @class='poem' or @class='strophe' or @class='strophec'] ">
        <l>
          <xsl:apply-templates select="node()|@*"/>
        </l>
      </xsl:when>
      <xsl:otherwise>
        <p>
          <xsl:apply-templates select="@*"/>
          <xsl:choose>
            <xsl:when test="@class = 'p2'"/>
            <xsl:when test="$class=''"/>
            <xsl:otherwise>
              <xsl:attribute name="rend">
                <xsl:value-of select="$class"/>
              </xsl:attribute>
            </xsl:otherwise>
          </xsl:choose>
          <xsl:apply-templates/>
        </p>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
<!-- 
PHRASES
  -->
  <!-- typographic phrasing -->
  <xsl:template match="html:b | html:small | html:strong | html:sub | html:u">
    <hi>
      <xsl:apply-templates select="@*"/>
      <xsl:attribute name="rend">
        <xsl:value-of select="local-name(.)"/>
        <xsl:if test="@class != ''">
          <xsl:text> </xsl:text>
          <xsl:value-of select="normalize-space(@class)"/>
        </xsl:if>
      </xsl:attribute>
      <xsl:apply-templates/>
    </hi>
  </xsl:template>
  <xsl:template match="html:i">
    <hi>
      <xsl:apply-templates select="@*"/>
      <xsl:apply-templates/>
    </hi>
  </xsl:template>
  <!-- -->
  <xsl:template match="html:sup">
    <xsl:variable name="mixed">
      <xsl:call-template name="mixed"/>
    </xsl:variable>
    <xsl:choose>
      <!-- probably a foot note ref -->
      <xsl:when test="$mixed = '' and count(*)=1 and html:a">
        <xsl:apply-templates select="*"/>
      </xsl:when>
      <xsl:when test="@class='reference'">
        <xsl:apply-templates/>
      </xsl:when>
      <xsl:otherwise>
        <hi rend="sup">
          <xsl:apply-templates/>
        </hi>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  <!-- specific to Gutenberg ? -->
  <xsl:template match="html:cite">
    <title>
      <xsl:apply-templates select="@*"/>
      <xsl:apply-templates/>
    </title>
  </xsl:template>
  <xsl:template match="html:br">
    <xsl:value-of select="$lf"/>
    <lb/>
  </xsl:template>
  
  <!-- get an element name from a class name -->
  <xsl:template name="map_class">
    <xsl:param name="class" select="normalize-space(translate(@class, $ÂBC, $abc))"/>
    <xsl:variable name="map_class">
      <xsl:variable name="txt">
        <xsl:call-template name="map_loop">
          <xsl:with-param name="class" select="$class"/>
        </xsl:call-template>
      </xsl:variable>
      <xsl:value-of select="normalize-space($txt)"/>
    </xsl:variable>
    <!-- multiple class can match more than el, choose first -->
    <xsl:value-of select="substring-before(concat($map_class, ' '), ' ')"/>
  </xsl:template>
  
  <xsl:template name="map_loop">
    <xsl:param name="class"/>
    <xsl:choose>
      <xsl:when test="normalize-space($class = '')"/>
      <xsl:when test="contains($class, ' ')">
        <xsl:call-template name="map_loop">
          <xsl:with-param name="class" select="substring-before($class, ' ')"/>
        </xsl:call-template>
        <xsl:call-template name="map_loop">
          <xsl:with-param name="class" select="substring-after($class, ' ')"/>
        </xsl:call-template>
      </xsl:when>
      <!-- simple class search in list -->
      <xsl:otherwise>
        <xsl:for-each select="$sheet">
          <xsl:if test="count(key('class', $class)) &gt; 0">
            <xsl:value-of select="$class"/>
          </xsl:if>
          <xsl:text> </xsl:text>
        </xsl:for-each>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  <!-- 
  Non semantic html element, try to translate
  -->
  <xsl:template match="html:span">
    <xsl:variable name="class" select="translate(@class, $ÂBC, $abc)"/>
    <xsl:variable name="id" select="translate(@id, $ÂBC, $abc)"/>
    <xsl:variable name="map_class">
      <xsl:call-template name="map_class"/>
    </xsl:variable>

    <xsl:choose>
      <xsl:when test="@epub:type = 'pagebreak' or @role='doc-pagebreak'">
        <pb>
          <xsl:apply-templates select="@title|@id|node()"/>
        </pb>
      </xsl:when>
      <!-- specific not well handle by mapping -->
      <xsl:when test="contains(@class, 'pagenum')">
        <pb n="{@id}"/>
      </xsl:when>
      <xsl:when test="$map_class != ''">
        <xsl:call-template name="map_xml">
          <xsl:with-param name="map_class" select="$map_class"/>
        </xsl:call-template>
      </xsl:when>
      <!-- span class="i3 smcap" -->
      <xsl:when test="contains(@class, 'smcap') and ancestor::*[@class='quote']">
        <author>
          <xsl:apply-templates/>
        </author>
      </xsl:when>
      <xsl:when test="@lang | @xml:lang">
        <foreign>
          <xsl:apply-templates select="@*"/>
          <xsl:apply-templates/>
        </foreign>
      </xsl:when>
      <xsl:when test="$class='sc'">
        <hi rend="sc">
          <xsl:apply-templates/>
        </hi>
      </xsl:when>
      <xsl:when test="@class='add2em'">
        <seg type="tab">    </seg>
        <xsl:apply-templates/>
      </xsl:when>
      <xsl:when test="@class='add4em'">
        <seg type="tab">    </seg>
        <seg type="tab">    </seg>
        <xsl:apply-templates/>
      </xsl:when>
      <xsl:when test="@class='add6em'">
        <seg type="tab">    </seg>
        <seg type="tab">    </seg>
        <seg type="tab">    </seg>
        <xsl:apply-templates/>
      </xsl:when>
      <xsl:when test="@class='add8em'">
        <seg type="tab">    </seg>
        <seg type="tab">    </seg>
        <seg type="tab">    </seg>
        <seg type="tab">    </seg>
        <xsl:apply-templates/>
      </xsl:when>
      <xsl:otherwise>
        <seg>
          <xsl:apply-templates select="@*"/>
          <xsl:apply-templates/>
        </seg>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  <xsl:template match="html:hr"/>
  <xsl:template match="html:em">
    <emph>
      <xsl:apply-templates select="@* | node()"/>
    </emph>
  </xsl:template>
  <!-- Links -->
  <xsl:template match="html:a">
    <xsl:variable name="id">
      <xsl:if test="substring(@href, 1, 1) = '#'">
        <xsl:value-of select="substring-after(@href, '#')"/>
      </xsl:if>
    </xsl:variable>
    <xsl:choose>
      <!-- keep anchors, maybe a page number -->
      <xsl:when test=". = '' and @id">
        <anchor>
          <xsl:attribute name="xml:id">
            <xsl:value-of select="@id"/>
          </xsl:attribute>
        </anchor>
      </xsl:when>
      <!-- back note -->
      <xsl:when test="@class = 'footnote-backref'"/>
      <!-- 
<sup id="fnref1:1"><a href="#fn:1" class="footnote-ref">1</a></sup>
      -->
      <xsl:when test="$id != '' and (@class = 'fn' or @class='footnote-ref')">
        <xsl:variable name="note">
          <xsl:apply-templates select="key('id', $id)/node()"/>
        </xsl:variable>
        <xsl:choose>
          <xsl:when test="count(key('id', $id)) &gt; 0">
            <note>
              <xsl:apply-templates select="key('id', $id)/node()"/>
            </note>
          </xsl:when>
          <xsl:otherwise>
            <xsl:call-template name="ref"/>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:when>
      <xsl:otherwise>
        <xsl:call-template name="ref"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  <xsl:template name="ref">
    <ref>
      <xsl:apply-templates select="@*"/>
      <xsl:apply-templates/>
    </ref>
  </xsl:template>
  <xsl:template match="html:table">
    <table>
      <xsl:apply-templates select="node()|@*"/>
    </table>
  </xsl:template>
  <xsl:template match="html:tr">
    <row>
      <xsl:apply-templates select="node()|@*"/>
    </row>
  </xsl:template>
  <xsl:template match="html:td">
    <cell>
      <xsl:apply-templates select="node()|@*"/>
    </cell>
  </xsl:template>
  <xsl:template match="html:th">
    <cell role="label">
      <xsl:apply-templates select="node()|@*"/>
    </cell>
  </xsl:template>
  
  <!-- Gutenberg notices -->
  <xsl:template match="html:pre">
    <xsl:choose>
      <xsl:when test="contains(., 'Gutenberg')"/>
      <xsl:otherwise>
        <eg>
          <xsl:apply-templates select="node() | @*"/>
        </eg>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  
  <!-- Maping logic -->
  <xsl:template name="mapping">
    <xsl:param name="mapping"/>
    <xsl:choose>
      <!-- error ? -->
      <xsl:when test="not($mapping)"/>
      <xsl:when test="not($mapping/@tei)"/>
      <!-- Filter element by class -->
      <xsl:when test="$mapping/@tei = ''">
        <xsl:apply-templates/>
      </xsl:when>
      <xsl:otherwise>
        <xsl:element name="{$mapping/@tei}">
          <xsl:apply-templates select="@*[name() != 'class']"/>
          <xsl:if test="$mapping/@rend">
            <xsl:attribute name="rend">
              <xsl:value-of select="$mapping/@tei"/>
            </xsl:attribute>
          </xsl:if>
          <xsl:if test="$mapping/@type">
            <xsl:attribute name="type">
              <xsl:value-of select="$mapping/@type"/>
            </xsl:attribute>
          </xsl:if>
          <xsl:apply-templates select="@*[name() != 'class']"/>
          <xsl:apply-templates/>
        </xsl:element>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  
  <!-- 
 ATTRIBUTES
  -->
  <xsl:template match="html:*/@*" priority="0"/>
  
  <xsl:template match="@n | @rend | @xml:lang">
    <xsl:copy>
      <xsl:value-of select="."/>
    </xsl:copy>
  </xsl:template>
  
  <xsl:template match="@class">
    <xsl:param name="class" select="normalize-space(translate(., $ÂBC, $abc))"/>
    <xsl:choose>
      <!-- probably non semantic auto style like “t15”, keep ? -->
      <!--
      <xsl:when test="translate(substring($class, 2), '0123456789', '') = ''">
        
      </xsl:when>
      -->
      <xsl:when test="$class != ''">
        <xsl:attribute name="rend">
          <xsl:value-of select="$class"/>
        </xsl:attribute>
      </xsl:when>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="rend">
    <xsl:param name="suffix"/>
    <xsl:param name="class" select="translate(@class, $ÂBC, $abc)"/>
    <xsl:variable name="rend" select="normalize-space(concat($class, ' ', $suffix))"/>
    <xsl:if test="$rend != ''">
      <xsl:attribute name="rend">
        <xsl:value-of select="$rend"/>
      </xsl:attribute>
    </xsl:if>
  </xsl:template>

  <xsl:template match="@lang">
    <xsl:attribute name="xml:lang">
      <xsl:value-of select="."/>
    </xsl:attribute>
  </xsl:template>
  
  <xsl:template match="@name">
    <xsl:attribute name="xml:id">
      <xsl:value-of select="."/>
    </xsl:attribute>
  </xsl:template>

  <xsl:template match="@id">
    <xsl:attribute name="xml:id">
      <xsl:value-of select="."/>
    </xsl:attribute>
  </xsl:template>
  
  <xsl:template match="@title">
    <xsl:variable name="title" select="normalize-space(.)"/>
    <xsl:if test="$title != ''">
      <xsl:attribute name="n">
        <xsl:value-of select="."/>
      </xsl:attribute>
    </xsl:if>
  </xsl:template>

  <xsl:template name="debug">
    <xsl:for-each select="ancestor-or-self::*">
      <xsl:text>/</xsl:text>
      <xsl:variable name="name" select="name()"/>
      <xsl:value-of select="$name"/>
      <xsl:if test="count(../*[name()=$name]) &gt; 1">
        <xsl:text>[</xsl:text>
          <xsl:number/>
        <xsl:text>]</xsl:text>
      </xsl:if>
    </xsl:for-each>
  </xsl:template>
</xsl:transform>
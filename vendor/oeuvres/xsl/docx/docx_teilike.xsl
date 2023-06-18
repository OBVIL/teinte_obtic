<?xml version="1.0" encoding="UTF-8"?>
<xsl:transform version="1.0" 
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  
  xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
  xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006"
  xmlns:o="urn:schemas-microsoft-com:office:office"
  xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"
  xmlns:pkg="http://schemas.microsoft.com/office/2006/xmlPackage"
  xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
  xmlns:rels="http://schemas.openxmlformats.org/package/2006/relationships"
  xmlns:v="urn:schemas-microsoft-com:vml"
  xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"
  xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"

  xmlns:teinte="https://github.com/oeuvres/teinte_xsl"

  xmlns="http://www.tei-c.org/ns/1.0"
  exclude-result-prefixes="a mc pic pkg r rels teinte o v w wp"
  >
  <xsl:output encoding="UTF-8" indent="no" omit-xml-declaration="yes"/>
  <xsl:variable name="UC">ABCDEFGHIJKLMNOPQRSTUVWXYZ</xsl:variable>
  <xsl:variable name="lc">abcdefghijklmnopqrstuvwxyz</xsl:variable>
  <xsl:key name="footnotes" match="//w:footnotes/w:footnote" use="@w:id"/>
  <xsl:key name="endnotes" match="//w:endnotes/w:endnote" use="@w:id"/>
  <xsl:key name="document.xml.rels" 
    match="/pkg:package/pkg:part[@pkg:name = '/word/_rels/document.xml.rels']/pkg:xmlData/*/rels:Relationship" 
    use="@Id"/>
  <xsl:key name="footnotes.xml.rels" 
    match="/pkg:package/pkg:part[@pkg:name = '/word/_rels/ootnotes.xml.rels']/pkg:xmlData/*/rels:Relationship" 
    use="@Id"/>
  <xsl:key name="endnotes.xml.rels" 
    match="/pkg:package/pkg:part[@pkg:name = '/word/_rels/ndnotes.xml.rels']/pkg:xmlData/*/rels:Relationship" 
    use="@Id"/>
  <xsl:key name="w:style" 
    match="w:style" 
    use="@w:styleId"/>
  <xsl:key name="teinte_p" 
    match="teinte:style[@level='p']" 
    use="@name"/>
  <xsl:key name="teinte_c" 
    match="teinte:style[@level='c']" 
    use="@name"/>
  <xsl:key name="teinte_0" 
    match="teinte:style[@level='0']" 
    use="@name"/>
  <xsl:template match="node()|@*">
    <xsl:copy>
      <xsl:apply-templates select="node()|@*"/>
    </xsl:copy>
  </xsl:template>
  <xsl:template match="/">
    <xsl:apply-templates select="/pkg:package/pkg:part[@pkg:name = '/word/document.xml']/pkg:xmlData/w:document"/>
  </xsl:template>
  <!-- root element -->
  <xsl:template match="w:document">
    <xsl:apply-templates/>
  </xsl:template>
  <xsl:template match="w:body">
    <body>
      <!-- 1 page -->
      <xsl:if test=".//w:br[@w:type='page']">
        <pb/>
      </xsl:if>
      <xsl:apply-templates/>
    </body>
  </xsl:template>
  <!-- Go through -->
  <xsl:template match="mc:AlternateContent | mc:Choice | mc:Fallback">
    <xsl:apply-templates select="*"/>
  </xsl:template>
  <!-- block -->
  <xsl:template match="w:p">
    <xsl:variable name="pStyle" select="normalize-space(translate(w:pPr/w:pStyle/@w:val, $UC, $lc))"/>
    <xsl:variable name="teinte_p" select="key('teinte_p', $pStyle)"/>
    <xsl:variable name="w:style" select="key('w:style', w:pPr/w:pStyle/@w:val)"/>
    <xsl:variable name="lvl" select="$w:style/w:pPr/w:outlineLvl/@w:val"/>
    <xsl:choose>
      <!-- para in table cell -->
      <xsl:when test="ancestor::w:tc">
        <xsl:text>&#10;      </xsl:text>
      </xsl:when>
      <xsl:when test="w:pPr/w:numPr">
        <xsl:text>&#10;</xsl:text>
      </xsl:when>
      <!-- para in footnote force line break -->
      <xsl:when test="ancestor::w:footnote|ancestor::w:endnote">
        <xsl:text>&#10;    </xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <xsl:text>&#10;&#10;</xsl:text>
      </xsl:otherwise>
    </xsl:choose>
    <!-- get some local rendering -->
    <xsl:variable name="_rend">
      <xsl:if test="number(w:pPr/w:ind/@w:hanging) &gt; 150"> hanging </xsl:if>
      <xsl:if test="number(w:pPr/w:ind/@w:firstLine) &gt; 150"> indent </xsl:if>
      <xsl:text> </xsl:text>
      <xsl:value-of select="w:pPr/w:jc/@w:val"/>
    </xsl:variable>
    <xsl:variable name="rend" select="normalize-space($_rend)"/>
    <xsl:choose>
      <xsl:when test="$lvl != '' and not(ancestor::w:tc|ancestor::w:footnote)">
        <head level="{$lvl+1}">
          <xsl:apply-templates select="w:hyperlink | w:r"/>
        </head>
      </xsl:when>
      <!-- list item, TODO listStyle, see in w:pPr/w:numPr/w:numId/@w:val -->
      <xsl:when test="w:pPr/w:numPr">
        <item level="{w:pPr/w:numPr/w:ilvl/@w:val + 1}">
          <xsl:apply-templates select="w:hyperlink | w:r"/>
        </item>
      </xsl:when>
      <xsl:when test="$pStyle = '' or key('teinte_0', $pStyle)">
        <p>
          <xsl:if test="$rend != ''">
            <xsl:attribute name="rend">
              <xsl:value-of select="$rend"/>
            </xsl:attribute>
          </xsl:if>
          <xsl:apply-templates select="w:hyperlink | w:r"/>
        </p>
      </xsl:when>
      <xsl:when test="$teinte_p/@parent != ''">
        <xsl:element name="{$teinte_p/@parent}">
          <xsl:text>&#10;  </xsl:text>
          <xsl:element name="{$teinte_p/@element}">
            <xsl:if test="$rend != ''">
              <xsl:attribute name="rend">
                <xsl:value-of select="$rend"/>
              </xsl:attribute>
            </xsl:if>
            <xsl:if test="$teinte_p/@attribute">
              <xsl:attribute name="{$teinte_p/@attribute}">
                <xsl:value-of select="$teinte_p/@value"/>
              </xsl:attribute>
            </xsl:if>
            <xsl:apply-templates select="w:hyperlink | w:r"/>
          </xsl:element>
          <xsl:text>&#10;</xsl:text>
        </xsl:element>
      </xsl:when>
      <xsl:when test="$teinte_p/@element">
        <xsl:element name="{$teinte_p/@element}">
          <xsl:if test="$teinte_p/@attribute">
            <xsl:attribute name="{$teinte_p/@attribute}">
              <xsl:value-of select="$teinte_p/@value"/>
            </xsl:attribute>
          </xsl:if>
          <xsl:apply-templates select="w:hyperlink | w:r"/>
        </xsl:element>
      </xsl:when>
      <!-- Output unknown style -->
      <xsl:when test="$pStyle != ''">
        <xsl:variable name="el">
          <xsl:if test="translate(substring($pStyle, 1, 1), 'abcdefghijklmnopqrstuvwxyz', '') != ''">_</xsl:if>
          <xsl:value-of select="$pStyle"/>
        </xsl:variable>
        <xsl:element name="{$el}">
          <xsl:if test="$rend != ''">
            <xsl:attribute name="rend">
              <xsl:value-of select="$rend"/>
            </xsl:attribute>
          </xsl:if>
          <xsl:apply-templates select="w:hyperlink | w:r"/>
        </xsl:element>
      </xsl:when>
      <xsl:otherwise>
        <p>
          <xsl:if test="$rend != ''">
            <xsl:attribute name="rend">
              <xsl:value-of select="$rend"/>
            </xsl:attribute>
          </xsl:if>
          <xsl:apply-templates select="w:hyperlink | w:r"/>
        </p>
      </xsl:otherwise>
    </xsl:choose>
    <xsl:if test="(ancestor::w:footnote|ancestor::w:endnote) and position() = last()">
      <xsl:text>&#10;</xsl:text>
    </xsl:if>
  </xsl:template>
  <xsl:template match="w:br">
    <xsl:text>&#10;</xsl:text>
    <lb/>
  </xsl:template>
  <xsl:template match="w:br[@w:type='page']">
    <xsl:text>&#10;</xsl:text>
    <pb/>
    <xsl:text>&#10;</xsl:text>
  </xsl:template>
  <xsl:template match="w:noBreakHyphen">
    <xsl:text>‑</xsl:text>
  </xsl:template>
  <!-- Do not output auto page break but only explicit page breaks -->
  <xsl:template match="w:lastRenderedPageBreak">
  </xsl:template>
  <xsl:template match="w:t">
    <xsl:value-of select="."/>
  </xsl:template>
  <!-- image 

<w:drawing>
  <wp:inline distT="0" distB="0" distL="0" distR="0" wp14:anchorId="46B9EEF1" wp14:editId="3570CC40">
    <wp:extent cx="4558430" cy="3237230"/>
    <wp:effectExtent l="0" t="0" r="1270" b="1270"/>
    <wp:docPr id="4" name="Image 4"/>
    <wp:cNvGraphicFramePr/>
    <a:graphic>
      <a:graphicData>
        <pic:pic>
          <pic:nvPicPr>
            <pic:cNvPr id="1" name="Picture 1"/>
            <pic:cNvPicPr/>
          </pic:nvPicPr>
          <pic:blipFill rotWithShape="1">

            <a:blip r:embed="rId8"/>

            <a:srcRect l="1094"/>
            <a:stretch/>
          </pic:blipFill>
        </pic:pic>
      </a:graphicData>
    </a:graphic>
  </wp:inline>
</w:drawing>


<w:pict>
  <v:shape id="Image 1" o:spid="_x0000_i1025" type="#_x0000_t75" alt=":::Graphique cercle des sciences" style="width:159.2pt;height:37.1pt;visibility:visible;mso-wrap-style:square">
    <v:imagedata r:id="rId7" o:title="Graphique cercle des sciences"/>
  </v:shape>
</w:pict>
-->
  <xsl:template match="w:drawing | w:pict">
    <xsl:text>&#10;</xsl:text>
    <figure>
      <xsl:text>&#10;  </xsl:text>
      <graphic>
        <xsl:variable name="target">
          <xsl:choose>
            <xsl:when test="v:shape/v:imagedata">
              <xsl:call-template name="target">
                <xsl:with-param name="id" select="v:shape/v:imagedata/@r:id"/>
              </xsl:call-template>
            </xsl:when>
            <xsl:when test="wp:inline/a:graphic/a:graphicData/pic:pic/pic:blipFill/a:blip">
              <xsl:call-template name="target">
                <xsl:with-param name="id" select="wp:inline/a:graphic/a:graphicData/pic:pic/pic:blipFill/a:blip/@r:embed"/>
              </xsl:call-template>
            </xsl:when>
          </xsl:choose>
        </xsl:variable>
        <xsl:attribute name="url">
          <xsl:value-of select="$target"/>
        </xsl:attribute>
        <xsl:apply-templates select="wp:inline/wp:extent"/>
      </graphic>
      <xsl:apply-templates select="v:shape/v:imagedata/@o:title"/>
      <xsl:text>&#10;</xsl:text>
    </figure>
    <xsl:text>&#10;</xsl:text>
  </xsl:template>

  <!-- Image size
  <wp:extent cx="4558430" cy="3237230"/>
 -->
  <xsl:template match="wp:extent">
    <xsl:attribute name="width">
      <xsl:value-of select="@cx"/>
    </xsl:attribute>
    <xsl:attribute name="height">
      <xsl:value-of select="@cy"/>
    </xsl:attribute>
    <xsl:attribute name="subtype">unit:EMU</xsl:attribute>
  </xsl:template>

  <xsl:template match="v:imagedata/@o:title">
    <xsl:text>&#10;  </xsl:text>
    <head>
      <xsl:value-of select="."/>
    </head>
  </xsl:template>
  <!-- Do nothing with that -->
  <xsl:template match="w:rPr"/>
  <!-- chars -->
  <xsl:template match="w:r">
    <!-- 
LibreOffice
<w:r>
  <w:rPr/>
  <w:t>Une ligne avec saut de ligne</w:t>
  <w:br/>
  <w:t>Ligne de suite</w:t>
</w:r>
Seen
<w:r w:rsidR="00E74673" w:rsidRPr="00C04750">
  <w:rPr>
    <w:i/>
  </w:rPr>
  <w:br w:type="page"/>
</w:r>

      <w:r>
        <w:rPr>
          <w:b w:val="0"/>
          <w:bCs w:val="0"/>
          <w:i w:val="0"/>
          <w:iCs w:val="0"/>
          <w:smallCaps w:val="0"/>
          <w:u w:val="none"/>
        </w:rPr>
        
-->
    <!-- style tag -->
    <xsl:variable name="w:style" select="key('w:style', w:rPr/w:rStyle/@w:val)"/>
    <xsl:variable name="class" select="normalize-space(translate(w:rPr/w:rStyle/@w:val, $UC, $lc))"/>
    <xsl:variable name="teinte_c" select="key('teinte_c', $class)"/>
    <!-- process children in order, for line breaks, see <w:br/> in LibreOffice -->
    <xsl:variable name="t">
      <xsl:apply-templates select="*"/>
    </xsl:variable>
    
    <!-- Background color -->
    <xsl:variable name="hi">
      <xsl:variable name="val" select="normalize-space(w:rPr/w:highlight/@w:val)"/>
      <xsl:value-of select="$val"/>
    </xsl:variable>
    
    <!-- underline -->
    <xsl:variable name="u">
      <xsl:variable name="val" select="w:rPr/w:u/@w:val"/>
      <xsl:choose>
        <xsl:when test="not(w:rPr/w:u)"/>
        <xsl:when test="not($w:style) and not($w:style/w:rPr/w:u)"/>
        <xsl:when test="$val = '0' or $val='false' or $val = 'off' or $val = 'none'"/>
        <xsl:when test="$val != ''">
          <xsl:value-of select="$val"/>
        </xsl:when>
      </xsl:choose>
    </xsl:variable>
    <!-- small caps -->
    <xsl:variable name="sc">
      <xsl:variable name="val" select="w:rPr/w:smallCaps/@w:val"/>
      <xsl:choose>
        <xsl:when test="not(w:rPr/w:u)"/>
        <xsl:when test="$val = '0' or $val='false' or $val = 'off'"/>
        <xsl:otherwise>
          <xsl:value-of select="$val"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <!-- italic -->
    <xsl:variable name="i">
      <xsl:variable name="val1" select="w:rPr/w:i/@w:val"/>
      <xsl:variable name="val2" select="w:rPr/w:iCs/@w:val"/>
      <xsl:choose>
        <xsl:when test="not(w:rPr/w:i) and not(w:rPr/w:iCs)"/>
        <xsl:when test="$val1 = '0' or $val1 ='false' or $val1 = 'off'"/>
        <xsl:when test="$val1 != ''">
          <xsl:value-of select="$val1"/>
        </xsl:when>
        <xsl:otherwise>true</xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <!-- bold, dangerous in titles, to think -->
    <xsl:variable name="b">
      <xsl:variable name="val1" select="w:rPr/w:b"/>
      <xsl:variable name="val2" select="w:rPr/w:bCs"/>
      <xsl:choose>
        <xsl:when test="not(w:rPr/w:b) and not(w:rPr/w:bCs)"/>
        <xsl:when test="$val1 = '0' or $val1 ='false' or $val1 = 'off'"/>
        <xsl:when test="$val1 != ''">
          <xsl:value-of select="$val1"/>
        </xsl:when>
        <xsl:when test="w:rPr/w:b">true</xsl:when>
        <xsl:when test="$val2 = '0' or $val2 ='false' or $val2 = 'off'"/>
        <xsl:otherwise/>
      </xsl:choose>
    </xsl:variable>
    
    <xsl:variable name="subsup" select="w:rPr/w:vertAlign/@w:val"/>

    <xsl:variable name="xml1">
      <xsl:choose>
        <!-- probably footnote reference or graphic, do not put in <tag> -->
        <xsl:when test="$t = ''">
           <xsl:copy-of select="$t"/>
        </xsl:when>
        <xsl:when test="$subsup = 'superscript'">
          <sup>
            <xsl:copy-of select="$t"/>
          </sup>
        </xsl:when>
        <xsl:when test="$subsup = 'subscript'">
          <sup>
            <xsl:copy-of select="$t"/>
          </sup>
        </xsl:when>
        <xsl:otherwise>
          <xsl:copy-of select="$t"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="xml2">
      <xsl:choose>
        <!-- probably footnote reference or graphic, do not put in <sc> -->
        <xsl:when test="$xml1 = ''">
           <xsl:copy-of select="$xml1"/>
        </xsl:when>
        <xsl:when test="$sc = ''">
          <xsl:copy-of select="$xml1"/>
        </xsl:when>
        <xsl:otherwise>
          <sc>
            <xsl:copy-of select="$xml1"/>
          </sc>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    
    
    <xsl:variable name="xml3">
      <xsl:choose>
        <xsl:when test="$i = ''">
           <xsl:copy-of select="$xml2"/>
        </xsl:when>
        <xsl:otherwise>
          <hi>
            <xsl:copy-of select="$xml2"/>
          </hi>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>

    <xsl:variable name="xml4">
      <xsl:choose>
        <xsl:when test="$u = ''">
           <xsl:copy-of select="$xml3"/>
        </xsl:when>
        <xsl:otherwise>
          <u>
            <xsl:copy-of select="$xml3"/>
          </u>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>

    <xsl:variable name="xml5">
      <xsl:choose>
        <xsl:when test="$b = ''">
          <xsl:copy-of select="$xml4"/>
        </xsl:when>
        <xsl:otherwise>
          <b>
            <xsl:copy-of select="$xml4"/>
          </b>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    

    <xsl:variable name="el">
      <xsl:choose>
        <!-- elements not starting by a letter -->
        <xsl:when test="translate(substring($class, 1, 1), 'abcdefghijklmnopqrstuvwxyz', '') != ''">_</xsl:when>
      </xsl:choose>
      <xsl:value-of select="$class"/>
    </xsl:variable>
    <xsl:variable name="xml10">
      <xsl:choose>
        <xsl:when test="$class = ''">
          <xsl:copy-of select="$xml5"/>
        </xsl:when>
        <!-- redundant -->
        <xsl:when test="ancestor::w:hyperlink">
          <xsl:copy-of select="$xml5"/>
        </xsl:when>
        <xsl:when test="$teinte_c/@element != ''">
          <xsl:element name="{$teinte_c/@element}">
            <xsl:copy-of select="$xml5"/>
          </xsl:element>
        </xsl:when>
        <xsl:when test="key('teinte_0', $class)">
          <xsl:copy-of select="$xml5"/>
        </xsl:when>
        <!-- auto style Calibre -->
        <xsl:when test="substring($el, 1, 1) = '_' and $w:style/w:rPr">
          <xsl:variable name="val" select="$w:style/w:rPr/w:i/@w:val"/>
          <xsl:choose>
            <xsl:when test="$val = '' or $val = '0' or $val ='false' or $val = 'off'">
              <seg rend="{$class}">
                <xsl:copy-of select="$xml5"/>
              </seg>
            </xsl:when>
            <xsl:otherwise>
              <i>
                <xsl:copy-of select="$xml5"/>
              </i>
            </xsl:otherwise>
          </xsl:choose>
        </xsl:when>
        <xsl:when test="$class != ''">
          <xsl:element name="{$el}">
            <xsl:copy-of select="$xml5"/>
          </xsl:element>
        </xsl:when>
        <xsl:otherwise>
          <xsl:copy-of select="$xml5"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <!-- Background level -->
    <xsl:variable name="xml11">
      <xsl:choose>
        <xsl:when test="$hi != ''">
          <xsl:element name="bg_{$hi}">
            <xsl:copy-of select="$xml10"/>
          </xsl:element>
        </xsl:when>
        <xsl:otherwise>
          <xsl:copy-of select="$xml10"/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:copy-of select="$xml11"/>
  </xsl:template>
  <xsl:template match="w:sectPr"/>
  <!-- spaces -->
  <xsl:template match="w:tab">
    <space type="tab">
      <xsl:text>    </xsl:text>
    </space>
  </xsl:template>
  <!-- 
      <w:tblGrid>
      <w:gridCol w:w="3020" />
      <w:gridCol w:w="3021" />
      <w:gridCol w:w="3021" />
    </w:tblGrid>
    -->
  <xsl:template match="w:tbl">
    <xsl:text>&#10;&#10;</xsl:text>
    <table>
      <xsl:apply-templates select="w:tr"/>
      <xsl:text>&#10;</xsl:text>
    </table>
  </xsl:template>
  <xsl:template match="w:tr">
    <xsl:text>&#10;  </xsl:text>
    <row>
      <xsl:apply-templates select="w:tc"/>
      <xsl:text>&#10;  </xsl:text>
    </row>
  </xsl:template>
  <xsl:template match="w:tc">
    <xsl:text>&#10;    </xsl:text>
    <cell>
      <xsl:apply-templates/>
    <xsl:text>&#10;    </xsl:text>
    </cell>
  </xsl:template>
  <!-- Info cell -->
  <xsl:template match="w:tcPr"/>
  <!-- Notes -->
  <xsl:template match="w:footnoteReference">
    <xsl:for-each select="key('footnotes',@w:id)">
      <note place="foot">
        <xsl:choose>
          <xsl:when test="count(w:p) = 1">
            <xsl:apply-templates select="w:p/w:hyperlink | w:p/w:r"/>
          </xsl:when>
          <xsl:otherwise>
            <xsl:apply-templates select="*"/>
          </xsl:otherwise>
        </xsl:choose>
      </note>
    </xsl:for-each>
  </xsl:template>
  <!-- number in note -->
  <xsl:template match="w:footnoteRef | w:endnoteRef"/>
  <xsl:template match="w:endnoteReference">
    <xsl:for-each select="key('endnotes',@w:id)">
      <note place="end">
        <xsl:choose>
          <xsl:when test="count(w:p) = 1">
            <xsl:apply-templates select="w:p/w:hyperlink | w:p/w:r"/>
          </xsl:when>
          <xsl:otherwise>
            <xsl:apply-templates select="*"/>
          </xsl:otherwise>
        </xsl:choose>
      </note>
    </xsl:for-each>
  </xsl:template>
  <xsl:template name="target">
    <xsl:param name="id"/>
    <xsl:choose>
      <xsl:when test="ancestor::w:endnote">
        <xsl:for-each select="key('endnotes.xml.rels', $id)">
          <xsl:value-of select="@Target"/>
        </xsl:for-each>
      </xsl:when>
      <xsl:when test="ancestor::w:footnote">
        <xsl:for-each select="key('footnotes.xml.rels', $id)">
          <xsl:value-of select="@Target"/>
        </xsl:for-each>
      </xsl:when>
      <xsl:otherwise>
        <xsl:for-each select="key('document.xml.rels', $id)">
          <xsl:value-of select="@Target"/>
        </xsl:for-each>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  <xsl:template match="w:hyperlink">
    <ref>
      <xsl:variable name="target">
        <xsl:call-template name="target">
          <xsl:with-param name="id" select="@r:id"/>
        </xsl:call-template>
      </xsl:variable>
      <xsl:if test="$target != ''">
        <xsl:attribute name="target">
          <xsl:value-of select="$target"/>
        </xsl:attribute>
      </xsl:if>
      <xsl:apply-templates select="w:r"/>
    </ref>
  </xsl:template>
  <xsl:template match="w:bookmarkStart"/>
  <xsl:template match="w:bookmarkEnd"/>
  <!-- fields -->
  <xsl:template match="w:fldChar"/>
  <xsl:template match="w:instrText">
    <xsl:variable name="text" select="normalize-space(.)"/>
    <xsl:choose>
      <!-- Is it bad ? -->
      <xsl:when test="$text = ''"/>
      <xsl:otherwise>
        <field>
          <xsl:attribute name="name">
            <xsl:value-of select="substring-before(concat($text, ' '), ' ')"/>
          </xsl:attribute>
          <xsl:value-of select="substring-before(substring-after($text, '&quot;'), '&quot;')"/>
        </field>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
</xsl:transform>

<?xml version="1.0" encoding="utf-8"?>
<xsl:transform version="1.0" 
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:tei="http://www.tei-c.org/ns/1.0"
  exclude-result-prefixes="tei"

  xmlns:str="http://exslt.org/strings"
  xmlns:exslt="http://exslt.org/common" 
  extension-element-prefixes="str exslt"
  >
  <!-- 
TEI Styleshest for LaTeX, forked from Sebastian Rahtz
https://github.com/TEIC/Stylesheets/tree/dev/latex
  
1. Distributed under a Creative Commons Attribution-ShareAlike 3.0
   License http://creativecommons.org/licenses/by-sa/3.0/ 
2. http://www.opensource.org/licenses/BSD-2-Clause

A light version for XSLT1, with local improvements.
2021, frederic.glorieux@fictif.org
  -->
  
  <xsl:template match="tei:row">
    <xsl:choose>
      <xsl:when test="normalize-space(.) = ''">
        <xsl:text>\midrule&#10;</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <xsl:choose>
          <!-- nothing done for merged cells here, see after row content -->
          <xsl:when test="tei:cell[@cols]"/>
          <!-- probably bottom labels, top borders -->
          <xsl:when test="
            @role = 'label'
            and preceding-sibling::tei:row[1][not(@role) or @role != 'label']
            ">
            <xsl:for-each select="tei:cell">
              <xsl:if test="normalize-space(.) !=''">
                <xsl:text>\cmidrule</xsl:text>
                <xsl:choose>
                  <xsl:when test="position() = 1">(r)</xsl:when>
                  <xsl:when test="position() = last()">(l)</xsl:when>
                  <xsl:otherwise>(rl)</xsl:otherwise>
                </xsl:choose>
                <xsl:text>{</xsl:text>
                <xsl:value-of select="position()"/>
                <xsl:text>-</xsl:text>
                <xsl:value-of select="position()"/>
                <xsl:text>} </xsl:text>
              </xsl:if>
            </xsl:for-each>
            <xsl:text>&#10;</xsl:text>
          </xsl:when>
        </xsl:choose>
        <xsl:apply-templates select="tei:cell"/>
        <!-- for booktabs, all row needs a \\ -->
        <xsl:text> \\&#10;</xsl:text>
        <xsl:choose>
          <xsl:when test="tei:cell[@cols]">
            <xsl:for-each select="tei:cell">
              <xsl:if test="normalize-space(.) != ''">
                <xsl:variable name="pos">
                  <xsl:for-each select="preceding-sibling::tei:cell">
                    <xsl:text>-</xsl:text>
                    <xsl:value-of select="substring('--------------------------', 1, number(@cols) - 1)"/>
                  </xsl:for-each>
                </xsl:variable>
                <xsl:variable name="from" select="string-length($pos) + 1"/>
                <xsl:variable name="to">
                  <xsl:choose>
                    <xsl:when test="number(@cols) &gt; 1">
                      <xsl:value-of select="$from + number(@cols) - 1"/>
                    </xsl:when>
                    <xsl:otherwise>
                      <xsl:value-of select="$from"/>
                    </xsl:otherwise>
                  </xsl:choose>
                </xsl:variable>
                  
                <xsl:text>\cmidrule(rl){</xsl:text>
                <xsl:value-of select="$from"/>
                <xsl:text>-</xsl:text>
                <xsl:value-of select="$to"/>
                <xsl:text>} </xsl:text>
              </xsl:if>
            </xsl:for-each>
            <xsl:text>&#10;</xsl:text>
          </xsl:when>
          <!-- Head row, or middle row, with label 
          and not(preceding-sibling::tei:row[not(@role) or @role != 'label'])
          -->
          <xsl:when test="
            @role = 'label' 
            and following-sibling::tei:row[1][not(@role) or @role != 'label']
            
            ">
            <xsl:for-each select="tei:cell">
              <xsl:if test="normalize-space(.) !=''">
                <xsl:text>\cmidrule</xsl:text>
                <xsl:choose>
                  <xsl:when test="position() = 1">(r)</xsl:when>
                  <xsl:when test="position() = last()">(l)</xsl:when>
                  <xsl:otherwise>(rl)</xsl:otherwise>
                </xsl:choose>
                <xsl:text>{</xsl:text>
                <xsl:value-of select="position()"/>
                <xsl:text>-</xsl:text>
                <xsl:value-of select="position()"/>
                <xsl:text>} </xsl:text>
              </xsl:if>
            </xsl:for-each>
            <xsl:text>&#10;</xsl:text>
          </xsl:when>
        </xsl:choose>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <!--
  <xsl:template match="tei:row">
    <xsl:if test="@role='label'">\rowcolor{label}</xsl:if>
    <xsl:apply-templates/>
    <xsl:if test="following-sibling::tei:row">
      <xsl:text>\\</xsl:text>
      <xsl:if test="@role='label' or parent::tei:table[contains(@rend, 'rules')]">\hline </xsl:if>
      <xsl:text>&#10;</xsl:text>
    </xsl:if>
  </xsl:template>
  -->
  
  <xsl:template match="tei:cell">
    <xsl:variable name="rend" select="concat(' ', normalize-space(@rend), ' ')"/>
    <xsl:variable name="length" select="string-length(normalize-space(.))"/>
    <xsl:variable name="isnum" select="string-length(translate(., '0123456789', '')) &lt; $length"/>
    <xsl:variable name="nonumbers" select="translate(., '0123456789', '') = ."/>
    <xsl:variable name="content">
      <xsl:choose>
        <xsl:when test="@role = 'label'">
          <xsl:text>\textbf{</xsl:text>
          <xsl:apply-templates/>
          <xsl:text>}</xsl:text>
        </xsl:when>
        <!--
        <xsl:when test="$nonumbers and $length &gt; 4">
          <xsl:text>{\footnotesize </xsl:text>
          <xsl:apply-templates/>
          <xsl:text>}</xsl:text>
        </xsl:when>
        -->
        <xsl:otherwise>
          <xsl:apply-templates/>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:choose>
      <xsl:when test="floor(@cols) &gt; 1">
        <xsl:text>\multicolumn{</xsl:text>
        <xsl:value-of select="floor(@cols)"/>
        <xsl:text>}</xsl:text>
        <xsl:text>{c}</xsl:text>
        <xsl:text>{</xsl:text>
        <xsl:copy-of select="$content"/>
        <xsl:text>}</xsl:text>
      </xsl:when>
      <xsl:when test="floor(@rows) &gt; 1">
        <xsl:text>\multirow{</xsl:text>
        <xsl:value-of select="floor(@rows)"/>
        <xsl:text>}</xsl:text>
        <xsl:text>{c}</xsl:text>
        <xsl:text>{</xsl:text>
        <xsl:copy-of select="$content"/>
        <xsl:text>}</xsl:text>
      </xsl:when>
      <xsl:when test="contains($rend, ' right ')">
        <xsl:text>\raggedleft\arraybackslash </xsl:text>
        <xsl:copy-of select="$content"/>
      </xsl:when>
      <xsl:when test="contains($rend, ' center ')">
        <xsl:text>\centering\arraybackslash </xsl:text>
        <xsl:copy-of select="$content"/>
      </xsl:when>
      <xsl:when test="contains($rend, ' left ')">
        <xsl:copy-of select="$content"/>
      </xsl:when>
      <!-- center label -->
      <xsl:when test="@role = 'label'">
        <xsl:text>\centering\arraybackslash </xsl:text>
        <xsl:copy-of select="$content"/>
      </xsl:when>
      <xsl:when test="$isnum != ''">
        <xsl:text>\raggedleft\arraybackslash </xsl:text>
        <xsl:copy-of select="$content"/>
      </xsl:when>
      <xsl:otherwise>
        <xsl:copy-of select="$content"/>
      </xsl:otherwise>
    </xsl:choose>
    <xsl:choose>
      <xsl:when test="following-sibling::tei:cell">&#10;   &amp; </xsl:when>
    </xsl:choose>
  </xsl:template>
  <!-- let justify -->
  <xsl:template match="tei:cell//tei:lb">
    <xsl:text> </xsl:text>
  </xsl:template>
  <xsl:template match="tei:figDesc"/>
  
  <xsl:template match="tei:figure">
    <xsl:choose>
      <xsl:when test="not(ancestor::tei:note)">
        <xsl:text>&#10;\begin{figure}[h]&#10;  \centering&#10;</xsl:text>
        <xsl:apply-templates/>
        <xsl:text>\end{figure}&#10;&#10;</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <xsl:apply-templates/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  <xsl:template match="tei:figure/tei:head">
    <xsl:text>\caption{</xsl:text>
    <xsl:apply-templates/>
    <xsl:text>}&#10;</xsl:text>
  </xsl:template>
  
  <xsl:template match="tei:graphic|tei:media">
    <xsl:call-template name="tei:makeHyperTarget"/>
    <!-- ??
    <xsl:choose>
      <xsl:when test="contains(@rend, 'noindent')">
        <xsl:text>\noindent</xsl:text>
      </xsl:when>
      <xsl:when test="not(preceding-sibling::*)">
        <xsl:text>\noindent</xsl:text>
      </xsl:when>
    </xsl:choose>
    -->
    <xsl:variable name="pic">
      <xsl:text>\noindent\includegraphics[width=\linewidth,</xsl:text>
      <!--
      <xsl:call-template name="graphicsAttributes">
        <xsl:with-param name="mode">latex</xsl:with-param>
      </xsl:call-template>
      -->
      <xsl:text>]{</xsl:text>
      <xsl:value-of select="@url"/>
      <xsl:text>}&#10;</xsl:text>
    </xsl:variable>
    <xsl:choose>
      <xsl:when test="parent::tei:ref">
        <xsl:value-of select="$pic"/>
      </xsl:when>
      <xsl:when test="not(following-sibling::tei:graphic[contains(@rend,'alignright')])
        and contains(@rend,'alignright')">
        <xsl:text>\begin{wrapfigure}{r}{</xsl:text>
        <xsl:value-of select="@width"/>
        <xsl:text>}</xsl:text>
        <xsl:value-of select="$pic"/>
        <xsl:text>\end{wrapfigure}</xsl:text>
      </xsl:when>
      <xsl:when test="not (following-sibling::tei:graphic[contains(@rend,'alignleft')])
        and contains(@rend,'alignleft')">
        <xsl:text>\begin{wrapfigure}{l}{</xsl:text>
        <xsl:value-of select="@width"/>
        <xsl:text>}</xsl:text>
        <xsl:value-of select="$pic"/>
        <xsl:text>\end{wrapfigure}</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="$pic"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  <xsl:template name="graphicsAttributes">
    <xsl:if test="@width">
      <xsl:variable name="unit" select="translate(@width, '  0123456789', '')"/>
      <xsl:choose>
        <xsl:when test="@subtype='unit:EMU'"/>
        <xsl:when test="$unit = '%'">
          <xsl:text>width=</xsl:text>
          <xsl:value-of select="number(substring-before(@width,'%')) div 100"/>
          <xsl:text>\linewidth,</xsl:text>
        </xsl:when>
        <xsl:otherwise>
          <xsl:variable name="w">
            <xsl:choose>
              <xsl:when test="$unit = 'px'">
                <xsl:value-of select="substring-before(@width,'px')"/>
                <xsl:text>pt</xsl:text>
              </xsl:when>
              <xsl:when test="$unit = ''">
                <xsl:value-of select="@width"/>
                <xsl:text>pt</xsl:text>
              </xsl:when>
              <xsl:otherwise>
                <xsl:value-of select="@width"/>
              </xsl:otherwise>
            </xsl:choose>
          </xsl:variable>
          <xsl:text>width=</xsl:text>
          <xsl:value-of select="$w"/>
          <xsl:text>,</xsl:text>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:if>
    <xsl:if test="not(@width)">width=\linewidth,</xsl:if>
    <xsl:if test="@height">
      <xsl:variable name="unit" select="translate(@height, '  0123456789', '')"/>
      <xsl:choose>
        <xsl:when test="@subtype='unit:EMU'"/>
        <xsl:when test="$unit = '%'">
          <xsl:text>height=</xsl:text>
          <xsl:value-of select="number(substring-before(@height,'%')) div 100"/>
          <xsl:text>\textheight,</xsl:text>
        </xsl:when>
        <xsl:otherwise>
          <xsl:variable name="h">
            <xsl:choose>
              <xsl:when test="$unit = 'px'">
                <xsl:value-of select="substring-before(@height,'px')"/>
                <xsl:text>pt</xsl:text>
              </xsl:when>
              <xsl:when test="$unit = ''">
                <xsl:value-of select="@height"/>
                <xsl:text>pt</xsl:text>
              </xsl:when>
              <xsl:otherwise>
                <xsl:value-of select="@height"/>
              </xsl:otherwise>
            </xsl:choose>
          </xsl:variable>
          <xsl:text>height=</xsl:text>
          <xsl:value-of select="$h"/>
          <xsl:text>,</xsl:text>
        </xsl:otherwise>
      </xsl:choose>
    </xsl:if>
    <!-- For larger graphics in XSL:FO, we want to make sure they're scaled 
     nicely to fit on the page. -->
    <!-- We have no idea how the height & width were specified for latex -->
    <xsl:variable name="unit" select="translate(@height, '  0123456789', '')"/>
    <xsl:variable name="s">
      <xsl:choose>
        <xsl:when test="@scale and $unit = '%'">
          <xsl:value-of select="number(substring-before(@scale,'%')) div 100"/>
        </xsl:when>
        <xsl:when test="@scale">
          <xsl:value-of select="@scale"/>
        </xsl:when>
        <!-- $standardScale ?
        <xsl:when test="not(@width) and not(@height) and not($standardScale=1)">
          <xsl:value-of select="$standardScale"/>
        </xsl:when>
        -->
      </xsl:choose>
    </xsl:variable>
    <xsl:if test="not($s='')">
      <xsl:text>scale=</xsl:text>
      <xsl:value-of select="$s"/>
      <xsl:text>,</xsl:text>
    </xsl:if>
  </xsl:template>
  
  

  
  <xsl:template match="tei:table" mode="xref">
    <xsl:text>the table on p. \pageref{</xsl:text>
    <xsl:value-of select="@xml:id"/>
    <xsl:text>}</xsl:text>
  </xsl:template>
  
  
  <!-- Tables using booktabs package  -->
  <xsl:template match="tei:table">
    <xsl:param name="type" select="@type"/>
    <xsl:call-template name="tei:makeHyperTarget"/>
    <!-- package float for table position  -->
    <xsl:text>\begin{table}[H]&#10;</xsl:text>
    <xsl:text>\tablestart&#10;</xsl:text>
    <xsl:text>\begin{tabularx}{1 \linewidth}</xsl:text>
    <xsl:text>{@{} </xsl:text>
    <xsl:choose>
      <!-- colspec in cols attribute -->
      <xsl:when test="@cols and string(number(@cols)) = 'NaN'">
        <xsl:value-of select="@cols"/>
      </xsl:when>
      <!-- Find the longest row used as template -->
      <xsl:otherwise>
        <xsl:for-each select="tei:row">
          <xsl:sort data-type="number" order="descending" select="count(tei:cell[not(@cols)])"/>
          <xsl:sort data-type="number" order="ascending" select="count(tei:cell[@role])"/>
          <xsl:if test="position() = 1">
            <xsl:for-each select="tei:cell">
              <xsl:choose>
                <xsl:when test="true()">X</xsl:when>
                <xsl:when test="contains(@rend, 'right')">r</xsl:when>
                <xsl:when test="contains(@rend, 'center')">c</xsl:when>
                <xsl:otherwise>l</xsl:otherwise>
              </xsl:choose>
            </xsl:for-each>
          </xsl:if>
        </xsl:for-each>
      </xsl:otherwise>
    </xsl:choose>
    <xsl:text> @{}}&#10;</xsl:text>
    <xsl:text>\toprule&#10;</xsl:text>
    <xsl:apply-templates select="tei:row"/>
    <xsl:text>&#10;\bottomrule&#10;</xsl:text>
    <xsl:text>\end{tabularx}&#10;</xsl:text>
    <xsl:text>\parnotes&#10;</xsl:text>
    <xsl:text>\end{table}&#10;</xsl:text>
  </xsl:template>
  
</xsl:transform>

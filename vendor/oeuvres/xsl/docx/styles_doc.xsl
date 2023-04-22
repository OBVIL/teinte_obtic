<?xml version="1.0" encoding="UTF-8"?>
<xsl:transform version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:teinte="https://github.com/oeuvres/teinte_xsl">
  <xsl:output omit-xml-declaration="yes" encoding="UTF-8"/>
  <xsl:template match="/*">
    <xsl:call-template name="md"/>
  </xsl:template>


  <xsl:template name="md">
    <xsl:text>
# Teinte, docx > tei

Teinte for docx (word processor format) supports basic formatting and styling. Find here the table of explicit word processor styles supported. Unknown styles are transformed as XML elements, even if it’s not correct TEI.

Entries are keywords (ascii lower case letter, without accents), but real life styles can be more human friendly, for example, `quotesalute` could appears as &lt;Quote, Salute&gt; for the user in his word processor (a style for a letter salutation in a citation).

Text processor styles may be “paragraph” level (visible as ¶ in text processor) or character level (inside a paragraph). Yous must ensure the level of your styles in your text processor if you want that Teinte works well. Microsoft.Office may create linked styles, for example one style name for Quote, allowed for a full paragraph or for quotes of some words inline. This may confused an automat. It is good idea to conceive your template of styles in LibreOffice, less confusing, even if your users prefers MS.Word for production.



</xsl:text>
    <xsl:text>
## Styles, paragraph level</xsl:text>
      <xsl:apply-templates select="*[@doc = 'true'][@level='p']" mode="md">
        <xsl:sort select="@name"/>
      </xsl:apply-templates>
    <xsl:text>
## Styles, character level</xsl:text>
      <xsl:apply-templates select="*[@doc = 'true'][@level='c']" mode="md">
        <xsl:sort select="@name"/>
      </xsl:apply-templates>
  </xsl:template>


  <xsl:template match="teinte:style" mode="md">
    <xsl:text>&#10;&#10;**</xsl:text>
    <xsl:value-of select="@name"/>
    <xsl:text>**</xsl:text>
    <xsl:text>&#10;```xml</xsl:text>
    <xsl:text>&#10;</xsl:text>
    <xsl:if test="@level = 'c'">character… </xsl:if>
    <xsl:choose>
      <xsl:when test="@parent">
        <xsl:element name="{@parent}">
          <xsl:text>&#10;  </xsl:text>
          <xsl:element name="{@element}">
            <xsl:if test="@attribute">
              <xsl:attribute name="{@attribute}">
                <xsl:value-of select="@value"/>
              </xsl:attribute>
            </xsl:if>
            <xsl:if test="@rend">
              <xsl:attribute name="rend">right italic… (direct formatting)</xsl:attribute>
            </xsl:if>
            <xsl:text>content ¶</xsl:text>
          </xsl:element>
          <xsl:text>&#10;</xsl:text>
        </xsl:element>
      </xsl:when>
      <xsl:otherwise>
        <xsl:element name="{@element}">
          <xsl:if test="@attribute">
            <xsl:attribute name="{@attribute}">
              <xsl:value-of select="@value"/>
            </xsl:attribute>
          </xsl:if>
          <xsl:if test="@rend">
            <xsl:attribute name="rend">right italic… (direct formatting)</xsl:attribute>
          </xsl:if>
          <xsl:text>content</xsl:text>
          <xsl:if test="@level = 'p'"> ¶</xsl:if>
        </xsl:element>
      </xsl:otherwise>
    </xsl:choose>
    <xsl:if test="@level = 'c'"> …level</xsl:if>
    <xsl:text>&#10;</xsl:text>
    <xsl:text>```</xsl:text>
  </xsl:template>
  
    <xsl:template match="teinte:style">
    <dt>
      <xsl:value-of select="@name"/>
    </dt>
    <dd>
      <pre>
        <xsl:if test="@level = 'c'">character… </xsl:if>
        <xsl:choose>
          <xsl:when test="@parent">
            <xsl:element name="{@parent}">
              <xsl:text>&#10;  </xsl:text>
              <xsl:element name="{@element}">
                <xsl:if test="@attribute">
                  <xsl:attribute name="{@attribute}">
                    <xsl:value-of select="@value"/>
                  </xsl:attribute>
                </xsl:if>
                <xsl:if test="@rend">
                  <xsl:attribute name="rend">right italic… (direct formatting)</xsl:attribute>
                </xsl:if>
                <xsl:text>content ¶</xsl:text>
              </xsl:element>
              <xsl:text>&#10;</xsl:text>
            </xsl:element>
          </xsl:when>
          <xsl:otherwise>
            <xsl:element name="{@element}">
              <xsl:if test="@attribute">
                <xsl:attribute name="{@attribute}">
                  <xsl:value-of select="@value"/>
                </xsl:attribute>
              </xsl:if>
              <xsl:if test="@rend">
                <xsl:attribute name="rend">right italic… (direct formatting)</xsl:attribute>
              </xsl:if>
              <xsl:text>content</xsl:text>
              <xsl:if test="@level = 'p'"> ¶</xsl:if>
            </xsl:element>
          </xsl:otherwise>
        </xsl:choose>
        <xsl:if test="@level = 'c'"> …level</xsl:if>
      </pre>
    </dd>
  </xsl:template>

</xsl:transform>

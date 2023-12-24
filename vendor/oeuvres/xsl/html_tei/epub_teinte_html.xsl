<?xml version="1.0" encoding="UTF-8"?>
<!-- 

Clean html extracted from epub of some oddities

-->
<xsl:transform xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0"
  xmlns="http://www.w3.org/1999/xhtml" 
  xmlns:html="http://www.w3.org/1999/xhtml"
  xmlns:epub="http://www.idpf.org/2007/ops"
  exclude-result-prefixes="html"
  >
  <!-- do not omit xml declaration for dom loading -->
  <!-- indent, yes or no ? mmmh… -->
  <xsl:output indent="yes" encoding="UTF-8" method="xml"/>
  <!-- space separated css class name to strip -->
  <xsl:param name="class_exclude"> chp italic niv1p txt </xsl:param>
  <xsl:variable name="class_ex" select="concat(' ', $class_exclude, ' ')"/>
  <xsl:variable name="class_in"/>
  <!-- load a possible css island  -->
  <xsl:key name="css" match="html:css/html:rule" use="@selector"/>
  <xsl:template match="node()|@*">
    <xsl:copy>
      <xsl:apply-templates select="node()|@*"/>
    </xsl:copy>
  </xsl:template>
  
  <xsl:template match="html:section">
    <xsl:param name="title" select="@title"/>
    <xsl:variable name="count" select="count(*)"/>
    <xsl:variable name="mixed">
      <xsl:call-template name="mixed"/>
    </xsl:variable>
    <xsl:choose>
      <!-- explicit epub section, give it instead of this one -->
      <xsl:when test="$count = 1 and html:section[@epub:type]">
        <!-- report title attribute (in case) -->
        <xsl:apply-templates>
          <xsl:with-param name="title" select="$title"/>
        </xsl:apply-templates>
      </xsl:when>
      <!-- generated section, keep it -->
      <xsl:when test="@data-src">
        <xsl:copy>
          <xsl:apply-templates select="@*|node()"/>
        </xsl:copy>
      </xsl:when>
      <!-- what ? text content ? -->
      <xsl:when test="$mixed != ''">
        <xsl:call-template name="p"/>
      </xsl:when>
      <xsl:when test="$count = 1 and html:section">
        <xsl:copy>
          <xsl:copy-of select="@*"/>
          <xsl:if test="$title != ''">
            <xsl:attribute name="title">
              <xsl:value-of select="$title"/>
            </xsl:attribute>
          </xsl:if>
          <xsl:apply-templates select="*/node()"/>
        </xsl:copy>
      </xsl:when>
      <xsl:when test="$count = 1 and html:div">
        <xsl:copy>
          <xsl:copy-of select="@*"/>
          <xsl:if test="$title != ''">
            <xsl:attribute name="title">
              <xsl:value-of select="$title"/>
            </xsl:attribute>
          </xsl:if>
          <xsl:variable name="div_mixed">
            <xsl:apply-templates select="*" mode="mixed"/>
          </xsl:variable>
          <xsl:choose>
            <!-- May be bad for a simple stanza or quote -->
            <xsl:when test="$div_mixed = ''">
              <xsl:apply-templates select="*/node()"/>
            </xsl:when>
            <xsl:otherwise>
              <xsl:apply-templates/>
            </xsl:otherwise>
          </xsl:choose>
        </xsl:copy>
      </xsl:when>
      <!-- 
        <section>
          <div>
            <p/>
            <p/>
          </div>
          <section/>
          <section/>
      -->
      <xsl:otherwise>
        <xsl:copy>
          <xsl:copy-of select="@*"/>
          <xsl:if test="$title != ''">
            <xsl:attribute name="title">
              <xsl:value-of select="$title"/>
            </xsl:attribute>
          </xsl:if>
          <xsl:apply-templates/>
        </xsl:copy>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  <!-- semantize some elements -->
  <xsl:template match="html:span[@class]">
    <xsl:variable name="props">
      <xsl:call-template name="class_props">
        <xsl:with-param name="class" select="@class"/>
      </xsl:call-template>
    </xsl:variable>
    <xsl:choose>
      <xsl:when test="contains($props, ' italic ')">
        <i>
          <xsl:apply-templates select="node()|@*[name() != 'class']"/>
        </i>
      </xsl:when>
      <xsl:when test="contains($props, ' small-caps ')">
        <sc>
          <xsl:apply-templates select="node()|@*[name() != 'class']"/>
        </sc>
      </xsl:when>
      <xsl:otherwise>
        <xsl:copy>
          <xsl:apply-templates select="node()|@*"/>
        </xsl:copy>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="mixed" match="*" mode="mixed">
    <xsl:variable name="text">
      <xsl:for-each select="text()">
        <xsl:value-of select="."/>
      </xsl:for-each>
    </xsl:variable>
    <xsl:value-of select="normalize-space(translate($text, ' ', ''))"/>
  </xsl:template>

  <xsl:template name="class">
    <xsl:param name="suffix"/>
    <xsl:variable name="class">
      <xsl:variable name="text">
        <xsl:call-template name="class_clean">
          <xsl:with-param name="class" select="@class"/>
        </xsl:call-template>
        <xsl:text> </xsl:text>
        <xsl:value-of select="$suffix"/>
      </xsl:variable>
      <xsl:value-of select="normalize-space($text)"/>
    </xsl:variable>
    <xsl:if test="$class != ''">
      <xsl:attribute name="class">
        <xsl:value-of select="$class"/>
      </xsl:attribute>
    </xsl:if>
  </xsl:template>

  <xsl:template name="atts">
    <xsl:variable name="props">
      <xsl:call-template name="class_props"/>
    </xsl:variable>
    <xsl:apply-templates select="@*[name() != 'class']"/>
    <xsl:call-template name="class">
      <xsl:with-param name="suffix" select="$props"/>
    </xsl:call-template>
  </xsl:template>

  <xsl:template match="html:p" name="p">
    <xsl:variable name="mixed">
      <xsl:call-template name="mixed"/>
    </xsl:variable>
    <xsl:variable name="props">
      <xsl:call-template name="class_props"/>
    </xsl:variable>
    <xsl:variable name="count" select="count(*)"/>
    <xsl:variable name="num">
      <xsl:number/>
    </xsl:variable>
    <xsl:choose>
      <xsl:when test="$mixed != ''">
        <p>
          <xsl:call-template name="atts"/>
          <xsl:apply-templates/>
        </p>
      </xsl:when>
      <xsl:when test="$count = 1 and html:img">
        <figure>
          <xsl:call-template name="atts"/>
          <xsl:apply-templates/>
        </figure>
      </xsl:when>
      <!-- maybe page break, at least anchor -->
      <xsl:when test="$count = 1 and html:a and normalize-space(.) = ''">
          <xsl:apply-templates/>
      </xsl:when>
      <!-- usually spacing -->
      <xsl:when test="$count = 1 and html:br">
        <p> </p>
      </xsl:when>
      <!-- <p> contents, only one <i> or <em> -->
      <xsl:when test="$count = 1">
        <xsl:variable name="name" select="name(*[1])"/>
        <xsl:variable name="suffix">
          <xsl:choose>
            <xsl:when test="$name = 'i'">italic</xsl:when>
            <xsl:when test="$name = 'em'">italic</xsl:when>
            <xsl:when test="$name = 'span'">
              <xsl:call-template name="class_props">
                <xsl:with-param name="class" select="*/@class"/>
              </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
              <xsl:value-of select="$name"/>
            </xsl:otherwise>
          </xsl:choose>
          <xsl:choose>
            <xsl:when test="not(@align)"/>
            <xsl:when test="@align = 'right'"> right</xsl:when>
            <xsl:when test="@align = 'center'"> center</xsl:when>
          </xsl:choose>
        </xsl:variable>
        <p>
          <xsl:apply-templates select="@*[name() != 'class']"/>
          <xsl:call-template name="class">
            <xsl:with-param name="suffix" select="concat($props, ' ', $suffix)"/>
          </xsl:call-template>
          <xsl:apply-templates select="*/node()"/>
        </p>
      </xsl:when>
      <!-- empty para at start of a section -->
      <xsl:when test="$count = 0 and $num = 1"/>
      <xsl:when test="$count = 0">
        <!-- probably a spacer -->
        <p> </p>
      </xsl:when>
      <xsl:otherwise>
        <p>
          <xsl:call-template name="atts"/>
          <xsl:apply-templates/>
        </p>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <!-- Anchors should be merged -->
  <xsl:template match="html:a">
    <a>
      <xsl:apply-templates select="@*"/>
      <xsl:if test="@href and contains(@href, '#')">
        <xsl:attribute name="href">
          <xsl:text>#</xsl:text>
          <xsl:value-of select="substring-after(@href, '#')"/>
        </xsl:attribute>
      </xsl:if>
      <xsl:apply-templates/>
    </a>
  </xsl:template>

  <xsl:template match="html:body">
    <xsl:variable name="mixed">
      <xsl:call-template name="mixed"/>
    </xsl:variable>
    <xsl:variable name="count" select="count(*)"/>
    <body>
      <xsl:choose>
        <xsl:when test="$mixed = '' and $count = 1">
          <xsl:apply-templates select="*/node()"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:apply-templates/>
        </xsl:otherwise>
      </xsl:choose>
    </body>
  </xsl:template>

  <xsl:template match="html:div">
    <xsl:variable name="mixed">
      <xsl:call-template name="mixed"/>
    </xsl:variable>
    <xsl:variable name="props">
      <xsl:call-template name="class_props">
        <xsl:with-param name="class" select="@class"/>
      </xsl:call-template>
      <xsl:choose>
        <xsl:when test="not(@align)"/>
        <xsl:when test="@align = 'right'"> right</xsl:when>
        <xsl:when test="@align = 'center'"> center</xsl:when>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="count" select="count(*)"/>
    <!-- 
      SLOW | ../html:p[translate(., '  &#10;&#9;&#13;', '') != '']
      | ../html:p[text()[normalize-space(.) != '']]
      | ../html:div[text()[normalize-space(.) != '']] 
    -->
    <xsl:variable name="bros" select="count(
        ../html:blockquote
      | ../html:ol
      | ../html:ul
      | ../html:p[text()[normalize-space(.) != '']]
    )"/>
    <!-- 
    -->
    <xsl:variable name="children" select="count(
        html:p
      | html:div[text()[normalize-space(.) != '']] 
    )"/>
    <xsl:choose>
      <!-- with a title, let’s bet it is a section -->
      <xsl:when test="$children &gt; 0 and (html:h1 | html:h2 | html:h3  | html:h4  | html:h5  | html:h6 | html:header)">
        <section>
          <xsl:apply-templates select="@*[name() != 'class']"/>
          <xsl:call-template name="class">
            <xsl:with-param name="suffix" select="$props"/>
          </xsl:call-template>
          <xsl:apply-templates/>
        </section>
      </xsl:when>
      <!-- mixed content, it’s a para -->
      <xsl:when test="$mixed != ''">
        <xsl:call-template name="p"/>
      </xsl:when>
      <!-- content grouping ? like stanza or quote ? keep it -->
      <xsl:when test="$children &gt; 1">
        <blockquote>
          <xsl:apply-templates select="@*[name() != 'class']"/>
          <xsl:call-template name="class">
            <xsl:with-param name="suffix" select="$props"/>
          </xsl:call-template>
          <xsl:apply-templates/>
        </blockquote>
      </xsl:when>
      <!-- para separator ? -->
      <xsl:when test="$mixed = '' and not(*)">
        <br class="space"/>
      </xsl:when>
      <!-- no content brothers, seems presentation, go through -->
      <xsl:when test="$bros = 0 and $mixed != ''">
        <xsl:apply-templates/>
      </xsl:when>
      <!-- structural div -->
      <xsl:when test="html:div">
        <xsl:apply-templates/>
      </xsl:when>
      <!-- default, maybe a figure container, or a para with one <span> -->
      <xsl:otherwise>
        <xsl:call-template name="p"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  <!-- No output -->
  <xsl:template match="html:p/@align | html:div/@align"/>
  <!-- used for mapping class name style to font prop -->
  <xsl:template match="html:template[@id='css']"/>

  <xsl:template match="@class">
    <xsl:variable name="class">
      <xsl:call-template name="class_clean">
        <xsl:with-param name="class" select="."/>
      </xsl:call-template>
    </xsl:variable>
    <xsl:choose>
      <xsl:when test="$class != ''">
        <xsl:attribute name="class">
          <xsl:value-of select="$class"/>
        </xsl:attribute>
      </xsl:when>
    </xsl:choose>
  </xsl:template>

  <!-- delete different class -->
  <xsl:template name="class_clean">
    <xsl:param name="class"/>
    <xsl:variable name="class_clean">
      <xsl:call-template name="class_cleaner">
        <xsl:with-param name="class" select="normalize-space($class)"/>
      </xsl:call-template>
    </xsl:variable>
    <xsl:value-of select="normalize-space($class_clean)"/>
  </xsl:template>

  <xsl:template name="class_cleaner">
    <xsl:param name="class"/>
    <xsl:variable name="norm" select="normalize-space($class)"/>
    <xsl:choose>
      <xsl:when test="$norm = ''"/>
      <xsl:when test="contains($norm, ' ')">
        <xsl:call-template name="class_cleaner">
          <xsl:with-param name="class" select="substring-before($norm, ' ')"/>
        </xsl:call-template>
        <xsl:call-template name="class_cleaner">
          <xsl:with-param name="class" select="substring-after($norm, ' ')"/>
        </xsl:call-template>
      </xsl:when>
      <!-- keep only include -->
      <xsl:when test="$class_in != ''">
        <xsl:if test="contains($class_in, concat(' ', $norm, ' '))">
          <xsl:text> </xsl:text>
          <xsl:value-of select="$norm"/>
        </xsl:if>
      </xsl:when>
      <xsl:when test="contains($class_ex, concat(' ', $norm, ' '))"/>
      <xsl:otherwise>
        <xsl:text> </xsl:text>
        <xsl:value-of select="$norm"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="class_props">
    <xsl:param name="class" select="normalize-space(@class)"/>
    <xsl:variable name="norm" select="normalize-space($class)"/>
    <xsl:choose>
      <xsl:when test="$norm = ''"/>
      <xsl:when test="contains($norm, ' ')">
        <xsl:call-template name="class_props">
          <xsl:with-param name="class" select="substring-before($norm, ' ')"/>
        </xsl:call-template>
        <xsl:call-template name="class_props">
          <xsl:with-param name="class" select="substring-after($norm, ' ')"/>
        </xsl:call-template>
      </xsl:when>
      <xsl:otherwise>
        <xsl:for-each select="key('css', concat('.', $norm))">
          <xsl:for-each select="html:declaration">
            <xsl:text> </xsl:text>
            <xsl:value-of select="@value"/>
            <xsl:text> </xsl:text>
          </xsl:for-each>
        </xsl:for-each>
        <xsl:for-each select="key('css', concat('span.', $norm))">
          <xsl:for-each select="html:declaration">
            <xsl:text> </xsl:text>
            <xsl:value-of select="@value"/>
            <xsl:text> </xsl:text>
          </xsl:for-each>
        </xsl:for-each>
        <xsl:for-each select="key('css', concat('div.', $norm))">
          <xsl:for-each select="html:declaration">
            <xsl:text> </xsl:text>
            <xsl:value-of select="@value"/>
            <xsl:text> </xsl:text>
          </xsl:for-each>
        </xsl:for-each>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
</xsl:transform>
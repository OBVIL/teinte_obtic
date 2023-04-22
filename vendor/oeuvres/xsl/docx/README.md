
# Teinte, docx &gt; tei

Teinte for docx (word processor format) supports basic formatting and styling. Find here the table of explicit word processor styles supported. Unknown styles are transformed as XML elements, even if it’s not correct TEI.

Entries are keywords (ascii lower case letter, without accents), but real life styles can be more human friendly, for example, `quotesalute` could appears as &lt;Quote, Salute&gt; for the user in his word processor (a style for a letter salutation in a citation).

Text processor styles may be “paragraph” level (visible as ¶ in text processor) or character level (inside a paragraph). Yous must ensure the level of your styles in your text processor if you want that Teinte works well. Microsoft.Office may create linked styles, for example one style name for Quote, allowed for a full paragraph or for quotes of some words inline. This may confused an automat. It is good idea to conceive your template of styles in LibreOffice, less confusing, even if your users prefers MS.Word for production.




## Styles, paragraph level

**ab**
```xml
<ab type="ornament">content ¶</ab>
```

**address**
```xml
<address>
  <addrLine>content ¶</addrLine>
</address>
```

**argument**
```xml
<argument>
  <p>content ¶</p>
</argument>
```

**bibl**
```xml
<bibl>content ¶</bibl>
```

**byline**
```xml
<byline>content ¶</byline>
```

**camera**
```xml
<camera>content ¶</camera>
```

**caption**
```xml
<caption>content ¶</caption>
```

**castitem**
```xml
<castList>
  <castItem>content ¶</castItem>
</castList>
```

**castlist**
```xml
<castList>content ¶</castList>
```

**closer**
```xml
<closer>content ¶</closer>
```

**dateline**
```xml
<dateline>content ¶</dateline>
```

**def**
```xml
<entryFree>
  <def>content ¶</def>
</entryFree>
```

**desc**
```xml
<desc>content ¶</desc>
```

**docauthor**
```xml
<docAuthor>content ¶</docAuthor>
```

**docdate**
```xml
<docDate>content ¶</docDate>
```

**docimprint**
```xml
<docImprint>content ¶</docImprint>
```

**doctitle**
```xml
<docTitle>content ¶</docTitle>
```

**eg**
```xml
<eg>content ¶</eg>
```

**entry**
```xml
<entry>content ¶</entry>
```

**epigraph**
```xml
<epigraph>
  <p rend="right italic… (direct formatting)">content ¶</p>
</epigraph>
```

**epigraphl**
```xml
<epigraph>
  <l>content ¶</l>
</epigraph>
```

**fw**
```xml
<fw>content ¶</fw>
```

**index**
```xml
<index>
  <item>content ¶</item>
</index>
```

**l**
```xml
<l rend="right italic… (direct formatting)">content ¶</l>
```

**label**
```xml
<label>content ¶</label>
```

**labeldateline**
```xml
<label type="dateline">content ¶</label>
```

**labelhead**
```xml
<label type="head">content ¶</label>
```

**labelquestion**
```xml
<label type="question">content ¶</label>
```

**labelsalute**
```xml
<label type="salute">content ¶</label>
```

**labelspeaker**
```xml
<label type="speaker">content ¶</label>
```

**lg**
```xml
<lg>
  <l>content ¶</l>
</lg>
```

**opener**
```xml
<opener>content ¶</opener>
```

**p**
```xml
<p rend="right italic… (direct formatting)">content ¶</p>
```

**pb**
```xml
<pb>content ¶</pb>
```

**postscript**
```xml
<postscript>
  <p>content ¶</p>
</postscript>
```

**q**
```xml
<q>content ¶</q>
```

**quote**
```xml
<quote>
  <p rend="right italic… (direct formatting)">content ¶</p>
</quote>
```

**quotedateline**
```xml
<quote>
  <dateline>content ¶</dateline>
</quote>
```

**quotel**
```xml
<quote>
  <l>content ¶</l>
</quote>
```

**quotesalute**
```xml
<quote>
  <salute>content ¶</salute>
</quote>
```

**quotesigned**
```xml
<quote>
  <signed>content ¶</signed>
</quote>
```

**role**
```xml
<castItem>
  <role>content ¶</role>
</castItem>
```

**roledesc**
```xml
<castItem>
  <roleDesc>content ¶</roleDesc>
</castItem>
```

**said**
```xml
<said>content ¶</said>
```

**salutation**
```xml
<salute>content ¶</salute>
```

**salute**
```xml
<salute>content ¶</salute>
```

**set**
```xml
<set>
  <p>content ¶</p>
</set>
```

**signed**
```xml
<signed>content ¶</signed>
```

**speaker**
```xml
<speaker>content ¶</speaker>
```

**stage**
```xml
<stage>content ¶</stage>
```

**term**
```xml
<index>
  <term>content ¶</term>
</index>
```

**trailer**
```xml
<trailer>content ¶</trailer>
```
## Styles, character level

**abbr**
```xml
character… <abbr>content</abbr> …level
```

**actor**
```xml
character… <actor>content</actor> …level
```

**add**
```xml
character… <add>content</add> …level
```

**affiliation**
```xml
character… <affiliation>content</affiliation> …level
```

**age**
```xml
character… <age>content</age> …level
```

**author**
```xml
character… <author>content</author> …level
```

**bibl**
```xml
character… <bibl>content</bibl> …level
```

**c**
```xml
character… <c>content</c> …level
```

**code**
```xml
character… <code>content</code> …level
```

**corr**
```xml
character… <corr>content</corr> …level
```

**date**
```xml
character… <date>content</date> …level
```

**del**
```xml
character… <del>content</del> …level
```

**distinct**
```xml
character… <distinct>content</distinct> …level
```

**email**
```xml
character… <email>content</email> …level
```

**emph**
```xml
character… <emph>content</emph> …level
```

**geogname**
```xml
character… <geogName>content</geogName> …level
```

**gloss**
```xml
character… <gloss>content</gloss> …level
```

**label**
```xml
character… <label>content</label> …level
```

**milestone**
```xml
character… <milestone>content</milestone> …level
```

**name**
```xml
character… <name>content</name> …level
```

**num**
```xml
character… <num>content</num> …level
```

**pb**
```xml
character… <pb>content</pb> …level
```

**persname**
```xml
character… <persName>content</persName> …level
```

**placename**
```xml
character… <placeName>content</placeName> …level
```

**stage**
```xml
character… <stage>content</stage> …level
```

**title**
```xml
character… <title>content</title> …level
```

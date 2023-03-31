# [Teinte_php](https://github.com/oeuvres/teinte_php), TEI shades, PHP pilot

Teinte_php is an [open source](https://github.com/oeuvres/teinte_php) library to convert and publish texts with PHP. It is available as a composer package [oeuvres/teinte](https://packagist.org/packages/oeuvres/teinte). The heart is an embeded XSLT-1.0 pack of transformations [teinte_xsl](https://github.com/oeuvres/teinte_xsl). The pivot format is a subset of [TEI](https://tei-c.org/release/docs/tei-p5-docs/en/html/REF-ELEMENTS.html), an academic XML schema dedicated to all forms of texts, not only blog posts but also: novels, theatre, letters… *Teinte suite* has been developed during more than 15 years, mainly by <a onmouseover="this.href='mailto'+'\x3A'+'frederic.glorieux'+'\x40'+'fictif.org'" href="#">Frédéric Glorieux</a> under different academic affiliations.

![Teinte xsl](https://github.com/oeuvres/teinte/blob/main/docs/teinte_xsl.png)


## Some project using *Teinte*

### [*Complete works* of Denis de Rougemont (1906–1985)](https://www.unige.ch/rougemont/)

<a  href="https://www.unige.ch/rougemont/"><img width="300px" align="left" src="https://oeuvres.github.io/teinte/docs/screens/rougemont_teinte.png"  alt="Rougemont"/></a> The *eRougemont* project, directed by Nicolas Stenger (Université de Genève), includes digitization, publication and mining of the complete works in [open source](https://github.com/erougemont/) (about 30 books and 1000 articles). *Teinte* was used to convert files obtained with OCR, from DOCX to TEI. The team of editors has been trained to correct TEI. Web publishing is done from the XML master to HTML, for the CMS of the university (Concrete 5). 


### [The *ObTIC* converter](https://obtic.huma-num.fr/teinte/)


<a href="https://obtic.huma-num.fr/teinte/"><img src="https://oeuvres.github.io/teinte/docs/screens/obtic_teinte.png"  width="300px" align="right" alt="ObTIC"/></a> [ObTIC](https://obtic.sorbonne-universite.fr/), “Observatoire des textes, des idées et des corpus”, is a lab of Sorbonne University, dedicated to texts. *Teinte* is used to convert texts from different sources and destinations. Their support made possible to develop a friendly interface online for conversions.



### [*Galenus verbatim*, complete works of Galen (129–216)](https://galenus-verbatim.huma-num.fr/)

<a href="https://galenus-verbatim.huma-num.fr/"><img src="https://oeuvres.github.io/teinte/docs/screens/galenus_teinte.png"  width="300px" align="left"  alt="Galenus Verbatim"/></a> The *Galenus verbatim* project, directed by Nathalie Rousseau (Sorbonne Université), is an electronic edition with a lemmatized search engine for the complete works of [Galen of Pergamon](https://en.wikipedia.org/wiki/Galen), about 100 “books”, or 14 000 pages. *Teinte* is used for the Greek works in open source, transformed from *Epidoc* TEI to HTML, and for the new digital edition of the modern Latin translations, from OCR, docx, to TEI (and HTML). Their support permits development of a PHP framework dedicated to publication of texts, with routing, logging and especially, transformations.


### [*eBalzac*, complete works of Honoré de Balzac (1799–1850)](https://www.ebalzac.com/)

<a href="https://www.ebalzac.com/"><img src="https://oeuvres.github.io/teinte/docs/screens/ebalzac_teinte.png"  width="300px" align="right" alt="eBalzac"/></a> The *eBalzac* project; directed by Andrea Del Lungo (Université de Lille), Pierre Glaudes (Sorbonne Université) & Jean-Gabriel Ganascia (Sorbonne Université); is an  [Open Source](https://github.com/ebalzac/FC) new edition of the Balzac *works* according to the *Furne corrected* volumes (last correction of the author). *Teinte* was used to transform the office DOCX files of the editor in TEI and generate different formats for publication (HTML) and research (TXT). Their quality requirements was very high and good for the code.


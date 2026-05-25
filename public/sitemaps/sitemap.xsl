<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
  xmlns:html="http://www.w3.org/1999/xhtml"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:s="http://www.sitemaps.org/schemas/sitemap/0.9"
  exclude-result-prefixes="s">

  <xsl:output method="html" encoding="UTF-8" indent="yes"/>

  <!-- ========== Base layout ========== -->
  <xsl:template match="/">
    <html>
      <head>
        <meta charset="utf-8"/>
        <title>
          <xsl:choose>
            <xsl:when test="s:sitemapindex">Sitemap Index</xsl:when>
            <xsl:when test="s:urlset">Sitemap URLs</xsl:when>
            <xsl:otherwise>Sitemap</xsl:otherwise>
          </xsl:choose>
        </title>
        <style>
          
					body {
						font-family:"Lucida Grande","Lucida Sans Unicode",Tahoma,Verdana;
						font-size:13px;
					}
					
					#intro {
						background-color:#CFEBF7;
						border:1px #2580B2 solid;
						padding:5px 13px 5px 13px;
						margin:10px;
					}
					
					#intro p {
						line-height:	16.8667px;
					}
					#intro strong {
						font-weight:normal;
					}
					
					td {
						font-size:11px;
					}
					
					th {
						text-align:left;
						padding-right:30px;
						font-size:11px;
					}
					
					tr.high {
						background-color:whitesmoke;
					}
					
					#footer {
						padding:2px;
						margin-top:10px;
						font-size:8pt;
						color:gray;
					}
					
					#footer a {
						color:gray;
					}
					
					a {
						color:black;
					}
				
        </style>
      </head>
      <body>
        <div class="container">
          <xsl:choose>
            <xsl:when test="s:sitemapindex">
              <h1>🗂️ Sitemap Index</h1>
              <div class="card">
                <table>
                  <thead>
                    <tr>
                      <th>URL</th>
                      <th>Last Modified</th>
                    </tr>
                  </thead>
                  <tbody>
                    <xsl:for-each select="s:sitemapindex/s:sitemap">
                      <tr>
                        <td>
                          <a href="{s:loc}">
                            <xsl:value-of select="s:loc"/>
                          </a>
                        </td>
                        <td><span class="badge"><xsl:value-of select="s:lastmod"/></span></td>
                      </tr>
                    </xsl:for-each>
                  </tbody>
                </table>
              </div>
            </xsl:when>
            <xsl:when test="s:urlset">
              <h1>🔗 URLs in Sitemap</h1>
              <div class="card">
                <table>
                  <thead>
                    <tr>
                      <th>URL</th>
                      <th>Lastmod</th>
                      <th>Changefreq</th>
                      <th>Priority</th>
                    </tr>
                  </thead>
                  <tbody>
                    <xsl:for-each select="s:urlset/s:url">
                      <tr>
                        <td><a href="{s:loc}"><xsl:value-of select="s:loc"/></a></td>
                        <td><xsl:value-of select="s:lastmod"/></td>
                        <td><xsl:value-of select="s:changefreq"/></td>
                        <td><xsl:value-of select="s:priority"/></td>
                      </tr>
                    </xsl:for-each>
                  </tbody>
                </table>
              </div>
            </xsl:when>
            <xsl:otherwise>
              <h1>Sitemap</h1>
              <div class="meta">Không xác định root element.</div>
            </xsl:otherwise>
          </xsl:choose>
        </div>
      </body>
    </html>
  </xsl:template>
</xsl:stylesheet>

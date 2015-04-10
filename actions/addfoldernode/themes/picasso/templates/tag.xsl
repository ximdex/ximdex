<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    <xsl:template name="tag" match="tag">
        <xsl:choose>
            <xsl:when test="@type='people'">
                <span uid="{@uid}" about="foaf:Person" property="foaf:name" class="enriched" title="Enriched Content [tag:Person]" data-url="{@url}"><xsl:apply-templates/></span>
            </xsl:when>
            <xsl:when test="@type='organizations'">
                <span uid="{@uid}" about="foaf:Organization" property="foaf:name" class="enriched" title="Enriched Content  [tag:Organization]" data-url="{@url}"><xsl:apply-templates/></span>
            </xsl:when>
            <xsl:when test="@type='places'">
                <span uid="{@uid}" about="foaf:Place" property="foaf:name" class="enriched" title="Enriched Content [tag:Place]" data-url="{@url}"><xsl:apply-templates/></span>
            </xsl:when>
            <xsl:when test="@type='creativework'">
                <span uid="{@uid}" about="schema:CreativeWork" property="schema:name" class="enriched" title="Enriched Content [tag:Place]" data-url="{@url}"><xsl:apply-templates/></span>
            </xsl:when>
            <xsl:otherwise>
                <span uid="{@uid}" data-url="{@url}" class="enriched">
                    <xsl:apply-templates/>
                </span>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
</xsl:stylesheet>
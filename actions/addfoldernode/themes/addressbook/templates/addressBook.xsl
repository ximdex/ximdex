<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
<xsl:template name="addressBook" match="addressBook"> 
	<div class="addressBook" uid="{@uid}">
		<xsl:apply-templates/>
	</div>
</xsl:template>
</xsl:stylesheet>

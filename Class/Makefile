# ============================================
# $Id: Makefile,v 1.2 2007/11/22 14:35:01 eric Exp $
#
# ============================================


include $(utildir)/PubRule

pages_fdl = $(patsubst %.php,$(pubdir)/$(style)/$(applib)/%.php,$(wildcard Method*.php))
pages_fdl += $(patsubst %.php,$(pubdir)/$(style)/$(applib)/%.php,$(wildcard Class*.php))

$(pubdir)/$(style)/$(applib)/%.php: %.php
	cd $(pubdir)/$(style)/$(applib); \
	ln -sf ../$(appname)/$< .

publish: $(pubdir)/$(applib) $(pages_fdl) 